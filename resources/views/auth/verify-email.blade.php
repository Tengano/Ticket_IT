<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900">Xác thực email</h2>
        <p class="mt-1 text-sm text-slate-500">
            Cảm ơn bạn đã đăng ký. Vui lòng kiểm tra email và nhấn liên kết xác thực để sử dụng đầy đủ tính năng.
        </p>
    </div>

    @if (session('status') === 'verification-link-sent')
        <div class="mb-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
            Liên kết xác thực mới đã được gửi tới email của bạn.
        </div>
    @endif

    <div class="flex flex-col gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn-primary w-full">Gửi lại email xác thực</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-secondary w-full">Đăng xuất</button>
        </form>
    </div>
</x-guest-layout>
