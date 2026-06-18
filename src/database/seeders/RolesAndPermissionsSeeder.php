<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Admin
            'manage-vendors',
            'suspend-users',
            'manage-listings',
            'manage-orders',
            'view-admin-dashboard',
            'approve-vendors',
            // Vendor admin
            'manage-team',
            'manage-own-listings',
            'view-vendor-dashboard',
            'manage-vendor-orders',
            'invite-vendor-worker',
            'manage-vendor-profile',
            // Vendor worker (subset of vendor permissions)
            // Agent
            'view-agent-dashboard',
            'manage-agent-profile',
            // Shared / customer
            'create-listing',
            'view-listings',
            'place-order',
            // Rider (Phase 7R)
            'view-rider-dashboard',
            'view-assigned-deliveries',
            'update-delivery-status',
            'record-cod-collection',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // super_admin — wildcard handled in model; give all permissions too
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        // admin
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions([
            'manage-vendors',
            'suspend-users',
            'manage-listings',
            'manage-orders',
            'view-admin-dashboard',
            'approve-vendors',
        ]);

        // vendor_admin
        $vendorAdmin = Role::firstOrCreate(['name' => 'vendor_admin', 'guard_name' => 'web']);
        $vendorAdmin->syncPermissions([
            'manage-team',
            'manage-own-listings',
            'view-vendor-dashboard',
            'manage-vendor-orders',
            'invite-vendor-worker',
            'manage-vendor-profile',
        ]);

        // vendor_worker
        $vendorWorker = Role::firstOrCreate(['name' => 'vendor_worker', 'guard_name' => 'web']);
        $vendorWorker->syncPermissions([
            'manage-own-listings',
            'view-vendor-dashboard',
            'manage-vendor-orders',
        ]);

        // agent
        $agent = Role::firstOrCreate(['name' => 'agent', 'guard_name' => 'web']);
        $agent->syncPermissions([
            'manage-own-listings',
            'view-agent-dashboard',
            'manage-agent-profile',
        ]);

        // private_seller
        $privateSeller = Role::firstOrCreate(['name' => 'private_seller', 'guard_name' => 'web']);
        $privateSeller->syncPermissions([
            'create-listing',
            'view-listings',
        ]);

        // customer
        $customer = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        $customer->syncPermissions([
            'view-listings',
            'place-order',
        ]);

        // rider (Phase 7R) — delivery personnel; FBS deliveries + COD collection
        $rider = Role::firstOrCreate(['name' => 'rider', 'guard_name' => 'web']);
        $rider->syncPermissions([
            'view-rider-dashboard',
            'view-assigned-deliveries',
            'update-delivery-status',
            'record-cod-collection',
        ]);
    }
}
