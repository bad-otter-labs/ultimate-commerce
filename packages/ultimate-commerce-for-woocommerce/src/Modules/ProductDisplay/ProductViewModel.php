<?php

namespace BadOtter\UltimateCommerce\Modules\ProductDisplay;

use BadOtter\UltimateCommerce\Storefront\ProductCardViewModel;

defined('ABSPATH') || exit;

final class ProductViewModel
{
    /**
     * Historical compatibility facade.
     *
     * New consumers should use ProductCardViewModel::fromProduct() or Storefront\Catalog.
     * The `ultimate_commerce_product_view_model` filter remains active inside the new public view-model path.
     *
     * @param int|\WC_Product $product
     */
    public static function fromProduct($product): array
    {
        return ProductCardViewModel::fromProduct($product);
    }
}
