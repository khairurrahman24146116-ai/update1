<?php

namespace Database\Factories;

use App\Models\SppInvoice;
use App\Models\SppPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SppPayment>
 */
class SppPaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'spp_invoice_id' => SppInvoice::factory(),
            'unit' => 'sma',
            'receipt_number' => 'SPP-SMA-'.fake()->unique()->numerify('########').'-0001',
            'amount' => 500000,
            'paid_at' => now(),
            'method' => 'tunai',
            'received_by' => User::factory(),
        ];
    }
}
