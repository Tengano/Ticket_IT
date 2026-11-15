<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Danh mục sự cố 2 cấp.
     */
    public function up(): void
    {
        Schema::create('danh_muc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('danh_muc_cha_id')->nullable()->constrained('danh_muc')->nullOnDelete();
            $table->string('ma', 64)->unique();
            $table->string('ten');
            $table->string('mo_ta')->nullable();
            $table->foreignId('nhom_mac_dinh_id')->nullable()->constrained('nhom_xu_ly')->nullOnDelete();
            $table->string('uu_tien_mac_dinh', 2)->nullable();
            $table->boolean('dang_hoat_dong')->default(true);
            $table->timestamps();
            $table->index(['danh_muc_cha_id', 'dang_hoat_dong']);
        });
    }

    /**
     * Hoàn tác migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('danh_muc');
    }
};
