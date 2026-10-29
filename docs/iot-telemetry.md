# Intégration des capteurs IoT

Ce document décrit le contrat entre le capteur et l'API. L'architecture, les protections et l'affichage déjà implémentés côté application sont détaillés dans [`iot-application-securite.md`](iot-application-securite.md).

## Architecture et sécurité

L'API IoT est distincte de l'authentification Sanctum des utilisateurs. Chaque appareil appartient à un utilisateur LevelUp et possède un token aléatoire dédié. Seul le SHA-256 de ce token à haute entropie est conservé dans `iot_devices`; le token en clair est affiché une seule fois par la commande de provisionnement.

En production, l'endpoint refuse les requêtes non HTTPS avec le statut `426`. Le proxy inverse ou le load balancer doit transmettre correctement le protocole d'origine à Laravel et ses journaux/APM doivent masquer l'en-tête `Authorization`. L'application ne journalise jamais le token ni le corps de télémétrie.

## Provisionnement et cycle de vie

Exécuter les migrations, puis rattacher le capteur à l'identifiant numérique ou au `username` d'un utilisateur existant :

```bash
php artisan migrate
php artisan iot:device create LUP-ESP32-001 --user=42
```

Copier immédiatement le token affiché et le placer dans le stockage sécurisé du firmware. Il ne pourra pas être relu depuis le SI.

```bash
php artisan iot:device disable LUP-ESP32-001
php artisan iot:device enable LUP-ESP32-001
php artisan iot:device revoke LUP-ESP32-001
php artisan iot:device renew LUP-ESP32-001
```

`renew` invalide immédiatement l'ancien token et affiche le nouveau une seule fois. Un appareil révoqué doit être renouvelé avant de pouvoir être réutilisé. Un appareil désactivé conserve son secret mais ne peut plus publier. La rotation du token ne remet pas `sequence` à zéro.

Si le capteur a perdu son compteur (reflashage, NVS effacée) et reçoit des `409` en boucle, resynchroniser :

```bash
php artisan iot:device resync LUP-ESP32-001
```

Cette commande supprime les mesures de l'appareil (et leurs analyses IA) et remet `last_sequence` à `NULL`; le capteur peut alors repartir de 0 ou 1.

## Contrat HTTP

`POST /api/internal/iot/v1/telemetry`

En-têtes requis :

```text
Authorization: Bearer <token-de-l-appareil>
Content-Type: application/json
Accept: application/json
```

