<?php

namespace Benchero\Controllers\Tenant;

use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Core\TenantContext;

class LegacyRouteController
{
    public function teams(Request $request, string $slug): Response
    {
        return $this->redirectWithSportContext($request, $slug, 'teams');
    }

    public function players(Request $request, string $slug): Response
    {
        return $this->redirectWithSportContext($request, $slug, 'players');
    }

    public function fixtures(Request $request, string $slug): Response
    {
        return $this->redirectWithSportContext($request, $slug, 'fixtures');
    }

    public function results(Request $request, string $slug): Response
    {
        return $this->redirectWithSportContext($request, $slug, 'results');
    }

    public function seasons(Request $request, string $slug): Response
    {
        return $this->redirectWithSportContext($request, $slug, 'seasons');
    }

    private function redirectWithSportContext(Request $request, string $slug, string $segment): Response
    {
        $tenant = $request->getAttribute('tenant');
        $sport = $request->getAttribute('sport') ?: TenantContext::getSport();

        if ($sport && !empty($sport['slug'])) {
            return new Response('', 302, [
                'Location' => "/o/" . urlencode($slug) . "/s/" . urlencode($sport['slug']) . "/" . $segment
            ]);
        }

        // If organization has no active sport, redirect safely to sports catalogue
        return new Response('', 302, [
            'Location' => "/o/" . urlencode($slug) . "/sports"
        ]);
    }
}
