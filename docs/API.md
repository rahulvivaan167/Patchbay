# JSON API

Create an API key from the Patchbay dashboard. Send it as a bearer token:

```text
Authorization: Bearer pb_...
```

API keys are only shown once. Patchbay stores a SHA-256 hash and a short display prefix.

## Endpoints

- `GET /api/v1/workflows` — workflow summary
- `GET /api/v1/events` — latest captured events
- `GET /api/v1/executions` — latest executions
- `GET /api/v1/executions/{id}` — execution plus step logs

`/api/v1/executions?status=failed` filters by execution status.

The API is currently read-only by design. Administrative mutations remain behind the browser session and CSRF protection.
