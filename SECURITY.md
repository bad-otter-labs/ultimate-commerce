# Security Policy

Ultimate Commerce is intended to run on commerce sites that may contain sensitive customer and operational data. Security reports are treated as private vulnerability reports, not ordinary feature issues.

## Supported development lines

During the current Free/Pro migration:

- the canonical `ultimate-commerce-for-woocommerce` 0.2.x line is the active development target;
- the historical `ultimate-commerce` 0.1.x package is supported only as the temporary migration bridge needed to move existing installations safely;
- older development snapshots are not separate supported release lines.

Support status will be tightened before the public 1.0 release.

## Reporting a vulnerability

Do **not** open a public GitHub issue containing exploit details, credentials, customer information or a working proof of concept.

Use GitHub's private security-advisory / private vulnerability-reporting flow for this repository when available. If that channel is unavailable, contact Bad Otter Labs through the private support/security contact published at `https://badotter.io` and clearly mark the report as a security issue.

Include, where possible:

- affected plugin/repository and version/commit;
- prerequisites and affected roles/users;
- clear reproduction steps;
- security impact;
- whether the issue is exploitable remotely or requires authentication;
- relevant request/response details with secrets and personal data removed;
- suggested mitigation if known.

Do not send real production credentials, full card data, customer exports or unnecessary personal information.

## Scope priorities

High-priority areas include:

- authentication/authorisation bypass;
- customer/order/resource ownership bypass;
- CSRF on privileged mutations;
- SQL injection, XSS or unsafe file handling;
- SSRF or unsafe outbound provider requests;
- exposed credentials/secrets;
- webhook signature/replay bypass;
- gift-card/stored-value double-spend;
- inventory or order mutation replay/double execution;
- privilege escalation in Hub or WordPress admin;
- package/update integrity failures;
- leakage of personal data through REST, diagnostics, logs or exports.

## Security engineering rules

The project security model is defined in `BLUEPRINT.md` and the focused contracts under `docs/`.

Important invariants include:

- WooCommerce remains authoritative for commerce truth;
- Free never depends on Pro;
- nonces are CSRF protection, not authorisation;
- customer resources require object-level ownership checks;
- secrets use the Secret Store and do not belong in config exports/logs/REST;
- external HTTP uses allowlisted SSRF-safe provider clients;
- high-risk operations use idempotency/concurrency controls appropriate to their domain;
- release packages are deterministic and checksum-verifiable;
- the public Free package never contains the private Bad Otter updater or Pro implementation.

## Disclosure and fixes

Bad Otter Labs will validate reports privately, coordinate a fix and release process appropriate to the affected distribution channel, and communicate disclosure timing with the reporter where practical. No fixed response-time SLA is promised in this pre-1.0 development phase.
