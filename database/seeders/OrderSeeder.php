<?php

declare(strict_types = 1);

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementType;
use App\Enums\TransactionStatus;
use App\Enums\UserRole;
use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderTotalsCalculator;
use App\ValueObjects\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Builds a realistic order history over the last 90 days so that the
 * dashboards, reports and customer accounts have data to show.
 */
final class OrderSeeder extends Seeder
{
    private const int ORDERS = 140;

    public function __construct(private readonly OrderTotalsCalculator $totals) {}

    public function run(): void
    {
        $customers = User::query()->where('role', UserRole::Customer)->with('addresses')->get();
        $products  = Product::query()->with('primaryImage')->where('stock_quantity', '>', 10)->get();

        if ($customers->isEmpty() || $products->isEmpty()) {
            return;
        }

        $demo = $customers->firstWhere('email', UserSeeder::CUSTOMER_EMAIL);

        for ($i = 0; $i < self::ORDERS; $i++) {
            // Roughly one order in six belongs to the demo customer.
            $customer = $demo !== null && $i % 6 === 0 ? $demo : $customers->random();
            $placedAt = CarbonImmutable::now()->subMinutes(random_int(30, 90 * 24 * 60));

            $this->createOrder($customer, $products, $placedAt);
        }
    }

    /**
     * @param Collection<int, Product> $catalogue
     */
    private function createOrder(User $customer, Collection $catalogue, CarbonImmutable $placedAt): void
    {
        $status = $this->statusFor($placedAt);
        $method = random_int(1, 100) <= 60 ? PaymentMethod::CashOnDelivery : PaymentMethod::Stripe;
        $paid   = $method === PaymentMethod::Stripe ? $status !== OrderStatus::Cancelled : $status === OrderStatus::Delivered;

        /** @var Collection<int, Product> $picked */
        $picked   = $catalogue->random(random_int(1, 4));
        $lines    = [];
        $subtotal = Money::zero();

        foreach ($picked as $product) {
            $quantity  = random_int(1, 100) <= 75 ? 1 : random_int(2, 3);
            $lineTotal = $product->price->multiply($quantity);
            $subtotal  = $subtotal->add($lineTotal);
            $lines[]   = [$product, $quantity, $lineTotal];
        }

        $discount = random_int(1, 100) <= 15 ? $subtotal->percentage(10) : Money::zero();
        $totals   = $this->totals->calculate($subtotal, $discount);
        $address  = $customer->addresses->first() ?? Address::factory()->for($customer)->default()->create();

        $order = Order::query()->create([
            'number'               => 'ORD-' . $placedAt->format('ymd') . '-' . Str::upper(Str::random(6)),
            'user_id'              => $customer->id,
            'coupon_code'          => $discount->isPositive() ? 'WELCOME10' : null,
            'status'               => $status,
            'payment_status'       => $paid ? PaymentStatus::Paid : PaymentStatus::Unpaid,
            'payment_method'       => $method,
            'currency'             => $totals->total->currency,
            'subtotal'             => $totals->subtotal,
            'discount_total'       => $totals->discount,
            'shipping_total'       => $totals->shipping,
            'tax_total'            => $totals->tax,
            'grand_total'          => $totals->total,
            'customer_email'       => $customer->email,
            'shipping_name'        => $address->full_name,
            'shipping_phone'       => $address->phone,
            'shipping_line1'       => $address->line1,
            'shipping_line2'       => $address->line2,
            'shipping_city'        => $address->city,
            'shipping_state'       => $address->state,
            'shipping_postal_code' => $address->postal_code,
            'shipping_country'     => $address->country,
            'placed_at'            => $placedAt,
            'paid_at'              => $paid ? $placedAt->addMinutes(2) : null,
            'created_at'           => $placedAt,
            'updated_at'           => $placedAt,
        ]);

        foreach ($lines as [$product, $quantity, $lineTotal]) {
            $order->items()->create([
                'product_id'    => $product->id,
                'product_name'  => $product->name,
                'product_sku'   => $product->sku,
                'product_image' => $product->primaryImage?->thumbnail_path,
                'unit_price'    => $product->price,
                'quantity'      => $quantity,
                'line_total'    => $lineTotal,
            ]);

            if ($status !== OrderStatus::Cancelled) {
                $this->recordSale($product, $quantity, $order, $placedAt);
            }
        }

        $this->recordTimeline($order, $status, $placedAt);
        $this->recordPayment($order, $method, $paid, $placedAt);
    }

