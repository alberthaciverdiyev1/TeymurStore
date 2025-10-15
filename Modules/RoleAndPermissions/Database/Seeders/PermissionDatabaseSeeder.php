<?php

namespace Modules\RoleAndPermissions\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionDatabaseSeeder extends Seeder
{
    protected string $guard = 'sanctum';

    public function run(): void
    {
        $allPermissions = [
            'banner' => [
                'view banners',
                'add banners',
                'delete banners',
            ],
            'brands' => [
                'view brands',
                'add brand',
                'details brand',
                'update brand',
                'delete brand',
            ],
            'categories' => [
                'view categories',
                'view categories-with-products',
                'add category',
                'details category',
                'update category',
                'delete category',
            ],
            'colors' => [
                'view colors',
                'add color',
                'details color',
                'update color',
                'delete color',
            ],
            'delivery' => [
                'view deliveries',
                'add delivery',
                'details delivery',
                'update delivery',
                'delete delivery',
            ],
            'faqs' => [
                'view faqs',
                'add faq',
                'update faq',
                'delete faq',
            ],
            'legal-terms' => [
                'view legal-terms',
                'update legal-terms',
            ],
            'notifications' => [
                'view notifications',
                'send notification'
            ],
            'orders' => [
                'view orders',
                'view orders-admin',
                'details order',
                'basket order',
                'buy-one order',
                'update order',
                'delete order',
                'completed-orders',
                'download-receipt',
                'view-receipt'
            ],
            'balance' => [
                'view balances',
                'deposit balance',
                'withdraw balance',
                'get-balance balance',
                'history balance',
            ],
            'products' => [
                'view products',
                'add product',
                'details product',
                'update product',
                'delete product',
                'statistics product',
                'details-admin product',
            ],
            'reviews' => [
                'view reviews',
                'add review',
                'delete review',
            ],
            'promo-codes' => [
                'view promo-codes',
                'check promo-code',
                'add promo-code',
                'details promo-code',
                'update promo-code',
                'delete promo-code',
                'check-promo-code-with-price',
            ],
            'roles-and-permissions' => [
                'view roles-and-permissions',
                'add role',
                'details role',
                'update role',
                'delete role',
                'give-role-to-user',
                'revoke-role-from-user',
            ],
            'settings' => [
                'view setting',
                'update setting',
            ],
            'sizes' => [
                'view sizes',
                'add size',
                'details size',
                'update size',
                'delete size',
            ],
            'users' => [
                'view users',
                'details user',
                'update user',
            ]
        ];

        $developerRole = Role::firstOrCreate([
            'name' => 'developer',
            'guard_name' => $this->guard,
        ]);

        $adminRole = Role::firstOrCreate([
            'name' => 'admin',
            'guard_name' => $this->guard,
        ]);

        foreach ($allPermissions as $group => $groupPermissions) {
            foreach ($groupPermissions as $permissionName) {
                $permission = Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => $this->guard,
                ]);

                $developerRole->givePermissionTo($permission);
                $adminRole->givePermissionTo($permission);
            }
        }

        app('cache')->forget('spatie.permission.cache');
    }
}
