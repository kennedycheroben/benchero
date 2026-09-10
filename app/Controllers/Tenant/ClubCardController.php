<?php

namespace Benchero\Controllers\Tenant;

use Benchero\Core\Controller;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Services\EntitlementService;
use Benchero\Services\OrganizationService;
use Benchero\Services\QrCodeService;

class ClubCardController extends Controller
{
    private OrganizationService $orgService;
    private EntitlementService $entitlementService;

    public function __construct()
    {
        parent::__construct();
        $this->orgService = new OrganizationService();
        $this->entitlementService = new EntitlementService();
    }

    public function show(Request $request, string $slug): Response
    {
        $org = $this->orgService->getOrganizationBySlug($slug);
        if (!$org) {
            return $this->error('Organization not found', 404);
        }

        $hasPro = $this->entitlementService->hasCapability($org['id'], EntitlementService::CAP_DIGITAL_CLUB_CARD);

        $appUrl = env('APP_URL', 'https://benchero.co.ke');
        $publicUrl = rtrim($appUrl, '/') . '/club/' . $org['slug'];
        $cardUrl = $publicUrl . '/card';

        $qrCodeSvg = QrCodeService::generateSvg($cardUrl, 220);

        return $this->render('tenant/club_card/index', [
            'org' => $org,
            'has_pro' => $hasPro,
            'public_url' => $publicUrl,
            'card_url' => $cardUrl,
            'qr_code_svg' => $qrCodeSvg
        ]);
    }
}