    private function statusFor(CarbonImmutable $placedAt): OrderStatus
    {
        $ageInDays = (int) $placedAt->diffInDays(now());
        $roll      = random_int(1, 100);

        return match (true) {
            $roll <= 8      => OrderStatus::Cancelled,
            $ageInDays >= 7 => OrderStatus::Delivered,
            $ageInDays >= 3 => $roll <= 60 ? OrderStatus::Shipped : OrderStatus::Delivered,
            $ageInDays >= 1 => $roll <= 50 ? OrderStatus::Processing : OrderStatus::Shipped,
            default         => $roll <= 60 ? OrderStatus::Pending : OrderStatus::Processing,
        };
    }

    private function recordSale(Product $product, int $quantity, Order $order, CarbonImmutable $at): void
    {
        $product->stock_quantity -= $quantity;
        $product->sales_count += $quantity;
        $product->save();

        $product->stockMovements()->create([
            'order_id'      => $order->id,
            'type'          => StockMovementType::Sale,
            'quantity'      => -$quantity,
            'balance_after' => $product->stock_quantity,
            'note'          => "Order {$order->number}",
            'created_at'    => $at,
        ]);
    }

    private function recordTimeline(Order $order, OrderStatus $final, CarbonImmutable $placedAt): void
    {
        $path = match ($final) {
            OrderStatus::Pending    => [OrderStatus::Pending],
            OrderStatus::Processing => [OrderStatus::Pending, OrderStatus::Processing],
            OrderStatus::Shipped    => [OrderStatus::Pending, OrderStatus::Processing, OrderStatus::Shipped],
            OrderStatus::Delivered  => [OrderStatus::Pending, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered],
            OrderStatus::Cancelled  => [OrderStatus::Pending, OrderStatus::Cancelled],
        };

        $previous = null;
        $at       = $placedAt;
        $dates    = [];

        foreach ($path as $step => $status) {
            $at = $step === 0 ? $placedAt : min($at->addHours(random_int(6, 40)), CarbonImmutable::now());

            $order->statusHistories()->create([
                'from_status' => $previous,
                'to_status'   => $status,
                'note'        => match ($status) {
                    OrderStatus::Pending   => 'Order placed.',
                    OrderStatus::Cancelled => 'Cancelled by the customer.',
                    default                => null,
                },
                'created_at' => $at,
            ]);

            $dates[$status->value] = $at;
            $previous              = $status;
        }

        $order->forceFill([
            'shipped_at'          => $dates[OrderStatus::Shipped->value]   ?? null,
            'delivered_at'        => $dates[OrderStatus::Delivered->value] ?? null,
            'cancelled_at'        => $dates[OrderStatus::Cancelled->value] ?? null,
            'cancellation_reason' => $final === OrderStatus::Cancelled ? 'Cancelled by the customer.' : null,
            'paid_at'             => $order->paid_at ?? ($dates[OrderStatus::Delivered->value] ?? null),
        ])->saveQuietly();
    }

    private function recordPayment(Order $order, PaymentMethod $method, bool $paid, CarbonImmutable $placedAt): void
    {
        $order->payments()->create([
            'gateway' => $method,
            'status'  => match (true) {
                $paid                                     => TransactionStatus::Succeeded,
                $order->status === OrderStatus::Cancelled => TransactionStatus::Failed,
                default                                   => TransactionStatus::Pending,
            },
            'amount'     => $order->grand_total,
            'currency'   => $order->currency,
            'reference'  => $method === PaymentMethod::Stripe ? 'cs_test_seed_' . Str::lower(Str::random(24)) : null,
            'paid_at'    => $paid ? $order->paid_at : null,
            'created_at' => $placedAt,
        ]);
    }
}
