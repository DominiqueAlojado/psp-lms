<?php

namespace Tests\Unit;

use App\Http\Middleware\TrustCloudflare;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class TrustCloudflareTest extends TestCase
{
    public function test_it_ignores_cloudflare_headers_from_untrusted_sources(): void
    {
        config()->set('trustedproxy.cloudflare.enabled', true);
        config()->set('trustedproxy.proxies', ['173.245.48.0/20']);

        $request = Request::create('/', 'GET', server: [
            'REMOTE_ADDR' => '203.0.113.10',
            'HTTP_CF_CONNECTING_IP' => '198.51.100.25',
        ]);

        $middleware = new TrustCloudflare;

        $middleware->handle($request, fn (Request $request): Response => new Response(
            $request->server->get('REMOTE_ADDR')
        ));

        $this->assertSame('203.0.113.10', $request->server->get('REMOTE_ADDR'));
    }

    public function test_it_uses_cloudflare_headers_from_trusted_proxies(): void
    {
        config()->set('trustedproxy.cloudflare.enabled', true);
        config()->set('trustedproxy.proxies', ['173.245.48.0/20']);

        $request = Request::create('/', 'GET', server: [
            'REMOTE_ADDR' => '173.245.48.5',
            'HTTP_CF_CONNECTING_IP' => '198.51.100.25',
        ]);

        $middleware = new TrustCloudflare;

        $middleware->handle($request, fn (Request $request): Response => new Response(
            $request->server->get('REMOTE_ADDR')
        ));

        $this->assertSame('198.51.100.25', $request->server->get('REMOTE_ADDR'));
    }

    public function test_it_ignores_invalid_cloudflare_ip_values(): void
    {
        config()->set('trustedproxy.cloudflare.enabled', true);
        config()->set('trustedproxy.proxies', ['173.245.48.0/20']);

        $request = Request::create('/', 'GET', server: [
            'REMOTE_ADDR' => '173.245.48.5',
            'HTTP_CF_CONNECTING_IP' => 'not-an-ip',
        ]);

        $middleware = new TrustCloudflare;

        $middleware->handle($request, fn (Request $request): Response => new Response(
            $request->server->get('REMOTE_ADDR')
        ));

        $this->assertSame('173.245.48.5', $request->server->get('REMOTE_ADDR'));
    }
}
