<?php

namespace App\Http\Requests;

use App\Enums\LoaiBinhLuan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LuuBinhLuanRequest extends FormRequest
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
            'noi_dung' => ['required', 'string', 'min:2', 'max:5000'],
            'loai' => ['required', Rule::enum(LoaiBinhLuan::class)],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'max:'.config('ho_tro.tep_dinh_kem.kich_thuoc_toi_da_kb'),
                'mimes:'.implode(',', config('ho_tro.tep_dinh_kem.dinh_dang')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'noi_dung.required' => 'Vui lòng nhập nội dung phản hồi.',
            'noi_dung.min' => 'Nội dung phản hồi quá ngắn.',
            'loai.required' => 'Loại phản hồi không hợp lệ.',
            'attachments.*.max' => 'Mỗi tệp đính kèm không được vượt quá :max KB.',
            'attachments.*.mimes' => 'Định dạng tệp đính kèm không được hỗ trợ.',
        ];
    }
}
