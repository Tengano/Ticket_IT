<x-app-layout>
    <x-page-header title="Tổng quan" description="Tổng quan phiếu và tiến độ xử lý yêu cầu hỗ trợ IT." :breadcrumbs="[['label' => 'Tổng quan']]">
        <x-slot name="actions">
            <a href="{{ route('phieu.create') }}" class="btn-primary">
                <x-icon name="plus" class="h-4 w-4" /> Tạo phiếu
            </a>
        </x-slot>
    </x-page-header>

    @if (auth()->user()->isStaff())
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Phiếu đang mở" :value="$stats['open']" icon="ticket" tone="indigo" href="{{ route('phieu.index') }}" hint="Chưa giải quyết / chưa đóng" />
            <x-stat-card label="Chưa phân công" :value="$stats['unassigned']" icon="inbox" tone="amber" href="{{ route('phieu.index', ['nguoi_xu_ly' => 'unassigned']) }}" hint="Cần gán kỹ thuật viên" />
            <x-stat-card label="Được giao cho tôi" :value="$stats['mine']" icon="user" tone="sky" href="{{ route('phieu.index', ['nguoi_xu_ly' => 'me']) }}" hint="Phiếu đang mở" />
            <x-stat-card label="Ưu tiên P1" :value="$stats['critical']" icon="shield" tone="rose" href="{{ route('phieu.index', ['uu_tien' => 'P1']) }}" hint="Sự cố nghiêm trọng" />
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card label="Phiếu đang xử lý" :value="$stats['open']" icon="ticket" tone="indigo" href="{{ route('phieu.index') }}" hint="Chưa giải quyết / chưa đóng" />
            <x-stat-card label="Đã giải quyết" :value="$stats['da_giai_quyet']" icon="check" tone="emerald" href="{{ route('phieu.index', ['trang_thai' => 'da_giai_quyet']) }}" hint="Chờ xác nhận / đóng" />
            <x-stat-card label="Đã đóng" :value="$stats['da_dong']" icon="lock" tone="sky" href="{{ route('phieu.index', ['trang_thai' => 'da_dong']) }}" hint="Đã hoàn tất" />
            <x-stat-card label="Tổng phiếu của tôi" :value="$stats['total']" icon="user" tone="amber" hint="Toàn bộ thời gian" />
        </div>
    @endif

    <div class="mt-6 grid gap-6 {{ auth()->user()->isStaff() ? 'lg:grid-cols-3' : '' }}">
        <div class="card overflow-hidden {{ auth()->user()->isStaff() ? 'lg:col-span-2' : '' }}">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-800">PhieuYeuCau gần đây</h2>
                    <p class="text-xs text-slate-400">8 ticket được cập nhật mới nhất</p>
                </div>
                <a href="{{ route('phieu.index') }}" class="text-xs font-medium text-indigo-600 hover:underline">Xem tất cả</a>
            </div>

            @if ($recentTickets->isEmpty())
                <x-empty-state title="Chưa có ticket nào" description="Tạo phiếu đầu tiên để bắt đầu theo dõi yêu cầu hỗ trợ IT.">
                    <x-slot name="action">
                        <a href="{{ route('phieu.create') }}" class="btn-primary">Tạo phiếu</a>
                    </x-slot>
                </x-empty-state>
            @else
                <div class="overflow-x-auto">
                    <table class="table-base">
                        <thead>
                            <tr>
                                <th>Mã / Tiêu đề</th>
                                <th>Người gửi</th>
                                <th>Ưu tiên</th>
                                <th>Trạng thái</th>
                                <th>Cập nhật</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($recentTickets as $ticket)
                                <tr>
                                    <td>
                                        <a href="{{ route('phieu.show', $ticket) }}" class="font-mono text-xs text-indigo-600 hover:underline">{{ $ticket->ma }}</a>
                                        <p class="mt-0.5 max-w-xs truncate font-medium text-slate-800">{{ $ticket->tieu_de }}</p>
                                        <p class="text-xs text-slate-400">{{ $ticket->category?->ten ?? 'Chưa phân loại' }}</p>
                                    </td>
                                    <td class="text-sm">{{ $ticket->requester->ten }}</td>
                                    <td><x-priority-badge :priority="$ticket->uu_tien" /></td>
                                    <td><x-status-badge :status="$ticket->trang_thai" /></td>
                                    <td class="text-xs text-slate-500">{{ $ticket->updated_at->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        @if (auth()->user()->isStaff())
            <div class="card overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-semibold text-slate-800">Hàng đợi chưa phân công</h2>
                    <p class="text-xs text-slate-400">PhieuYeuCau đang chờ kỹ thuật viên nhận xử lý</p>
                </div>

                @if ($queue->isEmpty())
                    <x-empty-state title="Hàng đợi trống" description="Tất cả ticket đang mở đã có người phụ trách." />
                @else
                    <ul class="divide-y divide-slate-100">
                        @foreach ($queue as $ticket)
                            <li>
                                <a href="{{ route('phieu.show', $ticket) }}" class="flex items-start gap-3 px-5 py-3 transition hover:bg-slate-50">
                                    <x-priority-badge :priority="$ticket->uu_tien" />
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate text-sm font-medium text-slate-800">{{ $ticket->tieu_de }}</span>
                                        <span class="mt-0.5 flex items-center gap-2 text-xs text-slate-400">
                                            <span class="font-mono">{{ $ticket->ma }}</span>
                                            <span>·</span>
                                            <span>{{ $ticket->created_at->diffForHumans() }}</span>
                                        </span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
