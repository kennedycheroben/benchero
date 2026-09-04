<?php

namespace Benchero\Controllers\Tenant;

use Benchero\Core\Controller;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Services\MediaService;

class MediaController extends Controller
{
    private MediaService $mediaService;

    public function __construct()
    {
        parent::__construct();
        $this->mediaService = new MediaService();
    }

    private function requireOwnerOrAdmin(Request $request): void
    {
        $role = $request->getAttribute('tenant_role');
        if (!in_array($role, ['owner', 'admin', 'manager'])) {
            throw new \Exception('403 Forbidden - Access denied.');
        }
    }

    public function index(Request $request): Response
    {
        try {
            $this->requireOwnerOrAdmin($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        $tenant = $request->getAttribute('tenant');
        $category = $request->get('category');
        $mediaList = $this->mediaService->getMediaByOrg($tenant['id'], $category);

        return $this->render('tenant/media/index', [
            'tenant' => $tenant,
            'mediaList' => $mediaList,
            'currentCategory' => $category
        ]);
    }

    public function store(Request $request): Response
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
        $category = trim((string)$request->input('category', 'general'));
        $altText = trim((string)$request->input('alt_text'));
        $caption = trim((string)$request->input('caption'));

        if (empty($_FILES['file']['name'])) {
            $_SESSION['error'] = 'No image file selected.';
            return Response::redirect("/o/{$tenant['slug']}/media");
        }

        try {
            $this->mediaService->uploadImage($tenant['id'], $_FILES['file'], $category, $altText, $caption);
            $_SESSION['success'] = 'Media file uploaded successfully!';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Upload failed: ' . $e->getMessage();
        }

        return Response::redirect("/o/{$tenant['slug']}/media");
    }

    public function delete(Request $request, array $params): Response
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
            $this->mediaService->deleteMedia($tenant['id'], $id);
            $_SESSION['success'] = 'Media file deleted.';
        }

        return Response::redirect("/o/{$tenant['slug']}/media");
    }
}
