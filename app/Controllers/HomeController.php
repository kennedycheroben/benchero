<?php

namespace Teamora\Controllers;

use Teamora\Core\Controller;
use Teamora\Core\Http\Request;
use Teamora\Core\Http\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->render('home', [
            'title' => env('BRAND_NAME', 'Teamora') . ' - ' . env('BRAND_TAGLINE', 'One platform. Every team.')
        ]);
    }
}
