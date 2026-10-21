# Security notes

Patchbay is designed to be self-hosted on a trusted server.

- Admin forms use session authentication and CSRF tokens.
- Passwords use PHP `password_hash()`.
- API tokens are stored only as SHA-256 hashes.
- Workflow signing secrets and Secrets-vault values are encrypted with AES-256-GCM using a key derived from `APP_KEY` (or the auto-generated `storage/app.key` fallback).
- Captured Authorization, Cookie, and X-API-Key headers are redacted before storage.
- Incoming endpoints are rate-limited per workflow and source IP.
- Payload size is capped by `MAX_PAYLOAD_BYTES`.
- HTTP action destinations reject private, reserved, and loopback IP addresses to reduce SSRF risk.
- Optional HMAC-SHA256 verification uses the exact raw request body and the `X-Patchbay-Signature` header.
- `public/` should be the only web-accessible directory.

Patchbay does not attempt to replace a network firewall. Restrict the admin interface at the reverse proxy or VPN layer when deploying it to sensitive infrastructure.
