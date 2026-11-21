<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CapNhatNguoiDungRequest extends FormRequest
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
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique('nguoi_dung', 'email')->ignore($this->route('user')),
            ],
            'dien_thoai' => ['nullable', 'string', 'max:32'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'phong_ban_id' => ['nullable', Rule::exists('phong_ban', 'id')],
            'vi_tri_id' => ['nullable', Rule::exists('vi_tri', 'id')],
            'team_ids' => ['nullable', 'array'],
            'team_ids.*' => [Rule::exists('nhom_xu_ly', 'id')],
            'dang_hoat_dong' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'dang_hoat_dong' => $this->boolean('dang_hoat_dong'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Email này đã được sử dụng.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            'role.required' => 'Vui lòng chọn vai trò.',
        ];
    }
}
