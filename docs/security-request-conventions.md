# Security and Request Conventions

Status: **Phase 1 foundation**

This document defines the first reusable trust-boundary conventions for Ultimate Commerce Free. It does not make a feature secure by itself; each state-changing or customer-data feature still requires a specific security review.

## Dedicated capabilities

UC administrative surfaces should use product-specific capabilities rather than broad WordPress capabilities when practical.

Initial capabilities:

- `uc_view_diagnostics` — view UC diagnostics/system state
- `uc_manage_settings` — change UC settings when a settings surface is implemented

Default role grants:

- Administrator: both capabilities
- WooCommerce Shop Manager: diagnostics only

Capability grants are versioned through `uc_capability_version` so future additions can be migrated deliberately.

A capability check is authorisation. A nonce is not.

## Authorisation helpers

`BadOtter\UltimateCommerce\Security\Authorization` provides narrow primitives:

- authentication required
- exact capability required
- exact owner-user match

Ownership helpers deliberately do not include an implicit administrator bypass. Callers that need a privileged override must state it explicitly in their permission policy, for example ownership **or** a documented management capability.

Every customer/resource identifier remains untrusted even after authentication.

## CSRF / nonces

`BadOtter\UltimateCommerce\Security\Csrf` namespaces nonce actions with `uc_` and provides verify/require helpers.

Rules:

1. nonce verification protects a browser request against CSRF
2. nonce success never implies the current user may access or mutate the target object
3. capability/ownership checks remain separate and mandatory
4. guest flows should use purpose-built guest tokens rather than pretending a WordPress nonce is guest authorisation

Guest-token primitives are a separate Phase 1 workstream.

## REST routes

UC-owned routes use namespace:

```text
ultimate-commerce/v1
```

Register UC-owned endpoints through `BadOtter\UltimateCommerce\Rest\RouteRegistrar` from `rest_api_init`.

The registrar requires:

- explicit methods
- callable request callback
- explicit callable `permission_callback`
- typed argument schemas
- valid callback functions when custom sanitizers/validators are supplied

Public endpoints may intentionally use a permissive callback, but it must still be explicit so public access is visible in review.

### Pagination

Use the provided bounded helpers where they fit:

- `RouteRegistrar::pageArg()` — page starts at 1 and has an explicit maximum
- `RouteRegistrar::perPageArg()` — maximum cannot exceed 100

Do not add public `limit=-1`, unbounded page sizes or arbitrary full-history ranges.

## Permission callback shape

A customer-resource permission callback should compose the exact requirements instead of checking only login state.

Conceptually:

```php
$authenticated = Authorization::requireAuthenticated();
if (is_wp_error($authenticated)) {
    return $authenticated;
}

return Authorization::requireOwnership($resourceOwnerUserId);
```

An administrative route should use an exact UC capability:

```php
return Authorization::requireCapability(Capabilities::MANAGE_SETTINGS);
```

State-changing wp-admin/browser forms additionally apply the CSRF convention.

## What is not implemented by this foundation

This slice intentionally does not yet provide:

- guest-token storage/expiry/revocation
- Secret Store
- SSRF-safe provider HTTP client
- webhook signature/replay gateway
- audit persistence
- rate limiting
- privacy exporter/eraser integration
- idempotency/locking framework

Those remain Phase 1 deliverables and should build on, not bypass, these request primitives.
