# External-boundary security foundations

Status: **Phase 1 public security conventions**

This document defines the reusable Ultimate Commerce primitives for guest tokens, secret persistence, outbound provider HTTP and signed incoming webhooks. These are platform primitives. Feature modules should consume them rather than inventing local token formats, plaintext credential options, permissive HTTP wrappers or ad-hoc webhook verification.

The public security API version is `ULTIMATE_COMMERCE_SECURITY_API_VERSION = 1.0.0`.

## Guest tokens

Use `BadOtter\UltimateCommerce\Security\GuestToken` for short-lived guest access to a specific purpose and subject.

Properties:

- HMAC-SHA256 signed with WordPress auth salt-derived key material
- explicit purpose binding
- explicit resource/subject binding
- issued-at and expiry timestamps
- random token ID
- maximum lifetime seven days
- small scalar-only claims

Guest tokens are **signed, not encrypted**. Do not put credentials, private customer data or other secrets in token claims. A token authorises only the purpose/resource your permission callback explicitly associates with it; possession of a guest token is not a general UC identity.

## Secret Store

Use `BadOtter\UltimateCommerce\Security\SecretStore` rather than writing credentials to feature-specific options.

The default provider is `EncryptedOptionSecretStore`:

- one non-autoloaded UC secret option
- per-secret authenticated encryption
- XChaCha20-Poly1305 when Sodium is available
- AES-256-GCM fallback when OpenSSL is available
- key material derived from the WordPress secure-auth salt
- key name is authenticated as additional data, preventing ciphertext swapping between names

External/enterprise secret providers can replace the default through `ultimate_commerce_secret_store_provider` by returning an implementation of `BadOtter\UltimateCommerce\Contracts\SecretStore`.

Important limitations:

- encrypted-at-rest storage protects against a database-only disclosure; it does not protect secrets from an attacker who already controls WordPress/PHP or the WordPress salts
- rotating WordPress salts makes existing encrypted secrets unreadable; operational salt rotation must include credential re-entry/re-provisioning
- if neither Sodium nor OpenSSL authenticated encryption is available, writes fail closed instead of storing plaintext

## Provider HTTP

Use `BadOtter\UltimateCommerce\Http\ProviderHttp` for UC-owned outbound provider requests.

Every call requires an explicit list of exact allowed authorities such as:

```php
array('api.example.com', 'api.example.com:8443')
```

The wrapper enforces:

- HTTPS only
- DNS hostnames rather than literal IP URLs
- exact host/port allowlisting
- no URL userinfo or fragments
- WordPress URL safety validation
- `wp_safe_remote_request()`
- TLS verification
- unsafe-URL rejection
- zero automatic redirects
- bounded timeout
- bounded in-memory response size
- no caller-supplied `Host` header override

Redirects are deliberately not followed automatically. A provider adapter that legitimately uses redirects must validate the new destination as a fresh allowlisted request.

Provider adapters should construct URLs from configuration they own. Do not pass arbitrary customer-supplied URLs directly into provider requests.

## Signed webhook gateway

`BadOtter\UltimateCommerce\Security\SignedWebhook` provides the UC HMAC webhook convention for integrations that can use it. Provider adapters with proprietary signature formats may perform provider-specific signature parsing, but should preserve the same timestamp, raw-body, replay and idempotency principles.

UC v1 signing input:

```text
timestamp.event_id.raw_request_body
```

Signature header/value:

```text
v1=<hex hmac-sha256>
```

Multiple comma-separated `v1=` signatures may be supplied during key rotation.

Verification requires:

- raw request body (before JSON parsing/re-encoding)
- provider/integration scope
- stable event ID
- timestamp within the configured tolerance
- at least 16 bytes/characters of webhook secret
- HMAC match using constant-time comparison
- body size at or below 2 MiB

## Replay leases

`OptionReplayStore` implements the default replay guard through atomic `add_option()` claims keyed by a SHA-256 hash of scope + event ID.

A successful claim returns a random lease token. Duplicate claims are rejected. The stored record has a bounded expiry and a scheduled cleanup hook. A handler that fails before completing its durable side effect may release only the matching lease.

The replay store protects the webhook acceptance window; it does not replace business-level idempotency. High-risk handlers must still make their own domain mutation idempotent (for example, a payment/refund/stock movement must have a stable external idempotency key or state transition).

Recommended flow:

```text
read raw request body
 -> verify signature + timestamp
 -> claim replay lease
 -> perform or durably enqueue idempotent domain work
 -> success: keep lease until expiry
 -> transient failure before durable work: release matching lease
```

Do not release a lease after a successful side effect merely to allow provider retries.

## Public-contract rule

Pro and third-party extensions may consume these primitives through the documented classes/interfaces. Free must not add special private security bypasses for Pro.

A Pro feature that needs different policy may compose additional checks around these primitives, but should not weaken the shared guarantees silently.
