<?php

namespace Benchero\Controllers\Tenant;

use Benchero\Core\Controller;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Services\ContentService;
use Benchero\Services\MediaService;

class ContentController extends Controller
{
    private ContentService $contentService;
    private MediaService $mediaService;

    public function __construct()
    {
        parent::__construct();
        $this->contentService = new ContentService();
        $this->mediaService = new MediaService();
    }

    private function requireManagerRole(Request $request): void
    {
        $role = $request->getAttribute('tenant_role');
        if (!in_array($role, ['owner', 'admin', 'manager'])) {
            throw new \Exception('403 Forbidden - Insufficient permissions');
        }
    }

    public function index(Request $request): Response
    {
        $tenant = $request->getAttribute('tenant');
        $tab = $request->input('tab', 'news');

        $news = $this->contentService->getNews($tenant['id']);
        $gallery = $this->contentService->getGallery($tenant['id']);
        $sponsors = $this->contentService->getSponsors($tenant['id']);

        return $this->render('tenant/content/index', [
            'tenant' => $tenant,
            'tab' => $tab,
            'news' => $news,
            'gallery' => $gallery,
            'sponsors' => $sponsors
        ]);
    }

    // --- News Handlers ---
    public function storeNews(Request $request): Response
    {
        try {
            $this->requireManagerRole($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        if (!$request->validateCsrf()) {
            return new Response('403 Forbidden - CSRF failed', 403);
        }

        $tenant = $request->getAttribute('tenant');
        $title = trim((string)$request->input('title'));
        $category = trim((string)$request->input('category', 'General'));
        $excerpt = trim((string)$request->input('excerpt'));
        $content = trim((string)$request->input('content'));
        $imageUrl = '';

        if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            try {
                $uploaded = $this->mediaService->uploadImage($tenant['id'], $_FILES['image_file'], 'news');
                $imageUrl = $uploaded['url'];
            } catch (\Exception $e) {
                $_SESSION['error'] = 'News image upload failed: ' . $e->getMessage();
                return Response::redirect("/o/{$tenant['slug']}/content?tab=news");
            }
        }

        if (empty($title) || empty($content)) {
            $_SESSION['error'] = 'Title and Content are required for news articles.';
            return Response::redirect("/o/{$tenant['slug']}/content?tab=news");
        }

        $this->contentService->createNews($tenant['id'], $title, $category, $excerpt, $content, $imageUrl);
        $_SESSION['success'] = 'News article published successfully.';

        return Response::redirect("/o/{$tenant['slug']}/content?tab=news");
    }

    public function deleteNews(Request $request, string $slug, string $id): Response
    {
        try {
            $this->requireManagerRole($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        $tenant = $request->getAttribute('tenant');
        $this->contentService->deleteNews($tenant['id'], $id);
        $_SESSION['success'] = 'News article deleted.';

        return Response::redirect("/o/{$tenant['slug']}/content?tab=news");
    }

    // --- Gallery Handlers ---
    public function storeGallery(Request $request): Response
    {
        try {
            $this->requireManagerRole($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        if (!$request->validateCsrf()) {
            return new Response('403 Forbidden - CSRF failed', 403);
        }

        $tenant = $request->getAttribute('tenant');
        $title = trim((string)$request->input('title'));
        $category = trim((string)$request->input('category', 'Matchday'));
        $imageUrl = '';

        if (empty($_FILES['image_file']['name']) || $_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Please select a photo file (Max 2MB) to upload.';
            return Response::redirect("/o/{$tenant['slug']}/content?tab=gallery");
        }

        try {
            $uploaded = $this->mediaService->uploadImage($tenant['id'], $_FILES['image_file'], 'gallery');
            $imageUrl = $uploaded['url'];
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Gallery photo upload failed: ' . $e->getMessage();
            return Response::redirect("/o/{$tenant['slug']}/content?tab=gallery");
        }

        $this->contentService->addGalleryImage($tenant['id'], $title, $category, $imageUrl);
        $_SESSION['success'] = 'Photo added to gallery.';

        return Response::redirect("/o/{$tenant['slug']}/content?tab=gallery");
    }

    public function deleteGallery(Request $request, string $slug, string $id): Response
    {
        try {
            $this->requireManagerRole($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        $tenant = $request->getAttribute('tenant');
        $this->contentService->deleteGalleryImage($tenant['id'], $id);
        $_SESSION['success'] = 'Gallery image removed.';

        return Response::redirect("/o/{$tenant['slug']}/content?tab=gallery");
    }

    // --- Sponsor Handlers ---
    public function storeSponsor(Request $request): Response
    {
        try {
            $this->requireManagerRole($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        if (!$request->validateCsrf()) {
            return new Response('403 Forbidden - CSRF failed', 403);
        }

        $tenant = $request->getAttribute('tenant');
        $name = trim((string)$request->input('name'));
        $websiteUrl = trim((string)$request->input('website_url'));
        $sponsorLevel = trim((string)$request->input('sponsor_level', 'Official Partner'));
        $logoUrl = '';

        if (empty($name)) {
            $_SESSION['error'] = 'Sponsor name is required.';
            return Response::redirect("/o/{$tenant['slug']}/content?tab=sponsors");
        }

        if (empty($_FILES['logo_file']['name']) || $_FILES['logo_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Please select a logo image file (Max 2MB) for the sponsor.';
            return Response::redirect("/o/{$tenant['slug']}/content?tab=sponsors");
        }

        try {
            $uploaded = $this->mediaService->uploadImage($tenant['id'], $_FILES['logo_file'], 'sponsor');
            $logoUrl = $uploaded['url'];
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Sponsor logo upload failed: ' . $e->getMessage();
            return Response::redirect("/o/{$tenant['slug']}/content?tab=sponsors");
        }

        $this->contentService->addSponsor($tenant['id'], $name, $logoUrl, $websiteUrl, $sponsorLevel);
        $_SESSION['success'] = 'Sponsor added successfully.';

        return Response::redirect("/o/{$tenant['slug']}/content?tab=sponsors");
    }

    public function deleteSponsor(Request $request, string $slug, string $id): Response
    {
        try {
            $this->requireManagerRole($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        $tenant = $request->getAttribute('tenant');
        $this->contentService->deleteSponsor($tenant['id'], $id);
        $_SESSION['success'] = 'Sponsor removed.';

        return Response::redirect("/o/{$tenant['slug']}/content?tab=sponsors");
    }
}
