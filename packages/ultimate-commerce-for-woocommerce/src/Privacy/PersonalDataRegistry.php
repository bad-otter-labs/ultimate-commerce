<?php

namespace BadOtter\UltimateCommerce\Privacy;

defined('ABSPATH') || exit;

final class PersonalDataRegistry
{
    public const CONTRACT_VERSION = '1.0.0';

    private static ?self $instance = null;
    private static bool $hooksRegistered = false;
    private static bool $collected = false;
    private static bool $collecting = false;

    /** @var array<string, array{name:string,exporter:callable,eraser:callable,retention:string}> */
    private array $handlers = array();

    public static function hooks(): void
    {
        if (self::$hooksRegistered) {
            return;
        }

        self::$hooksRegistered = true;
        add_filter('wp_privacy_personal_data_exporters', array(__CLASS__, 'exporters'));
        add_filter('wp_privacy_personal_data_erasers', array(__CLASS__, 'erasers'));
    }

    public static function instance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @param callable(string, int):array $exporter
     * @param callable(string, int):array $eraser
     * @return true|\WP_Error
     */
    public function register(
        string $key,
        string $name,
        callable $exporter,
        callable $eraser,
        string $retention = DataRetention::MERCHANT_POLICY
    ) {
        $key = strtolower(trim($key));
        $name = trim($name);

        if (!preg_match('/^[a-z][a-z0-9_-]{0,63}$/', $key)) {
            return self::error('uc_privacy_handler_key', __('Personal-data handler key is invalid.', 'ultimate-commerce-for-woocommerce'));
        }
        if ($name === '' || strlen($name) > 120) {
            return self::error('uc_privacy_handler_name', __('Personal-data handler name is invalid.', 'ultimate-commerce-for-woocommerce'));
        }
        if (!DataRetention::valid($retention)) {
            return self::error('uc_privacy_retention', __('Personal-data retention class is invalid.', 'ultimate-commerce-for-woocommerce'));
        }
        if (isset($this->handlers[$key])) {
            return self::error('uc_privacy_handler_duplicate', __('A personal-data handler is already registered with this key.', 'ultimate-commerce-for-woocommerce'));
        }

        $this->handlers[$key] = array(
            'name' => $name,
            'exporter' => $exporter,
            'eraser' => $eraser,
            'retention' => $retention,
        );

        return true;
    }

    /** @param array<string, array<string, mixed>> $exporters
     *  @return array<string, array<string, mixed>>
     */
    public static function exporters(array $exporters): array
    {
        self::collect();
        foreach (self::instance()->handlers as $key => $handler) {
            $exporters['ultimate-commerce-' . $key] = array(
                'exporter_friendly_name' => $handler['name'],
                'callback' => $handler['exporter'],
            );
        }
        return $exporters;
    }

    /** @param array<string, array<string, mixed>> $erasers
     *  @return array<string, array<string, mixed>>
     */
    public static function erasers(array $erasers): array
    {
        self::collect();
        foreach (self::instance()->handlers as $key => $handler) {
            $erasers['ultimate-commerce-' . $key] = array(
                'eraser_friendly_name' => $handler['name'],
                'callback' => $handler['eraser'],
            );
        }
        return $erasers;
    }

    /** @return array<string, array{classification:string,retention:string,name:string}> */
    public function metadata(): array
    {
        $metadata = array();
        foreach ($this->handlers as $key => $handler) {
            $metadata[$key] = array(
                'classification' => DataClassification::PERSONAL,
                'retention' => $handler['retention'],
                'name' => $handler['name'],
            );
        }
        return $metadata;
    }

    private static function collect(): void
    {
        if (self::$collected || self::$collecting) {
            return;
        }

        self::$collecting = true;
        do_action('ultimate_commerce_register_personal_data_handlers', self::instance());
        self::$collecting = false;
        self::$collected = true;
    }

    private static function error(string $code, string $message): \WP_Error
    {
        return new \WP_Error($code, $message);
    }
}
