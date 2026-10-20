@props(['priority', 'withLabel' => false])

<span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $priority->color() }}"
      title="{{ $priority->label() }}">
    {{ $priority->shortLabel() }}
    @if ($withLabel)
        <span class="font-normal">– {{ $priority->label() }}</span>
    @endif
</span>
