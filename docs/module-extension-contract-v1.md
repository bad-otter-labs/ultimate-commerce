# Module Extension Contract v1

Status: **Phase 0 public extension contract**

Ultimate Commerce Pro and third-party extensions register modules through the same public Free contract. Free does not depend on Pro and no extension receives a secret privileged registration path.

## Contract version

The canonical Free plugin exposes:

```php
define('ULTIMATE_COMMERCE_MODULE_API_VERSION', '1.0.0');
```

The PHP interface also exposes `BadOtter\UltimateCommerce\Contracts\Module::CONTRACT_VERSION` with the same value. A breaking change to this contract requires an explicit version/deprecation decision.

## Module metadata

Every module declares:

- `key()` — stable machine identifier using WordPress `sanitize_key()` form
- `name()` — human-readable module name
- `product()` — owning product/plugin identifier such as `ultimate-commerce-for-woocommerce` or `ultimate-commerce-pro`
- `tier()` — `free`, `pro` or `extension`
- `dependencies()` — other UC module keys that must boot first
- `compatibility()` — declarative compatibility metadata
- `settingsSchema()` — declarative settings shape where applicable
- `assets()` — declarative frontend/admin asset requirements
- `register()` — module hook/service registration lifecycle

`AbstractModule` supplies safe defaults for dependencies, module-API compatibility, settings and assets. Extensions may implement `Module` directly instead.

## Registration lifecycle

Free creates one `ModuleRegistry`, registers its built-in modules, then fires:

```php
do_action('uc_register_modules', $registry);
```

Pro and third-party plugins hook that action and call only the public `ModuleRegistry::register()` method.

Example:

```php
use BadOtter\UltimateCommerce\Contracts\AbstractModule;
use BadOtter\UltimateCommerce\Contracts\Module;
use BadOtter\UltimateCommerce\Support\ModuleRegistry;

final class ExampleModule extends AbstractModule
{
    public function key(): string { return 'example_feature'; }
    public function name(): string { return 'Example Feature'; }
    public function product(): string { return 'example-commerce-extension'; }
    public function tier(): string { return Module::TIER_EXTENSION; }
    public function dependencies(): array { return array('product_display'); }

    public function register(): void
    {
        add_action('wp_footer', array($this, 'render'));
    }

    public function render(): void
    {
        // Example only. Real output still follows normal escaping and capability rules.
    }
}

add_action('uc_register_modules', static function (ModuleRegistry $registry): void {
    if (!defined('ULTIMATE_COMMERCE_MODULE_API_VERSION')) {
        return;
    }

    if (version_compare(ULTIMATE_COMMERCE_MODULE_API_VERSION, '1.0.0', '<')) {
        return;
    }

    $registry->register(new ExampleModule());
});
```

If Free is absent, `uc_register_modules` never fires. Pro should separately present its dependency guard to an administrator; it must not make Free call into Pro.

## Dependency rules

The registry resolves module dependencies before booting dependants.

- missing dependencies block the dependant module
- disabled dependencies block the dependant module
- circular dependencies are blocked
- duplicate module keys are rejected
- invalid/self dependencies are rejected at registration
- blocked modules expose a machine-readable issue in registry diagnostics

The registry does not currently interpret arbitrary semantic-version constraints in `compatibility()`. The metadata is public and inspectable now; standardized support-matrix enforcement belongs with the Phase 1 compatibility/test baseline rather than being improvised inside individual modules.

## Assets and settings

`assets()` and `settingsSchema()` are declarative v1 surfaces. They exist now so Pro/third parties do not need a new incompatible module shape later. Central asset/settings managers may consume them as those Phase 2/3 foundations are implemented.

Disabled or blocked modules must not call `register()`, which prevents their runtime hooks/assets/jobs from being registered through the module lifecycle.

## Compatibility filter

The historical `uc_module_classes` filter remains available during the pre-release transition, but it is not the preferred Pro/third-party contract. New extensions should use `uc_register_modules` and `ModuleRegistry::register()`.

## Public-contract rule

Pro must be capable of registering through this documented contract alone. Adding a secret Free-to-Pro hook or direct access to private registry internals requires architecture review and would violate ADR 0001 / the master blueprint.
