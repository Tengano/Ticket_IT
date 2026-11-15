<?php

namespace App\Http\Controllers;

use App\Http\Requests\CapNhatHoSoRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class HoSoController extends Controller
{
    /**
     * Hiển thị biểu mẫu hồ sơ cá nhân.
     */
    public function edit(Request $request): View
    {
        return view('ho-so.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Cập nhật thông tin hồ sơ cá nhân.
     */
    public function update(CapNhatHoSoRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('ho-so.edit')->with('status', 'profile-updated');
    }

    /**
     * Xoá tài khoản của chính người dùng.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
