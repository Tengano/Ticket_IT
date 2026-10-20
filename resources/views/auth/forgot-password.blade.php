<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900">Quên mật khẩu</h2>
        <p class="mt-1 text-sm text-slate-500">Nhập email đã đăng ký, hệ thống sẽ gửi liên kết đặt lại mật khẩu.</p>
    </div>

    <x-auth-session-status class="mb-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="field-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="field" placeholder="ten.ban@ticketit.test">
            <x-input-error :messages="$errors->get('email')" class="field-error" />
        </div>

        <button type="submit" class="btn-primary w-full">Gửi liên kết đặt lại mật khẩu</button>

        <p class="text-center text-sm text-slate-500">
            <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Quay lại đăng nhập</a>
        </p>
    </form>
</x-guest-layout>