Corps JSON (aucun autre champ n'est accepté) :

```json
{
  "device_id": "LUP-ESP32-001",
  "temperature": 24.00,
  "humidity": 50.00,
  "sequence": 1,
  "measured_at": "2026-10-22T14:30:00Z"
}
```

- `device_id` : 1 à 64 caractères parmi `A-Z`, `0-9`, `_` et `-`; il doit correspondre au token présenté.
- `temperature` : nombre avec deux décimales au maximum, de −40 à 80 °C par défaut.
- `humidity` : nombre avec deux décimales au maximum, de 0 à 100 % par défaut.
- `sequence` : entier croissant positif ou nul, sur 64 bits signés.
- `measured_at` : facultatif, date ISO 8601 avec fuseau. L'omettre tant que l'ESP32 n'a pas d'heure fiable.

Le serveur renseigne toujours `received_at` avec sa propre horloge UTC. Il ne remplace pas un `measured_at` absent par cette valeur, afin de conserver la distinction entre heure de mesure inconnue et heure de réception.

### Réponses

Une nouvelle mesure retourne `201` :

```json
{
  "status": "accepted",
  "data": {
    "id": 123,
    "device_id": "LUP-ESP32-001",
    "sequence": 1,
    "measured_at": "2026-10-22T14:30:00+00:00",
    "received_at": "2026-10-22T14:30:01+00:00"
  }
}
```

Le renvoi strictement identique de la même séquence retourne `200`, `status: "duplicate"` et l'identifiant de la mesure existante. La même séquence avec des valeurs différentes retourne `409 sequence_conflict`. Une séquence inférieure à la dernière séquence acceptée et encore inconnue retourne `409 replayed_sequence`. Ces contrôles sont exécutés dans une transaction et protégés par une contrainte unique `(iot_device_id, sequence)`.

Autres statuts : `400` JSON mal formé, `401` identifiants absents/invalides ou appareil inconnu, `403` appareil désactivé/révoqué, `413` charge trop grande, `415` mauvais type de contenu, `422` validation, `426` HTTPS requis en production et `429` limite de débit dépassée. Toutes les réponses portent `Cache-Control: no-store`, sauf les réponses générées directement par le limiteur Laravel.

### Exemple curl

Le token suivant est volontairement faux :

```bash
curl --request POST 'https://levelup.example/api/internal/iot/v1/telemetry' \
  --header 'Authorization: Bearer lup_iot_FAUX_TOKEN_NON_FONCTIONNEL' \
  --header 'Content-Type: application/json' \
  --header 'Accept: application/json' \
  --data '{"device_id":"LUP-ESP32-001","temperature":24.00,"humidity":50.00,"sequence":1}'
```

## Configuration

Les valeurs par défaut conviennent au DHT22 actuel :

```dotenv
IOT_TOKEN_BYTES=32
IOT_TELEMETRY_MAX_PAYLOAD_BYTES=4096
IOT_TELEMETRY_RATE_LIMIT_PER_MINUTE=30
IOT_TELEMETRY_IP_RATE_LIMIT_PER_MINUTE=120
IOT_TEMPERATURE_MIN=-40
IOT_TEMPERATURE_MAX=80
IOT_HUMIDITY_MIN=0
IOT_HUMIDITY_MAX=100
IOT_SAFE_TEMPERATURE_MIN=5
IOT_SAFE_TEMPERATURE_MAX=35
IOT_AI_ENABLED=false
IOT_AI_MODEL=gpt-5.4-mini
IOT_AI_REASONING_EFFORT=low
IOT_AI_MAX_OUTPUT_TOKENS=1000
IOT_AI_TIMEOUT_SECONDS=60
IOT_AI_HISTORY_SIZE=10
IOT_AI_MIN_HISTORY_SIZE=3
IOT_AI_PERIODIC_INTERVAL_MINUTES=15
IOT_AI_MIN_SECONDS_BETWEEN_ANALYSES=60
IOT_AI_TRIGGER_ON_THRESHOLD=true
IOT_AI_TEMPERATURE_SPIKE_DELTA=5
IOT_AI_HUMIDITY_SPIKE_DELTA=25
IOT_AI_ANOMALY_DEVIATION=8
```

`IOT_TOKEN_BYTES` est ramené à 32 octets minimum par le code. Avec une mesure toutes les 10 secondes, la limite de 30 requêtes/minute par appareil laisse une marge de rafale tout en freinant les abus. Une seconde limite de 120 requêtes/minute s'applique par adresse IP.

Les deux variables `IOT_SAFE_TEMPERATURE_*` définissent la plage considérée comme normale dans le widget du tableau de bord. Une mesure en dehors de cette plage déclenche l'état visuel « température dangereuse ».

Lorsque `IOT_AI_ENABLED=true`, les mesures acceptées sélectionnées par des règles déterministes (périodique, franchissement de seuil, variation brutale, valeur inhabituelle, avec limitation par appareil) créent une analyse asynchrone de leur plausibilité et des risques liés au fait de jouer dans ces conditions. La mesure brute est toujours conservée ; seul le résultat IA décide si elle est marquée comme exploitable. Le worker de file d'attente Laravel doit fonctionner. Les détails et limites figurent dans [`iot-application-securite.md`](iot-application-securite.md#analyse-ia-des-risques-et-de-la-fiabilité).

## Adaptation du firmware ESP32

Le firmware doit mémoriser `device_id`, le token affiché au provisionnement et un compteur `sequence` persistant en mémoire non volatile. Pour chaque mesure, il doit effectuer un POST HTTPS avec les en-têtes ci-dessus, incrémenter la séquence sans la réutiliser et omettre `measured_at` jusqu'à synchronisation fiable de l'heure (NTP). Il peut réessayer exactement la même charge après une erreur réseau : la réponse `duplicate` confirme alors que la première tentative avait été reçue. Un `409` impose de ne pas recycler la séquence; un `401`/`403` impose un reprovisionnement ou une intervention administrative; un `429` impose d'attendre la durée indiquée par `Retry-After`.
