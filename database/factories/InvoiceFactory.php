<?php

namespace Database\Factories;

use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition()
    {
        $subtotal = $this->faker->randomFloat(2, 100, 1000);
        $shipping = $this->faker->randomFloat(2, 0, 100);
        $tax = $this->faker->randomFloat(2, 0, 50);
        
        return [
            'invoice_number' => 'INV-'.$this->faker->unique()->numberBetween(1000, 9999),
            'invoice_date' => $this->faker->date(),
            'due_date' => $this->faker->optional()->date(),
            'country_code' => $this->faker->optional()->countryCode(),
            'seller_name' => $this->faker->company(),
            'seller_phone' => $this->faker->optional()->phoneNumber(),
            'seller_address' => $this->faker->optional()->address(),
            'seller_tax_id' => $this->faker->optional()->bothify('TAX-####'),
            'client_name' => $this->faker->name(),
            'client_phone' => $this->faker->optional()->phoneNumber(),
            'client_address' => $this->faker->optional()->address(),
            'client_tax_id' => $this->faker->optional()->bothify('CLT-####'),
            'subtotal' => $subtotal,
            'shipping_total' => $shipping,
            'tax_total' => $tax,
            'total' => $subtotal + $shipping + $tax,
            'currency' => 'USD',
            'status' => $this->faker->randomElement(['draft', 'issued', 'paid', 'overdue', 'cancelled']),
            'legal_notes' => $this->faker->optional()->paragraph(),
            'tax_meta' => null,
            'custom_charges' => null,
            'paid_at' => null,
        ];
    }

    /**
     * Configure the factory to create items.
     */
    public function configure()
    {
        return $this->afterCreating(function (Invoice $invoice) {
            // Create 1-3 items for each invoice
            \App\Models\InvoiceItem::factory()
                ->count($this->faker->numberBetween(1, 3))
                ->create(['invoice_id' => $invoice->id]);
        });
    }
}
