<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class UseRequestHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->headers->get('x-forwarded-host') ?: $request->getHttpHost();
        $proto = strtolower((string) ($request->headers->get('x-forwarded-proto') ?: $request->getScheme()));

        if (! in_array($proto, ['http', 'https'], true)) {
            $proto = $request->isSecure() ? 'https' : 'http';
        }

        if (is_string($host) && $host !== '') {
            $host = trim(explode(',', $host)[0]);
            $root = $proto.'://'.$host;

            URL::forceRootUrl($root);
            URL::forceScheme($proto);
            config(['app.url' => $root]);

            if ($proto === 'https') {
                $request->server->set('HTTPS', 'on');
                config([
                    'session.secure' => true,
                    'session.same_site' => 'none',
                ]);
            }
        }

        $response = $next($request);

        return $this->stripLoopbackUrls($response);
    }

    protected function stripLoopbackUrls(Response $response): Response
    {
        $loopbacks = [
            'https://127.0.0.1:8000',
            'http://127.0.0.1:8000',
            'https://127.0.0.1',
            'http://127.0.0.1',
            'http://0.0.0.0:8000',
            'https://localhost:8000',
            'http://localhost:8000',
            'http://localhost',
        ];

        if ($response->headers->has('Location')) {
            $location = (string) $response->headers->get('Location');
            $stripped = str_replace($loopbacks, '', $location);
            if ($stripped === '' || $stripped === $location && str_contains($location, '127.0.0.1')) {
                $stripped = preg_replace('#^https?://(?:127\.0\.0\.1|localhost|0\.0\.0\.0)(?::\d+)?#', '', $location) ?: '/';
            }
            if ($stripped === '') {
                $stripped = '/';
            }
            $response->headers->set('Location', $stripped);
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        if (str_contains($contentType, 'text/html') && method_exists($response, 'getContent')) {
            $html = $response->getContent();
            if (is_string($html) && $html !== '') {
                $response->setContent(str_replace($loopbacks, '', $html));
            }
        }

        return $response;
    }
}
