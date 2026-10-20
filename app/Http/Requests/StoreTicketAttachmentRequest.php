<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketAttachmentRequest extends FormRequest
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
            'file' => [
                'required',
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
            'file.required' => 'Vui lòng chọn tệp cần tải lên.',
            'file.max' => 'Tệp không được vượt quá :max KB.',
            'file.mimes' => 'Định dạng tệp không được hỗ trợ.',
        ];
    }
}
