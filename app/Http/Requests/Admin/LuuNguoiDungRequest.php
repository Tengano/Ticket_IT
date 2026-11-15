<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class LuuNguoiDungRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('user.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'ten' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('nguoi_dung', 'email')],
            'dien_thoai' => ['nullable', 'string', 'max:32'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'phong_ban_id' => ['nullable', Rule::exists('phong_ban', 'id')],
            'vi_tri_id' => ['nullable', Rule::exists('vi_tri', 'id')],
            'team_ids' => ['nullable', 'array'],
            'team_ids.*' => [Rule::exists('nhom_xu_ly', 'id')],
            'dang_hoat_dong' => ['boolean'],
            'la_vip' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'dang_hoat_dong' => $this->boolean('dang_hoat_dong'),
            'la_vip' => $this->boolean('la_vip'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ten.required' => 'Vui lòng nhập họ tên.',
            'email.required' => 'Vui lòng nhập email.',
            'email.unique' => 'Email này đã được sử dụng.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'role.required' => 'Vui lòng chọn vai trò.',
            'role.exists' => 'Vai trò không hợp lệ.',
        ];
    }
}
