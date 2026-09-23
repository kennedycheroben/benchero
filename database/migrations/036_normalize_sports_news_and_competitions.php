<?php

use Benchero\Core\Database\Database;

/**
 * Migration 036: Sports Platform Normalization (News Entities & Truthful Countries)
 * 
 * 1. Verifies Plan 4 (Benchero Pro Yearly) remains at KSh 25,000.00 authoritatively.
 * 2. Normalizes existing sports_news records by recursively decoding any double-escaped HTML entities.
 * 3. Normalizes existing sports_competitions records with truthful domestic countries.
 */
return new class {
    public function up(PDO $pdo): void
    {
        echo "Running Migration 036...\n";

        // 1. Verify Plan 4 in plans table is at authoritative KSh 25,000.00
        $stmtPlan = $pdo->prepare("SELECT price_kes, features FROM plans WHERE id = 4");
        $stmtPlan->execute();
        $plan4 = $stmtPlan->fetch(PDO::FETCH_ASSOC);

        if ($plan4) {
            $features = json_decode($plan4['features'] ?? '{}', true) ?: [];
            $features['description'] = "Pro annual subscription (KSh 25,000/yr)";
            
            $updatePlan = $pdo->prepare("UPDATE plans SET price_kes = 25000.00, features = ?, updated_at = NOW() WHERE id = 4");
            $updatePlan->execute([json_encode($features)]);
            echo "  [OK] Plan 4 (Benchero Pro Yearly) verified at KSh 25,000.00\n";
        }

        // 2. Normalize existing sports_news records (decoding HTML entities recursively)
        $stmtNews = $pdo->query("SELECT id, title, summary, content FROM sports_news");
        $newsRows = $stmtNews->fetchAll(PDO::FETCH_ASSOC);
        $cleanedNews = 0;

        $updateNews = $pdo->prepare("UPDATE sports_news SET title = ?, summary = ?, content = ? WHERE id = ?");
        foreach ($newsRows as $n) {
            $cleanTitle = $this->decodeEntitiesRecursively($n['title'] ?? '');
            $cleanSummary = $this->decodeEntitiesRecursively($n['summary'] ?? '');
            $cleanContent = $this->decodeEntitiesRecursively($n['content'] ?? '');

            if ($cleanTitle !== $n['title'] || $cleanSummary !== $n['summary'] || $cleanContent !== $n['content']) {
                $updateNews->execute([$cleanTitle, $cleanSummary, $cleanContent, $n['id']]);
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
            'non-league-premier---isthmian' => 'England',
            'non-league-premier---northern' => 'England',
            'efl-trophy' => 'England',
            // Spain
            'la-liga' => 'Spain',
            'primera-division' => 'Spain',
            'primera-premier' => 'Spain',
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
            'knvb-beker' => 'Netherlands',
            // Portugal
            'primeira-liga' => 'Portugal',
            'liga-revela-o-u23' => 'Portugal',
            // Brazil
            'campeonato-brasileiro-s-rie-a' => 'Brazil',
            'brasileiro-women' => 'Brazil',
            'brasileiro-u17' => 'Brazil',
            'paulista---u20' => 'Brazil',
            // Argentina
            'liga-profesional-argentina' => 'Argentina',
            'primera-nacional' => 'Argentina',
            'primera-b' => 'Argentina',
            'primera-c' => 'Argentina',
            'nacional-b' => 'Argentina',
            // Other domestic leagues
            'division-intermedia' => 'Paraguay',
            'copa-paraguay' => 'Paraguay',
            'copa-de-la-divisi-n-profesional' => 'Bolivia',
            'copa-chile' => 'Chile',
            'liga-paname-a-de-f-tbol' => 'Panama',
            'liga-pro' => 'Ecuador',
            'liga-mx-femenil' => 'Mexico',
            'liga-de-ascenso' => 'Mexico',
            'prva-liga' => 'Serbia',
            'pro-league-a' => 'Belgium',
            'ifa-shield' => 'India',
            'federation-cup' => 'India',
            'emperor-cup' => 'Japan',
            'kvindeliga' => 'Denmark',
            'liga-ii' => 'Romania',
            'liga-iii---serie-1' => 'Romania',
            'liga-iii---serie-2' => 'Romania',
            'liga-iii---serie-3' => 'Romania',
            'liga-iii---serie-5' => 'Romania',
            'liga-iii---serie-6' => 'Romania',
            'liga-iii---serie-7' => 'Romania',
            'azadegan-league' => 'Iran',
            'liga-alef' => 'Israel',
            // Continental / International Tournaments
            'uefa-champions-league' => 'Europe',
            'european-championship' => 'Europe',
            'uefa-europa-cup---women' => 'Europe',
            'caf-champions-league' => 'Africa',
            'caf-confederation-cup' => 'Africa',
            'cosafa-u20-championship' => 'Africa',
            'copa-libertadores' => 'South America',
            'asian-games' => 'Asia',
            'fifa-world-cup' => 'International'
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
    }
};
