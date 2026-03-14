<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LaravelDaily\Invoices\Classes\InvoiceItem;
use LaravelDaily\Invoices\Classes\Party;
use LaravelDaily\Invoices\Invoice as PdfInvoice;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'invoice_date',
        'due_date',
        'country_code',
        'seller_name',
        'seller_phone',
        'seller_address',
        'seller_tax_id',
        'client_name',
        'client_phone',
        'client_address',
        'client_tax_id',
        'subtotal',
        'shipping_total',
        'tax_total',
        'total',
        'currency',
        'status',
        'legal_notes',
        'tax_meta',
        'custom_charges',
        'paid_at',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'shipping_total' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
        'tax_meta' => 'array',
        'custom_charges' => 'array',
        'status' => 'string', // Add this to ensure proper casting
    ];

    /**
     * Get the items for the invoice.
     */
    public function items(): HasMany
    {
        return $this->hasMany(\App\Models\InvoiceItem::class);
    }

    public function generatePdf()
    {
        // Create seller party
        $seller = new Party([
            'name' => $this->seller_name,
            'phone' => $this->seller_phone,
            'address' => $this->seller_address,
            'custom_charges' => [
                'Tax ID' => $this->seller_tax_id,
                // Add more custom fields if needed
            ],
        ]);

        // Create client party
        $client = new Party([
            'name' => $this->client_name,
            'phone' => $this->client_phone,
            'address' => $this->client_address,
            'code' => $this->invoice_number, // Using invoice number as code
            'custom_charges' => [
                'Tax ID' => $this->client_tax_id,
                // Add more custom fields if needed
            ],
        ]);

        // / Convert items to InvoiceItem objects
        $items = $this->items->map(function ($item) {
            return InvoiceItem::make($item->description)
                ->pricePerUnit($item->unit_price)
                ->quantity($item->quantity);
        })->toArray();

        $custom_charges = 0;
        // Add custom fields as separate items if they exist
        if ($this->custom_charges) {
            $customFields = json_decode($this->custom_charges, true);

            if (is_array($customFields)) {
                foreach ($customFields as $field) {
                    // Check if field has required data
                    if (! empty($field['label'])) {
                        // Get amount safely with fallbacks
                        if (isset($field['stored_amount'])) {
                            // From stored data in database
                            $amount = (float) $field['stored_amount'];
                        } else {
                            // Calculate from type
                            $type = $field['type'] ?? 'fixed';

                            if ($type === 'percentage') {
                                $percentage = (float) ($field['stored_percentage'] ?? $field['percentage'] ?? 0);
                                $amount = ($this->subtotal * $percentage) / 100;
                            } else {
                                $amount = (float) ($field['amount'] ?? 0);
                            }
                        }

                        // Build description
                        $description = $field['label'];
                        if (isset($field['type']) && $field['type'] === 'percentage') {
                            $percentage = $field['stored_percentage'] ?? $field['percentage'] ?? 0;
                            $description .= " ($percentage%)";
                        }

                        // Only add if amount is not zero (unless you want to show zero amounts)
                        if ($amount != 0) {
                            $custom_charges += $amount;
                        }
                    }
                }
            }
        }

        // Add shipping as separate item if exists
        if ($this->shipping_total > 0) {
            $items[] = InvoiceItem::make('Shipping')
                ->pricePerUnit($this->shipping_total)
                ->quantity(1);
        }

        // Add tax as separate item if exists
        if ($this->tax_total > 0) {
            $items[] = InvoiceItem::make('Tax')
                ->pricePerUnit($this->tax_total)
                ->quantity(1);
        }
        $company = Company::first(); // Assuming you have a Company model and want the first company
        $media = $company?->getFirstMedia('logos');

        if ($media) {
            $logoPath = $media->getPath(); // ✅ real filesystem path
        }
        // Create invoice
        $invoice = PdfInvoice::make('invoice')
            ->name('Invoice')
            ->setCustomData([
                'invoice_number' => $this->invoice_number,
                'custom_charge_fields' => $this->custom_charges,
                'custom_charges_total' => $custom_charges,
            ])
            ->seller($seller)
            ->buyer($client)
            ->date($this->invoice_date)
            ->status($this->status)
            ->dateFormat('d M ,Y')
            ->payUntilDays($this->due_date ? now()->diffInDays($this->due_date) : 30)
            ->currencySymbol($this->getCurrencySymbol())
            ->currencyCode($this->currency)
            ->currencyFormat('{SYMBOL}{VALUE}')
            ->status($this->getStatusLabel())
            ->sequence($this->getSequenceNumber())
            ->serialNumberFormat('{SEQUENCE}/{SERIES}')
            ->filename($this->getFilename())
            ->addItems($items)
            ->logo($media ? $logoPath : '');

        // Add notes if exists
        if ($this->legal_notes) {
            $invoice->notes($this->legal_notes);
        }

        // Add logo if you have one
        // $invoice->logo(public_path('logo.png'));

        return $invoice;
    }

    /**
     * Stream the PDF invoice
     */
    public function streamPdf()
    {
        return $this->generatePdf()->stream();
    }

    /**
     * Download the PDF invoice
     */
    public function downloadPdf()
    {
        return $this->generatePdf()->download($this->getFilename());
    }

    /**
     * Save PDF to disk
     */
    public function savePdf($disk = 'public')
    {
        return $this->generatePdf()->save($disk);
    }

    /**
     * Get the invoice filename
     */
    protected function getFilename(): string
    {
        return "Invoice_{$this->invoice_number}_{$this->client_name}_".$this->invoice_date->format('Y-m-d');
    }

    /**
     * Get currency symbol
     */
    public function getCurrencySymbol($currency = null): string
    {
        $currency = $currency ?? $this->currency;
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'INR' => '₹',
            'JPY' => '¥',
            'CAD' => 'C$',
            'AUD' => 'A$',
        ];

        return $symbols[$currency] ?? $currency;
    }

    public function getCurrencySymbolAttribute(): string
    {
        return $this->getCurrencySymbol();
    }

    /**
     * Get status label
     */
    protected function getStatusLabel(): string
    {
        $statuses = [
            'draft' => 'Draft',
            'issued' => 'Issued',
            'paid' => 'Paid',
            'cancelled' => 'Cancelled',
        ];

        return $statuses[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Extract sequence number from invoice number
     */
    protected function getSequenceNumber(): int
    {
        // Try to extract numbers from invoice number
        preg_match('/\d+/', $this->invoice_number, $matches);

        return $matches[0] ?? $this->id;
    }

    /**
     * Get the PDF URL if saved
     */
    public function getPdfUrl()
    {
        return $this->generatePdf()->url();
    }
}
