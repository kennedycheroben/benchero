<?php

namespace Benchero\Controllers\Tenant;

use Benchero\Core\Controller;
use Benchero\Core\Database\Database;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Core\RateLimiter;
use Benchero\Services\DomainService;
use Benchero\Services\EntitlementService;
use Benchero\Services\OrganizationService;
use InvalidArgumentException;
use PDO;

class DomainController extends Controller
{
    private PDO $db;
    private OrganizationService $orgService;
    private DomainService $domainService;
    private EntitlementService $entitlementService;
    private RateLimiter $rateLimiter;

    public function __construct()
    {
        parent::__construct();
        $this->db = Database::getConnection();
        $this->orgService = new OrganizationService($this->db);
        $this->domainService = new DomainService($this->db);
        $this->entitlementService = new EntitlementService($this->db);
        $this->rateLimiter = new RateLimiter($this->db);
    }

    public function index(Request $request, string $slug): Response
    {
        $org = $this->orgService->getOrganizationBySlug($slug);
        if (!$org) {
            return $this->error('Organization not found', 404);
        }

        $domainRecord = $this->domainService->getDomainByOrg($org['id']);
        $domains = $this->domainService->getDomainsByOrg($org['id']);
        $hasPro = $this->entitlementService->hasCapability($org['id'], EntitlementService::CAP_CUSTOM_DOMAIN);
        $dnsInstructions = null;

        if ($domainRecord && !empty($domainRecord['dns_records'])) {
            $dnsInstructions = is_array($domainRecord['dns_records']) ? $domainRecord['dns_records'] : json_decode($domainRecord['dns_records'], true);
        }

        return $this->render('tenant/domain/index', [
            'org' => $org,
            'domain' => $domainRecord,
            'domains' => $domains,
            'has_pro' => $hasPro,
            'dns_instructions' => $dnsInstructions,
            'error' => $request->getFlash('error'),
            'success' => $request->getFlash('success'),
            'info' => $request->getFlash('info')
        ]);
    }

    public function save(Request $request, string $slug): Response
    {
        $org = $this->orgService->getOrganizationBySlug($slug);
        if (!$org) {
            return $this->error('Organization not found', 404);
        }

        if (!$request->validateCsrf()) {
            $request->setFlash('error', 'Invalid security token. Please try again.');
            return $this->redirect("/o/{$slug}/domain");
        }

        // Rate limit: max 10 domain adds per 10 minutes
        if (!$this->rateLimiter->hit("domain_save_{$org['id']}", 10, 600)) {
            $request->setFlash('error', 'Too many domain connection attempts. Please wait 10 minutes before trying again.');
            return $this->redirect("/o/{$slug}/domain");
        }

        $rawDomain = trim($request->post('domain', ''));

        try {
            $this->domainService->saveDomain($org['id'], $rawDomain);
            $request->setFlash('success', 'Custom domain registered. Please configure the DNS TXT verification record below.');
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

        if (!$request->validateCsrf()) {
            $request->setFlash('error', 'Invalid security token. Please try again.');
            return $this->redirect("/o/{$slug}/domain");
        }

        // Rate limit: max 10 verification queries per 5 minutes to prevent hammering DNS
        if (!$this->rateLimiter->hit("domain_verify_{$org['id']}", 10, 300)) {
            $request->setFlash('error', 'Too many verification requests. DNS propagation takes time; please wait 5 minutes before checking again.');
            return $this->redirect("/o/{$slug}/domain");
        }

        $domainId = $request->post('domain_id', null);

        try {
            $result = $this->domainService->verifyDomain($org['id'], $domainId);
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

    public function activate(Request $request, string $slug): Response
    {
        $org = $this->orgService->getOrganizationBySlug($slug);
        if (!$org) {
            return $this->error('Organization not found', 404);
        }

        if (!$request->validateCsrf()) {
            $request->setFlash('error', 'Invalid security token. Please try again.');
            return $this->redirect("/o/{$slug}/domain");
        }

        if (!$this->rateLimiter->hit("domain_activate_{$org['id']}", 5, 300)) {
            $request->setFlash('error', 'Too many activation attempts. Please wait a few minutes.');
            return $this->redirect("/o/{$slug}/domain");
        }

        $domainId = $request->post('domain_id', null);

        try {
            $result = $this->domainService->activateDomain($org['id'], $domainId);
            $request->setFlash('success', $result['message']);
        } catch (InvalidArgumentException $e) {
            $request->setFlash('error', $e->getMessage());
        }

        return $this->redirect("/o/{$slug}/domain");
    }

    public function checkSsl(Request $request, string $slug): Response
    {
        $org = $this->orgService->getOrganizationBySlug($slug);
        if (!$org) {
            return $this->error('Organization not found', 404);
        }

        if (!$request->validateCsrf()) {
            $request->setFlash('error', 'Invalid security token. Please try again.');
            return $this->redirect("/o/{$slug}/domain");
        }

        if (!$this->rateLimiter->hit("domain_ssl_{$org['id']}", 5, 300)) {
            $request->setFlash('error', 'Too many SSL check requests. Please wait a few minutes.');
            return $this->redirect("/o/{$slug}/domain");
        }

        $domainId = $request->post('domain_id', null);

        try {
            $result = $this->domainService->checkSslStatus($org['id'], $domainId);
            if ($result['ssl_active']) {
                $request->setFlash('success', $result['message']);
            } else {
                $request->setFlash('info', $result['message']);
            }
        } catch (InvalidArgumentException $e) {
            $request->setFlash('error', $e->getMessage());
        }

        return $this->redirect("/o/{$slug}/domain");
    }

    public function regenerate(Request $request, string $slug): Response
    {
        $org = $this->orgService->getOrganizationBySlug($slug);
        if (!$org) {
            return $this->error('Organization not found', 404);
        }

        if (!$request->validateCsrf()) {
            $request->setFlash('error', 'Invalid security token. Please try again.');
            return $this->redirect("/o/{$slug}/domain");
        }

        if (!$this->rateLimiter->hit("domain_regen_{$org['id']}", 5, 600)) {
            $request->setFlash('error', 'Too many token regeneration requests. Please wait 10 minutes.');
            return $this->redirect("/o/{$slug}/domain");
        }

        $domainId = $request->post('domain_id', null);

        try {
            $this->domainService->regenerateToken($org['id'], $domainId);
            $request->setFlash('success', 'New verification token generated. Please update your DNS TXT record.');
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

        if (!$request->validateCsrf()) {
            $request->setFlash('error', 'Invalid security token. Please try again.');
            return $this->redirect("/o/{$slug}/domain");
        }

        if (!$this->rateLimiter->hit("domain_delete_{$org['id']}", 5, 300)) {
            $request->setFlash('error', 'Too many requests. Please wait a few minutes.');
            return $this->redirect("/o/{$slug}/domain");
        }

        $domainId = $request->post('domain_id', null);

        $this->domainService->deleteDomain($org['id'], $domainId);
        $request->setFlash('success', 'Custom domain disconnected.');

        return $this->redirect("/o/{$slug}/domain");
    }
}
