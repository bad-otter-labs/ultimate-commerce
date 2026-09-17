<?php

namespace BadOtter\UltimateCommerce\Storefront;

defined('ABSPATH') || exit;

final class MediaViewModel
{
    /** @return array<string, mixed>|null */
    public static function fromAttachment(int $attachmentId, string $size = 'woocommerce_thumbnail'): ?array
    {
        if ($attachmentId <= 0) {
            return null;
        }

        $image = wp_get_attachment_image_src($attachmentId, $size);
        if (!is_array($image) || empty($image[0])) {
            return null;
        }

        return array(
            'id' => $attachmentId,
            'src' => (string) $image[0],
            'width' => (int) ($image[1] ?? 0),
            'height' => (int) ($image[2] ?? 0),
            'srcset' => (string) (wp_get_attachment_image_srcset($attachmentId, $size) ?: ''),
            'sizes' => (string) (wp_get_attachment_image_sizes($attachmentId, $size) ?: ''),
            'alt' => (string) get_post_meta($attachmentId, '_wp_attachment_image_alt', true),
            'title' => (string) get_the_title($attachmentId),
        );
    }
}
