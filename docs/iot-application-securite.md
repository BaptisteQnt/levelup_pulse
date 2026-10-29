# IoT — fonctionnement et sécurité côté application

Ce document décrit uniquement la partie actuellement implémentée dans l'application LevelUp Pulse. La partie firmware/capteur doit faire l'objet d'un document séparé, construit autour du même contrat HTTP.

Le contrat d'échange complet, les réponses HTTP et les commandes de provisionnement sont décrits dans [`iot-telemetry.md`](iot-telemetry.md).

## Vue d'ensemble

```text
Capteur
  │ POST HTTPS + Bearer token + JSON
  ▼
API d'ingestion Laravel
  │ contrôles HTTP, débit, identité, validation et anti-rejeu
  ▼
Base de données
  │ mesure brute liée à un appareil, lui-même lié à un utilisateur
  ├──────────────► File d'attente ► Analyse IA structurée
  │                                  │ fiabilité + risque
  │◄─────────────────────────────────┘
  ▼
Lecture authentifiée
  │ dernière mesure appartenant à l'utilisateur connecté
  ▼
Widget Vue
    température, humidité et interprétation de la température
```

Les deux flux d'authentification sont volontairement séparés :

- le capteur publie avec son propre token Bearer ;
- le navigateur lit les données avec la session de l'utilisateur connecté.

Le token du capteur n'est donc jamais envoyé au navigateur et la session utilisateur n'est jamais placée dans le firmware.

## Composants en place

| Rôle | Implémentation |
|---|---|
| Réception des mesures | `POST /api/internal/iot/v1/telemetry` dans `routes/api.php` |
| Contrôles HTTP initiaux | `app/Http/Middleware/EnsureIotTelemetryRequest.php` |
| Authentification de l'appareil | `app/Http/Middleware/AuthenticateIotDevice.php` |
| Validation des champs | `app/Http/Requests/Iot/StoreTelemetryRequest.php` |
| Enregistrement et anti-rejeu | `app/Services/IngestIotTelemetry.php` |
| Analyse IA asynchrone | `app/Jobs/AnalyzeIotTelemetry.php` |
| Fournisseur OpenAI structuré | `app/Services/OpenAiIotTelemetryProvider.php` |
| Résultat de l'analyse | `iot_telemetry_ai_analyses` et `app/Models/IotTelemetryAiAnalysis.php` |
| Création et rotation des secrets | `app/Services/IotDeviceTokenManager.php` et `iot:device` |
| Sélection de la dernière mesure | `app/Actions/Iot/GetLatestTelemetry.php` |
| Lecture par le navigateur | `GET /iot/telemetry/latest` |
| Affichage | `resources/js/components/IotTelemetryWidget.vue` |
| Configuration | `config/iot.php` et variables `IOT_*` |

## Déroulement d'une mesure

1. Un administrateur provisionne un appareil et l'associe à un utilisateur avec `php artisan iot:device create`.
2. L'application génère un secret aléatoire d'au moins 32 octets avec `random_bytes`. Le secret en clair n'est affiché qu'une fois.
3. Le capteur envoie la mesure en HTTPS avec son `device_id`, son token Bearer et un numéro de séquence croissant.
4. L'application vérifie le protocole, le type de contenu, la taille et la validité syntaxique du JSON.
5. Une première limite de débit par adresse IP s'applique avant l'authentification.
6. Le couple `device_id`/token est vérifié, puis l'état actif et non révoqué de l'appareil est contrôlé.
7. Une seconde limite de débit, propre à l'appareil authentifié, s'applique.
8. Les champs, formats et plages autorisés sont validés. Les champs supplémentaires sont refusés.
9. Une transaction verrouille l'appareil, contrôle la séquence et enregistre la mesure avec l'heure de réception du serveur.
10. Pour un utilisateur connecté, le tableau de bord récupère uniquement la dernière mesure appartenant à l'un de ses appareils.
11. Lorsque l'analyse IA est activée et que les règles de déclenchement la sélectionnent, une tâche asynchrone évalue la cohérence et le risque sans ralentir la réponse au capteur.
12. Le serveur classe aussi immédiatement la température avec les seuils déterministes, puis le widget affiche les résultats et interroge le point de lecture toutes les 5 secondes.

## Protections de l'ingestion

