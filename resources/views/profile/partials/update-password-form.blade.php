<section>
    <header>
        <h2 class="text-lg font-semibold text-slate-800">Đổi mật khẩu</h2>
        <p class="mt-1 text-sm text-slate-500">Nên dùng mật khẩu dài, có chữ hoa, chữ số và ký tự đặc biệt.</p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-4">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="field-label">Mật khẩu hiện tại</label>
            <input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" class="field">
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="field-error" />
        </div>

        <div>
            <label for="update_password_password" class="field-label">Mật khẩu mới</label>
            <input id="update_password_password" name="password" type="password" autocomplete="new-password" class="field">
            <x-input-error :messages="$errors->updatePassword->get('password')" class="field-error" />
        </div>

        <div>
            <label for="update_password_password_confirmation" class="field-label">Nhập lại mật khẩu mới</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="field">
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="field-error" />
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="btn-primary">Đổi mật khẩu</button>

            @if (session('status') === 'password-updated')
                <span x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)" class="text-sm text-emerald-600">
                    Đã đổi mật khẩu.
                </span>
            @endif
        </div>
    </form>
</section>
