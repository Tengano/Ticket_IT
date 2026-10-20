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
        <div class="grid min-h-screen lg:grid-cols-2">
            <section class="relative hidden flex-col justify-between overflow-hidden bg-gradient-to-br from-indigo-700 via-indigo-800 to-slate-900 p-10 text-white lg:flex">
                <div class="absolute -top-24 -right-24 h-72 w-72 rounded-full bg-indigo-500/30 blur-3xl"></div>
                <div class="absolute -bottom-32 -left-20 h-80 w-80 rounded-full bg-sky-500/20 blur-3xl"></div>

                <div class="relative">
                    <div class="flex items-center gap-3">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/20">
                            <x-icon name="ticket" class="h-6 w-6" />
                        </span>
                        <div>
                            <p class="text-base font-bold">IT Helpdesk</p>
                            <p class="text-xs text-indigo-200">Hệ thống ticket hỗ trợ nội bộ</p>
                        </div>
                    </div>

                    <h1 class="mt-14 max-w-md text-3xl font-bold leading-tight">
                        Tiếp nhận – phân loại – xử lý yêu cầu IT trong một quy trình duy nhất.
                    </h1>
                    <p class="mt-4 max-w-md text-sm leading-relaxed text-indigo-100">
                        Theo dõi tiến độ, trao đổi với kỹ thuật viên và đo lường thời gian phản hồi theo cam kết SLA.
                    </p>

                    <ul class="mt-8 space-y-3 text-sm text-indigo-100">
                        <li class="flex items-start gap-2">
                            <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-300" />
                            Một ticket cho mỗi sự cố, không thất lạc qua chat/điện thoại.
                        </li>
                        <li class="flex items-start gap-2">
                            <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-300" />
                            Phân công đúng nhóm xử lý theo danh mục sự cố.
                        </li>
                        <li class="flex items-start gap-2">
                            <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-300" />
                            Ghi chú nội bộ và lịch sử thay đổi minh bạch.
                        </li>
                    </ul>
                </div>

                <p class="relative text-xs text-indigo-300">© {{ now()->year }} IT Helpdesk · Vận hành theo ITIL rút gọn</p>
            </section>

            <section class="flex items-center justify-center px-4 py-10 sm:px-8">
                <div class="w-full max-w-md">
                    <div class="mb-6 flex items-center gap-3 lg:hidden">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-white">
                            <x-icon name="ticket" class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="text-sm font-bold text-slate-900">IT Helpdesk</p>
                            <p class="text-xs text-slate-500">Hệ thống ticket hỗ trợ</p>
                        </div>
                    </div>

                    <div class="card p-6 sm:p-8">
                        {{ $slot }}
                    </div>

                    <p class="mt-6 text-center text-xs text-slate-400">
                        {{ config('app.name', 'IT Helpdesk') }} · Hỗ trợ kỹ thuật nội bộ
                    </p>
                </div>
            </section>
        </div>
    </body>
</html>
