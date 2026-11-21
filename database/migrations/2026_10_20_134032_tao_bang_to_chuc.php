<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Danh mục tổ chức: phòng ban và vị trí.
     */
    public function up(): void
    {
        Schema::create('phong_ban', function (Blueprint $table) {
            $table->id();
            $table->string('ma', 32)->unique();
            $table->string('ten');
            $table->boolean('dang_hoat_dong')->default(true);
            $table->timestamps();
        });

        Schema::create('vi_tri', function (Blueprint $table) {
            $table->id();
            $table->string('ten');
            $table->boolean('dang_hoat_dong')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Hoàn tác migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('vi_tri');
        Schema::dropIfExists('phong_ban');
    }
};
