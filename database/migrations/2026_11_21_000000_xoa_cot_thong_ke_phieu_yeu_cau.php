<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Xóa các cột thống kê không còn được sử dụng trên bảng phiếu yêu cầu.
     */
    public function up(): void
    {
        $columns = array_values(array_filter(
            ['phut_xu_ly', 'phut_cho', 'phut_tam_dung', 'so_lan_mo_lai', 'diem_hai_long'],
            fn (string $column): bool => Schema::hasColumn('phieu_yeu_cau', $column),
        ));

        if ($columns === []) {
            return;
        }

        Schema::table('phieu_yeu_cau', function (Blueprint $table) use ($columns): void {
            $table->dropColumn($columns);
        });
    }

    /**
     * Khôi phục các cột khi rollback migration.
     */
    public function down(): void
    {
        Schema::table('phieu_yeu_cau', function (Blueprint $table): void {
            $table->unsignedInteger('phut_xu_ly')->default(0)->after('dong_luc');
            $table->unsignedInteger('phut_cho')->default(0)->after('phut_xu_ly');
            $table->unsignedInteger('phut_tam_dung')->default(0)->after('phut_cho');
            $table->unsignedTinyInteger('so_lan_mo_lai')->default(0)->after('phut_tam_dung');
            $table->unsignedTinyInteger('diem_hai_long')->nullable()->after('so_lan_mo_lai');
        });
    }
};
