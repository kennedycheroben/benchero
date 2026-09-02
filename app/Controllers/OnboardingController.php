<?php

namespace Teamora\Controllers;

use Teamora\Core\Http\Request;
use Teamora\Core\Http\Response;
use Teamora\Services\OrganizationService;

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

        if (empty($name) || empty($slug) || empty($country) || empty($timezone)) {
            $_SESSION['error'] = 'All fields are required.';
            return Response::redirect('/onboarding');
        }
        
        if (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
            $_SESSION['error'] = 'Slug can only contain lowercase letters, numbers, and hyphens.';
            return Response::redirect('/onboarding');
        }

        $service = new OrganizationService();
        try {
            $createdSlug = $service->createOrganization($name, $slug, $country, $timezone, $_SESSION['_user_id']);
            return Response::redirect('/o/' . $createdSlug . '/dashboard');
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            return Response::redirect('/onboarding');
        }
    }
}
