<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GuiXacThucEmailController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('tong-quan', absolute: false));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('trang_thai', 'verification-link-sent');
    }
}
