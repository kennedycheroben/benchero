<?php

namespace Benchero\Controllers\Tenant;

use Benchero\Core\Controller;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Repositories\HistoryRepository;
use Benchero\Repositories\PageRepository;
use Benchero\Services\MediaService;
use Benchero\Services\OrganizationService;
use Benchero\Services\WebsiteService;

class WebsiteBuilderController extends Controller
{
    private WebsiteService $websiteService;
    private OrganizationService $orgService;
    private HistoryRepository $historyRepo;
    private PageRepository $pageRepo;

    public function __construct()
    {
        parent::__construct();
        $this->websiteService = new WebsiteService();
        $this->orgService = new OrganizationService();
        $this->historyRepo = new HistoryRepository();
        $this->pageRepo = new PageRepository();
    }

    private function requireOwnerOrAdmin(Request $request): void
    {
        $role = $request->getAttribute('tenant_role');
        if (!in_array($role, ['owner', 'admin', 'manager'])) {
            throw new \Exception('403 Forbidden - Access denied.');
        }
    }

    public function overview(Request $request): Response
    {
        try {
            $this->requireOwnerOrAdmin($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        $tenant = $request->getAttribute('tenant');
        $org = $this->orgService->getOrganizationById($tenant['id']);
        $readiness = $this->websiteService->calculateCompletionScore($tenant['id'], $org);
        $settings = $this->websiteService->getSettings($tenant['id']);

        return $this->render('tenant/website/overview', [
            'tenant' => $tenant,
            'org' => $org,
            'readiness' => $readiness,
            'settings' => $settings
        ]);
    }

    public function customizeForm(Request $request): Response
    {
        try {
            $this->requireOwnerOrAdmin($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        $tenant = $request->getAttribute('tenant');
        $org = $this->orgService->getOrganizationById($tenant['id']);
        $settings = $this->websiteService->getSettings($tenant['id']);

        return $this->render('tenant/website/customize', [
            'tenant' => $tenant,
            'org' => $org,
            'settings' => $settings
        ]);
    }

    public function customizeSave(Request $request): Response
    {
        try {
            $this->requireOwnerOrAdmin($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        if (!$request->validateCsrf()) {
            return new Response('403 Forbidden - CSRF failed', 403);
        }

        $tenant = $request->getAttribute('tenant');

        // Check if image file was uploaded
        $mediaService = new MediaService();
        $heroImageUrl = trim((string)$request->input('hero_image_url'));

        if (!empty($_FILES['hero_image_file']['name'])) {
            try {
                $uploaded = $mediaService->uploadImage($tenant['id'], $_FILES['hero_image_file'], 'hero', 'Homepage Hero Image');
                $heroImageUrl = $uploaded['url'];
            } catch (\Exception $e) {
                $_SESSION['error'] = 'Hero Image upload failed: ' . $e->getMessage();
            }
        }

        $settingsData = [
            'primary_color' => trim((string)$request->input('primary_color', '#0d6efd')),
            'secondary_color' => trim((string)$request->input('secondary_color', '#1e293b')),
            'accent_color' => trim((string)$request->input('accent_color', '#ffc107')),
            'text_color' => trim((string)$request->input('text_color', '#0f172a')),
            'header_style' => trim((string)$request->input('header_style', 'dark')),
            'button_style' => trim((string)$request->input('button_style', 'pill')),
            'hero_title' => trim((string)$request->input('hero_title')),
            'hero_subtitle' => trim((string)$request->input('hero_subtitle')),
            'hero_cta_text' => trim((string)$request->input('hero_cta_text')),
            'hero_cta_url' => trim((string)$request->input('hero_cta_url')),
            'hero_image_url' => $heroImageUrl,
            'footer_text' => trim((string)$request->input('footer_text')),
            'copyright_text' => trim((string)$request->input('copyright_text')),
            'show_sponsors_in_footer' => $request->input('show_sponsors_in_footer') ? 1 : 0,
            'map_link' => trim((string)$request->input('map_link'))
        ];

        $this->websiteService->updateSettings($tenant['id'], $settingsData);

        // Also update Organization details (tagline/description, contact info)
        $orgData = [
            'description' => trim((string)$request->input('description')),
            'contact_email' => trim((string)$request->input('contact_email')),
            'contact_phone' => trim((string)$request->input('contact_phone')),
            'address' => trim((string)$request->input('address'))
        ];

        $logoUrl = trim((string)$request->input('logo_url'));
        if (!empty($_FILES['logo_file']['name'])) {
            try {
                $uploadedLogo = $mediaService->uploadImage($tenant['id'], $_FILES['logo_file'], 'branding', 'Club Logo');
                $logoUrl = $uploadedLogo['url'];
            } catch (\Exception $e) {
                $_SESSION['error'] = 'Logo upload failed: ' . $e->getMessage();
            }
        }
        if ($logoUrl) {
            $orgData['logo_url'] = $logoUrl;
        }

        $this->orgService->updateClubProfile($tenant['id'], array_merge(
            $this->orgService->getOrganizationById($tenant['id']),
            $orgData
        ));

        $_SESSION['success'] = 'Website branding and customization saved successfully!';
        return Response::redirect("/o/{$tenant['slug']}/website/customize");
    }

    public function homepageForm(Request $request): Response
    {
        try {
            $this->requireOwnerOrAdmin($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        $tenant = $request->getAttribute('tenant');
        $sections = $this->websiteService->getHomepageSections($tenant['id']);

        return $this->render('tenant/website/homepage', [
            'tenant' => $tenant,
            'sections' => $sections
        ]);
    }

    public function homepageSave(Request $request): Response
    {
        try {
            $this->requireOwnerOrAdmin($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        if (!$request->validateCsrf()) {
            return new Response('403 Forbidden - CSRF failed', 403);
        }

        $tenant = $request->getAttribute('tenant');
        $sectionsInput = $request->input('sections', []);

        if (is_array($sectionsInput)) {
            $this->websiteService->updateHomepageSections($tenant['id'], $sectionsInput);
        }

        $_SESSION['success'] = 'Homepage section order and visibility updated successfully!';
        return Response::redirect("/o/{$tenant['slug']}/website/homepage");
    }

    public function navigationForm(Request $request): Response
    {
        try {
            $this->requireOwnerOrAdmin($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        $tenant = $request->getAttribute('tenant');
        $settings = $this->websiteService->getSettings($tenant['id']);

        return $this->render('tenant/website/navigation', [
            'tenant' => $tenant,
            'settings' => $settings,
            'defaultVisibility' => $this->websiteService->getDefaultPageVisibility(),
            'defaultLabels' => $this->websiteService->getDefaultNavigationLabels()
        ]);
    }

    public function navigationSave(Request $request): Response
    {
        try {
            $this->requireOwnerOrAdmin($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        if (!$request->validateCsrf()) {
            return new Response('403 Forbidden - CSRF failed', 403);
        }

        $tenant = $request->getAttribute('tenant');
        $visibilityInput = $request->input('visibility', []);
        $labelsInput = $request->input('labels', []);

        $defaultVis = $this->websiteService->getDefaultPageVisibility();
        $finalVisibility = [];

        foreach (array_keys($defaultVis) as $pageKey) {
            $finalVisibility[$pageKey] = !empty($visibilityInput[$pageKey]);
        }

        $this->websiteService->updateSettings($tenant['id'], [
            'page_visibility' => $finalVisibility,
            'navigation_labels' => $labelsInput
        ]);

        $_SESSION['success'] = 'Website navigation visibility saved successfully!';
        return Response::redirect("/o/{$tenant['slug']}/website/navigation");
    }

    public function themesForm(Request $request): Response
    {
        try {
            $this->requireOwnerOrAdmin($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        $tenant = $request->getAttribute('tenant');
        $settings = $this->websiteService->getSettings($tenant['id']);

        $themes = [
            [
                'id' => 'modern_sport',
                'name' => 'MODERN SPORT',
                'description' => 'Sleek dark/light hybrid header, high-contrast dynamic scorecards, bold typography.',
                'badge' => 'POPULAR',
                'preview_color' => '#0d6efd'
            ],
            [
                'id' => 'classic_club',
                'name' => 'CLASSIC CLUB',
                'description' => 'Traditional centered badge header, subtle gradients, clean serif framing layout.',
                'badge' => 'TRADITIONAL',
                'preview_color' => '#198754'
            ],
            [
                'id' => 'dynamic_athletic',
                'name' => 'DYNAMIC ATHLETIC',
                'description' => 'Energetic diagonal accents, high-velocity stat blocks, vibrant action layout.',
                'badge' => 'HIGH ENERGY',
                'preview_color' => '#dc3545'
            ]
        ];

        return $this->render('tenant/website/themes', [
            'tenant' => $tenant,
            'settings' => $settings,
            'themes' => $themes
        ]);
    }

    public function themesSave(Request $request): Response
    {
        try {
            $this->requireOwnerOrAdmin($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        if (!$request->validateCsrf()) {
            return new Response('403 Forbidden - CSRF failed', 403);
        }

        $tenant = $request->getAttribute('tenant');
        $themeId = trim((string)$request->input('theme_id'));

        if (!in_array($themeId, ['modern_sport', 'classic_club', 'dynamic_athletic'])) {
            $themeId = 'modern_sport';
        }

        $this->websiteService->updateSettings($tenant['id'], ['theme_id' => $themeId]);

        $_SESSION['success'] = 'Club website theme changed successfully!';
        return Response::redirect("/o/{$tenant['slug']}/website/themes");
    }

    public function historyIndex(Request $request): Response
    {
        try {
            $this->requireOwnerOrAdmin($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        $tenant = $request->getAttribute('tenant');
        $history = $this->historyRepo->getByOrganization($tenant['id']);

        return $this->render('tenant/website/history', [
            'tenant' => $tenant,
            'history' => $history
        ]);
    }

    public function historySave(Request $request): Response
    {
        try {
            $this->requireOwnerOrAdmin($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        if (!$request->validateCsrf()) {
            return new Response('403 Forbidden - CSRF failed', 403);
        }

        $tenant = $request->getAttribute('tenant');
        $id = trim((string)$request->input('id'));
        $yearDate = trim((string)$request->input('year_date'));
        $title = trim((string)$request->input('title'));
        $description = trim((string)$request->input('description'));
        $category = trim((string)$request->input('category', 'Milestone'));
        $displayOrder = (int)$request->input('display_order', 0);
        $imageUrl = trim((string)$request->input('image_url'));

        if (!empty($_FILES['image_file']['name'])) {
            $mediaService = new MediaService();
            try {
                $uploaded = $mediaService->uploadImage($tenant['id'], $_FILES['image_file'], 'history', $title);
                $imageUrl = $uploaded['url'];
            } catch (\Exception $e) {
                $_SESSION['error'] = 'Image upload failed: ' . $e->getMessage();
            }
        }

        if (empty($yearDate) || empty($title) || empty($description)) {
            $_SESSION['error'] = 'Year/Date, Title, and Description are required.';
            return Response::redirect("/o/{$tenant['slug']}/website/history");
        }

        $data = [
            'organization_id' => $tenant['id'],
            'year_date' => $yearDate,
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'display_order' => $displayOrder,
            'image_url' => $imageUrl
        ];

        if ($id) {
            $this->historyRepo->update($id, $tenant['id'], $data);
            $_SESSION['success'] = 'History milestone updated successfully!';
        } else {
            $this->historyRepo->create($data);
            $_SESSION['success'] = 'History milestone created successfully!';
        }

        return Response::redirect("/o/{$tenant['slug']}/website/history");
    }

    public function historyDelete(Request $request, array $params): Response
    {
        try {
            $this->requireOwnerOrAdmin($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        if (!$request->validateCsrf()) {
            return new Response('403 Forbidden - CSRF failed', 403);
        }

        $tenant = $request->getAttribute('tenant');
        $id = $params['id'] ?? '';

        if ($id) {
            $this->historyRepo->delete($id, $tenant['id']);
            $_SESSION['success'] = 'Milestone deleted.';
        }

        return Response::redirect("/o/{$tenant['slug']}/website/history");
    }
}
