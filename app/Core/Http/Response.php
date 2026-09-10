<?php

namespace Benchero\Core\Http;

class Response
{
    private int $statusCode;
    private array $headers = [];
    private string $content;

    public function __construct(string $content = '', int $statusCode = 200, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);
            foreach ($this->headers as $name => $value) {
                header("{$name}: {$value}", false);
            }
        }
        echo $this->content;
    }

    public static function json(array $data, int $statusCode = 200): self
    {
        $response = new self(json_encode($data), $statusCode);
        $response->setHeader('Content-Type', 'application/json; charset=utf-8');
        return $response;
    }

    public static function view(string $template, array $data = [], int $statusCode = 200): self
    {
        $engine = new \League\Plates\Engine(dirname(__DIR__, 3) . '/views');
        $engine->registerFunction('url', 'url');
        $content = $engine->render($template, $data);
        return new self($content, $statusCode, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    public static function redirect(string $url, int $statusCode = 302): self
    {
        if (strpos($url, '/') === 0 && !str_starts_with($url, '//')) {
            $url = url($url);
        }
        $response = new self('', $statusCode);
        $response->setHeader('Location', $url);
        return $response;
    }
}
