<?php

namespace App\Http\Requests;

use App\Models\PhieuYeuCau;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LuuPhieuYeuCauRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PhieuYeuCau::class) ?? false;
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
            'tieu_de' => ['required', 'string', 'max:180'],
            'mo_ta' => ['required', 'string', 'min:10', 'max:10000'],
            'danh_muc_id' => [
                'required',
                Rule::exists('danh_muc', 'id')->where('dang_hoat_dong', true),
            ],
            'phong_ban_id' => ['nullable', Rule::exists('phong_ban', 'id')->where('dang_hoat_dong', true)],
            'vi_tri_id' => ['nullable', Rule::exists('vi_tri', 'id')->where('dang_hoat_dong', true)],
            'anh_huong' => ['required', 'integer', 'in:1,2,3'],
            'khan_cap' => ['required', 'integer', 'in:1,2,3'],
            'attachments' => $attachmentRules,
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
            'tieu_de.required' => 'Vui lòng nhập tiêu đề phiếu.',
            'tieu_de.max' => 'Tiêu đề không được vượt quá 180 ký tự.',
            'mo_ta.required' => 'Vui lòng mô tả sự cố.',
            'mo_ta.min' => 'Mô tả cần ít nhất 10 ký tự để kỹ thuật viên hiểu vấn đề.',
            'danh_muc_id.required' => 'Vui lòng chọn danh mục sự cố.',
            'danh_muc_id.exists' => 'Danh mục đã chọn không hợp lệ.',
            'anh_huong.required' => 'Vui lòng chọn mức ảnh hưởng.',
            'khan_cap.required' => 'Vui lòng chọn mức khẩn cấp.',
            'attachments.max' => 'Chỉ được đính kèm tối đa 5 tệp.',
            'attachments.*.max' => 'Mỗi tệp đính kèm không được vượt quá :max KB.',
            'attachments.*.mimes' => 'Định dạng tệp đính kèm không được hỗ trợ.',
        ];
    }
}
