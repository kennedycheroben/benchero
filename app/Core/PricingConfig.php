<?php

namespace Benchero\Core;

class PricingConfig
{
    public const PLANS = [
        'free-trial' => [
            'name' => 'Free Trial',
            'price_monthly_kes' => 0,
            'price_yearly_kes' => 0,
            'interval' => 'trial',
            'player_limit' => 25,
            'team_limit' => 2,
            'description' => '14-day free trial for new sports clubs'
        ],
        'standard-monthly' => [
            'name' => 'Standard Monthly',
            'price_monthly_kes' => 1000,
            'price_yearly_kes' => 10000,
            'interval' => 'monthly',
            'player_limit' => 100,
            'team_limit' => 10,
            'description' => 'KSh 1,000 per month'
        ],
        'standard-yearly' => [
            'name' => 'Standard Yearly',
            'price_monthly_kes' => 1000,
            'price_yearly_kes' => 10000,
            'interval' => 'yearly',
            'player_limit' => 500,
            'team_limit' => 25,
            'description' => 'KSh 10,000 per year'
        ],
        'pro-monthly' => [
            'name' => 'Benchero Pro Monthly',
            'price_monthly_kes' => 2500,
            'price_yearly_kes' => 20000,
            'interval' => 'monthly',
            'player_limit' => -1,
            'team_limit' => -1,
            'description' => 'KSh 2,500 per month'
        ],
        'benchero-pro' => [
            'name' => 'Benchero Pro Yearly',
            'price_monthly_kes' => 2500,
            'price_yearly_kes' => 20000,
            'interval' => 'yearly',
            'player_limit' => -1,
            'team_limit' => -1,
            'description' => 'KSh 20,000 per year'
        ]
    ];

    public static function formatPlayerLimit(int|string $limit): string
    {
        $val = (int)$limit;
        if ($val === -1 || $val < 0) {
            return 'Unlimited Players';
        }
        return $val . ' Players';
    }

    public static function formatTeamLimit(int|string $limit): string
    {
        $val = (int)$limit;
        if ($val === -1 || $val < 0) {
            return 'Unlimited Teams';
        }
        return $val . ' Teams';
    }

    public static function formatPrice(float|int|string $amount): string
    {
        $amt = (float)$amount;
        if ($amt <= 0) {
            return 'Free';
        }
        return 'KSh ' . number_format($amt);
    }
}
