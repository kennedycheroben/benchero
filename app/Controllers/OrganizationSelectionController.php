<?php

namespace Benchero\Controllers;

use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Core\Database\Database;

class OrganizationSelectionController
{
    public function index(Request $request): Response
    {
        if (empty($_SESSION['_user_id'])) {
            return Response::redirect('/login');
        }

        $db = Database::getConnection();
        
        $stmt = $db->prepare("
            SELECT o.*, ou.role 
            FROM organizations o
            JOIN organization_user ou ON o.id = ou.organization_id
            WHERE ou.user_id = :user_id
            ORDER BY o.name ASC
        ");
        $stmt->execute(['user_id' => $_SESSION['_user_id']]);
        $organizations = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($organizations)) {
            return Response::redirect('/onboarding');
        }

        if (count($organizations) === 1) {
            return Response::redirect('/o/' . $organizations[0]['slug'] . '/dashboard');
        }

        return Response::view('organizations/index', ['organizations' => $organizations]);
    }
}
