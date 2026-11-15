<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Chặn tài khoản đã bị vô hiệu hoá (dang_hoat_dong = false) truy cập hệ thống.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() !== null && ! $request->user()->dang_hoat_dong) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Tài khoản của bạn đã bị vô hiệu hoá. Vui lòng liên hệ quản trị viên IT.',
            ]);
        }

        return $next($request);
    }
}
