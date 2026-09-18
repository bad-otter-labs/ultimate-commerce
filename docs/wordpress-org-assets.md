# WordPress.org directory assets

Ultimate Commerce keeps WordPress.org directory screenshots outside the installable plugin source.

## Repository staging

The Git repository stages directory images in:

`wordpress-org-assets/`

For the first directory submission the current screenshot set is:

1. `screenshot-1.png` — Ultimate Commerce Overview with module health, diagnostics/documentation access and restrained optional Pro discovery.
2. `screenshot-2.png` — module-management table with dependencies, runtime state and merchant enable/disable controls.
3. `screenshot-3.png` — live storefront variable-product controls, Wishlist and Recently Viewed enhancements.
4. `screenshot-4.png` — accessible cart drawer backed by WooCommerce Store API.

The matching captions are in the canonical plugin `readme.txt`.

## Source of the screenshots

The files are captured by `scripts/capture-wordpress-org-screenshots.sh` from a disposable real WordPress 7.1.1 / WooCommerce 11.1.0 site using the same pinned Playwright toolchain as the accessibility baseline.

The capture is deterministic in scenario and data, but the PNG files are presentation assets rather than release-package inputs. They are deliberately generated from the actual plugin UI instead of composited marketing mockups.

## WordPress.org SVN mapping

After the directory slug is accepted, copy the repository-staged files to the **top-level** WordPress.org SVN `/assets/` directory. They do not belong in `trunk/assets/`, in version tags, or in the plugin ZIP.

The WordPress.org screenshot limit is 10 MB per image. `scripts/check-wordpress-org-assets.py` validates file integrity/size/dimensions and the one-to-one readme caption mapping.

Banner and icon artwork are not fabricated by this build. WordPress.org can provide its generated fallback icon until approved brand artwork is available.
