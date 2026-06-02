<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Cek bahwa user terautentikasi memiliki salah satu role yang diizinkan.
     * Pemakaian: ->middleware('role:admin,analis')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user || ! $user->is_active) {
            abort(403, 'Akun Anda tidak aktif atau tidak terotentikasi.');
        }
        if ($roles && ! in_array($user->role, $roles, true)) {
            abort(403, 'Anda tidak memiliki hak akses untuk halaman ini.');
        }
        return $next($request);
    }
}
