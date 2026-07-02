<?php

namespace App\Libraries;

use CodeIgniter\Debug\ExceptionHandlerInterface;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * Custom SYNAPSE exception handler.
 *
 * Routes 404s through app/Views/errors/404.php and all other HTTP exceptions
 * through app/Views/errors/exception.php — both branded, context-aware pages
 * that never leak stack traces to the user.
 *
 * Stack traces are still logged via the framework's logger (controlled by
 * Config\Exceptions::$log).
 *
 * Implements ExceptionHandlerInterface directly — the framework's default
 * ExceptionHandler class is `final` so we cannot extend it.
 */
class AppExceptionHandler implements ExceptionHandlerInterface
{
    public function handle(
        Throwable $exception,
        RequestInterface $request,
        ResponseInterface $response,
        int $statusCode,
        int $exitCode,
    ): void {
        // CLI mode — keep simple text output
        if ($request instanceof CLIRequest || is_cli()) {
            $this->renderCli($exception, $statusCode, $exitCode);
            return;
        }

        // JSON / AJAX responses — render a minimal JSON envelope so API
        // consumers still receive machine-readable payloads.
        $accept = $request instanceof IncomingRequest
            ? $request->getHeaderLine('accept')
            : ($_SERVER['HTTP_ACCEPT'] ?? '');
        if (is_string($accept) && stripos($accept, 'application/json') !== false) {
            $this->renderJson($exception, $response, $statusCode, $exitCode);
            return;
        }

        // HTML responses — route through our branded error views.
        $viewFile = $exception instanceof PageNotFoundException
            ? 'errors/404.php'
            : 'errors/exception.php';

        try {
            $body = view($viewFile, ['exception' => $exception]);
        } catch (\Throwable $renderError) {
            // If the error view itself fails, fall back to a minimal safe page
            // so we never produce a 500 inside a 500.
            $body = $this->fallbackBody($statusCode, $exception);
        }

        try {
            $response->setStatusCode($statusCode);
        } catch (\Throwable) {
            // Workaround for invalid HTTP status codes (matches framework behaviour)
            $statusCode = 500;
            $response->setStatusCode($statusCode);
        }

        if (! headers_sent()) {
            header(
                sprintf(
                    'HTTP/%s %s %s',
                    $request->getProtocolVersion(),
                    $response->getStatusCode(),
                    $response->getReasonPhrase(),
                ),
                true,
                $statusCode,
            );
        }

        $response->setBody($body);
        $response->send();

        if (defined('ENVIRONMENT') && ENVIRONMENT !== 'testing') {
            exit($exitCode);
        }
    }

    private function renderCli(Throwable $exception, int $statusCode, int $exitCode): void
    {
        fwrite(STDERR, sprintf("ERROR [%d]: %s\n", $statusCode, $exception->getMessage()));
        fwrite(STDERR, sprintf("  at %s:%d\n", $exception->getFile(), $exception->getLine()));
        if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
            fwrite(STDERR, $exception->getTraceAsString() . "\n");
        }
        exit($exitCode);
    }

    private function renderJson(Throwable $exception, ResponseInterface $response, int $statusCode, int $exitCode): void
    {
        $payload = [
            'status'   => $statusCode,
            'error'    => true,
            'message'  => $exception->getMessage() ?: 'Unexpected error',
        ];

        try {
            $response->setStatusCode($statusCode);
        } catch (\Throwable) {
            $statusCode = 500;
            $response->setStatusCode($statusCode);
            $payload['status'] = 500;
        }

        $response->setHeader('Content-Type', 'application/json; charset=UTF-8')
                 ->setBody(json_encode($payload, JSON_UNESCAPED_SLASHES));
        $response->send();

        if (defined('ENVIRONMENT') && ENVIRONMENT !== 'testing') {
            exit($exitCode);
        }
    }

    /**
     * Minimal HTML body used only if the error view itself blows up.
     * Never include sensitive information — only the status and a generic
     * hint to retry.
     */
    private function fallbackBody(int $statusCode, Throwable $exception): string
    {
        $code = (int) $statusCode ?: 500;
        $title = $code === 404 ? 'Page not found' : 'Unexpected error';
        $message = $code === 404
            ? 'The page you requested could not be found.'
            : 'SYNAPSE encountered an unexpected error. Please try again in a moment.';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{$code} · {$title}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
               background: #F9FAFB; color: #1F2937; min-height: 100vh;
               display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .box { max-width: 480px; background: white; border: 1px solid #E5E7EB;
               border-radius: 0.75rem; padding: 2rem; text-align: center; }
        h1 { margin: 0 0 0.5rem; font-size: 1.5rem; color: #111827; }
        p { margin: 0 0 1.5rem; color: #6B7280; line-height: 1.5; }
        a { display: inline-block; padding: 0.6rem 1.2rem; background: #4F46E5;
            color: white; border-radius: 0.5rem; text-decoration: none;
            font-weight: 600; font-size: 0.875rem; }
        .code { font-size: 0.75rem; color: #9CA3AF; margin-top: 1rem; font-family: monospace; }
    </style>
</head>
<body>
    <div class="box">
        <h1>{$title}</h1>
        <p>{$message}</p>
        <a href="/">Return to SYNAPSE</a>
        <p class="code">Reference: {$code}</p>
    </div>
</body>
</html>
HTML;
    }
}