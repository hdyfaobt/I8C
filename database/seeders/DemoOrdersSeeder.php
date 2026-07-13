<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoOrdersSeeder extends Seeder
{
    // 36 demo orders, every status, for showing the app.
    // Run with: php artisan db:seed --class=DemoOrdersSeeder
    public function run(): void
    {
        $products = Product::all();

        if ($products->isEmpty()) {
            $this->command->warn('Run ProductSeeder first — no products to order.');

            return;
        }

        $receptionist = User::whereHas('roles', fn ($q) => $q->where('name', 'receptionist'))->first();
        $orderpicker = User::whereHas('roles', fn ($q) => $q->where('name', 'orderpicker'))->first();

        $customers = Customer::all();
        if ($customers->count() < 10) {
            $customers = $customers->merge(Customer::factory(10 - $customers->count())->create());
        }

        // Weighted mix so the demo shows a healthy, varied pipeline.
        $statusPlan = [
            'awaiting_review' => 4,
            'pending' => 2,
            'sent' => 6,
            'ready_for_pickup' => 6,
            'received' => 12,
            'refused' => 2,
            'cancelled' => 2,
            'failed' => 2,
        ];

        foreach ($statusPlan as $status => $count) {
            for ($i = 0; $i < $count; $i++) {
                $this->createOrder($status, $products, $customers, $receptionist, $orderpicker);
            }
        }

        $this->command->info('36 demo orders created.');
    }

    private function createOrder($status, $products, $customers, ?User $receptionist, ?User $orderpicker): void
    {
        $customer = $customers->random();
        $createdAt = now()->subDays(random_int(0, 30))->subHours(random_int(0, 23));

        $order = Order::create([
            'customer_id' => $customer->id,
            'created_by' => $receptionist?->id,
            'notes' => fake()->optional(0.3)->sentence(),
            'status' => 'awaiting_review',
        ]);

        // Backdate created_at so the demo doesn't look like everything
        // happened in the last minute — direct property set, bypasses
        // $fillable (not meant to be mass-assignable).
        $order->created_at = $createdAt;
        $order->updated_at = $createdAt;
        $order->save();

        // Small order most of the time, occasionally a big one.
        $itemCount = fake()->boolean(70) ? random_int(1, 2) : random_int(3, 6);
        $chosenProducts = $products->random(min($itemCount, $products->count()));
        if (! $chosenProducts instanceof \Illuminate\Support\Collection) {
            $chosenProducts = collect([$chosenProducts]);
        }

        foreach ($chosenProducts as $product) {
            $order->items()->create([
                'product_id' => $product->id,
                'product' => $product->name,
                'quantity' => random_int(1, 20),
                'unit_price' => $product->price,
            ]);
        }

        $this->applyStatus($order->fresh('items'), $status, $orderpicker, $receptionist, $createdAt);
    }

    private function applyStatus(Order $order, string $status, ?User $orderpicker, ?User $receptionist, $createdAt): void
    {
        $attrs = ['status' => $status];
        $cursor = $createdAt->copy();

        if (in_array($status, ['pending', 'sent', 'ready_for_pickup', 'received', 'refused'], true)) {
            $cursor = $cursor->addMinutes(random_int(5, 120));
            $attrs['accepted_at'] = $cursor->copy();
        }

        if ($status === 'refused') {
            $attrs['refused_at'] = $cursor->copy()->addMinutes(random_int(5, 60));
        }

        if ($status === 'failed') {
            $attrs['failed_at'] = $createdAt->copy()->addMinutes(random_int(10, 60));
        }

        if (in_array($status, ['sent', 'ready_for_pickup', 'received'], true)) {
            $cursor = $cursor->addMinutes(random_int(1, 30));
            $attrs['sent_at'] = $cursor->copy();
        }

        if (in_array($status, ['ready_for_pickup', 'received'], true)) {
            $cursor = $cursor->addHours(random_int(1, 24));
            $attrs['prepared_by'] = $orderpicker?->id;
            $attrs['ready_at'] = $cursor->copy();

            foreach ($order->items as $item) {
                $item->update(['picked_at' => $cursor->copy()]);
            }
        }

        if ($status === 'received') {
            $cursor = $cursor->addHours(random_int(1, 48));
            $attrs['received_by'] = $receptionist?->id;
            $attrs['received_at'] = $cursor->copy();
        }

        if ($status === 'cancelled') {
            $attrs['cancelled_at'] = $createdAt->copy()->addMinutes(random_int(5, 60));
        }

        // Paid orders only make sense once picked up or delivered.
        if (in_array($status, ['ready_for_pickup', 'received'], true) && fake()->boolean(70)) {
            $attrs['paid'] = true;
            $attrs['paid_at'] = $cursor->copy();
            $attrs['payment_method'] = fake()->randomElement(['cash', 'bank_transfer']);
        }

        $order->update($attrs);
    }
}
