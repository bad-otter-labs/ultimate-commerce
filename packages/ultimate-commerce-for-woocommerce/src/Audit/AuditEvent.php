<?php

namespace BadOtter\UltimateCommerce\Audit;

use BadOtter\UltimateCommerce\Privacy\DataClassification;
use BadOtter\UltimateCommerce\Privacy\DataRetention;

defined('ABSPATH') || exit;

final class AuditEvent
{
    public const CONTRACT_VERSION = '1.0.0';
    public const ACTOR_USER = 'user';
    public const ACTOR_SYSTEM = 'system';
    public const ACTOR_GUEST = 'guest';
    public const ACTOR_PROVIDER = 'provider';

    private const MAX_CONTEXT_ITEMS = 32;
    private const MAX_CONTEXT_STRING = 512;

    private function __construct(
        private string $id,
        private string $action,
        private string $actorType,
        private string $actorId,
        private string $targetType,
        private string $targetId,
        private string $reasonCode,
        private array $context,
        private int $occurredAt
    ) {
    }

    /** @param array<string, scalar|null> $context
     *  @return self|\WP_Error
     */
    public static function create(
        string $action,
        string $actorType,
        string $actorId,
        string $targetType,
        string $targetId,
        string $reasonCode = '',
        array $context = array()
    ) {
        $action = strtolower(trim($action));
        $actorType = strtolower(trim($actorType));
        $actorId = trim($actorId);
        $targetType = strtolower(trim($targetType));
        $targetId = trim($targetId);
        $reasonCode = strtolower(trim($reasonCode));

        if (!self::validKey($action, 127)) {
            return self::error('uc_audit_action_invalid', __('Audit action is invalid.', 'ultimate-commerce-for-woocommerce'));
        }
        if (!in_array($actorType, self::actorTypes(), true)) {
            return self::error('uc_audit_actor_invalid', __('Audit actor type is invalid.', 'ultimate-commerce-for-woocommerce'));
        }
        if (!self::validIdentifier($actorId, $actorType === self::ACTOR_SYSTEM)) {
            return self::error('uc_audit_actor_invalid', __('Audit actor identifier is invalid.', 'ultimate-commerce-for-woocommerce'));
        }
        if ($actorType === self::ACTOR_USER && !preg_match('/^[1-9][0-9]*$/', $actorId)) {
            return self::error('uc_audit_actor_invalid', __('User audit actor must use a numeric WordPress user ID.', 'ultimate-commerce-for-woocommerce'));
        }
        if (!self::validKey($targetType, 63) || !self::validIdentifier($targetId, false)) {
            return self::error('uc_audit_target_invalid', __('Audit target is invalid.', 'ultimate-commerce-for-woocommerce'));
        }
        if ($reasonCode !== '' && !self::validKey($reasonCode, 63)) {
            return self::error('uc_audit_reason_invalid', __('Audit reason code is invalid.', 'ultimate-commerce-for-woocommerce'));
        }

        $contextProblem = self::validateContext($context);
        if ($contextProblem instanceof \WP_Error) {
            return $contextProblem;
        }

        $id = wp_generate_uuid4();
        if (!is_string($id) || $id === '') {
            return self::error('uc_audit_id_failed', __('Audit event identifier could not be generated.', 'ultimate-commerce-for-woocommerce'));
        }

        return new self(
            $id,
            $action,
            $actorType,
            $actorId,
            $targetType,
            $targetId,
            $reasonCode,
            $context,
            time()
        );
    }

    /** @param array<string, scalar|null> $context
     *  @return self|\WP_Error
     */
    public static function forCurrentUser(
        string $action,
        string $targetType,
        string $targetId,
        string $reasonCode = '',
        array $context = array()
    ) {
        $userId = (int) get_current_user_id();
        if ($userId <= 0) {
            return self::error('uc_audit_actor_invalid', __('A logged-in user is required for this audit event.', 'ultimate-commerce-for-woocommerce'));
        }

        return self::create(
            $action,
            self::ACTOR_USER,
            (string) $userId,
            $targetType,
            $targetId,
            $reasonCode,
            $context
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array(
            'id' => $this->id,
            'action' => $this->action,
            'actor_type' => $this->actorType,
            'actor_id' => $this->actorId,
            'target_type' => $this->targetType,
            'target_id' => $this->targetId,
            'reason_code' => $this->reasonCode,
            'context' => $this->context,
            'occurred_at' => $this->occurredAt,
            'classification' => DataClassification::MERCHANT_OPERATIONAL,
            'retention' => DataRetention::OPERATIONAL_HISTORY,
        );
    }

    /** @return array<int, string> */
    private static function actorTypes(): array
    {
        return array(self::ACTOR_USER, self::ACTOR_SYSTEM, self::ACTOR_GUEST, self::ACTOR_PROVIDER);
    }

    private static function validKey(string $value, int $maxLength): bool
    {
        return $value !== ''
            && strlen($value) <= $maxLength
            && (bool) preg_match('/^[a-z][a-z0-9_.:-]*$/', $value);
    }

    private static function validIdentifier(string $value, bool $allowEmpty): bool
    {
        if ($value === '') {
            return $allowEmpty;
        }
        return strlen($value) <= 191 && !preg_match('/[\x00-\x1F\x7F]/', $value);
    }

    /** @param array<string, scalar|null> $context
     *  @return true|\WP_Error
     */
    private static function validateContext(array $context)
    {
        if (count($context) > self::MAX_CONTEXT_ITEMS) {
            return self::error('uc_audit_context_too_large', __('Audit context contains too many fields.', 'ultimate-commerce-for-woocommerce'));
        }

        foreach ($context as $key => $value) {
            if (!is_string($key) || !self::validKey(strtolower($key), 63)) {
                return self::error('uc_audit_context_invalid', __('Audit context field name is invalid.', 'ultimate-commerce-for-woocommerce'));
            }
            if (self::sensitiveKey($key)) {
                return self::error('uc_audit_context_sensitive', __('Credentials and authentication material must not be placed in audit context.', 'ultimate-commerce-for-woocommerce'));
            }
            if ($value !== null && !is_scalar($value)) {
                return self::error('uc_audit_context_invalid', __('Audit context values must be scalar.', 'ultimate-commerce-for-woocommerce'));
            }
            if (is_string($value) && strlen($value) > self::MAX_CONTEXT_STRING) {
                return self::error('uc_audit_context_too_large', __('Audit context string value is too large.', 'ultimate-commerce-for-woocommerce'));
            }
        }

        return true;
    }

    private static function sensitiveKey(string $key): bool
    {
        $key = strtolower($key);
        return (bool) preg_match('/(^|[_.:-])(password|passwd|secret|token|authorization|cookie|nonce|credential|api[_-]?key|licen[cs]e[_-]?key)($|[_.:-])/', $key);
    }

    private static function error(string $code, string $message): \WP_Error
    {
        return new \WP_Error($code, $message);
    }
}
