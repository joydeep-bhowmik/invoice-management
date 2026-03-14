<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();

            // Jurisdiction
            $table->char('country_code', 2)->nullable(); // ISO-3166 (US, DE, IN)

            // Seller snapshot
            $table->string('seller_name');
            $table->string('seller_phone')->nullable();
            $table->text('seller_address')->nullable();
            $table->string('seller_tax_id')->nullable();

            // Buyer snapshot
            $table->string('client_name');
            $table->string('client_phone')->nullable();
            $table->text('client_address')->nullable();
            $table->string('client_tax_id')->nullable();

            // Money (order-level)
            $table->decimal('subtotal', 10, 2);
            $table->decimal('shipping_total', 10, 2)->default(0);
            $table->decimal('tax_total', 10, 2)->default(0);
            $table->json('custom_charges')->nullable();
            /*
                    [
                        {
                            "label": "Environmental Fee",
                            "amount": 12.50
                        },
                        {
                            "label": "Fuel Surcharge",
                            "amount": 8.75
                        }
                    ]
            */
            $table->decimal('total', 10, 2);

            // Currency
            $table->char('currency', 3)->default('USD'); // ISO-4217

            // Status
            $table->enum('status', [
                'draft',
                'issued',
                'paid',
                'overdue',
                'cancelled',
            ])->default('draft');

            // Compliance escape hatches
            $table->text('legal_notes')->nullable(); // mandatory country text
            $table->json('tax_meta')->nullable();    // VAT/GST splits, reverse charge, etc.

            // Payment
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
