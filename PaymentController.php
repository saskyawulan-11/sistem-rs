<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Visit;
use App\Models\Prescription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $payments = Payment::with(['visit.patient', 'visit.doctor'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        $totalPayments = Payment::count();
        $paidPayments = Payment::where('status', 'PAID')->count();
        $pendingPayments = Payment::where('status', 'PENDING')->count();
        $failedPayments = Payment::where('status', 'FAILED')->count();
        $totalRevenue = Payment::where('status', 'PAID')->sum('amount');
        
        return view('admin.payments.index', compact(
            'payments',
            'totalPayments',
            'paidPayments',
            'pendingPayments',
            'failedPayments',
            'totalRevenue'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $visits = Visit::where('status', 'COMPLETED')
            ->whereDoesntHave('payments', function($query) {
                $query->where('status', 'PAID');
            })
            ->with(['patient.user', 'doctor.user', 'prescription'])
            ->orderBy('visit_date', 'desc')
            ->get();
            
        return view('admin.payments.create', compact('visits'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'visit_id' => 'required|exists:visits,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:CASH,TRANSFER,DEBIT_CARD,CREDIT_CARD',
            'notes' => 'nullable|string',
        ]);

        $visit = Visit::with('prescription')->findOrFail($request->visit_id);
        
        // Generate payment number
        $paymentNumber = 'PAY' . date('Ymd') . str_pad(Payment::count() + 1, 3, '0', STR_PAD_LEFT);

        $payment = Payment::create([
            'visit_id' => $visit->id,
            'payment_number' => $paymentNumber,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'status' => 'PENDING',
            'notes' => $request->notes,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('payments.show', $payment)
            ->with('success', 'Pembayaran berhasil dibuat.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $payment = Payment::with(['visit.patient', 'visit.doctor', 'visit.prescription.items.medicine'])
            ->findOrFail($id);
            
        return view('admin.payments.show', compact('payment'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $payment = Payment::with(['visit.patient', 'visit.doctor'])->findOrFail($id);
        
        return view('admin.payments.edit', compact('payment'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $payment = Payment::findOrFail($id);
        
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|in:CASH,TRANSFER,DEBIT_CARD,CREDIT_CARD',
            'status' => 'required|in:PENDING,PAID,FAILED,CANCELLED',
            'notes' => 'nullable|string',
        ]);

        $payment->update($request->all());

        // Update prescription payment status if payment is completed
        if ($request->status === 'PAID') {
            $payment->visit->prescription?->update(['payment_status' => 'PAID']);
        }

        return redirect()->route('payments.show', $payment)
            ->with('success', 'Data pembayaran berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $payment = Payment::findOrFail($id);
        
        if ($payment->status === 'PAID') {
            return redirect()->route('payments.index')
                ->with('error', 'Pembayaran yang sudah lunas tidak dapat dihapus.');
        }
        
        $payment->delete();
        
        return redirect()->route('payments.index')
            ->with('success', 'Pembayaran berhasil dihapus.');
    }

    /**
     * Process payment
     */
    public function process(string $id)
    {
        $payment = Payment::with(['visit.prescription'])->findOrFail($id);
        
        return view('admin.payments.process', compact('payment'));
    }

    /**
     * Get bill amount for a visit
     */
    public function getBill($visitId)
    {
        try {
            $visit = Visit::with(['prescription.items.medicine'])->findOrFail($visitId);
            
            $totalAmount = 0;
            $billDetails = [];
            
            // Calculate prescription total if exists
            if ($visit->prescription) {
                $totalAmount = $visit->prescription->total_amount;
                $billDetails[] = [
                    'type' => 'Resep Obat',
                    'amount' => $visit->prescription->total_amount,
                    'items' => $visit->prescription->items->map(function($item) {
                        return [
                            'name' => $item->medicine->name,
                            'quantity' => $item->quantity,
                            'price' => $item->unit_price,
                            'subtotal' => $item->subtotal
                        ];
                    })
                ];
            }
            
            // Add consultation fee (if needed)
            $consultationFee = 50000; // Default consultation fee
            $totalAmount += $consultationFee;
            $billDetails[] = [
                'type' => 'Biaya Konsultasi',
                'amount' => $consultationFee,
                'items' => []
            ];
            
            return response()->json([
                'success' => true,
                'total_amount' => $totalAmount,
                'bill_details' => $billDetails,
                'visit' => [
                    'id' => $visit->id,
                    'visit_number' => $visit->visit_number,
                    'patient_name' => optional($visit->patient->user)->name ?? optional($visit->patient)->name,
                    'doctor_name' => optional($visit->doctor->user)->name ?? optional($visit->doctor)->name,
                    'visit_date' => $visit->visit_date->format('d F Y')
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Kunjungan tidak ditemukan atau belum memiliki resep'
            ], 404);
        }
    }

    /**
     * Confirm payment
     */
    public function confirm(Request $request, string $id)
    {
        $payment = Payment::with(['visit.prescription'])->findOrFail($id);
        
        $request->validate([
            'payment_method' => 'required|in:CASH,TRANSFER,DEBIT_CARD,CREDIT_CARD',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        
        try {
            $payment->update([
                'status' => 'PAID',
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number,
                'notes' => $request->notes,
                'paid_at' => now(),
            ]);
            
            // Update prescription payment status
            if ($payment->visit->prescription) {
                $payment->visit->prescription->update(['payment_status' => 'PAID']);
            }
            
            DB::commit();
            
            return redirect()->route('payments.show', $payment)
                ->with('success', 'Pembayaran berhasil dikonfirmasi.');
                
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Terjadi kesalahan saat mengkonfirmasi pembayaran.']);
        }
    }

    /**
     * Generate payment reports
     */
    public function report(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        $payments = Payment::with(['visit.patient', 'visit.doctor'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->get();

        $statistics = [
            'total_payments' => $payments->count(),
            'paid_payments' => $payments->where('status', 'PAID')->count(),
            'pending_payments' => $payments->where('status', 'PENDING')->count(),
            'failed_payments' => $payments->where('status', 'FAILED')->count(),
            'total_revenue' => $payments->where('status', 'PAID')->sum('amount'),
            'cash_payments' => $payments->where('payment_method', 'CASH')->where('status', 'PAID')->sum('amount'),
            'transfer_payments' => $payments->where('payment_method', 'TRANSFER')->where('status', 'PAID')->sum('amount'),
            'card_payments' => $payments->whereIn('payment_method', ['DEBIT_CARD', 'CREDIT_CARD'])->where('status', 'PAID')->sum('amount'),
        ];

        return view('admin.payments.report', compact('payments', 'statistics', 'startDate', 'endDate'));
    }

    /**
     * Show patient's payments
     */
    public function pasienPayments()
    {
        $user = Auth::user();
        
        $payments = Payment::whereHas('visit', function($query) use ($user) {
            $query->where('patient_id', $user->id);
        })->with(['visit.doctor'])
        ->orderBy('created_at', 'desc')
        ->paginate(10);
        
        // Get statistics
        $totalPayments = Payment::whereHas('visit', function($query) use ($user) {
            $query->where('patient_id', $user->id);
        })->count();
        
        $paidAmount = Payment::whereHas('visit', function($query) use ($user) {
            $query->where('patient_id', $user->id);
        })->where('status', 'PAID')->sum('amount');
        
        $pendingAmount = Payment::whereHas('visit', function($query) use ($user) {
            $query->where('patient_id', $user->id);
        })->where('status', 'PENDING')->sum('amount');
        
        $failedAmount = Payment::whereHas('visit', function($query) use ($user) {
            $query->where('patient_id', $user->id);
        })->where('status', 'FAILED')->sum('amount');

        return view('pasien.payments', compact(
            'payments',
            'totalPayments',
            'paidAmount',
            'pendingAmount',
            'failedAmount'
        ));
    }
}