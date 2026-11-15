<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900">Đăng nhập hệ thống</h2>
        <p class="mt-1 text-sm text-slate-500">Sử dụng tài khoản nội bộ để gửi và theo dõi yêu cầu hỗ trợ IT.</p>
    </div>

    <x-auth-session-status class="mb-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="field-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="field" placeholder="username@ticket.it">
            <x-input-error :messages="$errors->get('email')" class="field-error" />
        </div>

        <div>
            <label for="password" class="field-label">Mật khẩu</label>
            <input id="password" type="password" name="password" required autocomplete="current-password" class="field" placeholder="••••••••">
            <x-input-error :messages="$errors->get('password')" class="field-error" />
        </div>

        <div class="flex items-center justify-between gap-3">
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                Ghi nhớ đăng nhập
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">Quên mật khẩu?</a>
            @endif
        </div>

        <button type="submit" class="btn-primary w-full">Đăng nhập</button>

        <p class="text-center text-sm text-slate-500">
            Chưa có tài khoản?
            <a href="{{ route('register') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Đăng ký ngay</a>
        </p>
    </form>

</x-guest-layout>
