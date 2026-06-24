<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Weekly vendor payout batch (BUSINESS_MODEL.md §4). Admin approves each batch.
Schedule::command('wallet:generate-payouts')->weeklyOn(1, '02:00');

// Nightly ledger reconciliation — proves cached balances match the ledger.
Schedule::command('wallet:reconcile')->dailyAt('01:30');

// Auto-complete delivered vendor-fulfilled orders the buyer never confirmed.
Schedule::command('orders:auto-complete-vf')->dailyAt('03:00');

// Expire stale RFQ requests and quotes.
Schedule::command('rfq:expire')->dailyAt('02:30');

// Lapse expired dealer package subscriptions.
Schedule::command('promotions:expire')->dailyAt('02:45');

// D5: remind sellers before their vehicle listings lapse, then sweep expired ones.
Schedule::command('vehicles:expiry-reminders')->dailyAt('08:00');
Schedule::command('vehicles:expire')->dailyAt('04:00');

// H5: pre-aggregate listing analytics hourly (keeps dashboards fresh + bounds raw table).
Schedule::command('analytics:aggregate')->hourly();

// H7: email buyers about new listings matching their saved-search alerts.
Schedule::command('alerts:saved-searches')->dailyAt('07:30');
