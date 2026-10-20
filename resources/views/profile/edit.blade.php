<x-app-layout>
    <x-page-header title="Hồ sơ cá nhân" description="Cập nhật thông tin liên hệ và mật khẩu đăng nhập của bạn."
        :breadcrumbs="[['label' => 'Dashboard', 'url' => route('dashboard')], ['label' => 'Hồ sơ cá nhân']]" />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card p-5 sm:p-6">
                @include('profile.partials.update-profile-information-form')
            </div>

            <div class="card p-5 sm:p-6">
                @include('profile.partials.update-password-form')
            </div>

            <div class="card border-rose-200 p-5 sm:p-6">
                @include('profile.partials.delete-user-form')
            </div>
        </div>

        <aside class="space-y-4">
            <div class="card p-5">
                <div class="flex items-center gap-3">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-700">
                        {{ $user->initials() }}
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-slate-800">{{ $user->name }}</p>
                        <p class="text-xs text-slate-500">{{ $user->roleLabel() }}</p>
                    </div>
                </div>

                <dl class="mt-4 space-y-2 border-t border-slate-100 pt-4 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Phòng ban</dt>
                        <dd class="font-medium text-slate-800">{{ $user->department?->name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Vị trí</dt>
                        <dd class="font-medium text-slate-800">{{ $user->location?->fullName() ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-slate-500">Nhóm xử lý</dt>
                        <dd class="text-right font-medium text-slate-800">{{ $user->teams->pluck('name')->join(', ') ?: '—' }}</dd>
                    </div>
                </dl>
            </div>
        </aside>
    </div>
</x-app-layout>
