<?php

namespace App\Http\Controllers\Admin\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\Invoice\InvoicePdfService;
use App\Services\Invoice\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $query = Invoice::with(['marketplace', 'vendor', 'user']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'ilike', "%{$search}%")
                    ->orWhere('billing_name', 'ilike', "%{$search}%")
                    ->orWhere('billing_email', 'ilike', "%{$search}%");
            });
        }

        $invoices = $query->orderByDesc('created_at')->paginate(20);

        return view('admin.invoice.index', compact('invoices'));
    }

    public function show(int $id): View
    {
        $invoice = Invoice::with(['items', 'marketplace', 'vendor', 'user', 'creditNote'])->findOrFail($id);

        return view('admin.invoice.show', compact('invoice'));
    }

    public function generateFromOrder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $service = app(InvoiceService::class);
        $invoice = $service->createFromOrder($data['order_id'], [
            'notes' => $data['notes'] ?? null,
        ]);

        return redirect("/admin/invoices/{$invoice->id}")->with('status', "Invoice {$invoice->invoice_number} created.");
    }

    public function pdf(int $id, InvoicePdfService $pdfService): \Symfony\Component\HttpFoundation\Response
    {
        $invoice = Invoice::findOrFail($id);

        return $pdfService->download($invoice);
    }

    public function streamPdf(int $id, InvoicePdfService $pdfService): \Symfony\Component\HttpFoundation\Response
    {
        $invoice = Invoice::findOrFail($id);

        return $pdfService->stream($invoice);
    }

    public function markPaid(int $id): RedirectResponse
    {
        $invoice = Invoice::findOrFail($id);
        abort_if($invoice->status === 'paid', 400, 'Invoice is already paid.');
        abort_if($invoice->status === 'credit_note', 400, 'Cannot mark a credit note as paid.');

        $invoice->update(['status' => 'paid', 'paid_at' => now()]);

        return back()->with('status', 'Invoice marked as paid.');
    }

    public function markPending(int $id): RedirectResponse
    {
        $invoice = Invoice::findOrFail($id);

        $invoice->update(['status' => 'pending', 'paid_at' => null]);

        return back()->with('status', 'Invoice marked as pending.');
    }

    public function cancel(int $id): RedirectResponse
    {
        $invoice = Invoice::findOrFail($id);
        abort_if($invoice->status === 'credit_note', 400, 'Cannot cancel a credit note.');

        $invoice->update(['status' => 'cancelled']);

        return back()->with('status', 'Invoice cancelled.');
    }

    public function createCreditNote(Request $request, int $id): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $service = app(InvoiceService::class);
        $creditNote = $service->createCreditNote($id, $data['reason']);

        return redirect("/admin/invoices/{$creditNote->id}")->with('status', "Credit note {$creditNote->invoice_number} created.");
    }

    public function email(int $id): RedirectResponse
    {
        $invoice = Invoice::findOrFail($id);
        abort_unless($invoice->billing_email, 400, 'No billing email on this invoice.');

        $pdfService = app(InvoicePdfService::class);
        $pdfService->generate($invoice);

        // Queue email with PDF attachment
        // Mail::to($invoice->billing_email)->send(new InvoiceMail($invoice));

        return back()->with('status', "Invoice emailed to {$invoice->billing_email}.");
    }
}
