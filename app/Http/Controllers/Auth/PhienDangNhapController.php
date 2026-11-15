<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\DangNhapRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PhienDangNhapController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.dang-nhap');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(DangNhapRequest $request): RedirectResponse
    {
        $request->authenticate();

        if (! Auth::user()->dang_hoat_dong) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Tài khoản của bạn đã bị vô hiệu hoá. Vui lòng liên hệ quản trị viên IT.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('tong-quan', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
