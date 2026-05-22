#!/usr/bin/env bash
set -euo pipefail

# Allow opting out when someone explicitly disables the helper.
if [[ "${STRIPE_AUTO_LISTEN:-1}" =~ ^(0|false|False|FALSE)$ ]]; then
    echo "[stripe-listen] Auto listener disabled via STRIPE_AUTO_LISTEN."
    exit 0
fi

if ! command -v stripe >/dev/null 2>&1; then
    cat <<'MSG'
[stripe-listen] Stripe CLI introuvable. Installez-le depuis https://stripe.com/docs/stripe-cli si vous
souhaitez recevoir automatiquement les webhooks en local.
MSG
    exit 0
fi

forward_url="${STRIPE_FORWARD_URL:-}"
if [[ -z "${forward_url}" ]]; then
    base_url="${STRIPE_APP_URL:-http://127.0.0.1:8000}"
    base_url="${base_url%/}"
    forward_url="${base_url}/stripe/webhook"
fi

forward_url="${forward_url%/}"

>&2 echo "[stripe-listen] Forwarding events to ${forward_url}" 
set -x
exec stripe listen --forward-to "${forward_url}"
