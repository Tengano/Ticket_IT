<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nhóm xử lý (nhóm kỹ thuật viên) và bảng trung gian thành viên.
     */
    public function up(): void
    {
        Schema::create('nhom_xu_ly', function (Blueprint $table) {
            $table->id();
            $table->string('ma', 32)->unique();
            $table->string('ten');
            $table->string('mo_ta')->nullable();
            $table->foreignId('truong_nhom_id')->nullable()->constrained('nguoi_dung')->nullOnDelete();
            $table->string('chien_luoc_phan_cong', 32)->default('khop_ky_nang');
            $table->unsignedSmallInteger('dinh_muc')->default(20);
            $table->boolean('dang_hoat_dong')->default(true);
            $table->timestamps();
        });

        Schema::create('nhom_xu_ly_nguoi_dung', function (Blueprint $table) {
            $table->foreignId('nhom_xu_ly_id')->constrained('nhom_xu_ly')->cascadeOnDelete();
            $table->foreignId('nguoi_dung_id')->constrained('nguoi_dung')->cascadeOnDelete();
            $table->boolean('la_truong_nhom')->default(false);
            $table->unsignedSmallInteger('dinh_muc')->nullable();
            $table->timestamps();
            $table->primary(['nhom_xu_ly_id', 'nguoi_dung_id']);
        });
    }

    /**
     * Hoàn tác migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('nhom_xu_ly_nguoi_dung');
        Schema::dropIfExists('nhom_xu_ly');
    }
};
