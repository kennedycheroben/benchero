<?php

namespace Benchero\Services;

use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;
use PDO;

class WebsiteService
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::getConnection();
    }

    public function getSettings(string $orgId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM website_settings WHERE organization_id = ?");
        $stmt->execute([$orgId]);
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$settings) {
            $settings = $this->initializeDefaultSettings($orgId);
        }

        // Parse JSON fields safely
        if (!empty($settings['page_visibility']) && is_string($settings['page_visibility'])) {
            $settings['page_visibility'] = json_decode($settings['page_visibility'], true);
        } elseif (empty($settings['page_visibility'])) {
            $settings['page_visibility'] = $this->getDefaultPageVisibility();
        }

        if (!empty($settings['navigation_order']) && is_string($settings['navigation_order'])) {
            $settings['navigation_order'] = json_decode($settings['navigation_order'], true);
        } elseif (empty($settings['navigation_order'])) {
            $settings['navigation_order'] = $this->getDefaultNavigationOrder();
        }

        if (!empty($settings['navigation_labels']) && is_string($settings['navigation_labels'])) {
            $settings['navigation_labels'] = json_decode($settings['navigation_labels'], true);
        } elseif (empty($settings['navigation_labels'])) {
            $settings['navigation_labels'] = $this->getDefaultNavigationLabels();
        }

        return $settings;
    }

    public function updateSettings(string $orgId, array $data): bool
    {
        // Ensure settings record exists
        $this->getSettings($orgId);

        $fields = [
            'theme_id', 'primary_color', 'secondary_color', 'accent_color', 'text_color',
            'header_style', 'button_style', 'hero_title', 'hero_subtitle', 'hero_cta_text',
            'hero_cta_url', 'hero_image_url', 'footer_text', 'copyright_text',
            'show_sponsors_in_footer', 'map_link'
        ];

        $updates = [];
        $params = [];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $updates[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        if (array_key_exists('page_visibility', $data)) {
            $updates[] = "page_visibility = ?";
            $params[] = is_array($data['page_visibility']) ? json_encode($data['page_visibility']) : $data['page_visibility'];
        }

        if (array_key_exists('navigation_order', $data)) {
            $updates[] = "navigation_order = ?";
            $params[] = is_array($data['navigation_order']) ? json_encode($data['navigation_order']) : $data['navigation_order'];
        }

        if (array_key_exists('navigation_labels', $data)) {
            $updates[] = "navigation_labels = ?";
            $params[] = is_array($data['navigation_labels']) ? json_encode($data['navigation_labels']) : $data['navigation_labels'];
        }

        if (empty($updates)) {
            return true;
        }

        $params[] = $orgId;
        $sql = "UPDATE website_settings SET " . implode(', ', $updates) . " WHERE organization_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function getHomepageSections(string $orgId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM homepage_sections
            WHERE organization_id = ?
            ORDER BY display_order ASC, created_at ASC
        ");
        $stmt->execute([$orgId]);
        $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($sections)) {
            $sections = $this->initializeDefaultSections($orgId);
        }

        foreach ($sections as &$section) {
            $section['configuration'] = !empty($section['configuration']) ? json_decode($section['configuration'], true) : [];
        }

        return $sections;
    }

    public function updateHomepageSections(string $orgId, array $sectionsInput): void
    {
        foreach ($sectionsInput as $index => $item) {
            $sectionId = $item['id'] ?? '';
            $isVisible = !empty($item['is_visible']) ? 1 : 0;
            $displayOrder = (int)($item['display_order'] ?? $index);
            $title = $item['title'] ?? null;
            $subtitle = $item['subtitle'] ?? null;

            if ($sectionId) {
                $stmt = $this->db->prepare("
                    UPDATE homepage_sections
                    SET is_visible = ?, display_order = ?, title = ?, subtitle = ?
                    WHERE id = ? AND organization_id = ?
                ");
                $stmt->execute([$isVisible, $displayOrder, $title, $subtitle, $sectionId, $orgId]);
            }
        }
    }

    public function calculateCompletionScore(string $orgId, array $org): array
    {
        $settings = $this->getSettings($orgId);

        // Counts
        $teamCount = (int)$this->db->query("SELECT COUNT(*) FROM teams WHERE organization_id = '{$orgId}' AND deleted_at IS NULL")->fetchColumn();
        $playerCount = (int)$this->db->query("SELECT COUNT(*) FROM players WHERE organization_id = '{$orgId}' AND deleted_at IS NULL")->fetchColumn();
        $staffCount = (int)$this->db->query("SELECT COUNT(*) FROM staff WHERE organization_id = '{$orgId}'")->fetchColumn();
        $fixtureCount = (int)$this->db->query("SELECT COUNT(*) FROM fixtures WHERE organization_id = '{$orgId}' AND deleted_at IS NULL")->fetchColumn();
        $newsCount = (int)$this->db->query("SELECT COUNT(*) FROM news_articles WHERE organization_id = '{$orgId}'")->fetchColumn();
        $galleryCount = (int)$this->db->query("SELECT COUNT(*) FROM gallery_images WHERE organization_id = '{$orgId}'")->fetchColumn();

        $checklist = [
            [
                'key' => 'logo',
                'label' => 'Club Crest / Logo Uploaded',
                'passed' => !empty($org['logo_url']),
                'weight' => 15,
                'action_url' => "/o/{$org['slug']}/website/customize",
                'action_label' => 'Upload Crest'
            ],
            [
                'key' => 'hero',
                'label' => 'Homepage Hero Banner & Tagline',
                'passed' => !empty($settings['hero_image_url']) || !empty($settings['hero_title']),
                'weight' => 10,
                'action_url' => "/o/{$org['slug']}/website/customize",
                'action_label' => 'Customize Hero'
            ],
            [
                'key' => 'about',
                'label' => 'Club Description & History',
                'passed' => !empty($org['description']),
                'weight' => 15,
                'action_url' => "/o/{$org['slug']}/website/customize",
                'action_label' => 'Add About Info'
            ],
            [
                'key' => 'contact',
                'label' => 'Club Contact Details (Phone/Email)',
                'passed' => !empty($org['contact_email']) || !empty($org['contact_phone']),
                'weight' => 10,
                'action_url' => "/o/{$org['slug']}/website/customize",
                'action_label' => 'Add Contact'
            ],
            [
                'key' => 'teams',
                'label' => 'At least 1 Team Created',
                'passed' => $teamCount > 0,
                'weight' => 10,
                'action_url' => "/o/{$org['slug']}/sports",
                'action_label' => 'Create Team'
            ],
            [
                'key' => 'players',
                'label' => 'Player Roster Registered',
                'passed' => $playerCount > 0,
                'weight' => 10,
                'action_url' => "/o/{$org['slug']}/sports",
                'action_label' => 'Add Players'
            ],
            [
                'key' => 'staff',
                'label' => 'Coaching / Executive Staff Added',
                'passed' => $staffCount > 0,
                'weight' => 10,
                'action_url' => "/o/{$org['slug']}/staff",
                'action_label' => 'Add Staff'
            ],
            [
                'key' => 'fixtures',
                'label' => 'Match Fixtures Scheduled',
                'passed' => $fixtureCount > 0,
                'weight' => 10,
                'action_url' => "/o/{$org['slug']}/sports",
                'action_label' => 'Add Fixtures'
            ],
            [
                'key' => 'content',
                'label' => 'News Article or Gallery Photos Published',
                'passed' => ($newsCount > 0 || $galleryCount > 0),
                'weight' => 10,
                'action_url' => "/o/{$org['slug']}/content",
                'action_label' => 'Post Content'
            ]
        ];

        $totalPassed = 0;
        foreach ($checklist as $item) {
            if ($item['passed']) {
                $totalPassed += $item['weight'];
            }
        }

        return [
            'score' => min(100, $totalPassed),
            'checklist' => $checklist,
            'is_ready' => $totalPassed >= 70
        ];
    }

    private function initializeDefaultSettings(string $orgId): array
    {
        $id = Ulid::generate();
        $visibility = json_encode($this->getDefaultPageVisibility());
        $order = json_encode($this->getDefaultNavigationOrder());
        $labels = json_encode($this->getDefaultNavigationLabels());

        $stmt = $this->db->prepare("
            INSERT INTO website_settings (
                id, organization_id, theme_id, primary_color, secondary_color, accent_color, text_color,
                header_style, button_style, page_visibility, navigation_order, navigation_labels,
                show_sponsors_in_footer, created_at, updated_at
            ) VALUES (
                ?, ?, 'modern_sport', '#0d6efd', '#1e293b', '#ffc107', '#0f172a',
                'dark', 'pill', ?, ?, ?, 1, NOW(), NOW()
            )
        ");
        $stmt->execute([$id, $orgId, $visibility, $order, $labels]);

        return [
            'id' => $id,
            'organization_id' => $orgId,
            'theme_id' => 'modern_sport',
            'primary_color' => '#0d6efd',
            'secondary_color' => '#1e293b',
            'accent_color' => '#ffc107',
            'text_color' => '#0f172a',
            'header_style' => 'dark',
            'button_style' => 'pill',
            'hero_title' => null,
            'hero_subtitle' => null,
            'hero_cta_text' => null,
            'hero_cta_url' => null,
            'hero_image_url' => null,
            'page_visibility' => $this->getDefaultPageVisibility(),
            'navigation_order' => $this->getDefaultNavigationOrder(),
            'navigation_labels' => $this->getDefaultNavigationLabels(),
            'footer_text' => null,
            'copyright_text' => null,
            'show_sponsors_in_footer' => 1,
            'map_link' => null
        ];
    }

    private function initializeDefaultSections(string $orgId): array
    {
        $defaults = [
            ['type' => 'hero', 'title' => 'Hero Banner', 'order' => 1],
            ['type' => 'about', 'title' => 'Club Overview', 'order' => 2],
            ['type' => 'fixtures', 'title' => 'Upcoming Matches', 'order' => 3],
            ['type' => 'results', 'title' => 'Match Results', 'order' => 4],
            ['type' => 'teams', 'title' => 'Our Teams', 'order' => 5],
            ['type' => 'players', 'title' => 'Featured Players', 'order' => 6],
            ['type' => 'staff', 'title' => 'Coaching & Management', 'order' => 7],
            ['type' => 'standings', 'title' => 'League Standings', 'order' => 8],
            ['type' => 'news', 'title' => 'Latest Club News', 'order' => 9],
            ['type' => 'gallery', 'title' => 'Photo Gallery', 'order' => 10],
            ['type' => 'achievements', 'title' => 'Club Honors & History', 'order' => 11],
            ['type' => 'sponsors', 'title' => 'Official Partners', 'order' => 12],
            ['type' => 'contact', 'title' => 'Contact Us', 'order' => 13],
        ];

        $sections = [];
        foreach ($defaults as $d) {
            $id = Ulid::generate();
            $stmt = $this->db->prepare("
                INSERT INTO homepage_sections (id, organization_id, section_type, title, display_order, is_visible, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())
            ");
            $stmt->execute([$id, $orgId, $d['type'], $d['title'], $d['order']]);
            $sections[] = [
                'id' => $id,
                'organization_id' => $orgId,
                'section_type' => $d['type'],
                'title' => $d['title'],
                'subtitle' => null,
                'display_order' => $d['order'],
                'is_visible' => 1,
                'configuration' => []
            ];
        }

        return $sections;
    }

    public function getDefaultPageVisibility(): array
    {
        return [
            'about' => true,
            'teams' => true,
            'players' => true,
            'staff' => true,
            'fixtures' => true,
            'results' => true,
            'standings' => true,
            'news' => true,
            'gallery' => true,
            'history' => true,
            'sponsors' => true,
            'contact' => true
        ];
    }

    public function getDefaultNavigationOrder(): array
    {
        return ['about', 'teams', 'players', 'staff', 'fixtures', 'results', 'standings', 'news', 'gallery', 'history', 'sponsors', 'contact'];
    }

    public function getDefaultNavigationLabels(): array
    {
        return [
            'about' => 'About',
            'teams' => 'Teams',
            'players' => 'Players',
            'staff' => 'Staff',
            'fixtures' => 'Fixtures',
            'results' => 'Results',
            'standings' => 'Standings',
            'news' => 'News',
            'gallery' => 'Gallery',
            'history' => 'History',
            'sponsors' => 'Sponsors',
            'contact' => 'Contact'
        ];
    }
}
