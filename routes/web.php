<?php

use App\Http\Controllers\Admin\NguoiDungController;
use App\Http\Controllers\Admin\NhomXuLyController;
use App\Http\Controllers\Admin\PhongBanController;
use App\Http\Controllers\Admin\ViTriController;
use App\Http\Controllers\BinhLuanPhieuController;
use App\Http\Controllers\HoSoController;
use App\Http\Controllers\PhieuYeuCauController;
use App\Http\Controllers\TepDinhKemPhieuController;
use App\Http\Controllers\ThongBaoController;
use App\Http\Controllers\TongQuanController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/tong-quan');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/tong-quan', [TongQuanController::class, 'index'])->name('tong-quan');

    // Phiếu yêu cầu (người yêu cầu + kỹ thuật viên + trưởng nhóm + quản trị)
    Route::resource('phieu-yeu-cau', PhieuYeuCauController::class)
        ->only(['index', 'create', 'store', 'show', 'update'])
        ->parameters(['phieu-yeu-cau' => 'ticket'])
        ->names([
            'index' => 'phieu.index',
            'create' => 'phieu.create',
            'store' => 'phieu.store',
            'show' => 'phieu.show',
            'update' => 'phieu.update',
        ]);

    Route::post('phieu-yeu-cau/{ticket}/binh-luan', [BinhLuanPhieuController::class, 'store'])->name('phieu.binh-luan.store');
    Route::post('phieu-yeu-cau/{ticket}/tep-dinh-kem', [TepDinhKemPhieuController::class, 'store'])->name('phieu.tep.store');
    Route::get('phieu-yeu-cau/{ticket}/tep-dinh-kem/{attachment}/tai', [TepDinhKemPhieuController::class, 'download'])->name('phieu.tep.tai');
    Route::post('phieu-yeu-cau/{ticket}/dong', [PhieuYeuCauController::class, 'close'])->name('phieu.dong');
    Route::post('phieu-yeu-cau/{ticket}/mo-lai', [PhieuYeuCauController::class, 'reopen'])->name('phieu.mo-lai');
    Route::post('phieu-yeu-cau/{ticket}/phan-cong', [PhieuYeuCauController::class, 'assign'])->name('phieu.phan-cong');

    // Thông báo
    Route::get('thong-bao', [ThongBaoController::class, 'index'])->name('thong-bao.index');
    Route::post('thong-bao/doc-het', [ThongBaoController::class, 'markAllAsRead'])->name('thong-bao.doc-het');
    Route::post('thong-bao/{notification}/doc', [ThongBaoController::class, 'markAsRead'])->name('thong-bao.doc');

    // Hồ sơ cá nhân
    Route::get('/ho-so', [HoSoController::class, 'edit'])->name('ho-so.edit');
    Route::patch('/ho-so', [HoSoController::class, 'update'])->name('ho-so.update');
    Route::delete('/ho-so', [HoSoController::class, 'destroy'])->name('ho-so.destroy');

    // Quản trị người dùng
    Route::prefix('quan-tri')->name('quan-tri.')->middleware('permission:user.manage')->group(function () {
        Route::resource('nguoi-dung', NguoiDungController::class)
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy'])
            ->parameters(['nguoi-dung' => 'user']);

        Route::post('nguoi-dung/{user}/kich-hoat', [NguoiDungController::class, 'toggle'])->name('nguoi-dung.kich-hoat');
    });

    // Quản trị danh mục nền
    Route::prefix('quan-tri')->name('quan-tri.')->middleware('permission:catalog.manage')->group(function () {
        Route::resource('phong-ban', PhongBanController::class)
            ->except(['show'])
            ->parameters(['phong-ban' => 'department']);

        Route::resource('vi-tri', ViTriController::class)
            ->except(['show'])
            ->parameters(['vi-tri' => 'location']);

        Route::resource('nhom-xu-ly', NhomXuLyController::class)
            ->except(['show'])
            ->parameters(['nhom-xu-ly' => 'team']);
    });
});

require __DIR__.'/auth.php';
