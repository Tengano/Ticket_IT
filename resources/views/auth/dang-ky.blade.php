<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900">Đăng ký tài khoản</h2>
        <p class="mt-1 text-sm text-slate-500">Tài khoản mới có vai trò Người yêu cầu và có thể gửi ticket hỗ trợ.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <label for="ten" class="field-label">Họ và tên</label>
            <input id="ten" type="text" name="ten" value="{{ old('name') }}" required autofocus autocomplete="name" class="field" placeholder="Nguyễn Văn A">
            <x-input-error :messages="$errors->get('name')" class="field-error" />
        </div>

        <div>
            <label for="email" class="field-label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username" class="field" placeholder="username@ticket.it">
            <x-input-error :messages="$errors->get('email')" class="field-error" />
        </div>

        <div>
            <label for="password" class="field-label">Mật khẩu</label>
            <input id="password" type="password" name="password" required autocomplete="new-password" class="field" placeholder="Tối thiểu 8 ký tự">
            <x-input-error :messages="$errors->get('password')" class="field-error" />
        </div>

        <div>
            <label for="password_confirmation" class="field-label">Nhập lại mật khẩu</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="field" placeholder="Nhập lại mật khẩu">
            <x-input-error :messages="$errors->get('password_confirmation')" class="field-error" />
        </div>

        <button type="submit" class="btn-primary w-full">Tạo tài khoản</button>

        <p class="text-center text-sm text-slate-500">
            Đã có tài khoản?
            <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-700">Đăng nhập</a>
        </p>
    </form>
</x-guest-layout>
