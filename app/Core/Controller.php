<?php

namespace Teamora\Core;

use League\Plates\Engine;
use Teamora\Core\Http\Response;

abstract class Controller
{
    protected Engine $templates;

    public function __construct()
    {
        $this->templates = new Engine(__DIR__ . '/../../views');
        $this->templates->registerFunction('url', 'url');
    }

    protected function render(string $template, array $data = [], int $statusCode = 200): Response
    {
        $content = $this->templates->render($template, $data);
        return new Response($content, $statusCode, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    protected function json(array $data, int $statusCode = 200): Response
    {
        return Response::json($data, $statusCode);
    }
}
