<section>
    <header>
        <h2 class="text-lg font-semibold text-rose-700">Xoá tài khoản</h2>
        <p class="mt-1 text-sm text-slate-600">
            Khi tài khoản bị xoá, toàn bộ dữ liệu liên quan sẽ bị xoá vĩnh viễn. Vui lòng sao lưu thông tin cần thiết trước khi tiếp tục.
        </p>
    </header>

    <form method="post" action="{{ route('ho-so.destroy') }}" class="mt-5 space-y-3"
          onsubmit="return confirm('Bạn chắc chắn muốn xoá tài khoản? Hành động này không thể hoàn tác.')">
        @csrf
        @method('delete')

        <div class="max-w-sm">
            <label for="delete_password" class="field-label">Nhập mật khẩu để xác nhận</label>
            <input id="delete_password" name="password" type="password" class="field" placeholder="Mật khẩu hiện tại">
            <x-input-error :messages="$errors->userDeletion->get('password')" class="field-error" />
        </div>

        <button type="submit" class="btn-danger">Xoá tài khoản</button>
    </form>
</section>
