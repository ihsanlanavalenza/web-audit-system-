<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuditorMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (!$user instanceof User || !$user->isAuditor()) {
            abort(403, 'Akses hanya untuk Auditor.');
        }

        return $next($request);
    }
}
