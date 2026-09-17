# Settings import/export

Ultimate Commerce Free provides a portable JSON settings file under **Ultimate Commerce → Settings**.

## Scope

The transfer format is intentionally allowlisted. Version 1 contains stored module enable/disable preferences only. It does not export WordPress/WooCommerce data, runtime version/schema metadata, secrets, licence credentials, caches, sessions, orders, products, stock, cart state or payment data.

Module preferences for temporarily unavailable Pro or third-party modules are retained so a configuration can move between sites without requiring every extension to be active during the transfer.

## Import behaviour

- files are limited to 64 KB;
- the document must identify `ultimate-commerce-for-woocommerce` and settings schema version `1`;
- only the `modules` settings section is accepted;
- module keys must be canonical Ultimate Commerce keys and values must be JSON booleans;
- at most 128 module preferences are accepted;
- imported preferences merge into existing stored module states, so omitted extension keys are not deleted;
- the normal module registry applies the imported preferences on the next request.

Import/export actions require the exact `uc_manage_settings` capability and the shared Ultimate Commerce CSRF contract.
