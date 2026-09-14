<?php

namespace Benchero\Controllers;

use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Services\OrganizationService;

class OnboardingController
{
    public function index(Request $request): Response
    {
        if (empty($_SESSION['_user_id'])) {
            return Response::redirect('/login');
        }

        return Response::view('onboarding/index');
    }

    public function store(Request $request): Response
    {
        if (empty($_SESSION['_user_id'])) {
            return Response::redirect('/login');
        }

        $name = trim($request->input('name') ?? '');
        $slug = trim($request->input('slug') ?? '');
        $country = trim($request->input('country') ?? '');
        $timezone = trim($request->input('timezone') ?? '');

        // Auto-generate slug from name if empty
        if (empty($slug) && !empty($name)) {
            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $name), '-'));
        }

        if (empty($name) || empty($country) || empty($timezone)) {
            $_SESSION['error'] = 'Please fill in all required fields.';
            $_SESSION['old_onboarding_input'] = ['name' => $name, 'slug' => $slug, 'country' => $country, 'timezone' => $timezone];
            return Response::redirect('/onboarding');
        }
        
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9\-]+/', '-', $slug), '-'));
        if (empty($slug)) {
            $slug = 'club';
        }
        $slug = mb_substr($slug, 0, 80);

        $service = new OrganizationService();
        try {
            $createdSlug = $service->createOrganization($name, $slug, $country, $timezone, $_SESSION['_user_id']);
            unset($_SESSION['old_onboarding_input']);
            return Response::redirect('/o/' . $createdSlug . '/dashboard');
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $_SESSION['old_onboarding_input'] = ['name' => $name, 'slug' => $slug, 'country' => $country, 'timezone' => $timezone];
            return Response::redirect('/onboarding');
        }
    }
}
