# Workflow model

A Patchbay workflow receives an HTTP event through its unique `/hook/{token}` endpoint and stores the original request before execution begins. The worker processes the workflow steps in order.

## Step types

### Filter

Reads a value using dot notation, such as `repository.full_name`, then compares it using operators including `equals`, `contains`, `exists`, `gt`, `lte`, and `in`. A false filter marks the execution as skipped.

### Transform

Four modes are available: `map`, `merge`, `pick`, and `remove`. Map mode creates a new payload. A mapping source prefixed with `=` is treated as a literal string.

### HTTP request

Sends the current or a custom payload to an HTTP/HTTPS endpoint. It supports methods, custom headers, timeouts, retry limits, and retry delays. For SSRF protection, loopback, private, and reserved destination addresses are rejected.

### Delay

Queues the same execution for a future time and resumes at the following step. A single delay can be from 1 second to 24 hours.

### Log

Writes a templated message to the execution timeline without making an external request.

## Template values

HTTP URLs, headers, custom bodies, and log messages can use values such as:

- `{{payload.user.email}}`
- `{{headers.x-github-event}}`
- `{{query.source}}`
- `{{event.request_id}}`
- `{{workflow.name}}`
- `{{secret.DEPLOY_TOKEN}}` (resolved from the encrypted Secrets vault)

## Retry behavior

HTTP steps retry from the failed HTTP step, not from the beginning of the workflow. A manual retry from the dashboard resets the execution to step zero.
