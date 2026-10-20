<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900">Xác nhận mật khẩu</h2>
        <p class="mt-1 text-sm text-slate-500">Vì lý do bảo mật, vui lòng nhập lại mật khẩu để tiếp tục.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf

        <div>
            <label for="password" class="field-label">Mật khẩu</label>
            <input id="password" type="password" name="password" required autocomplete="current-password" class="field">
            <x-input-error :messages="$errors->get('password')" class="field-error" />
        </div>

        <button type="submit" class="btn-primary w-full">Xác nhận</button>
    </form>
</x-guest-layout>