### Secret propre à chaque appareil

Chaque appareil possède un token différent. Le token contient au minimum 256 bits aléatoires avant son encodage hexadécimal. Seul son SHA-256 est stocké dans `iot_devices`, dans une colonne unique. Cette utilisation de SHA-256 est adaptée ici parce que la source est un secret aléatoire de haute entropie, et non un mot de passe choisi par une personne.

La comparaison est réalisée avec `hash_equals`. Le token doit correspondre au `device_id` envoyé : le token d'un appareil ne peut pas publier pour un autre appareil.

Les réponses d'échec utilisent un message générique afin de limiter l'énumération des appareils. Les journaux contiennent l'identifiant public, la raison et l'adresse IP, mais ni le token ni le corps de télémétrie.

### Cycle de vie du secret

La commande `iot:device` permet de :

- créer un appareil et afficher son token une seule fois ;
- désactiver temporairement un appareil ;
- le réactiver s'il n'est pas révoqué ;
- le révoquer immédiatement ;
- renouveler son token, ce qui invalide immédiatement l'ancien.

Une rotation ne remet pas la séquence à zéro. Un appareil révoqué doit recevoir un nouveau token avant de pouvoir être réutilisé.

### Transport et format HTTP

En environnement `production`, une requête non HTTPS est refusée avec le statut `426`. Le déploiement doit néanmoins configurer correctement les proxies de confiance afin que Laravel reconnaisse le protocole HTTPS d'origine.

L'API impose également :

- `Content-Type: application/json` ;
- un objet JSON valide, avec une profondeur de décodage limitée ;
- une charge de 4 096 octets au maximum par défaut ;
- les seuls champs `device_id`, `temperature`, `humidity`, `sequence` et `measured_at` ;
- un identifiant d'appareil de 64 caractères au maximum, limité à `A-Z`, `0-9`, `_` et `-` ;
- au maximum deux décimales pour la température et l'humidité ;
- des plages physiques configurables, par défaut de −40 à 80 °C et de 0 à 100 % ;
- un numéro de séquence compris entre 0 et la valeur maximale d'un entier signé 64 bits ;
- une date ISO 8601 avec fuseau lorsqu'elle est fournie.

### Limitation de débit

Deux limites indépendantes sont appliquées :

| Limite par défaut | Objectif |
|---|---|
| 120 requêtes/minute par IP | Freiner les essais d'authentification et les requêtes anonymes |
| 30 requêtes/minute par appareil authentifié | Contenir un appareil défectueux ou compromis |

Ces valeurs sont configurables. Elles réduisent le risque d'abus mais ne remplacent pas une protection réseau en amont lorsque l'application est exposée publiquement.

### Anti-rejeu, idempotence et concurrence

Le numéro `sequence` est monotone pour toute la durée de vie de l'appareil :

- une nouvelle séquence supérieure est acceptée ;
- le renvoi strictement identique de la dernière charge est traité comme un doublon sans créer une seconde ligne ;
- la réutilisation d'une séquence avec d'autres valeurs produit `sequence_conflict` ;
- une ancienne séquence encore inconnue produit `replayed_sequence`.

La vérification et l'écriture sont réalisées dans une transaction avec verrouillage de la ligne appareil. La base ajoute une contrainte unique sur `(iot_device_id, sequence)`, ce qui fournit une seconde protection contre les insertions concurrentes.

### Horodatage

`received_at` est toujours produit par le serveur. `measured_at` reste l'heure annoncée par le capteur et peut être absent. Cette séparation évite de faire passer une horloge capteur absente ou incorrecte pour une heure serveur fiable.

## Stockage et séparation des utilisateurs

Un appareil appartient à un seul utilisateur par clé étrangère. Une mesure appartient à un seul appareil. Les suppressions sont en cascade : supprimer l'utilisateur supprime ses appareils, puis leurs mesures.

La lecture utilisée par l'interface ne recherche pas simplement la dernière mesure globale. Elle ajoute une contrainte sur `iot_devices.user_id` et ne peut donc retourner qu'une mesure appartenant à l'utilisateur connecté. Le test fonctionnel crée volontairement une mesure plus récente appartenant à un autre utilisateur pour vérifier cette isolation.

Les valeurs stockées dans `iot_telemetry_measurements` sont :

