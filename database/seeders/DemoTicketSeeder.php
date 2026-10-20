<?php

namespace Database\Seeders;

use App\Enums\TicketEventType;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Tickets\TicketService;
use Illuminate\Database\Seeder;

class DemoTicketSeeder extends Seeder
{
    public function run(TicketService $tickets): void
    {
        $categories = Category::query()->pluck('id', 'code');
        $teams = Team::query()->pluck('id', 'code');
        $users = User::query()->pluck('id', 'email');

        $samples = [
            [
                'subject' => 'Không in được từ máy 305',
                'description' => 'Máy in HP LaserJet ở phòng 305 không nhận lệnh in, đèn báo lỗi nhấp nháy. Đã thử in từ 2 máy tính khác nhau.',
                'category' => 'printer-not-printing', 'impact' => 2, 'urgency' => 2,
                'requester' => 'requester@ticketit.test', 'assignee' => 'agent2@ticketit.test',
                'team' => 'devices', 'progress' => 'in_progress', 'days_ago' => 1,
            ],
            [
                'subject' => 'Sập mạng toàn bộ phòng 305',
                'description' => 'Cả phòng 305 mất kết nối mạng và wifi từ 8h sáng. Khoảng 25 người không truy cập được ERP và email.',
                'category' => 'network-down', 'impact' => 3, 'urgency' => 3,
                'requester' => 'requester@ticketit.test', 'assignee' => 'lead@ticketit.test',
                'team' => 'infrastructure', 'progress' => 'in_progress', 'days_ago' => 0,
            ],
            [
                'subject' => 'Quên mật khẩu tài khoản LMS',
                'description' => 'Tôi quên mật khẩu đăng nhập LMS, cần cấp lại để kịp nhập điểm cho lớp CNTT K12.',
                'category' => 'account-forgot', 'impact' => 1, 'urgency' => 2,
                'requester' => 'requester2@ticketit.test', 'assignee' => 'agent1@ticketit.test',
                'team' => 'helpdesk', 'progress' => 'closed', 'days_ago' => 4,
            ],
            [
                'subject' => 'Email không gửi được cho sinh viên',
                'description' => 'Khi gửi email thông báo lịch thi, hệ thống trả về lỗi Message not delivered. Đã thử gửi lại nhiều lần.',
                'category' => 'email-send', 'impact' => 2, 'urgency' => 2,
                'requester' => 'requester2@ticketit.test', 'assignee' => null,
                'team' => 'applications', 'progress' => 'new', 'days_ago' => 0,
            ],
            [
                'subject' => 'Lỗi đăng nhập ERP phân hệ kế toán',
                'description' => 'Tài khoản kế toán báo Invalid company code sau khi đổi mật khẩu Windows. Cần hỗ trợ trước 14h để chốt số liệu.',
                'category' => 'erp-login', 'impact' => 2, 'urgency' => 3,
                'requester' => 'requester2@ticketit.test', 'assignee' => null,
                'team' => 'applications', 'progress' => 'new', 'days_ago' => 0,
            ],
            [
                'subject' => 'Máy tính phòng 201 không lên nguồn',
                'description' => 'Máy tính để bàn không lên nguồn, quạt nguồn không quay. Đã kiểm tra dây điện và ổ cắm.',
                'category' => 'hardware-power', 'impact' => 1, 'urgency' => 3,
                'requester' => 'requester2@ticketit.test', 'assignee' => 'agent2@ticketit.test',
                'team' => 'devices', 'progress' => 'resolved', 'days_ago' => 3,
            ],
            [
                'subject' => 'Email lừa đảo mạo danh phòng đào tạo',
                'description' => 'Tôi nhận được email yêu cầu đăng nhập lại cổng thông tin bằng link lạ, nghi ngờ là phishing. Nhờ IT kiểm tra.',
                'category' => 'security-phishing', 'impact' => 2, 'urgency' => 2,
                'requester' => 'director@ticketit.test', 'assignee' => null,
                'team' => 'security', 'progress' => 'new', 'days_ago' => 0,
            ],
        ];

        foreach ($samples as $sample) {
            if (Ticket::query()->where('subject', $sample['subject'])->exists()) {
                continue;
            }

            $requester = User::query()->findOrFail($users[$sample['requester']]);
            $assignee = $sample['assignee'] !== null ? User::query()->find($users[$sample['assignee']]) : null;

            $ticket = $tickets->create($requester, [
                'subject' => $sample['subject'],
                'description' => $sample['description'],
                'category_id' => $categories[$sample['category']] ?? null,
                'impact' => $sample['impact'],
                'urgency' => $sample['urgency'],
                'channel' => 'web',
            ]);

            $ticket->forceFill([
                'created_at' => now()->subDays($sample['days_ago'])->subMinutes(37),
                'updated_at' => now()->subDays($sample['days_ago'])->subMinutes(37),
            ])->save();

            if ($assignee !== null) {
                $team = isset($teams[$sample['team']]) ? Team::query()->find($teams[$sample['team']]) : null;
                $ticket = $tickets->assign($ticket, $assignee, $team, $assignee);
            }

            if ($sample['progress'] !== 'new') {
                $ticket = $tickets->changeStatus($ticket, TicketStatus::InProgress, $assignee ?? $requester);
                $ticket->update(['first_responded_at' => now()->subDays($sample['days_ago'])->subMinutes(20)]);

                $this->seedConversation($tickets, $ticket, $requester, $assignee);
            }

            if (in_array($sample['progress'], ['resolved', 'closed'], true)) {
                $ticket = $tickets->changeStatus($ticket, TicketStatus::Resolved, $assignee ?? $requester, 'Đã khắc phục sự cố');
            }

            if ($sample['progress'] === 'closed') {
                $tickets->changeStatus($ticket, TicketStatus::Closed, $assignee ?? $requester, 'Người dùng xác nhận đã ổn');
            }
        }
    }

    private function seedConversation(TicketService $tickets, Ticket $ticket, User $requester, ?User $assignee): void
    {
        $staff = $assignee ?? User::query()->where('email', 'agent1@ticketit.test')->first();

        if ($staff === null) {
            return;
        }

        $ticket->comments()->create([
            'user_id' => $requester->id,
            'body' => 'Nhờ IT kiểm tra giúp, việc này đang ảnh hưởng đến công việc trong ngày.',
            'type' => 'public',
        ]);

        $reply = $ticket->comments()->create([
            'user_id' => $staff->id,
            'body' => 'Chào bạn, IT đã tiếp nhận và đang kiểm tra. Mình sẽ cập nhật tiến độ trong ticket này.',
            'type' => 'public',
            'is_first_response' => true,
        ]);

        $tickets->recordEvent($ticket, $staff, TicketEventType::Commented, null, null, ['comment_id' => $reply->id]);

        $note = $ticket->comments()->create([
            'user_id' => $staff->id,
            'body' => 'Đã ghi nhận thông tin từ người dùng, kiểm tra log thiết bị trước khi hẹn lịch xuống phòng.',
            'type' => 'internal',
        ]);

        $tickets->recordEvent($ticket, $staff, TicketEventType::InternalNoteAdded, null, null, ['comment_id' => $note->id]);
    }
}
