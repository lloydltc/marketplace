<?php

/*
|--------------------------------------------------------------------------
| Portal navigation — single source for the role-aware dark sidebar
|--------------------------------------------------------------------------
| One config drives <x-sidebar>. Each link is [label, route, active-pattern,
| optional role-gate]. A link with a 4th element shows only when the signed-in
| user's `role` matches it (e.g. super_admin-only Settings, vendor_admin-only
| money/account management).
*/

return [
    'admin' => [
        'cta' => null,
        'groups' => [
            'Overview' => [
                ['Dashboard', 'admin.dashboard', 'admin.dashboard'],
            ],
            'Approvals' => [
                ['Applications', 'admin.applications.index', 'admin.applications.*'],
                ['Vendors', 'admin.vendors.index', 'admin.vendors.*'],
                ['Products', 'admin.products.index', 'admin.products.*'],
                ['Vehicles', 'admin.vehicles.index', 'admin.vehicles.*'],
                ['Moderation', 'admin.moderation.index', 'admin.moderation.*'],
            ],
            'Catalogue' => [
                ['Parts catalog', 'admin.parts.index', 'admin.parts.*'],
                ['Categories', 'admin.categories.index', 'admin.categories.*'],
                ['Vehicle features', 'admin.vehicle-features.index', 'admin.vehicle-features.*'],
                ['Promotions', 'admin.promotions.index', 'admin.promotions.*'],
            ],
            'Operations' => [
                ['Dispatch', 'admin.dispatch.index', 'admin.dispatch.*'],
                ['Cash sessions', 'admin.cash-sessions.index', 'admin.cash-sessions.*'],
                ['Delivery zones', 'admin.delivery-zones.index', 'admin.delivery-zones.*'],
                ['RFQ', 'admin.rfq.index', 'admin.rfq.*'],
                ['Concierge', 'admin.concierge.index', 'admin.concierge.*'],
            ],
            'Growth' => [
                ['Users', 'admin.users.index', 'admin.users.*'],
                ['Leads', 'admin.leads.index', 'admin.leads.*'],
            ],
            'Money' => [
                ['Payouts', 'admin.payouts.index', 'admin.payouts.*'],
            ],
            'System' => [
                ['Settings', 'admin.settings.index', 'admin.settings.*', 'super_admin'],
            ],
        ],
    ],

    'vendor' => [
        'cta' => null,
        'groups' => [
            'Workspace' => [
                ['Dashboard', 'vendor.dashboard', 'vendor.dashboard'],
                ['Products', 'vendor.products.index', 'vendor.products.*'],
                ['Service kits', 'vendor.bundles.index', 'vendor.bundles.*', 'vendor_admin'],
                ['Vehicles', 'vendor.vehicles.index', 'vendor.vehicles.*'],
                ['Sales', 'vendor.orders.index', 'vendor.orders.*'],
                ['Leads', 'vendor.leads.index', 'vendor.leads.*'],
                ['Analytics', 'vendor.analytics.index', 'vendor.analytics.*'],
                ['Part requests', 'vendor.requests.index', 'vendor.requests.*'],
            ],
            'Manage' => [
                ['Wallet', 'vendor.wallet.show', 'vendor.wallet.*', 'vendor_admin'],
                ['Verification', 'vendor.verification.show', 'vendor.verification.*', 'vendor_admin'],
                ['Team', 'vendor.team.index', 'vendor.team.*', 'vendor_admin'],
                ['Documents', 'vendor.documents.index', 'vendor.documents.*', 'vendor_admin'],
                ['Bank accounts', 'vendor.bank-accounts.index', 'vendor.bank-accounts.*', 'vendor_admin'],
                ['Profile', 'vendor.profile.show', 'vendor.profile.*', 'vendor_admin'],
            ],
        ],
    ],

    'seller' => [
        'cta' => ['List a vehicle', 'seller.vehicles.create', 'seller.vehicles.create'],
        'groups' => [
            'Menu' => [
                ['Dashboard', 'seller.dashboard', 'seller.dashboard'],
                ['My listings', 'seller.vehicles.index', 'seller.vehicles.index'],
                ['Sales & enquiries', 'seller.sales.index', 'seller.sales.*'],
                ['Leads', 'seller.leads.index', 'seller.leads.*'],
                ['Analytics', 'seller.analytics.index', 'seller.analytics.*'],
            ],
        ],
    ],
];
