# Changelog

## 2.2.0 - 2026-09-05

Audited against the official fal.ai documentation and the fal-js / fal-client-python sources. Every change below was verified against the live API. No public signature was removed or changed; new parameters are optional and appended last.

### Fixed

- Queue `status()`, `result()`, `cancel()` and `subscribe()` returned 405 for models with a sub-path (`fal-ai/flux/schnell`, `fal-ai/flux/dev`, ...). The queue addresses requests by application id (`fal-ai/flux`); the client now derives it from the model id like the official clients, including the `workflows/` and `comfy/` namespaces.
- `models->get()` threw on unknown endpoint ids instead of returning null: the catalog API now answers 404 for them.
- Storage default base URL is now `https://rest.fal.ai`, the host used by the official clients. `https://rest.alpha.fal.ai` still works through the `storageBaseUrl` option.

### Added

- `FalAI::stream()`: run a model on its `/stream` endpoint and iterate over server-sent events; the last event is the final result.
- `QueueResource::streamStatus()`: iterate over status updates pushed by the queue instead of polling.
- `ObjectLifecycle` (`Data\ObjectLifecycle`) and the `lifecycle` option on `run()`, `stream()`, `queue->submit()`, `queue->subscribe()`, `storage->upload()` and `storage->uploadData()`: file expiration and initial access control (`X-Fal-Object-Lifecycle-Preference`).
- `storeIo` option on `run()`, `stream()`, `queue->submit()` and `queue->subscribe()` (`X-Fal-Store-IO`).
- `maxQueueLength` option on `queue->submit()` and `queue->subscribe()` (`fal_max_queue_length`).
- Multipart upload for files above 90 MB (10 MB parts, 3 attempts per part), matching the official clients.
- `QueueStatus::fromArray()`.

### Changed

- `WebhookVerifier` caches JWKS keys for 24 hours instead of forever and refetches them once (at most every 60 seconds) when a signature matches none of the cached keys, so rotated keys do not require a restart.
- README rewritten around the verified behavior: application id vs endpoint id, cancel response codes, webhook payload and retry policy, pricing units, validation errors surfacing as a 422 from the result endpoint.
