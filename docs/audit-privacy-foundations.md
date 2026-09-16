# Audit, privacy and data-lifecycle foundations

Status: **Phase 1 public governance contracts**

Ultimate Commerce treats privacy, portability and high-impact audit history as platform concerns rather than feature-local afterthoughts.

Public contract versions:

- `ULTIMATE_COMMERCE_AUDIT_API_VERSION = 1.0.0`
- `ULTIMATE_COMMERCE_PRIVACY_API_VERSION = 1.0.0`

These contracts are deliberately storage-neutral. This slice defines event/data semantics and WordPress privacy integration. It does not yet create an audit-history table or force one retention policy across every merchant/jurisdiction.

## Data classification

Every UC-owned data concept should be classified as one of:

| Classification | Meaning | Examples |
| --- | --- | --- |
| `public` | safe public/presentation data | published badge/collection presentation metadata |
| `merchant_operational` | merchant-owned operational/business data | inventory movement IDs, campaign state, workflow references |
| `personal` | identifies or relates to a person/customer/staff member | profile preferences, stock-alert subscriber data, customer workflow data |
| `credential_secret` | authentication or security-sensitive material | API credentials, licence keys, site tokens, webhook secrets |

Rules:

- credential/secret data must use the Secret Store and never appear in ordinary config export, REST responses, diagnostics, logs or audit context;
- personal data is minimised and must participate in WordPress export/erasure where UC owns it and erasure is legally/operationally appropriate;
- merchant-operational data remains merchant-owned/portable even when licence entitlement expires;
- public data is not automatically safe to accept from untrusted callers; request validation still applies.

`DataClassification` is a vocabulary, not an automatic legal conclusion. Modules remain responsible for classifying the data they introduce correctly.

## Retention vocabulary

`DataRetention` provides shared retention classes:

- `transient`
- `account_lifetime`
- `merchant_policy`
- `operational_history`
- `legal_policy`

A retention class describes why/how long data exists; it does not silently delete records. Actual retention jobs must be explicit, auditable and compatible with the relevant domain/legal obligations.

High-volume logs/events must not default to forever merely because storage is cheap.

## WordPress personal-data registry

Modules register UC-owned personal-data handlers through:

```php
add_action('uc_register_personal_data_handlers', function ($registry) {
    $registry->register(
        'stock_alerts',
        'Ultimate Commerce stock alerts',
        $exporter,
        $eraser,
        \BadOtter\UltimateCommerce\Privacy\DataRetention::ACCOUNT_LIFETIME
    );
});
```

`PersonalDataRegistry` then exposes those callbacks through WordPress core's:

- `wp_privacy_personal_data_exporters`
- `wp_privacy_personal_data_erasers`

Registration requires both an exporter and eraser callback. An eraser may legitimately retain records when law or operational integrity requires it, but it must report that retention through the normal WordPress eraser result rather than simply disappearing from privacy tooling.

The registry publishes only handler metadata and callbacks; it does not centralise copies of the personal data itself.

Privacy hooks are registered before the WooCommerce dependency gate so UC-owned personal data remains exportable/erasable even if WooCommerce is temporarily inactive.

## Audit event contract

Use `BadOtter\UltimateCommerce\Audit\AuditEvent` for high-impact actor/action history.

The event carries:

- generated event UUID
- stable action key
- actor type + identifier
- target type + identifier
- optional machine-readable reason code
- small scalar-only safe context
- UTC Unix timestamp
- fixed `merchant_operational` classification
- `operational_history` retention metadata

Actor types are:

- `user`
- `system`
- `guest`
- `provider`

User actors use the numeric WordPress user ID. Audit context should use identifiers and non-sensitive operational facts instead of names, emails, addresses or copied request payloads.

The event factory rejects obvious credential/authentication field names such as passwords, secrets, tokens, authorisation headers, cookies, nonces and API/licence keys. This is a guardrail, not a substitute for data-minimising event design.

Examples of actions that should eventually emit audit events include:

- inventory adjustment
- product publication/price change
- return/exchange override
- refund handoff
- manual stored-value issue/adjustment
- purchase-order approval
- campaign publication
- integration credential change (never the credential value)
- permission/role changes

## Audit sinks and persistence

`Audit::record($event)` sends events to an `AuditSink`.

The default `HookAuditSink` emits:

```text
uc_audit_event
```

It deliberately does **not** write to the PHP error log or a generic WordPress option. A later audit persistence slice can provide an indexed store with explicit retention/export policy, and Pro/enterprise deployments can replace the sink through `uc_audit_sink`.

A persistent sink must preserve the event contract and must not silently add secret/request payload data.

Debug logs and audit records are different products:

- debug logs support diagnosis and should minimise/redact data;
- audit history supports accountability for high-impact operations and has explicit retention/export controls.

## Cross-repository rule

Pro and third-party modules register personal-data handlers and emit audit events through these public Free contracts. Free does not gain knowledge of Pro's data models, and Pro does not create a private parallel privacy/audit framework.
