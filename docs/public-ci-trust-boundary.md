# Public CI trust boundary

Ultimate Commerce separates **untrusted validation** from **trusted publishing** so the Free repository can be made publicly reviewable without giving pull-request code access to a persistent Bad Otter runner.

## Untrusted pull requests

Every workflow triggered by `pull_request` must:

- run on the GitHub-hosted `ubuntu-latest` baseline;
- declare read-only repository contents permission;
- avoid write-scoped GitHub permissions;
- receive no Bad Otter publishing credentials or release identity;
- never use `pull_request_target` to execute contributor-controlled code.

The repository regression at `tests/ci-trust-boundary.py` enforces this across every workflow file, including newly added workflows.

## Trusted publishing

Publishing remains a separate trust domain.

`.github/workflows/development-release.yml` and `.github/workflows/release.yml` may use the controlled Bad Otter self-hosted runner because they do not run on pull-request events. They are allowed to hold the permissions needed for their explicit release duties.

A validation workflow must not be converted into a publisher merely to reuse credentials or release infrastructure.

## Repository visibility

Moving validation to GitHub-hosted runners removes the persistent-runner blocker recorded in the WordPress.org release gate. It does **not** itself change repository visibility or submit the plugin to WordPress.org.

Repository visibility remains an explicit release decision after the remaining Phase 4 security, compatibility, accessibility, upgrade and submission gates are green.
