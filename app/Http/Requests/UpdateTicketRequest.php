<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('ticket')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['nullable', Rule::enum(TicketStatus::class)],
            'priority' => ['nullable', Rule::enum(TicketPriority::class)],
            'assignee_id' => ['nullable', Rule::exists('users', 'id')->where('is_active', true)],
            'assigned_team_id' => ['nullable', Rule::exists('teams', 'id')->where('is_active', true)],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.enum' => 'Trạng thái không hợp lệ.',
            'priority.enum' => 'Mức ưu tiên không hợp lệ.',
            'assignee_id.exists' => 'Kỹ thuật viên được chọn không hợp lệ hoặc đã bị vô hiệu hoá.',
            'assigned_team_id.exists' => 'Nhóm được chọn không hợp lệ.',
        ];
    }
}
