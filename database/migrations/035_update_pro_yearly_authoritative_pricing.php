<?php

/**
 * Migration 035: Update Pro Yearly Authoritative Pricing
 * ======================================================
 * 
 * Updates Plan 4 (Benchero Pro Yearly) from KSh 20,000 to KSh 25,000.
 * 
 * BUSINESS RATIONALE:
 * - Pro Monthly = KSh 2,500/mo → Annual value = KSh 30,000
 * - New Pro Yearly = KSh 25,000/yr → Savings = KSh 5,000 (16.67%)
 * - This aligns the annual savings percentage with Standard Yearly (also 16.67%)
 * 
 * SAFETY:
 * - Does NOT modify any historical payment records
 * - Only updates the plan definition for future purchases
 * - Existing subscriptions at KSh 20,000 remain intact
 */

use Benchero\Core\Database\Database;

return new class {
    public function up(): void
    {
        $pdo = Database::getConnection();

        // Verify Plan 4 exists and is currently 20000
        $stmt = $pdo->prepare("SELECT id, name, slug, price_kes FROM plans WHERE id = 4 AND deleted_at IS NULL");
        $stmt->execute();
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$plan) {
            echo "  [SKIP] Plan 4 not found or is deleted.\n";
            return;
        }

        $currentPrice = (float)$plan['price_kes'];
        if ($currentPrice === 25000.0) {
            echo "  [SKIP] Plan 4 already at KSh 25,000. No change needed.\n";
            return;
        }

        if ($currentPrice !== 20000.0) {
            echo "  [WARNING] Plan 4 price is KSh " . number_format($currentPrice) . " (expected 20,000). Proceeding with update to 25,000.\n";
        }

        // Update Plan 4 price to KSh 25,000
        $update = $pdo->prepare("UPDATE plans SET price_kes = 25000.00, updated_at = NOW() WHERE id = 4");
        $update->execute();

        // Also update the features JSON description if it references the old price
        $featStmt = $pdo->prepare("SELECT features FROM plans WHERE id = 4");
        $featStmt->execute();
        $featRow = $featStmt->fetch(PDO::FETCH_ASSOC);
        if ($featRow && $featRow['features']) {
            $features = json_decode($featRow['features'], true);
            if ($features && isset($features['description'])) {
                $features['description'] = str_replace(
                    ['KSh 20,000', '20,000/yr', '20000'],
                    ['KSh 25,000', '25,000/yr', '25000'],
                    $features['description']
                );
                $updateFeat = $pdo->prepare("UPDATE plans SET features = ? WHERE id = 4");
                $updateFeat->execute([json_encode($features)]);
            }
        }

        echo "  [OK] Plan 4 (Benchero Pro Yearly) updated: KSh 20,000 → KSh 25,000\n";
    }

    public function down(): void
    {
        $pdo = Database::getConnection();
        $pdo->prepare("UPDATE plans SET price_kes = 20000.00, updated_at = NOW() WHERE id = 4")->execute();
        echo "  [OK] Plan 4 reverted to KSh 20,000\n";
    }
};
