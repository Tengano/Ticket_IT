<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900">Đặt lại mật khẩu</h2>
        <p class="mt-1 text-sm text-slate-500">Chọn mật khẩu mới cho tài khoản của bạn.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="field-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="username" class="field">
            <x-input-error :messages="$errors->get('email')" class="field-error" />
        </div>

        <div>
            <label for="password" class="field-label">Mật khẩu mới</label>
            <input id="password" type="password" name="password" required autocomplete="new-password" class="field">
            <x-input-error :messages="$errors->get('password')" class="field-error" />
        </div>

        <div>
            <label for="password_confirmation" class="field-label">Nhập lại mật khẩu mới</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="field">
            <x-input-error :messages="$errors->get('password_confirmation')" class="field-error" />
        </div>

        <button type="submit" class="btn-primary w-full">Đặt lại mật khẩu</button>
    </form>
</x-guest-layout>
