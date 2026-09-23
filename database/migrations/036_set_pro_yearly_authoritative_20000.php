<?php

use Benchero\Core\Database\Database;

/**
 * Migration 036: Authoritative Pricing & Sports Platform Normalization
 * 
 * 1. Sets Plan 4 (Benchero Pro Yearly) to KSh 20,000.00 authoritatively.
 *    - Regular equivalent: 12 × KSh 2,500 = KSh 30,000
 *    - Annual savings: KSh 30,000 - KSh 20,000 = KSh 10,000 (33.33% off)
 * 2. Normalizes existing sports_news records by recursively decoding any double-escaped HTML entities.
 * 3. Normalizes existing sports_competitions records with truthful domestic countries.
 */
return new class {
    public function up(PDO $pdo): void
    {
        echo "Running Migration 036...\n";

        // 1. Update Plan 4 in plans table
        $stmtPlan = $pdo->prepare("SELECT price_kes, features FROM plans WHERE id = 4");
        $stmtPlan->execute();
        $plan4 = $stmtPlan->fetch(PDO::FETCH_ASSOC);

        if ($plan4) {
            $features = json_decode($plan4['features'] ?? '{}', true) ?: [];
            $features['description'] = "Pro annual subscription (KSh 20,000/yr)";
            
            $updatePlan = $pdo->prepare("UPDATE plans SET price_kes = 20000.00, features = ?, updated_at = NOW() WHERE id = 4");
            $updatePlan->execute([json_encode($features)]);
            echo "  [OK] Plan 4 (Benchero Pro Yearly) updated to KSh 20,000.00\n";
        }

        // 2. Normalize existing sports_news records (decoding HTML entities)
        $stmtNews = $pdo->query("SELECT id, title, summary FROM sports_news");
        $newsRows = $stmtNews->fetchAll(PDO::FETCH_ASSOC);
        $cleanedNews = 0;

        $updateNews = $pdo->prepare("UPDATE sports_news SET title = ?, summary = ? WHERE id = ?");
        foreach ($newsRows as $n) {
            $cleanTitle = $this->decodeEntitiesRecursively($n['title'] ?? '');
            $cleanSummary = $this->decodeEntitiesRecursively($n['summary'] ?? '');

            if ($cleanTitle !== $n['title'] || $cleanSummary !== $n['summary']) {
                $updateNews->execute([$cleanTitle, $cleanSummary, $n['id']]);
                $cleanedNews++;
            }
        }
        echo "  [OK] Normalized {$cleanedNews} sports_news records with clean UTF-8 text\n";

        // 3. Normalize sports_competitions countries
        $countryMap = [
            // Kenya
            'kenyan-premier-league' => 'Kenya',
            'fkf-premier-league' => 'Kenya',
            // England
            'premier-league' => 'England',
            'championship' => 'England',
            'league' => 'England',
            'non-league-premier---southern-central' => 'England',
            // Spain
            'la-liga' => 'Spain',
            'primera-division' => 'Spain',
            // Germany
            'bundesliga' => 'Germany',
            // Italy
            'serie-a' => 'Italy',
            'serie-b' => 'Italy',
            'serie-c---girone-a' => 'Italy',
            'serie-c---girone-b' => 'Italy',
            'serie-d---girone-b' => 'Italy',
            // France
            'ligue-1' => 'France',
            // Netherlands
            'eredivisie' => 'Netherlands',
            // Portugal
            'primeira-liga' => 'Portugal',
            // Brazil
            'campeonato-brasileiro-s-rie-a' => 'Brazil',
            'brasileiro-women' => 'Brazil',
            'paulista---u20' => 'Brazil',
            // Argentina
            'liga-profesional-argentina' => 'Argentina',
            'primera-nacional' => 'Argentina',
            'primera-b' => 'Argentina',
            'primera-c' => 'Argentina',
            'nacional-b' => 'Argentina',
            // Other domestic
            'division-intermedia' => 'Paraguay',
            'copa-de-la-divisi-n-profesional' => 'Bolivia',
            'liga-paname-a-de-f-tbol' => 'Panama',
            'liga-pro' => 'Ecuador',
            'liga-mx-femenil' => 'Mexico',
            'prva-liga' => 'Serbia',
            'pro-league-a' => 'Belgium',
            'ifa-shield' => 'India',
            'federation-cup' => 'India'
        ];

        $updateComp = $pdo->prepare("UPDATE sports_competitions SET country = ? WHERE slug = ?");
        $updatedComps = 0;
        foreach ($countryMap as $slug => $country) {
            $updateComp->execute([$country, $slug]);
            $updatedComps += $updateComp->rowCount();
        }
        echo "  [OK] Normalized {$updatedComps} sports_competitions with truthful domestic countries\n";
    }

    private function decodeEntitiesRecursively(string $str): string
    {
        $prev = '';
        while ($prev !== $str) {
            $prev = $str;
            $str = html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return $str;
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec("UPDATE plans SET price_kes = 20000.00 WHERE id = 4");
    }
};
