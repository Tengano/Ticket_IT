<?php

namespace App\Http\Requests;

use App\Enums\CommentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('comment', $this->route('ticket')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:2', 'max:5000'],
            'type' => ['required', Rule::enum(CommentType::class)],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'max:'.config('helpdesk.attachments.max_kb'),
                'mimes:'.implode(',', config('helpdesk.attachments.mimes')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => 'Vui lòng nhập nội dung phản hồi.',
            'body.min' => 'Nội dung phản hồi quá ngắn.',
            'type.required' => 'Loại phản hồi không hợp lệ.',
            'attachments.*.max' => 'Mỗi tệp đính kèm không được vượt quá :max KB.',
            'attachments.*.mimes' => 'Định dạng tệp đính kèm không được hỗ trợ.',
        ];
    }
}
