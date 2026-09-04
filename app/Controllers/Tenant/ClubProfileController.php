<?php

namespace Benchero\Controllers\Tenant;

use Benchero\Core\Controller;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Services\OrganizationService;

class ClubProfileController extends Controller
{
    private OrganizationService $orgService;

    public function __construct()
    {
        parent::__construct();
        $this->orgService = new OrganizationService();
    }

    private function requireOwnerOrAdmin(Request $request): void
    {
        $role = $request->getAttribute('tenant_role');
        if (!in_array($role, ['owner', 'admin'])) {
            throw new \Exception('403 Forbidden - Only club owners and admins can manage club profile.');
        }
    }

    public function edit(Request $request): Response
    {
        try {
            $this->requireOwnerOrAdmin($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        $tenant = $request->getAttribute('tenant');
        $org = $this->orgService->getOrganizationById($tenant['id']);

        if (!$org) {
            return new Response('404 Not Found', 404);
        }

        $socialLinks = [];
        if (!empty($org['social_links'])) {
            $decoded = json_decode($org['social_links'], true);
            if (is_array($decoded)) {
                $socialLinks = $decoded;
            }
        }

        return $this->render('tenant/profile/edit', [
            'tenant' => $tenant,
            'org' => $org,
            'socialLinks' => $socialLinks
        ]);
    }

    public function update(Request $request): Response
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

        $socialLinks = [
            'facebook' => trim((string)$request->input('facebook')),
            'instagram' => trim((string)$request->input('instagram')),
            'x' => trim((string)$request->input('x')),
            'tiktok' => trim((string)$request->input('tiktok')),
            'youtube' => trim((string)$request->input('youtube')),
            'whatsapp' => trim((string)$request->input('whatsapp'))
        ];

        $data = [
            'name' => trim((string)$request->input('name')),
            'country' => trim((string)$request->input('country')),
            'timezone' => trim((string)$request->input('timezone')),
            'logo_url' => trim((string)$request->input('logo_url')),
            'cover_url' => trim((string)$request->input('cover_url')),
            'description' => trim((string)$request->input('description')),
            'founded_year' => trim((string)$request->input('founded_year')),
            'club_colors' => trim((string)$request->input('club_colors')),
            'contact_email' => trim((string)$request->input('contact_email')),
            'contact_phone' => trim((string)$request->input('contact_phone')),
            'address' => trim((string)$request->input('address')),
            'social_links' => $socialLinks,
            'featured_video_url' => trim((string)$request->input('featured_video_url'))
        ];

        if (empty($data['name'])) {
            $_SESSION['error'] = 'Club name is required.';
            return Response::redirect("/o/{$tenant['slug']}/profile");
        }

        $this->orgService->updateClubProfile($tenant['id'], $data);
        $_SESSION['success'] = 'Club profile updated successfully!';

        return Response::redirect("/o/{$tenant['slug']}/profile");
    }
}
