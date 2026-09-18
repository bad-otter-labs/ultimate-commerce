# Public CI trust boundary

Ultimate Commerce separates **untrusted validation** from **trusted same-repository development and publishing** so the Free repository can become publicly reviewable without giving external pull-request code access to a persistent Bad Otter runner.

## Pull-request runner routing

Every workflow triggered by `pull_request` uses the same trust-aware runner expression:

```yaml
runs-on: ${{ github.event_name == 'pull_request' && github.event.pull_request.head.repo.full_name != github.repository && 'ubuntu-latest' || 'badotter' }}
```

The result is intentionally asymmetric:

- a pull request whose head branch is in another repository (the normal external/fork contribution path) runs on GitHub-hosted `ubuntu-latest`;
- a same-repository pull request may use the controlled Bad Otter runner because creating its head branch requires repository write access;
- a push to `main` may use the controlled Bad Otter validation runner;
- a cross-repository pull request must never fall back to the Bad Otter runner if hosted capacity is unavailable.

While the repository remains private, GitHub-hosted capacity may not be provisioned. In that case an external/cross-repository job can fail before execution; failing closed is preferable to executing untrusted code on the trusted runner.

## Pull-request permissions

All pull-request workflows must:

- declare read-only repository contents permission;
- avoid write-scoped GitHub permissions;
- receive no Bad Otter publishing credentials or release identity;
- never use `pull_request_target` to execute contributor-controlled code.

The repository regression at `tests/ci-trust-boundary.py` enforces these rules across every workflow file, including newly added workflows.

## Trusted publishing

Publishing remains a separate trust domain.

`.github/workflows/development-release.yml` and `.github/workflows/release.yml` use the controlled Bad Otter self-hosted runner and may hold the permissions needed for their explicit release duties. Neither workflow may accept `pull_request` or `pull_request_target`.

A validation workflow must not be converted into a publisher merely to reuse credentials or release infrastructure.

## Repository visibility

This routing removes the path by which external fork code could reach a persistent Bad Otter runner. It does **not** itself change repository visibility or submit the plugin to WordPress.org.

Repository visibility remains an explicit release decision after the remaining Phase 4 security, compatibility, accessibility, upgrade and submission gates are green. Once public, an external fork smoke test should confirm GitHub-hosted validation operates as expected before accepting community contributions.
