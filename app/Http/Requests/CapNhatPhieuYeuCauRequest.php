<?php

namespace App\Http\Requests;

use App\Enums\TrangThaiPhieu;
use App\Enums\UuTienPhieu;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CapNhatPhieuYeuCauRequest extends FormRequest
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
            'trang_thai' => ['nullable', Rule::enum(TrangThaiPhieu::class)],
            'uu_tien' => ['nullable', Rule::enum(UuTienPhieu::class)],
            'nguoi_xu_ly_id' => ['nullable', Rule::exists('nguoi_dung', 'id')->where('dang_hoat_dong', true)],
            'nhom_xu_ly_id' => ['nullable', Rule::exists('nhom_xu_ly', 'id')->where('dang_hoat_dong', true)],
            'ly_do' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'trang_thai.enum' => 'Trạng thái không hợp lệ.',
            'uu_tien.enum' => 'Mức ưu tiên không hợp lệ.',
            'nguoi_xu_ly_id.exists' => 'Kỹ thuật viên được chọn không hợp lệ hoặc đã bị vô hiệu hoá.',
            'nhom_xu_ly_id.exists' => 'Nhóm được chọn không hợp lệ.',
        ];
    }
}
