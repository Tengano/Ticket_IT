<section>
    <header>
        <h2 class="text-lg font-semibold text-slate-800">Thông tin cá nhân</h2>
        <p class="mt-1 text-sm text-slate-500">Cập nhật họ tên và địa chỉ email dùng để nhận thông báo.</p>
    </header>

    <form method="post" action="{{ route('ho-so.update') }}" class="mt-6 space-y-4">
        @csrf
        @method('patch')

        <div>
            <label for="ten" class="field-label">Họ và tên</label>
            <input id="ten" name="ten" type="text" value="{{ old('ten', $user->ten) }}" required autofocus autocomplete="name" class="field">
            <x-input-error class="field-error" :messages="$errors->get('name')" />
        </div>

        <div>
            <label for="email" class="field-label">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username" class="field">
            <x-input-error class="field-error" :messages="$errors->get('email')" />
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="btn-primary">Lưu thay đổi</button>

            @if (session('status') === 'profile-updated')
                <span x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)" class="text-sm text-emerald-600">
                    Đã lưu thông tin.
                </span>
            @endif
        </div>
    </form>
</section>
