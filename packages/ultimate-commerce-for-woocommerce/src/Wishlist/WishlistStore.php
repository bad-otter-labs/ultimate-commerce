<?php

namespace BadOtter\UltimateCommerce\Wishlist;

use BadOtter\UltimateCommerce\Privacy\DataRetention;
use BadOtter\UltimateCommerce\Privacy\PersonalDataRegistry;

defined('ABSPATH') || exit;

final class WishlistStore
{
    public const BASE_META_KEY = 'uc_wishlist_product_ids';
    public const MAX_ITEMS = 100;

    private static bool $hooksRegistered = false;

    public static function hooks(): void
    {
        if (self::$hooksRegistered) {
            return;
        }
        self::$hooksRegistered = true;
        add_action('uc_register_personal_data_handlers', array(__CLASS__, 'registerPrivacy'));
    }

    public static function metaKey(): string
    {
        if (function_exists('is_multisite') && is_multisite() && function_exists('get_current_blog_id')) {
            return self::BASE_META_KEY . '_' . (int) get_current_blog_id();
        }

        return self::BASE_META_KEY;
    }

    /** @return list<int> */
    public static function ids(int $userId): array
    {
        if ($userId <= 0) {
            return array();
        }

        return self::storedIds($userId);
    }

    /** @return list<int>|\WP_Error */
    public static function add(int $userId, int $productId)
    {
        if ($userId <= 0) {
            return self::error('uc_wishlist_user_invalid', __('A signed-in customer is required for this wishlist.', 'ultimate-commerce-for-woocommerce'), 401);
        }
        if (!self::productAllowed($productId)) {
            return self::error('uc_wishlist_product_invalid', __('This product cannot be added to the wishlist.', 'ultimate-commerce-for-woocommerce'), 404);
        }

        $ids = self::ids($userId);
        if (in_array($productId, $ids, true)) {
            return $ids;
        }
        if (count($ids) >= self::MAX_ITEMS) {
            return self::error('uc_wishlist_full', __('The wishlist has reached its item limit.', 'ultimate-commerce-for-woocommerce'), 409);
        }

        $ids[] = $productId;
        self::save($userId, $ids);
        do_action('uc_wishlist_item_added', $userId, $productId, $ids);

        return $ids;
    }

    /** @return list<int> */
    public static function remove(int $userId, int $productId): array
    {
        if ($userId <= 0 || $productId <= 0) {
            return array();
        }

        $ids = array_values(array_filter(
            self::ids($userId),
            static fn(int $candidate): bool => $candidate !== $productId
        ));
        self::save($userId, $ids);
        do_action('uc_wishlist_item_removed', $userId, $productId, $ids);

        return $ids;
    }

    /**
     * Merge browser-local IDs into the signed-in wishlist. Invalid/stale Woo
     * product IDs are ignored rather than persisted as product snapshots.
     *
     * @param array<mixed> $productIds
     * @return list<int>
     */
    public static function merge(int $userId, array $productIds): array
    {
        if ($userId <= 0) {
            return array();
        }

        $ids = self::ids($userId);
        foreach (self::normalizeIds($productIds, false) as $productId) {
            if (count($ids) >= self::MAX_ITEMS) {
                break;
            }
            if (self::productAllowed($productId) && !in_array($productId, $ids, true)) {
                $ids[] = $productId;
            }
        }

        self::save($userId, $ids);
        do_action('uc_wishlist_merged', $userId, $ids);

        return $ids;
    }

    public static function registerPrivacy(PersonalDataRegistry $registry): void
    {
        $registry->register(
            'wishlist',
            __('Ultimate Commerce Wishlist', 'ultimate-commerce-for-woocommerce'),
            array(__CLASS__, 'privacyExporter'),
            array(__CLASS__, 'privacyEraser'),
            DataRetention::ACCOUNT_LIFETIME
        );
    }

    /** @return array<string, mixed> */
    public static function privacyExporter(string $emailAddress, int $page): array
    {
        if ($page > 1) {
            return array('data' => array(), 'done' => true);
        }

        $user = get_user_by('email', sanitize_email($emailAddress));
        if (!$user || empty($user->ID)) {
            return array('data' => array(), 'done' => true);
        }

        $ids = self::storedIds((int) $user->ID);
        if ($ids === array()) {
            return array('data' => array(), 'done' => true);
        }

        return array(
            'data' => array(
                array(
                    'group_id' => 'ultimate-commerce-wishlist',
                    'group_label' => __('Ultimate Commerce Wishlist', 'ultimate-commerce-for-woocommerce'),
                    'item_id' => 'wishlist-' . (int) $user->ID,
                    'data' => array(
                        array(
                            'name' => __('Product IDs', 'ultimate-commerce-for-woocommerce'),
                            'value' => implode(', ', array_map('strval', $ids)),
                        ),
                    ),
                ),
            ),
            'done' => true,
        );
    }

    /** @return array<string, mixed> */
    public static function privacyEraser(string $emailAddress, int $page): array
    {
        if ($page > 1) {
            return array('items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true);
        }

        $user = get_user_by('email', sanitize_email($emailAddress));
        if (!$user || empty($user->ID)) {
            return array('items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true);
        }

        $hadItems = self::storedIds((int) $user->ID) !== array();
        delete_user_meta((int) $user->ID, self::metaKey());

        return array(
            'items_removed' => $hadItems,
            'items_retained' => false,
            'messages' => array(),
            'done' => true,
        );
    }

    /** @return list<int> */
    private static function storedIds(int $userId): array
    {
        if ($userId <= 0) {
            return array();
        }
        $stored = get_user_meta($userId, self::metaKey(), true);
        return self::normalizeIds(is_array($stored) ? $stored : array(), false);
    }

    /** @param array<mixed> $values
     *  @return list<int>
     */
    private static function normalizeIds(array $values, bool $validateProducts): array
    {
        $ids = array();
        foreach ($values as $value) {
            $id = absint($value);
            if ($id <= 0 || in_array($id, $ids, true)) {
                continue;
            }
            if ($validateProducts && !self::productAllowed($id)) {
                continue;
            }
            $ids[] = $id;
            if (count($ids) >= self::MAX_ITEMS) {
                break;
            }
        }

        return $ids;
    }

    private static function productAllowed(int $productId): bool
    {
        if ($productId <= 0 || !function_exists('wc_get_product')) {
            return false;
        }

        $product = wc_get_product($productId);
        $allowed = is_object($product);
        if ($allowed && method_exists($product, 'get_status')) {
            $allowed = $product->get_status() === 'publish';
        }

        return (bool) apply_filters('uc_wishlist_product_allowed', $allowed, $product, $productId);
    }

    /** @param list<int> $ids */
    private static function save(int $userId, array $ids): void
    {
        $ids = self::normalizeIds($ids, false);
        if ($ids === array()) {
            delete_user_meta($userId, self::metaKey());
        } else {
            update_user_meta($userId, self::metaKey(), $ids);
        }
        do_action('uc_wishlist_updated', $userId, $ids);
    }

    private static function error(string $code, string $message, int $status): \WP_Error
    {
        return new \WP_Error($code, $message, array('status' => $status));
    }
}
