@props(['label', 'value', 'hint' => null, 'tone' => 'indigo', 'href' => null, 'icon' => 'ticket'])

@php
    $tones = [
        'indigo' => 'bg-indigo-50 text-indigo-600 ring-indigo-100',
        'sky' => 'bg-sky-50 text-sky-600 ring-sky-100',
        'emerald' => 'bg-emerald-50 text-emerald-600 ring-emerald-100',
        'rose' => 'bg-rose-50 text-rose-600 ring-rose-100',
        'amber' => 'bg-amber-50 text-amber-600 ring-amber-100',
    ];

    $toneClass = $tones[$tone] ?? $tones['indigo'];
@endphp

@if ($href)
    <a href="{{ $href }}" class="card block p-4 transition hover:-translate-y-0.5 hover:shadow-md">
@else
    <div class="card p-4">
@endif
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-xs font-semibold tracking-wide text-slate-500 uppercase">{{ $label }}</p>
            <p class="mt-2 text-2xl font-bold text-slate-900">{{ $value }}</p>
            @if ($hint)
                <p class="mt-1 text-xs text-slate-400">{{ $hint }}</p>
            @endif
        </div>
        <span class="flex h-10 w-10 items-center justify-center rounded-xl ring-1 {{ $toneClass }}">
            <x-icon :name="$icon" class="h-5 w-5" />
        </span>
    </div>
@if ($href)
    </a>
@else
    </div>
@endif

