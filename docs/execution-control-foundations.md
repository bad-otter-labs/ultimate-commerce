# Execution-control foundations

Status: **Phase 1 public concurrency, idempotency, rate-limit and background-job contracts**

Public contract version:

- `ULTIMATE_COMMERCE_EXECUTION_API_VERSION = 1.0.0`

These primitives exist so high-risk or expensive UC workflows do not invent incompatible locking, duplicate-execution, retry or abuse controls.

## The guarantees are intentionally different

Do not treat all duplicate/concurrent work as one problem.

### Mutual-exclusion lock

Use `Concurrency\Lock` when only one process should enter a short critical section at a time.

The default `OptionLockStore`:

- claims through atomic WordPress `add_option()` uniqueness
- stores only a hashed scope/key plus expiry and random lease
- returns `false` when an unexpired lock already exists
- releases only when the caller presents the matching lease
- schedules safe expiry cleanup that cannot delete a later replacement lease

Locks should be short. They are not historical proof that an operation already completed.

### Business idempotency

Use `Idempotency\Idempotency` when a business operation must not execute twice across browser retries, provider retries or job retries.

The default `OptionIdempotencyStore` tracks:

- `claimed` for the process that acquired a new operation key
- `in_progress` for duplicates while work is active
- `completed` plus an optional short result reference for later duplicates

A claim returns a random lease. Only the matching in-progress lease can complete or release the operation. Completion remains until the configured TTL, so a retry can observe that the business operation already succeeded.

`result_ref` is for an opaque identifier such as an order/ledger/movement ID. Do not store full API responses, personal data or secrets in the idempotency record.

For money-like or stock-critical mutations, this generic store does **not** replace domain-level unique constraints/transactions. Gift-card redemption, inventory receipt and similar workflows should combine the idempotency key with the strongest consistency mechanism available to their authoritative domain store.

### Replay protection

Signed-webhook `ReplayStore` remains a separate Security API primitive. Replay protection protects acceptance of an external event; business idempotency protects the resulting domain operation. A webhook handler may need both.

## Rate limiting

Use `RateLimit\RateLimit` for expensive public/guest operations such as balance checks, guest lookups or abusive search/notification requests.

The default `TransientRateLimiter` is a local fixed-window limiter:

- hashes scope + subject into the transient key, so raw subject values are not stored in the key
- returns `allowed`, `remaining` and `retry_after`
- bounds the limit and window
- stores only counter/reset metadata

WordPress transients do not provide a universal cross-node atomic increment. The default limiter is therefore an abuse-reduction baseline, not a claim of strict distributed quota enforcement. Large/multi-node or money-like validation endpoints can replace it through `uc_rate_limiter` with Redis/edge/other atomic infrastructure while preserving the public contract.

Callers decide the subject identity. Prefer an opaque user/customer/session identifier or a privacy-conscious derived value; do not persist raw personal data merely to rate-limit it.

## Lock/idempotency providers

The default option-backed stores can be replaced through:

- `uc_lock_store`
- `uc_idempotency_store`

Custom providers implement the public `LockStore` / `IdempotencyStore` interfaces.

## Action Scheduler

WooCommerce/Action Scheduler is the local background-work default.

Use `Jobs\ActionScheduler` for UC-owned jobs. It:

- schedules in the `ultimate-commerce` group
- creates hooks as `uc_job_<job>`
- supports unique scheduling
- rejects nested job payloads
- limits argument count/string size
- rejects obvious credential and personal-data snapshot field names
- fails clearly when Action Scheduler is unavailable
- limits scheduling to at most one year ahead

Jobs should carry object IDs, stable opaque references and flags, then rehydrate fresh domain state when they run. Avoid copied email/address/request payloads that become stale and create unnecessary personal-data retention.

## Retry policy

`RetryPolicy` defines bounded exponential retry:

- default maximum attempts: 5
- default delay after first failure: 60 seconds
- exponential growth
- default cap: 3600 seconds
- hard maximum configuration bounds

`ActionScheduler::scheduleRetry()` increments `uc_attempt` and refuses to reschedule after the retry limit.

A retry loop is not an excuse to hammer a failing provider. Provider-specific adapters should additionally respect HTTP/provider retry semantics and circuit/failure isolation where appropriate.

## Diagnostics and dead work

This foundation standardises scheduling/control semantics but does not yet build the queue-health UI. A later diagnostics slice must surface failed/dead Action Scheduler work, progress for long batches and actionable recovery information without leaking job payload secrets/PII.

## Cross-repository rule

Pro and third-party modules consume these public Free execution contracts. A Pro module that needs stronger storage consistency may supply a provider, but must not silently weaken idempotency/locking semantics or introduce a private parallel scheduler framework.
