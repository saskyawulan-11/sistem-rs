<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        // If user hits /login directly while already authenticated, log them out and show login page
        if ($request->isMethod('get') && $request->routeIs('login')) {
            foreach ($guards as $guard) {
                if (Auth::guard($guard)->check()) {
                    Auth::guard($guard)->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }
            }
            return $next($request);
        }

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // Allow forcing logout to reach login page (e.g., /login?force=1)
                if ($request->boolean('force') || $request->boolean('logout')) {
                    Auth::guard($guard)->logout();
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                    continue;
                }

                $user = Auth::guard($guard)->user();
                
                // Redirect based on role
                return $this->redirectBasedOnRole($user);
            }
        }

        return $next($request);
    }

    /**
     * Redirect user based on their role
     */
    private function redirectBasedOnRole($user)
    {
        return match($user->role) {
            'admin' => redirect('/dashboard'),
            'dokter' => redirect('/dokter/dashboard'),
            'perawat' => redirect('/perawat/dashboard'),
            'pasien' => redirect('/pasien/dashboard'),
            default => redirect('/dashboard'),
        };
    }
}
