<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'IT Helpdesk') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-100 font-sans antialiased">
        <div x-data="{ sidebarOpen: false, notificationsOpen: false, profileOpen: false }" class="min-h-screen">
            <div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden" @click="sidebarOpen = false"></div>

            <aside class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-slate-200 bg-white transition-transform duration-200 lg:translate-x-0"
                   :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
                <div class="flex h-16 items-center gap-3 border-b border-slate-100 px-5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 text-white">
                        <x-icon name="ticket" class="h-5 w-5" />
                    </span>
                    <div class="leading-tight">
                        <p class="text-sm font-bold text-slate-900">IT Helpdesk</p>
                        <p class="text-[11px] text-slate-400">Hệ thống ticket hỗ trợ</p>
                    </div>
                </div>

                <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5">
                    <div class="space-y-1">
                        <p class="px-3 pb-1 text-[11px] font-semibold tracking-wider text-slate-400 uppercase">Tổng quan</p>
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'nav-link-active' : '' }}">
                            <x-icon name="dashboard" /> Dashboard
                        </a>
                        <a href="{{ route('tickets.index') }}" class="nav-link {{ request()->routeIs('tickets.index', 'tickets.show') ? 'nav-link-active' : '' }}">
                            <x-icon name="ticket" /> Danh sách ticket
                        </a>
                        <a href="{{ route('tickets.create') }}" class="nav-link {{ request()->routeIs('tickets.create') ? 'nav-link-active' : '' }}">
                            <x-icon name="plus" /> Tạo ticket
                        </a>
                        <a href="{{ route('notifications.index') }}" class="nav-link {{ request()->routeIs('notifications.*') ? 'nav-link-active' : '' }}">
                            <x-icon name="bell" /> Thông báo
                            @if (($notificationCount ?? 0) > 0)
                                <span class="ml-auto rounded-full bg-rose-100 px-2 py-0.5 text-[11px] font-bold text-rose-600">{{ $notificationCount }}</span>
                            @endif
                        </a>
                    </div>

                    @if (auth()->user()->can('user.manage'))
                        <div class="space-y-1">
                            <p class="px-3 pb-1 text-[11px] font-semibold tracking-wider text-slate-400 uppercase">Quản trị</p>
                            <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'nav-link-active' : '' }}">
                                <x-icon name="users" /> Người dùng
                            </a>
                        </div>
                    @endif

                    @if (auth()->user()->can('catalog.manage'))
                        <div class="space-y-1">
                            <p class="px-3 pb-1 text-[11px] font-semibold tracking-wider text-slate-400 uppercase">Danh mục nền</p>
                            <a href="{{ route('admin.departments.index') }}" class="nav-link {{ request()->routeIs('admin.departments.*') ? 'nav-link-active' : '' }}">
                                <x-icon name="building" /> Phòng ban
                            </a>
                            <a href="{{ route('admin.locations.index') }}" class="nav-link {{ request()->routeIs('admin.locations.*') ? 'nav-link-active' : '' }}">
                                <x-icon name="pin" /> Vị trí
                            </a>
                            <a href="{{ route('admin.teams.index') }}" class="nav-link {{ request()->routeIs('admin.teams.*') ? 'nav-link-active' : '' }}">
                                <x-icon name="users" /> Nhóm xử lý
                            </a>
                            <a href="{{ route('admin.skills.index') }}" class="nav-link {{ request()->routeIs('admin.skills.*') ? 'nav-link-active' : '' }}">
                                <x-icon name="wrench" /> Kỹ năng
                            </a>
                            <a href="{{ route('admin.calendars.index') }}" class="nav-link {{ request()->routeIs('admin.calendars.*') ? 'nav-link-active' : '' }}">
                                <x-icon name="calendar" /> Lịch làm việc
                            </a>
                        </div>
                    @endif
                </nav>

                <div class="border-t border-slate-100 p-3">
                    <div class="flex items-center gap-3 rounded-lg px-2 py-2">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">
                            {{ auth()->user()->initials() }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-slate-500">{{ auth()->user()->roleLabel() }}</p>
                        </div>
                    </div>
                </div>
            </aside>

            <div class="flex min-h-screen flex-col lg:pl-64">
                <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6">
                    <button type="button" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden" @click="sidebarOpen = true" aria-label="Mở menu">
                        <x-icon name="menu" class="h-6 w-6" />
                    </button>

                    <div class="hidden items-center gap-2 text-sm text-slate-500 sm:flex">
                        <x-icon name="ticket" class="h-4 w-4 text-indigo-500" />
                        <span class="font-medium text-slate-700">{{ config('app.name', 'IT Helpdesk') }}</span>
                    </div>

                    <div class="ml-auto flex items-center gap-2">
                        <div class="relative" @click.outside="notificationsOpen = false">
                            <button type="button" class="relative rounded-lg p-2 text-slate-500 transition hover:bg-slate-100" @click="notificationsOpen = ! notificationsOpen" aria-label="Thông báo">
                                <x-icon name="bell" class="h-5 w-5" />
                                @if (($notificationCount ?? 0) > 0)
                                    <span class="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold text-white">
                                        {{ $notificationCount > 9 ? '9+' : $notificationCount }}
                                    </span>
                                @endif
                            </button>

                            <div x-cloak x-show="notificationsOpen" x-transition class="absolute right-0 mt-2 w-80 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                                    <p class="text-sm font-semibold text-slate-800">Thông báo gần đây</p>
                                    <a href="{{ route('notifications.index') }}" class="text-xs font-medium text-indigo-600 hover:underline">Xem tất cả</a>
                                </div>
                                <div class="max-h-80 divide-y divide-slate-100 overflow-y-auto">
                                    @forelse (($recentNotifications ?? collect()) as $notification)
                                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                                            @csrf
                                            <button type="submit" class="flex w-full items-start gap-3 px-4 py-3 text-left transition hover:bg-slate-50">
                                                <span class="mt-1 flex h-2 w-2 shrink-0 rounded-full {{ $notification->read_at ? 'bg-slate-300' : 'bg-indigo-500' }}"></span>
                                                <span class="min-w-0">
                                                    <span class="block truncate text-sm font-medium text-slate-800">{{ $notification->data['title'] ?? 'Thông báo' }}</span>
                                                    <span class="block truncate text-xs text-slate-500">{{ $notification->data['message'] ?? '' }}</span>
                                                    <span class="mt-0.5 block text-[11px] text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
                                                </span>
                                            </button>
                                        </form>
                                    @empty
                                        <p class="px-4 py-6 text-center text-sm text-slate-400">Chưa có thông báo nào.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="relative" @click.outside="profileOpen = false">
                            <button type="button" class="flex items-center gap-2 rounded-lg px-2 py-1.5 transition hover:bg-slate-100" @click="profileOpen = ! profileOpen">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">{{ auth()->user()->initials() }}</span>
                                <span class="hidden text-sm font-medium text-slate-700 sm:block">{{ auth()->user()->name }}</span>
                            </button>

                            <div x-cloak x-show="profileOpen" x-transition class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                                <div class="border-b border-slate-100 px-4 py-3">
                                    <p class="text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</p>
                                    <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
                                </div>
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-2.5 text-sm text-slate-700 transition hover:bg-slate-50">Hồ sơ cá nhân</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-rose-600 transition hover:bg-rose-50">
                                        <x-icon name="logout" class="h-4 w-4" /> Đăng xuất
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </header>

                <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 lg:px-8">
                    @if (session('success'))
                        <div class="mb-4 flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                            <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0" />
                            <span>{{ session('success') }}</span>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-4 flex items-start gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                            <x-icon name="x" class="mt-0.5 h-4 w-4 shrink-0" />
                            <span>{{ session('error') }}</span>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                            <p class="font-semibold">Vui lòng kiểm tra lại thông tin:</p>
                            <ul class="mt-1 list-inside list-disc space-y-0.5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @isset($header)
                        <div class="mb-6">{{ $header }}</div>
                    @endisset

                    {{ $slot }}
                </main>

                <footer class="border-t border-slate-200 bg-white px-4 py-4 text-center text-xs text-slate-400 sm:px-6 lg:px-8">
                    {{ config('app.name', 'IT Helpdesk') }} · {{ now()->year }} · Vận hành theo quy trình ITIL rút gọn
                </footer>
            </div>
        </div>
    </body>
</html>
