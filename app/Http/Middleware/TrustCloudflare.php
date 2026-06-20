<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrustCloudflare
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('trustedproxy.cloudflare.enabled')) {
            return $next($request);
        }

        $connectingIp = $request->header('CF-Connecting-IP');
        $remoteAddress = $request->server->get('REMOTE_ADDR');

        if (
            ! $connectingIp
            || ! is_string($remoteAddress)
            || ! $this->isTrustedProxy($remoteAddress, config('trustedproxy.proxies', []))
            || ! filter_var($connectingIp, FILTER_VALIDATE_IP)
        ) {
            return $next($request);
        }

        $request->server->set('REMOTE_ADDR', $connectingIp);

        return $next($request);
    }

    /**
     * Determine whether the remote address is one of the configured trusted proxies.
     *
     * @param  array<int, string>  $trustedProxies
     */
    private function isTrustedProxy(string $remoteAddress, array $trustedProxies): bool
    {
        foreach ($trustedProxies as $trustedProxy) {
            if ($trustedProxy === $remoteAddress || $this->ipMatchesCidr($remoteAddress, $trustedProxy)) {
                return true;
            }
        }

        return false;
    }

    private function ipMatchesCidr(string $ip, string $cidr): bool
    {
        if (! str_contains($cidr, '/')) {
            return false;
        }

        [$subnet, $prefixLength] = explode('/', $cidr, 2);

        if (! is_numeric($prefixLength)) {
            return false;
        }

        $ipBinary = @inet_pton($ip);
        $subnetBinary = @inet_pton($subnet);

        if ($ipBinary === false || $subnetBinary === false || strlen($ipBinary) !== strlen($subnetBinary)) {
            return false;
        }

        $prefixLength = (int) $prefixLength;
        $maxBits = strlen($ipBinary) * 8;

        if ($prefixLength < 0 || $prefixLength > $maxBits) {
            return false;
        }

        $fullBytes = intdiv($prefixLength, 8);
        $remainingBits = $prefixLength % 8;

        if ($fullBytes > 0 && substr($ipBinary, 0, $fullBytes) !== substr($subnetBinary, 0, $fullBytes)) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = ~(255 >> $remainingBits) & 255;

        return (ord($ipBinary[$fullBytes]) & $mask) === (ord($subnetBinary[$fullBytes]) & $mask);
    }
}
