<x-app-layout>
    <x-page-header title="Thông báo" description="Các cập nhật về phiếu bạn gửi, phiếu được phân công và phản hồi mới." :breadcrumbs="[['label' => 'Tổng quan', 'url' => route('tong-quan')], ['label' => 'Thông báo']]">
        <x-slot name="actions">
            @if (auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('thong-bao.doc-het') }}">
                    @csrf
                    <button type="submit" class="btn-secondary">Đánh dấu tất cả đã đọc</button>
                </form>
            @endif
        </x-slot>
    </x-page-header>

    <div class="card overflow-hidden">
        @if ($notifications->isEmpty())
            <x-empty-state title="Chưa có thông báo" description="Khi có phản hồi hoặc thay đổi trạng thái phiếu, thông báo sẽ xuất hiện tại đây." />
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($notifications as $notification)
                    <li class="flex items-start gap-3 px-5 py-4 {{ $notification->read_at ? '' : 'bg-indigo-50/40' }}">
                        <span class="mt-1.5 flex h-2.5 w-2.5 shrink-0 rounded-full {{ $notification->read_at ? 'bg-slate-300' : 'bg-indigo-500' }}"></span>

                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-slate-800">{{ $notification->data['title'] ?? 'Thông báo' }}</p>
                            <p class="mt-0.5 text-sm text-slate-600">{{ $notification->data['message'] ?? '' }}</p>
                            <p class="mt-1 flex flex-wrap items-center gap-2 text-xs text-slate-400">
                                @if (! empty($notification->data['ma_phieu']))
                                    <span class="font-mono">{{ $notification->data['ma_phieu'] }}</span>
                                    <span>·</span>
                                @endif
                                <span>{{ $notification->created_at->format('d/m/Y H:i') }}</span>
                            </p>
                        </div>

                        <form method="POST" action="{{ route('thong-bao.doc', $notification->id) }}" class="shrink-0">
                            @csrf
                            <button type="submit" class="btn-secondary px-3 py-1.5 text-xs">Xem phiếu</button>
                        </form>
                    </li>
                @endforeach
            </ul>

            <div class="border-t border-slate-100 px-5 py-4">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
