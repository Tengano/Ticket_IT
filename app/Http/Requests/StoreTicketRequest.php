<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Ticket::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $attachmentRules = [
            'nullable',
            'array',
            'max:5',
        ];

        return [
            'subject' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'min:10', 'max:10000'],
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where('is_active', true),
            ],
            'department_id' => ['nullable', Rule::exists('departments', 'id')->where('is_active', true)],
            'location_id' => ['nullable', Rule::exists('locations', 'id')->where('is_active', true)],
            'impact' => ['required', 'integer', 'in:1,2,3'],
            'urgency' => ['required', 'integer', 'in:1,2,3'],
            'attachments' => $attachmentRules,
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
            'subject.required' => 'Vui lòng nhập tiêu đề ticket.',
            'subject.max' => 'Tiêu đề không được vượt quá 180 ký tự.',
            'description.required' => 'Vui lòng mô tả sự cố.',
            'description.min' => 'Mô tả cần ít nhất 10 ký tự để kỹ thuật viên hiểu vấn đề.',
            'category_id.required' => 'Vui lòng chọn danh mục sự cố.',
            'category_id.exists' => 'Danh mục đã chọn không hợp lệ.',
            'impact.required' => 'Vui lòng chọn mức ảnh hưởng.',
            'urgency.required' => 'Vui lòng chọn mức khẩn cấp.',
            'attachments.max' => 'Chỉ được đính kèm tối đa 5 tệp.',
            'attachments.*.max' => 'Mỗi tệp đính kèm không được vượt quá :max KB.',
            'attachments.*.mimes' => 'Định dạng tệp đính kèm không được hỗ trợ.',
        ];
    }
}
