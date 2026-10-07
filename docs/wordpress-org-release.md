# WordPress.org release automation

Ultimate Commerce Free is published to the WordPress.org plugin directory from the canonical package in `packages/ultimate-commerce-for-woocommerce`.

The release workflow is `.github/workflows/wordpress-org-release.yml`. It is intentionally manual and guarded: publishing a release is an external production action, so a normal push to `main` never commits to WordPress.org SVN by itself.

## One-time GitHub setup

Create a GitHub environment named `wordpress-org` and configure these environment secrets:

- `WORDPRESS_ORG_SVN_USERNAME` — the WordPress.org SVN username. For the Bad Otter listing this is `badotterlabs`.
- `WORDPRESS_ORG_SVN_PASSWORD` — the dedicated SVN password from the WordPress.org account security screen.

Do not store the SVN password in the repository, workflow YAML, issue, pull request, or release notes.

Where repository settings permit it, protect the `wordpress-org` environment with a required reviewer so every directory publication has an explicit approval gate.

## Preparing a release

Before publication:

1. Update the plugin `Version` header in `ultimate-commerce-for-woocommerce.php`.
2. Update `ULTIMATE_COMMERCE_VERSION` to the same value.
3. Update `Stable tag` in `readme.txt` to the same value.
4. Add the release notes to the readme changelog.
5. Merge the release changes to `main`.
6. Confirm **Validate WordPress.org Package** is green for that `main` commit.

The release workflow accepts only an exact `x.y.z` version and refuses a mismatch between the requested version, plugin header, runtime version constant, and readme stable tag.

## Publishing

From **Actions > Publish WordPress.org Release > Run workflow**:

1. Select `main`.
2. Enter the exact release version.
3. Enable the explicit publication confirmation.
4. Run the workflow.
5. Approve the `wordpress-org` environment deployment if environment protection is enabled.

The workflow then:

- validates the release version and WordPress.org metadata;
- builds the canonical package twice and requires byte-for-byte deterministic output;
- runs the existing WordPress.org package boundary checker;
- checks out the official plugin SVN repository with non-cached credentials;
- refuses to mutate or republish an existing version tag;
- synchronizes the validated package into SVN `trunk/`;
- creates the immutable `tags/<version>/` release from that staged trunk;
- commits trunk and the release tag in one SVN publication;
- reads the published tag back from WordPress.org and verifies its version metadata.

## Directory assets

Normal plugin releases deliberately do **not** modify SVN `assets/`. Screenshots, banners, and icons are independent directory presentation assets and should not be overwritten as a side effect of publishing plugin code.

The live WordPress.org assets therefore remain unchanged when the release workflow publishes a new plugin version.

## Recovery rules

- If `tags/<version>/` already exists, do not replace it. Prepare a new plugin version instead.
- Do not bypass a failed package validation by uploading a hand-built ZIP.
- Do not put WordPress.org credentials into command output or repository files.
- A failed run before the SVN commit step has not published a release.
- After a successful SVN commit, verify the public WordPress.org listing and generated download ZIP before considering the release complete.
