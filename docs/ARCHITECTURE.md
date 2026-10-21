# Architecture

Patchbay separates ingestion from execution so inbound webhooks can be accepted quickly and processed independently.

## Request path

1. A sender calls `/hook/{token}`.
2. Patchbay resolves the active workflow, applies rate limits, optional Origin restrictions, payload-size limits, and optional HMAC verification.
3. The original event is stored in `events` with sensitive request headers redacted.
4. A queued row is created in `executions`.
5. The worker atomically claims the execution and evaluates steps in order.
6. Each step writes an `execution_logs` record containing timing, status, and safe input/output information.

## Execution state

The current transformed payload is persisted in `executions.payload_state_json`. This matters when a workflow pauses at a delay or retries an HTTP action: it resumes with the payload produced by the preceding transform steps rather than reconstructing from the original event.

HTTP failures are counted per HTTP step from step logs. Delay/resume cycles therefore do not consume HTTP retry attempts.

Patchbay uses at-least-once delivery semantics for outbound HTTP actions. A worker crash at the exact boundary after an external service accepts a request but before local progress is persisted can cause a duplicate delivery. Destination APIs should use an idempotency strategy when duplicate side effects would be harmful. The event `request_id` can be included in a custom request body or header for that purpose.

## Concurrency

A worker atomically changes an execution from `queued` to `running` before processing it. Competing workers cannot claim the same queued row successfully. A local `flock` additionally prevents accidental duplicate daemon workers on one host. Executions left running after an interrupted worker are requeued after a stale-claim timeout.

## Data boundaries

- `events` — immutable inbound requests.
- `workflows` — endpoint configuration and ordered step definitions.
- `executions` — mutable runtime state for one event/workflow run.
- `execution_logs` — append-only step history.
- `secrets` — AES-256-GCM encrypted workflow credentials.
- `api_keys` — one-way SHA-256 hashes of API bearer credentials.
- `audit_logs` — administrative changes made through the dashboard.
- `settings` — small instance-level values such as retention and schema version.

The browser-facing admin UI uses session authentication and CSRF protection. The JSON API uses independent bearer credentials and is intentionally read-only.
