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
            'price_yearly_kes' => 25000,
            'interval' => 'monthly',
            'player_limit' => -1,
            'team_limit' => -1,
            'description' => 'KSh 2,500 per month'
        ],
        'benchero-pro' => [
            'name' => 'Benchero Pro Yearly',
            'price_monthly_kes' => 2500,
            'price_yearly_kes' => 25000,
            'interval' => 'yearly',
            'player_limit' => -1,
            'team_limit' => -1,
            'description' => 'KSh 25,000 per year'
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

    /**
     * Check if a currency is supported by Benchero.
     */
    public static function isSupportedCurrency(string $currency): bool
    {
        return in_array(strtoupper(trim($currency)), ['KES', 'USD', 'EUR', 'GBP'], true);
    }

    /**
     * Resolve international checkout pricing for PayPal transactions.
     *
     * BUSINESS RATIONALE:
     * Benchero's canonical source-of-truth pricing is defined in Kenyan Shillings (KES):
     * - Benchero Pro Monthly: KSh 2,500 / month (Plan 5)
     * - Benchero Pro Yearly:  KSh 25,000 / year (Plan 4)
     *
     * Because PayPal does not accept Kenyan Shillings (KES) for merchant checkout or settlement,
     * international customers are charged an explicit international tier price in a supported currency
     * (primarily USD: $20.00 / month, $200.00 / year).
     *
     * Annual savings: 16.67% for both Standard and Pro tiers.
     *
     * These international prices are explicit business subscription price points (configurable via environment
     * variables), NOT a real-time foreign exchange rate conversion. Benchero does not perform or claim
     * currency exchange conversions; exchange_rate is tracked as NULL in records to reflect this.
     */
    public static function getInternationalPrice(int $planId, string $currency = 'USD'): array
    {
        $currency = strtoupper(trim($currency));

        // Canonical prices in KES (configurable via environment variables)
        $canonicalMap = [
            1 => (float)env('PLAN_FREE_TRIAL_PRICE_KES', 0.00),
            2 => (float)env('PLAN_STANDARD_MONTHLY_PRICE_KES', 1000.00),
            3 => (float)env('PLAN_STANDARD_YEARLY_PRICE_KES', 10000.00),
            4 => (float)env('PLAN_PRO_YEARLY_PRICE_KES', 25000.00),
            5 => (float)env('PLAN_PRO_MONTHLY_PRICE_KES', 2500.00),
        ];
        $canonicalAmount = $canonicalMap[$planId] ?? 0.00;

        // Configurable explicit USD international tier pricing
        $proMonthlyUsd = (float)env('PAYPAL_PLAN_PRO_MONTHLY_USD', 20.00);
        $proYearlyUsd  = (float)env('PAYPAL_PLAN_PRO_YEARLY_USD', 200.00);
        $stdMonthlyUsd = (float)env('PAYPAL_PLAN_STANDARD_MONTHLY_USD', 8.00);
        $stdYearlyUsd  = (float)env('PAYPAL_PLAN_STANDARD_YEARLY_USD', 80.00);

        $usdMap = [
            1 => 0.00,
            2 => $stdMonthlyUsd,
            3 => $stdYearlyUsd,
            4 => $proYearlyUsd,
            5 => $proMonthlyUsd,
        ];

        // Supported international currencies
        if ($currency === 'EUR') {
            $eurStdMonthly = (float)env('PAYPAL_PLAN_STANDARD_MONTHLY_EUR', 7.50);
            $eurStdYearly  = (float)env('PAYPAL_PLAN_STANDARD_YEARLY_EUR', 75.00);
            $eurProMonthly = (float)env('PAYPAL_PLAN_PRO_MONTHLY_EUR', 19.00);
            $eurProYearly  = (float)env('PAYPAL_PLAN_PRO_YEARLY_EUR', 150.00);
            $eurMap = [1 => 0.00, 2 => $eurStdMonthly, 3 => $eurStdYearly, 4 => $eurProYearly, 5 => $eurProMonthly];
            $charged = $eurMap[$planId] ?? 0.00;
        } elseif ($currency === 'GBP') {
            $gbpStdMonthly = (float)env('PAYPAL_PLAN_STANDARD_MONTHLY_GBP', 6.50);
            $gbpStdYearly  = (float)env('PAYPAL_PLAN_STANDARD_YEARLY_GBP', 65.00);
            $gbpProMonthly = (float)env('PAYPAL_PLAN_PRO_MONTHLY_GBP', 16.00);
            $gbpProYearly  = (float)env('PAYPAL_PLAN_PRO_YEARLY_GBP', 130.00);
            $gbpMap = [1 => 0.00, 2 => $gbpStdMonthly, 3 => $gbpStdYearly, 4 => $gbpProYearly, 5 => $gbpProMonthly];
            $charged = $gbpMap[$planId] ?? 0.00;
        } else {
            // Default to USD for all other currencies
            $currency = 'USD';
            $charged = $usdMap[$planId] ?? 0.00;
        }

        $symbol = match ($currency) {
            'EUR' => '€',
            'GBP' => '£',
            default => '$',
        };

        return [
            'plan_id' => $planId,
            'canonical_amount' => $canonicalAmount,
            'canonical_currency' => 'KES',
            'charged_amount' => $charged,
            'charged_currency' => $currency,
            'exchange_rate' => null, // Explicit pricing tier, not a FOREX conversion
            'pricing_model' => 'international_tier',
            'display_charged' => $symbol . number_format($charged, 2) . ' ' . $currency,
            'display_canonical' => 'KSh ' . number_format($canonicalAmount),
            'note' => $canonicalAmount > 0 
                ? "International checkout: {$symbol}" . number_format($charged, 2) . " {$currency} (canonical base: KSh " . number_format($canonicalAmount) . ")" 
                : 'Free'
        ];
    }

    /**
     * Calculate annual savings for a plan given monthly and annual prices.
     *
     * Returns derived savings values — never hardcode savings separately.
     *
     * @param float $monthlyPrice The monthly subscription price.
     * @param float $annualPrice  The yearly subscription price.
     * @return array{annual_value: float, annual_saving: float, saving_percentage: float, display_saving_kes: string, display_saving_usd: string}
     */
    public static function calculateSavings(float $monthlyPrice, float $annualPrice): array
    {
        $annualValue = $monthlyPrice * 12;
        $annualSaving = $annualValue - $annualPrice;
        $savingPercentage = ($annualValue > 0) ? round(($annualSaving / $annualValue) * 100, 2) : 0.0;

        return [
            'annual_value' => $annualValue,
            'annual_saving' => $annualSaving,
            'saving_percentage' => $savingPercentage,
        ];
    }
}
