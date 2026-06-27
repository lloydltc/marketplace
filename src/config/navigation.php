<?php

/*
|--------------------------------------------------------------------------
| Role → capability / navigation (single source of truth)
|--------------------------------------------------------------------------
|
| One authoritative definition of what each role can see and reach. Navigation
| renders from here (App\Support\Navigation) and route middleware enforces the
| same rules server-side (RoleMiddleware + ShopAccess). Do NOT scatter
| `@if(role == ...)` across views — add capabilities here.
|
*/

return [
    // Where each authenticated role's "Dashboard / Home" control points.
    'dashboards' => [
        'super_admin'    => 'admin.dashboard',
        'admin'          => 'admin.dashboard',
        'vendor_admin'   => 'vendor.dashboard',
        'vendor_worker'  => 'vendor.dashboard',
        'agent'          => 'agent.dashboard',
        'private_seller' => 'seller.dashboard',
        'rider'          => 'rider.dashboard',
    ],

    // Roles permitted to shop (cart, checkout, buyer orders, RFQ-as-buyer,
    // saved searches, concierge-as-buyer). Guests may also shop. A SELLER IS NOT
    // A CUSTOMER (production_readiness_task_order.md): only `customer` (+ guests)
    // get buyer surfaces. Sellers get their own "Sales" surface instead.
    'shopping_roles' => ['customer'],

    // Authenticated buyer-context links — shopping roles (customers) only. Public
    // browse links (Shop, Vehicles) + Cart are shown to everyone who may shop
    // (incl. guests) and to no one else.
    'buyer_links' => [
        ['label' => 'My orders', 'route' => 'orders.index'],
        ['label' => 'My garage', 'route' => 'garage.index'],
        ['label' => 'Requests', 'route' => 'rfq.index'],
        ['label' => 'Saved searches', 'route' => 'saved-searches.index'],
    ],

    // Seller "Sales / Orders Received" surface, per role. Vendors reuse the
    // Phase 12 orders-received list (relabelled "Sales"); private sellers get an
    // enquiries surface (vehicles are lead-gen — no transactional orders).
    'seller_links' => [
        'vendor_admin'   => [['label' => 'Sales', 'route' => 'vendor.orders.index']],
        'vendor_worker'  => [['label' => 'Sales', 'route' => 'vendor.orders.index']],
        'private_seller' => [['label' => 'Sales', 'route' => 'seller.sales.index']],
    ],

    // Settings-style surfaces reserved for super_admin only.
    'super_admin_only' => ['platform_settings'],
];
