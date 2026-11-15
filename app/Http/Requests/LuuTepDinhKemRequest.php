<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LuuTepDinhKemRequest extends FormRequest
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
            'file.required' => 'Vui lòng chọn tệp cần tải lên.',
            'file.max' => 'Tệp không được vượt quá :max KB.',
            'file.mimes' => 'Định dạng tệp không được hỗ trợ.',
        ];
    }
}
