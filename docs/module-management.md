# Ultimate Commerce module management

Ultimate Commerce Free exposes merchant-facing module controls at **Ultimate Commerce -> Modules**.

The page is a UI over the existing Module API and `uc_modules` preference store. It does not create a second module registry or a Pro-specific configuration path.

## Behaviour

- Registered modules default to enabled when no explicit preference exists.
- Saving the page writes an explicit enabled/disabled preference only for modules that are registered on that request.
- Preferences for temporarily unregistered modules are preserved. Deactivating Pro or another extension and saving Free settings therefore does not erase that extension's remembered module state.
- The next WordPress request resolves the saved preferences before modules boot.
- Dependencies are not silently enabled or disabled on the merchant's behalf. If an enabled module cannot boot because a dependency is unavailable, the Modules page reports the registry's blocked state and issue.
- `uc_module_enabled` remains the final code-level override. When its effective result differs from the stored merchant preference, the page reports that the effective state is overridden by code.

## Security

The Modules page and save action require the exact `uc_manage_settings` capability. State-changing requests use the shared Free `Csrf` contract with a purpose-specific nonce and an `admin-post.php` handler.

Shop managers continue to receive `uc_view_diagnostics` only by default. They can view the Ultimate Commerce Overview/Diagnostics pages but cannot change module settings unless a site administrator explicitly grants `uc_manage_settings`.

## Extension modules

Pro and third-party modules registered through `uc_register_modules` automatically participate in this UI. Free reads only the public Module contract metadata exposed by `ModuleRegistry::statuses()` and does not depend on Pro classes or product logic.

A registered extension module should provide a stable canonical key, product identifier, tier and dependency keys. Invalid or duplicate module keys are still rejected by the Module Registry before the admin page is built.

## WooCommerce ownership

Disabling an Ultimate Commerce experience module does not transfer commerce truth to Ultimate Commerce. WooCommerce continues to own products, variations, sellable stock, cart/session state, totals, tax, customers, orders, payments and monetary refunds.

For example, disabling the Free Cart module removes Ultimate Commerce's cart-drawer enhancement; it does not disable or replace WooCommerce's native cart and checkout flows.
