<?php

namespace Benchero\Controllers\Tenant;

use Benchero\Core\Controller;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Services\DomainService;
use Benchero\Services\EntitlementService;
use Benchero\Services\OrganizationService;
use InvalidArgumentException;

class DomainController extends Controller
{
    private OrganizationService $orgService;
    private DomainService $domainService;
    private EntitlementService $entitlementService;

    public function __construct()
    {
        parent::__construct();
        $this->orgService = new OrganizationService();
        $this->domainService = new DomainService();
        $this->entitlementService = new EntitlementService();
    }

    public function index(Request $request, string $slug): Response
    {
        $org = $this->orgService->getOrganizationBySlug($slug);
        if (!$org) {
            return $this->error('Organization not found', 404);
        }

        $domainRecord = $this->domainService->getDomainByOrg($org['id']);
        $hasPro = $this->entitlementService->hasCapability($org['id'], EntitlementService::CAP_CUSTOM_DOMAIN);
        $dnsInstructions = null;

        if ($domainRecord && !empty($domainRecord['dns_records'])) {
            $dnsInstructions = is_array($domainRecord['dns_records']) ? $domainRecord['dns_records'] : json_decode($domainRecord['dns_records'], true);
        }

        return $this->render('tenant/domain/index', [
            'org' => $org,
            'domain' => $domainRecord,
            'has_pro' => $hasPro,
            'dns_instructions' => $dnsInstructions,
            'error' => $request->getFlash('error'),
            'success' => $request->getFlash('success')
        ]);
    }

    public function save(Request $request, string $slug): Response
    {
        $org = $this->orgService->getOrganizationBySlug($slug);
        if (!$org) {
            return $this->error('Organization not found', 404);
        }

        $rawDomain = trim($request->post('domain', ''));

        try {
            $this->domainService->saveDomain($org['id'], $rawDomain);
            $request->setFlash('success', 'Custom domain submitted. Please configure your DNS settings and click Verify.');
        } catch (InvalidArgumentException $e) {
            $request->setFlash('error', $e->getMessage());
        }

        return $this->redirect("/o/{$slug}/domain");
    }

    public function verify(Request $request, string $slug): Response
    {
        $org = $this->orgService->getOrganizationBySlug($slug);
        if (!$org) {
            return $this->error('Organization not found', 404);
        }

        try {
            $result = $this->domainService->verifyDomain($org['id']);
            if ($result['success']) {
                $request->setFlash('success', $result['message']);
            } else {
                $request->setFlash('error', $result['message']);
            }
        } catch (InvalidArgumentException $e) {
            $request->setFlash('error', $e->getMessage());
        }

        return $this->redirect("/o/{$slug}/domain");
    }

    public function delete(Request $request, string $slug): Response
    {
        $org = $this->orgService->getOrganizationBySlug($slug);
        if (!$org) {
            return $this->error('Organization not found', 404);
        }

        $this->domainService->deleteDomain($org['id']);
        $request->setFlash('success', 'Custom domain disconnected.');

        return $this->redirect("/o/{$slug}/domain");
    }
}
