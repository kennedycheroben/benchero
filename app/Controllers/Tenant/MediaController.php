<?php

namespace Benchero\Controllers\Tenant;

use Benchero\Core\Controller;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Services\EntitlementService;
use Benchero\Services\MediaService;
use Benchero\Services\OrganizationService;
use InvalidArgumentException;

class MediaController extends Controller
{
    private OrganizationService $orgService;
    private MediaService $mediaService;
    private EntitlementService $entitlementService;

    public function __construct()
    {
        parent::__construct();
        $this->orgService = new OrganizationService();
        $this->mediaService = new MediaService();
        $this->entitlementService = new EntitlementService();
    }

    public function index(Request $request, string $slug): Response
    {
        $org = $this->orgService->getOrganizationBySlug($slug);
        if (!$org) {
            return $this->error('Organization not found', 404);
        }

        $category = $request->get('category', '');
        $mediaList = $this->mediaService->getMediaByOrg($org['id'], $category ?: null, 100);
        $storageUsage = $this->mediaService->getStorageUsage($org['id']);
        $hasVideoPro = $this->entitlementService->hasCapability($org['id'], EntitlementService::CAP_VIDEO_UPLOADS);

        return $this->render('tenant/media/index', [
            'org' => $org,
            'mediaList' => $mediaList,
            'storageUsage' => $storageUsage,
            'currentCategory' => $category,
            'hasVideoPro' => $hasVideoPro,
            'error' => $request->getFlash('error'),
            'success' => $request->getFlash('success')
        ]);
    }

    public function store(Request $request, string $slug): Response
    {
        $org = $this->orgService->getOrganizationBySlug($slug);
        if (!$org) {
            return $this->error('Organization not found', 404);
        }

        if (!$request->validateCsrf()) {
            return $this->error('403 Forbidden - CSRF validation failed', 403);
        }

        $type = $request->post('type', 'image');
        $category = trim($request->post('category', 'general'));
        $altText = trim($request->post('alt_text', ''));
        $caption = trim($request->post('caption', ''));

        if (empty($_FILES['file']['name']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $request->setFlash('error', 'Please select a valid file to upload.');
            return $this->redirect("/o/{$slug}/media");
        }

        try {
            if ($type === 'video') {
                $this->mediaService->uploadVideo($org['id'], $_FILES['file'], $category, $altText, $caption);
                $request->setFlash('success', 'Video successfully uploaded to Media Library!');
            } else {
                $this->mediaService->uploadImage($org['id'], $_FILES['file'], $category, $altText, $caption);
                $request->setFlash('success', 'Image successfully uploaded to Media Library!');
            }
        } catch (InvalidArgumentException $e) {
            $request->setFlash('error', 'Upload failed: ' . $e->getMessage());
        } catch (\Exception $e) {
            $request->setFlash('error', 'Server error uploading file: ' . $e->getMessage());
        }

        return $this->redirect("/o/{$slug}/media");
    }

    public function delete(Request $request, string $slug, string $id): Response
    {
        $org = $this->orgService->getOrganizationBySlug($slug);
        if (!$org) {
            return $this->error('Organization not found', 404);
        }

        if (!$request->validateCsrf()) {
            return $this->error('403 Forbidden - CSRF validation failed', 403);
        }

        $deleted = $this->mediaService->deleteMedia($org['id'], $id);
        if ($deleted) {
            $request->setFlash('success', 'Media file successfully removed.');
        } else {
            $request->setFlash('error', 'Failed to delete media asset or asset not found.');
        }

        return $this->redirect("/o/{$slug}/media");
    }
}
