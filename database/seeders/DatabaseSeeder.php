<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Nạp dữ liệu mẫu cho ứng dụng.
     */
    public function run(): void
    {
        $this->call([
            VaiTroQuyenSeeder::class,
            ToChucSeeder::class,
            DanhMucSeeder::class,
            NguoiDungMauSeeder::class,
            PhieuYeuCauMauSeeder::class,
        ]);
    }
}
