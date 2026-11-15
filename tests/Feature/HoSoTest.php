<?php

namespace Tests\Feature;

use App\Models\NguoiDung;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HoSoTest extends TestCase
{
    use RefreshDatabase;

    public function test_trang_ho_so_duoc_hien_thi(): void
    {
        $nguoiDung = NguoiDung::factory()->create();

        $this->actingAs($nguoiDung)->get('/ho-so')->assertOk();
    }

    public function test_co_the_cap_nhat_thong_tin_ho_so(): void
    {
        $nguoiDung = NguoiDung::factory()->create();

        $phanHoi = $this
            ->actingAs($nguoiDung)
            ->patch('/ho-so', [
                'ten' => 'Người dùng thử nghiệm',
                'email' => 'test@ticket.it',
            ]);

        $phanHoi
            ->assertSessionHasNoErrors()
            ->assertRedirect('/ho-so');

        $nguoiDung->refresh();

        $this->assertSame('Người dùng thử nghiệm', $nguoiDung->ten);
        $this->assertSame('test@ticket.it', $nguoiDung->email);
        $this->assertNull($nguoiDung->email_verified_at);
    }

    public function test_trang_thai_xac_thuc_email_khong_doi_khi_email_khong_doi(): void
    {
        $nguoiDung = NguoiDung::factory()->create();

        $this
            ->actingAs($nguoiDung)
            ->patch('/ho-so', [
                'ten' => 'Người dùng thử nghiệm',
                'email' => $nguoiDung->email,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/ho-so');

        $this->assertNotNull($nguoiDung->refresh()->email_verified_at);
    }

    public function test_nguoi_dung_co_the_xoa_tai_khoan_cua_minh(): void
    {
        $nguoiDung = NguoiDung::factory()->create();

        $this
            ->actingAs($nguoiDung)
            ->delete('/ho-so', ['password' => '11111111'])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($nguoiDung->fresh());
    }

    public function test_phai_nhap_dung_mat_khau_moi_xoa_duoc_tai_khoan(): void
    {
        $nguoiDung = NguoiDung::factory()->create();

        $this
            ->actingAs($nguoiDung)
            ->from('/ho-so')
            ->delete('/ho-so', ['password' => 'sai-mat-khau'])
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/ho-so');

        $this->assertNotNull($nguoiDung->fresh());
    }
}
