<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phiếu yêu cầu hỗ trợ và các bảng liên quan.
     */
    public function up(): void
    {
        Schema::create('phieu_yeu_cau', function (Blueprint $table) {
            $table->id();
            $table->string('ma', 24)->unique();
            $table->string('tieu_de', 180);
            $table->text('mo_ta');
            $table->foreignId('nguoi_yeu_cau_id')->constrained('nguoi_dung')->cascadeOnDelete();
            $table->foreignId('phong_ban_id')->nullable()->constrained('phong_ban')->nullOnDelete();
            $table->foreignId('vi_tri_id')->nullable()->constrained('vi_tri')->nullOnDelete();
            $table->foreignId('danh_muc_id')->nullable()->constrained('danh_muc')->nullOnDelete();
            $table->foreignId('nhom_xu_ly_id')->nullable()->constrained('nhom_xu_ly')->nullOnDelete();
            $table->foreignId('nguoi_xu_ly_id')->nullable()->constrained('nguoi_dung')->nullOnDelete();
            $table->string('trang_thai', 24)->default('moi')->index();
            $table->string('uu_tien', 2)->default('P3')->index();
            $table->string('anh_huong', 10)->default('trung_binh');
            $table->string('khan_cap', 10)->default('trung_binh');
            $table->string('kenh', 16)->default('web');
            $table->timestamp('phan_cong_luc')->nullable();
            $table->timestamp('phan_hoi_dau_luc')->nullable();
            $table->timestamp('giai_quyet_luc')->nullable();
            $table->timestamp('dong_luc')->nullable();
            $table->timestamps();

            $table->index(['nguoi_yeu_cau_id', 'trang_thai']);
            $table->index(['nguoi_xu_ly_id', 'trang_thai']);
            $table->index(['nhom_xu_ly_id', 'trang_thai']);
            $table->index('created_at');
        });

        Schema::create('binh_luan_phieu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phieu_yeu_cau_id')->constrained('phieu_yeu_cau')->cascadeOnDelete();
            $table->foreignId('nguoi_dung_id')->constrained('nguoi_dung')->cascadeOnDelete();
            $table->text('noi_dung');
            $table->string('loai', 16)->default('cong_khai')->index();
            $table->boolean('la_phan_hoi_dau')->default(false);
            $table->timestamps();
            $table->index(['phieu_yeu_cau_id', 'created_at']);
        });

        Schema::create('tep_dinh_kem', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phieu_yeu_cau_id')->constrained('phieu_yeu_cau')->cascadeOnDelete();
            $table->foreignId('binh_luan_id')->nullable()->constrained('binh_luan_phieu')->cascadeOnDelete();
            $table->foreignId('nguoi_tai_len_id')->nullable()->constrained('nguoi_dung')->nullOnDelete();
            $table->string('disk', 32)->default('local');
            $table->string('duong_dan');
            $table->string('ten_goc');
            $table->string('dinh_dang', 128)->nullable();
            $table->unsignedBigInteger('kich_thuoc')->default(0);
            $table->timestamps();
            $table->index('phieu_yeu_cau_id');
        });

        Schema::create('su_kien_phieu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phieu_yeu_cau_id')->constrained('phieu_yeu_cau')->cascadeOnDelete();
            $table->foreignId('nguoi_thuc_hien_id')->nullable()->constrained('nguoi_dung')->nullOnDelete();
            $table->string('loai', 32);
            $table->string('tu_gia_tri', 32)->nullable();
            $table->string('den_gia_tri', 32)->nullable();
            $table->json('du_lieu_them')->nullable();
            $table->timestamps();
            $table->index(['phieu_yeu_cau_id', 'created_at']);
            $table->index('loai');
        });

        Schema::create('lich_su_trang_thai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phieu_yeu_cau_id')->constrained('phieu_yeu_cau')->cascadeOnDelete();
            $table->string('trang_thai_truoc', 24)->nullable();
            $table->string('trang_thai_sau', 24);
            $table->foreignId('nguoi_thuc_hien_id')->nullable()->constrained('nguoi_dung')->nullOnDelete();
            $table->string('ly_do')->nullable();
            $table->timestamp('bat_dau_luc')->nullable();
            $table->timestamp('ket_thuc_luc')->nullable();
            $table->timestamps();
            $table->index(['phieu_yeu_cau_id', 'created_at']);
        });

        Schema::create('nguoi_theo_doi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phieu_yeu_cau_id')->constrained('phieu_yeu_cau')->cascadeOnDelete();
            $table->foreignId('nguoi_dung_id')->constrained('nguoi_dung')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['phieu_yeu_cau_id', 'nguoi_dung_id']);
        });
    }

    /**
     * Hoàn tác migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('nguoi_theo_doi');
        Schema::dropIfExists('lich_su_trang_thai');
        Schema::dropIfExists('su_kien_phieu');
        Schema::dropIfExists('tep_dinh_kem');
        Schema::dropIfExists('binh_luan_phieu');
        Schema::dropIfExists('phieu_yeu_cau');
    }
};
