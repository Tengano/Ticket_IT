<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Xóa các cột mô tả vị trí không còn được sử dụng.
     */
    public function up(): void
    {
        $this->dropColumnIfExists('vi_tri', 'toa_nha');
        $this->dropColumnIfExists('vi_tri', 'tang');
        $this->dropColumnIfExists('vi_tri', 'khu_vuc');
    }

    /**
     * Khôi phục các cột khi rollback migration.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('vi_tri', 'toa_nha')) {
            Schema::table('vi_tri', function (Blueprint $table): void {
                $table->string('toa_nha')->nullable()->after('ten');
            });
        }

        if (! Schema::hasColumn('vi_tri', 'tang')) {
            Schema::table('vi_tri', function (Blueprint $table): void {
                $table->string('tang', 32)->nullable()->after('toa_nha');
            });
        }

        if (! Schema::hasColumn('vi_tri', 'khu_vuc')) {
            Schema::table('vi_tri', function (Blueprint $table): void {
                $table->string('khu_vuc', 32)->nullable()->index()->after('tang');
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
