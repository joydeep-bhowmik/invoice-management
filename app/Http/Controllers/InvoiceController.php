<?php

namespace App\Http\Controllers;

class InvoiceController extends Controller
{
    public function download($id)
    {
        $invoice = \App\Models\Invoice::findOrFail($id);

        return $invoice->downloadPdf();
    }

    public function stream($id)
    {
        $invoice = \App\Models\Invoice::findOrFail($id);

        return $invoice->streamPdf();
    }
}
