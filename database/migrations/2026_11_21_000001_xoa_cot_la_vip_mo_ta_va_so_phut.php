<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Xóa các cột không còn được sử dụng.
     */
    public function up(): void
    {
        $this->dropColumnIfExists('nguoi_dung', 'la_vip');
        $this->dropColumnIfExists('phong_ban', 'mo_ta');
        $this->dropColumnIfExists('vi_tri', 'mo_ta');
        $this->dropColumnIfExists('lich_su_trang_thai', 'so_phut');
    }

    /**
     * Khôi phục các cột khi rollback migration.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('nguoi_dung', 'la_vip')) {
            Schema::table('nguoi_dung', function (Blueprint $table): void {
                $table->boolean('la_vip')->default(false)->after('dang_hoat_dong');
            });
        }

        if (! Schema::hasColumn('phong_ban', 'mo_ta')) {
            Schema::table('phong_ban', function (Blueprint $table): void {
                $table->string('mo_ta')->nullable()->after('ten');
            });
        }

        if (! Schema::hasColumn('vi_tri', 'mo_ta')) {
            Schema::table('vi_tri', function (Blueprint $table): void {
                $table->string('mo_ta')->nullable()->after('ten');
            });
        }

        if (! Schema::hasColumn('lich_su_trang_thai', 'so_phut')) {
            Schema::table('lich_su_trang_thai', function (Blueprint $table): void {
                $table->unsignedInteger('so_phut')->nullable()->after('ket_thuc_luc');
            });
        }
    }

    private function dropColumnIfExists(string $table, string $column): void
    {
        if (! Schema::hasColumn($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column): void {
            $blueprint->dropColumn($column);
        });
    }
};
