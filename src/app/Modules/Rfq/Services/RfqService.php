<?php

namespace App\Modules\Rfq\Services;

use App\Models\User;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderService;
use App\Modules\Rfq\Exceptions\RfqThresholdException;
use App\Modules\Rfq\Models\PartRequest;
use App\Modules\Rfq\Models\Quote;

class RfqService
{
    public function __construct(
        private readonly RfqThresholdService $thresholds,
        private readonly OrderService $orders,
        private readonly DepositService $deposits
    ) {}

    /**
     * Post a new part request. Enforces the monthly free quota only when
     * thresholds are enabled (otherwise unlimited).
     *
     * @param  array<string, mixed>  $data
     */
    public function createRequest(User $buyer, array $data): PartRequest
    {
        if (! $this->thresholds->withinFreeQuota($buyer)) {
            throw new RfqThresholdException('You have used all your free requests this month.');
        }

        return PartRequest::create([
            'buyer_user_id'     => $buyer->id,
            'make_id'           => $data['make_id'] ?? null,
            'model_id'          => $data['model_id'] ?? null,
            'year'              => $data['year'] ?? null,
            'part_description'  => $data['part_description'],
            'budget_min'        => $data['budget_min'] ?? null,
            'budget_max'        => $data['budget_max'] ?? null,
            'location'          => $data['location'],
            'estimated_value'   => $data['estimated_value'] ?? null,
            'status'            => 'open',
            'moderation_status' => 'approved',
            'expires_at'        => now()->addDays(30),
        ]);
    }

    /**
     * Accept a quote → create the order, mark the request converted, reject the
     * other quotes, and credit any paid deposit against the order.
     *
     * @param  array<string, mixed>  $customer
     */
    public function acceptQuote(PartRequest $request, Quote $quote, array $customer): Order
    {
        $order = $this->orders->createFromQuote($quote, $customer, $request->buyer_user_id);

        $quote->update(['status' => 'accepted']);
        $request->quotes()->where('id', '!=', $quote->id)->update(['status' => 'rejected']);
        $request->update([
            'status'             => 'converted',
            'accepted_quote_id'  => $quote->id,
            'converted_order_id' => $order->id,
        ]);

        if ($deposit = $request->paidDeposit()) {
            $this->deposits->credit($deposit);
        }

        return $order;
    }

    /**
     * Buyer closes a request before converting → refund any paid deposit.
     */
    public function close(PartRequest $request): void
    {
        $request->update(['status' => 'closed']);

        if ($deposit = $request->paidDeposit()) {
            $this->deposits->refund($deposit);
        }
    }
}
