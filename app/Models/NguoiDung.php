<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'ten', 'email', 'dien_thoai', 'password', 'phong_ban_id', 'vi_tri_id',
    'dang_hoat_dong', 'la_vip', 'anh_dai_dien', 'phan_cong_gan_nhat_luc',
])]
#[Hidden(['password', 'remember_token'])]
class NguoiDung extends Authenticatable
{
    use HasRoles, Notifiable;

    protected $table = 'nguoi_dung';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'dang_hoat_dong' => 'boolean',
            'la_vip' => 'boolean',
            'phan_cong_gan_nhat_luc' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PhongBan, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(PhongBan::class, 'phong_ban_id');
    }

    /**
     * @return BelongsTo<ViTri, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(ViTri::class, 'vi_tri_id');
    }

    /**
     * @return BelongsToMany<NhomXuLy, $this>
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(NhomXuLy::class, 'nhom_xu_ly_nguoi_dung', 'nguoi_dung_id', 'nhom_xu_ly_id')
            ->withPivot(['la_truong_nhom', 'dinh_muc'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<PhieuYeuCau, $this>
     */
    public function requestedTickets(): HasMany
    {
        return $this->hasMany(PhieuYeuCau::class, 'nguoi_yeu_cau_id');
    }

    /**
     * @return HasMany<PhieuYeuCau, $this>
     */
    public function assignedTickets(): HasMany
    {
        return $this->hasMany(PhieuYeuCau::class, 'nguoi_xu_ly_id');
    }

    /**
     * @return BelongsToMany<PhieuYeuCau, $this>
     */
    public function watchedTickets(): BelongsToMany
    {
        return $this->belongsToMany(PhieuYeuCau::class, 'nguoi_theo_doi', 'nguoi_dung_id', 'phieu_yeu_cau_id')
            ->withTimestamps();
    }

    /**
     * @param  Builder<NguoiDung>  $query
     * @return Builder<NguoiDung>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('dang_hoat_dong', true);
    }

    public function isStaff(): bool
    {
        return $this->hasAnyRole(['agent', 'lead', 'admin']);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    public function isLeadOrAdmin(): bool
    {
        return $this->hasAnyRole(['lead', 'admin']);
    }

    public function initials(): string
    {
        $words = preg_split('/\s+/', trim($this->ten)) ?: [];

        if ($words === []) {
            return '?';
        }

        $initials = count($words) > 1
            ? mb_substr($words[0], 0, 1).mb_substr(end($words), 0, 1)
            : mb_substr($words[0], 0, 2);

        return mb_strtoupper($initials);
    }

    public function roleLabel(): string
    {
        $labels = [
            'requester' => 'Người yêu cầu',
            'agent' => 'Kỹ thuật viên',
            'lead' => 'Trưởng nhóm IT',
            'admin' => 'Quản trị',
        ];

        $roles = $this->getRoleNames()
            ->map(fn (string $role) => $labels[$role] ?? $role)
            ->all();

        return $roles === [] ? 'Chưa gán vai trò' : implode(', ', $roles);
    }
}
