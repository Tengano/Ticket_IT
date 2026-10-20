@props(['title', 'breadcrumbs' => []])

<div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        @if (count($breadcrumbs))
            <nav class="mb-1 flex items-center gap-1 text-xs text-slate-500" aria-label="Breadcrumb">
                @foreach ($breadcrumbs as $crumb)
                    @if (! empty($crumb['url']))
                        <a href="{{ $crumb['url'] }}" class="transition hover:text-indigo-600">{{ $crumb['label'] }}</a>
                    @else
                        <span class="font-medium text-slate-700">{{ $crumb['label'] }}</span>
                    @endif

                    @unless ($loop->last)
                        <span class="text-slate-300">/</span>
                    @endunless
                @endforeach
            </nav>
        @endif

        <h1 class="text-xl font-bold text-slate-900 sm:text-2xl">{{ $title }}</h1>

        @isset($description)
            <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
        @endisset
    </div>

    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
