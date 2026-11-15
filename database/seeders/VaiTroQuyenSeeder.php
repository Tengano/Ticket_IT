<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class VaiTroQuyenSeeder extends Seeder
{
    /**
     * Bốn vai trò: requester, agent, lead, admin.
     *
     * Tên vai trò và mã quyền giữ nguyên dạng mã kỹ thuật (được dùng trong
     * middleware/policy), phần hiển thị đã có nhãn tiếng Việt ở giao diện.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'ticket.view.own',
            'ticket.view.all',
            'ticket.create',
            'ticket.update',
            'ticket.assign',
            'ticket.close',
            'ticket.reopen',
            'ticket.internal-note',
            'report.view',
            'catalog.manage',
            'user.manage',
            'admin.manage',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $matrix = [
            'requester' => [
                'ticket.view.own',
                'ticket.create',
            ],
            'agent' => [
                'ticket.view.all',
                'ticket.create',
                'ticket.update',
                'ticket.assign',
                'ticket.close',
                'ticket.reopen',
                'ticket.internal-note',
            ],
            'lead' => [
                'ticket.view.all',
                'ticket.create',
                'ticket.update',
                'ticket.assign',
                'ticket.close',
                'ticket.reopen',
                'ticket.internal-note',
                'report.view',
            ],
            'admin' => $permissions,
        ];

        foreach ($matrix as $roleName => $rolePermissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($rolePermissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
