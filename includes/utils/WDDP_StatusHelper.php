<?php

class WDDP_StatusHelper
{

    const PENDING_REVIEW = 'pending_review';
    const APPROVED = 'approved';
    const REJECTED = 'rejected';

    private static array $allowedTransitions = [
        WDDP_StatusHelper::PENDING_REVIEW   => ['approved','rejected'],
        WDDP_StatusHelper::APPROVED         => ['rejected'],
        WDDP_StatusHelper::REJECTED         => [/* kun delete i UI */],
    ];

    public static function all(): array
    {
        return [
            self::PENDING_REVIEW,
            self::APPROVED,
            self::REJECTED,
        ];
    }

    public static function assertTransitionAllowed(string $from, string $to): void {
        $key = $to === WDDP_StatusHelper::APPROVED ? 'approved'
            : ($to === WDDP_StatusHelper::REJECTED ? 'rejected' : $to);

        $allowed = self::$allowedTransitions[$from] ?? [];
        if ( ! in_array($key, $allowed, true) ) {
            throw new \RuntimeException('Transition ikke tilladt.');
        }
    }

    public static function isValid($status): bool
    {
        return in_array($status, self::all(), true);
    }

    public static function label(string $status): string
    {
        switch ($status) {
            case self::PENDING_REVIEW:
                return __('Afventer behandling', 'wd-dog-pension');
            case self::APPROVED:
                return __('Godkendt', 'wd-dog-pension');
            case self::REJECTED:
                return __('Afvist', 'wd-dog-pension');
            default:
                return __('Ukendt', 'wd-dog-pension');
        }
    }

    public static function css_class(string $status): string
    {
        switch ($status) {
            case self::PENDING_REVIEW:
                return 'wddp-status-pending-review';
            case self::APPROVED:
                return 'wddp-status-approved';
            case self::REJECTED:
                return 'wddp-status-rejected';
            default:
                return 'wddp-status-unknown';
        }
    }

    public static function renderBadge(string $status): string
    {
        $label = esc_html(self::label($status));
        $class = esc_attr(self::css_class($status));
        return '<span class="wddp-status ' . $class . '">' . $label . '</span>';
    }
}