- température et humidité avec deux décimales ;
- séquence ;
- heure annoncée par le capteur, si elle existe ;
- heure de réception du serveur ;
- références et horodatages techniques Laravel.

Le token en clair n'est pas stocké avec les mesures.

## Lecture sécurisée dans le navigateur

Le point `GET /iot/telemetry/latest` :

- exige une session utilisateur authentifiée ;
- est limité à 60 requêtes par minute ;
- applique le filtrage par propriétaire avant de retourner une mesure ;
- renvoie uniquement l'identifiant public du capteur, la mesure, les dates, le statut et les seuils ;
- ne renvoie jamais le token de l'appareil ni son empreinte ;
- utilise `Cache-Control: no-store, private` pour empêcher la mise en cache partagée ou persistante de la réponse.

La même dernière mesure est fournie au premier rendu Inertia pour éviter d'attendre le premier rafraîchissement. Pour un visiteur non authentifié, la propriété vaut `null` et aucun widget IoT n'est rendu.

## Traitement et affichage

Le statut est calculé côté serveur à partir de la température :

```text
safe    si minimum_sûr <= température <= maximum_sûr
danger  si température < minimum_sûr ou température > maximum_sûr
```

Avec la configuration par défaut, la plage sûre est de 5 à 35 °C inclus. Les seuils se règlent avec :

```dotenv
IOT_SAFE_TEMPERATURE_MIN=5
IOT_SAFE_TEMPERATURE_MAX=35
```

Le widget :

- affiche la température, l'humidité, le capteur et l'heure du relevé ;
- affiche un état vert normal ou une alerte rouge ;
- actualise les données toutes les 5 secondes et lors du retour sur un onglet visible ;
- permet une actualisation manuelle ;
- conserve la dernière valeur affichée en cas de panne réseau ;
- affiche l'heure du dernier contact du capteur, l'heure de synchronisation de l'interface et les erreurs de rafraîchissement ;
- est déplaçable à la souris, au tactile et avec les touches fléchées ;
- mémorise uniquement sa position dans `localStorage`, jamais les mesures ni les secrets ;
- utilise les interpolations Vue, qui échappent le texte, sans insertion HTML brute ;
- expose le changement d'état aux technologies d'assistance avec `status`, `alert` et `aria-live`.

## Séparation des états d'une mesure

Trois informations indépendantes décrivent une mesure :

| Information | Source | Dépend d'OpenAI ? | Rôle |
|---|---|---|---|
| Validité technique | Laravel, avant l'enregistrement (authentification, JSON, plages, séquence, débit) | Non | Une mesure invalide est refusée avec les codes HTTP du contrat |
| État thermique (`safe`/`danger`) | Laravel, à la lecture, avec les seuils `IOT_SAFE_TEMPERATURE_*` | Non | Toujours affiché, quel que soit l'état de l'analyse IA |
| Analyse de cohérence | Job asynchrone | Oui | Estimation de cohérence à partir de l'historique, jamais une certification du DHT22 |

Le DHT22 mesure uniquement la température et l'humidité de l'air à son emplacement ; ni l'IA ni l'interface n'en déduisent l'état interne de l'ordinateur.

### Cycle de vie de l'analyse

| État exposé | Signification |
|---|---|
| `not_required` | État calculé (aucune ligne en base) : l'IA est active mais la mesure n'a pas été sélectionnée. Ce n'est ni `pending` ni `failed`. |
| `pending` | Sélectionnée, en file d'attente |
| `processing` | Prise en charge par un job (réservation atomique) |
| `completed` | Verdict enregistré (`reliable`, `suspicious` ou `unknown`) |
| `failed` | Tentatives épuisées ; la mesure brute est conservée |

Lorsque `IOT_AI_ENABLED=false`, aucune analyse n'est créée et le widget ne présente pas de section IA. Toute la chaîne d'ingestion et l'alerte thermique fonctionnent sans clé OpenAI.

Un job réserve l'analyse par un `UPDATE` conditionnel (`pending`/`failed` → `processing`) et enregistre son résultat uniquement si elle est encore `processing`. Deux jobs concurrents ou un résultat tardif ne peuvent donc pas écraser une analyse terminée. Une erreur transitoire remet l'analyse en `pending` pour que la nouvelle tentative de la file puisse la traiter. L'analyse est rattachée à la mesure par son identifiant unique (`iot_telemetry_measurement_id`).

