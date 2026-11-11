# Public CI trust boundary

Ultimate Commerce separates **untrusted validation** from **trusted same-repository development and publishing** so the Free repository can become publicly reviewable without giving external pull-request code access to a persistent Bad Otter runner.

## Pull-request runner routing

Every public Free `pull_request` validation job with an explicit runner uses the **standard GitHub-hosted** `ubuntu-latest` environment. This applies to same-repository pull requests and untrusted external forks alike, including Docker/Playwright accessibility and official WordPress.org Plugin Check. Standard GitHub-hosted Actions usage for public repositories does not consume the private Pro runner budget. No PR may execute contributor-controlled code on persistent Bad Otter self-hosted machines.

This replaces the older expression that sent same-repository pull requests to the unqualified `badotter` runner. That alias could dispatch Docker-heavy checks to the lightweight `ci-fast` runner without Docker socket permission, producing environmental failures unrelated to product code. All jobs now receive an ephemeral runner with Docker available and no shared working-directory state. A public fork must never fall back to self-hosted execution because hosted capacity is temporarily unavailable.

A regression test checks each `pull_request` workflow for read-only permissions, no mutable action branch refs, no `pull_request_target`, and hosted-only declared runners. Publisher-only workflows are tested separately and retain their existing authorisation and release controls.

## Pull-request permissions

All pull-request workflows must:

- declare read-only repository contents permission;
- avoid write-scoped GitHub permissions;
- receive no Bad Otter publishing credentials or release identity;
- never use `pull_request_target` to execute contributor-controlled code.

The repository regression at `tests/ci-trust-boundary.py` enforces these rules across every workflow file, including newly added workflows.

## Trusted publishing

Publishing remains a separate trust domain.

`.github/workflows/development-release.yml` and `.github/workflows/release.yml` use their existing isolated GitHub-hosted runners and may hold only the permissions needed for their explicit release duties. Neither workflow may accept `pull_request` or `pull_request_target`.

A validation workflow must not be converted into a publisher merely to reuse credentials or release infrastructure.

## Repository visibility

This routing removes the path by which external fork code could reach a persistent Bad Otter runner. It does **not** itself change repository visibility or submit the plugin to WordPress.org.

Repository visibility remains an explicit release decision after the remaining Phase 4 security, compatibility, accessibility, upgrade and submission gates are green. Once public, an external fork smoke test should confirm GitHub-hosted validation operates as expected before accepting community contributions.
