<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Seguridad de frames (Evita Clickjacking)
        $response->headers->set('X-Frame-Options', 'DENY');
        
        // Protección contra XSS
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        // Prevenir "sniffing" de tipos MIME
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        
        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // HSTS (Solo si es HTTPS)
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Eliminar headers que revelan tecnología
        header_remove('X-Powered-By');

        return $response;
    }
}