### Critères d'approbation (`approved_for_use`)

`approved_for_use` vaut `true` uniquement si l'analyse est `completed`, le verdict est `reliable` et l'historique minimal (`IOT_AI_MIN_HISTORY_SIZE`) est disponible. Dans tous les autres cas (`pending`, `processing`, `failed`, `suspicious`, `unknown`, historique insuffisant), la valeur n'est pas `true`. Elle ne conditionne ni l'affichage de la mesure brute ni l'alerte thermique ; aucun traitement métier ne la consomme encore.

### Stratégie de déclenchement et maîtrise des coûts

La mesure est toujours enregistrée ; l'IA n'est sollicitée que selon des règles déterministes (`app/Services/IotAiAnalysisSelector.php`), évaluées dans cet ordre :

1. **Limitation par appareil** : aucune analyse si une autre a été créée pour le même appareil depuis moins de `IOT_AI_MIN_SECONDS_BETWEEN_ANALYSES` secondes.
2. **Franchissement de seuil** (`threshold`) : la température sort de la plage sûre alors que le relevé précédent y était.
3. **Variation brutale** (`spike`) : écart avec le relevé précédent d'au moins `IOT_AI_TEMPERATURE_SPIKE_DELTA` °C ou `IOT_AI_HUMIDITY_SPIKE_DELTA` points d'humidité.
4. **Valeur inhabituelle** (`anomaly`) : écart d'au moins `IOT_AI_ANOMALY_DEVIATION` °C avec la moyenne des relevés récents.
5. **Périodique** (`periodic`) : aucune analyse depuis `IOT_AI_PERIODIC_INTERVAL_MINUTES` minutes (ou première mesure de l'appareil).

La raison est stockée dans `iot_telemetry_ai_analyses.trigger_reason`. Un seuil ou une variation survenant pendant la limitation de fréquence n'est pas analysé par l'IA, mais l'alerte thermique déterministe reste affichée. Avec les valeurs par défaut, une mesure toutes les 10 secondes produit environ 96 analyses par jour et par appareil au lieu de 8 640, hors événements.

Le widget affiche en plus le dernier verdict terminé lorsque la mesure courante n'a pas été analysée, avec l'heure de la mesure concernée.

### Limites de l'évaluation de cohérence

- Le verdict estime la cohérence avec l'historique récent ; il ne vérifie ni la calibration ni la précision du capteur.
- Un capteur qui dérive lentement peut rester jugé cohérent.
- Les recommandations sont indicatives et ne remplacent pas une action locale.
- Une mesure non analysée n'est pas une mesure jugée fiable : elle est présentée comme « Fiabilité non évaluée ».

### Vérification en environnement réel

1. Appliquer la migration, activer l'IA avec une clé valide et lancer le worker.
2. Envoyer une première mesure : le widget affiche « Mesure reçue » puis « Analyse en attente », puis le verdict (`periodic`).
3. Envoyer des mesures stables : elles apparaissent avec « Analyse non requise pour cette mesure » et le dernier verdict reste visible.
4. Provoquer un écart (par exemple +6 °C ou un franchissement de seuil, après la limitation de fréquence) : une analyse `spike` ou `threshold` est créée.
5. Couper l'accès à OpenAI (clé invalide) : les mesures restent reçues, l'alerte thermique reste affichée et l'analyse passe en `failed` après les tentatives.
6. Désactiver `IOT_AI_ENABLED` : aucune analyse n'est créée et le widget n'affiche plus de section IA.

## Analyse IA des risques et de la fiabilité

### Pourquoi la mesure brute est enregistrée avant l'analyse

Une réponse IA ne doit pas décider si la preuve brute doit exister. Une indisponibilité réseau, un refus du modèle ou une mauvaise évaluation pourrait sinon provoquer une perte définitive de télémétrie.

Le fonctionnement retenu est donc :

1. les contrôles déterministes de l'API valident la structure, les plages et l'identité du capteur ;
2. la mesure brute est enregistrée ;
3. si les règles de déclenchement la sélectionnent, une analyse IA est créée avec le statut `pending` ;
4. le travail est envoyé dans la file d'attente ;
5. l'IA classe la fiabilité et le risque ;
6. `approved_for_use` passe à `true` uniquement si le verdict de fiabilité vaut `reliable` et si l'historique minimal est disponible.

Une mesure `suspicious`, `unknown` ou en échec reste en base pour l'audit, mais n'est pas approuvée pour une exploitation dérivée. Un doublon possédant déjà une analyse terminée n'est pas retraité. En revanche, un ancien doublon sans analyse peut créer le travail IA manquant afin de rattraper les mesures reçues avant l'activation de la fonction.

Par défaut, trois mesures antérieures du même capteur sont exigées. Si le modèle répond `reliable` avant ce minimum, l'application remplace ce verdict par `unknown`. Ce garde-fou empêche d'approuver une première valeur isolée sur la seule appréciation du modèle.

### Données envoyées au modèle

L'appel contient uniquement :

- la température, l'humidité et les deux horodatages de la mesure courante ;
- jusqu'à dix relevés précédents du même capteur ;
- la plage de température sûre configurée.

L'identifiant du capteur, le nom, l'adresse électronique et l'identifiant numérique de l'utilisateur ne sont pas placés dans le prompt. Le paramètre `safety_identifier` contient un HMAC non réversible de l'identifiant utilisateur. La requête utilise `store: false`.

La sortie OpenAI est contrainte par un schéma JSON strict, conformément au mécanisme Structured Outputs. Le schéma limite les valeurs possibles et refuse les propriétés supplémentaires. Cette contrainte garantit la forme technique de la réponse, pas l'exactitude scientifique de son contenu.

### Résultat enregistré

Chaque mesure possède au maximum une analyse contenant :

- `status` : `pending`, `processing`, `completed` ou `failed` ;
- `reliability` : `reliable`, `suspicious` ou `unknown` ;
- `approved_for_use` : vrai uniquement pour `reliable` ;
- `risk_level` : `low`, `moderate`, `high`, `critical` ou `unknown` ;
- un résumé du risque ;
- la justification de fiabilité ;
- jusqu'à cinq recommandations ;
- un avertissement, le modèle utilisé et l'heure d'analyse.

Le job dispose de trois tentatives avec délais progressifs. Après épuisement, l'analyse passe à `failed` et `approved_for_use` reste faux. La mesure brute n'est jamais supprimée par cet échec.

### Affichage

Le widget continue d'afficher immédiatement le statut thermique déterministe. Il ajoute ensuite :

- un indicateur pendant l'analyse ;
- le niveau de risque et les recommandations lorsque l'analyse aboutit ;
- « mesure exploitable », « mesure suspecte » ou « fiabilité indéterminée » ;
- un état indisponible si les tentatives IA échouent.

Une mesure potentiellement dangereuse n'est jamais masquée parce que l'IA la juge suspecte.

### Activation

L'intégration est désactivée par défaut afin d'éviter des appels et des coûts involontaires. Après avoir configuré la clé OpenAI, activer explicitement :

```dotenv
OPENAI_API_KEY=...
IOT_AI_ENABLED=true
IOT_AI_MODEL=gpt-5.4-mini
IOT_AI_REASONING_EFFORT=low
IOT_AI_MAX_OUTPUT_TOKENS=1000
IOT_AI_TIMEOUT_SECONDS=60
IOT_AI_HISTORY_SIZE=10
IOT_AI_MIN_HISTORY_SIZE=3
```

Puis appliquer la migration et reconstruire le cache de configuration si nécessaire :

```bash
php artisan migrate
php artisan config:cache
php artisan queue:work --tries=3
```

Le worker de file d'attente doit être supervisé et redémarré après un déploiement. Avec une mesure toutes les dix secondes et sans sélection, une analyse systématique produirait jusqu'à 8 640 appels par appareil et par jour ; la stratégie de déclenchement ci-dessus limite ce volume. Il faut malgré tout surveiller les coûts, les quotas et le retard de file.

## Ce que l'état « normal » ne garantit pas

Le widget est un affichage informatif, pas un système de sécurité certifié.

En particulier :

- « Situation normale » signifie seulement que la dernière température reçue est dans la plage configurée ;
- l'humidité est affichée mais n'est pas encore classée ;
- aucune alerte de mesure périmée ou de capteur hors ligne n'est actuellement calculée ;
- si le réseau tombe, la dernière mesure reste visible avec son heure ;
- le rafraîchissement est réalisé par interrogation toutes les 5 secondes, pas par WebSocket ;
- aucune notification externe, sirène, SMS ou courriel n'est déclenché ;
- les mesures ne disposent pas d'un chiffrement applicatif individuel en base ; leur protection au repos dépend de la base et de l'infrastructure ;
- aucune politique automatique de rétention ou de purge n'est encore appliquée ;
- le protocole actuel authentifie le capteur par un secret Bearer mais ne met pas en œuvre de signature HMAC par message ni de certificat client.
- la sortie structurée de l'IA peut toujours contenir une appréciation incorrecte ; l'IA ne certifie ni la calibration ni la précision physique du capteur ;
- l'analyse de risque ne connaît ni l'état de santé de la personne, ni la durée réelle de jeu, ni la ventilation réelle en dehors des mesures transmises.

Pour un usage où une température dangereuse peut causer un dommage réel, le capteur doit prendre localement les mesures urgentes nécessaires. L'application ne doit pas constituer l'unique mécanisme d'alarme ou d'arrêt.

## Configuration côté application

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

Après une modification de ces variables sur une installation utilisant le cache de configuration Laravel, reconstruire ce cache et redémarrer les processus PHP persistants concernés.

## Vérifications automatisées existantes

La suite de tests couvre notamment :

- l'acceptation et le stockage d'une mesure valide ;
- les tokens absents ou invalides sans fuite du secret ;
- les appareils inconnus, désactivés ou révoqués ;
- l'impossibilité d'utiliser le token d'un appareil pour un autre identifiant ;
- les JSON mal formés, les champs invalides et les valeurs hors limites ;
- l'idempotence, les conflits de séquence et les tentatives de rejeu ;
- la mise à jour de la dernière activité ;
- les deux limites de débit ;
- le refus de HTTP en production ;
- l'isolation des mesures entre deux utilisateurs ;
- la classification normale/dangereuse et l'en-tête `no-store` du point de lecture ;
- le rendu du widget dans le tableau de bord.
- la création du travail IA pour chaque nouvelle mesure lorsque la fonction est activée ;
- la requête OpenAI structurée sans identifiant direct ni conservation côté API ;
- l'utilisation de l'historique du même capteur ;
- l'approbation des seules mesures classées `reliable` ;
- la conservation de la mesure brute et le passage à `failed` après épuisement des tentatives.

Les principaux tests se trouvent dans `tests/Feature/Api/IotTelemetryTest.php`, `tests/Feature/DashboardTest.php` et `resources/js/__tests__/dashboard.spec.mjs`.

## Checklist de déploiement

- Servir l'application uniquement derrière HTTPS et configurer correctement les proxies de confiance.
- Masquer l'en-tête `Authorization` dans le proxy, l'APM et les journaux d'infrastructure.
- Ne jamais placer un vrai token dans Git, une capture d'écran, un ticket ou un journal.
- Provisionner un token différent pour chaque appareil.
- Conserver les tokens du firmware dans un stockage adapté au matériel.
- Révoquer ou renouveler immédiatement un token suspecté d'être exposé.
- Restreindre les accès humains à la base, aux sauvegardes et aux commandes Artisan.
- Sauvegarder la base et tester sa restauration.
- Surveiller les réponses `401`, `403`, `409`, `429` et l'absence prolongée de nouvelles mesures.
- Définir une politique de conservation des mesures adaptée au besoin réel.

## Contrat à reprendre dans la documentation du capteur

La documentation firmware devra au minimum préciser :

- comment `device_id` et le token sont installés et protégés ;
- comment le certificat TLS du serveur est vérifié ;
- comment la séquence est conservée en mémoire non volatile sans retour en arrière ;
- comment l'heure est synchronisée et quand `measured_at` doit être omis ;
- comment une même charge est renvoyée après une erreur réseau sans changer la séquence ;
- comment le capteur réagit aux statuts `200`, `201`, `401`, `403`, `409`, `422` et `429` ;
- comment les secrets sont exclus des logs série ;
- quelle temporisation et quel backoff sont appliqués ;
- quelles actions locales sont prises sans attendre l'application lorsqu'une valeur est réellement dangereuse.
