<?php

namespace App\Http\Controllers;

use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\Visit;
use App\Models\Medicine;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PrescriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        $query = Prescription::with(['visit.patient', 'visit.doctor', 'items.medicine']);
        
        // If user is a doctor, filter prescriptions by their doctor_id
        if ($user->isDokter()) {
            $doctor = Doctor::where('user_id', $user->id)->first();
            if ($doctor) {
                $query->where('doctor_id', $doctor->id);
            }
        }
        
        $prescriptions = $query->orderBy('created_at', 'desc')->paginate(15);
        
        // Calculate statistics based on filtered query
        $baseQuery = Prescription::query();
        if ($user->isDokter()) {
            $doctor = Doctor::where('user_id', $user->id)->first();
            if ($doctor) {
                $baseQuery->where('doctor_id', $doctor->id);
            }
        }
        
        $totalPrescriptions = (clone $baseQuery)->count();
        $paidPrescriptions = (clone $baseQuery)->where('payment_status', 'PAID')->count();
        $unpaidPrescriptions = (clone $baseQuery)->where('payment_status', 'UNPAID')->count();
        $totalRevenue = (clone $baseQuery)->where('payment_status', 'PAID')->sum('total_amount');
        
        return view('admin.prescriptions.index', compact(
            'prescriptions',
            'totalPrescriptions',
            'paidPrescriptions',
            'unpaidPrescriptions',
            'totalRevenue'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();
        $visitsQuery = Visit::where('status', 'COMPLETED')
            ->whereDoesntHave('prescription')
            ->with(['patient', 'doctor']);
        
        // If user is a doctor, filter visits by their doctor_id
        if ($user->isDokter()) {
            $doctor = Doctor::where('user_id', $user->id)->first();
            if ($doctor) {
                $visitsQuery->where('doctor_id', $doctor->id);
            }
        }
        
        $visits = $visitsQuery->get();
        $medicines = Medicine::where('stock', '>', 0)->orderBy('name')->get();
        
        return view('admin.prescriptions.create', compact('visits', 'medicines'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'visit_id' => 'required|exists:visits,id',
            'medicines' => 'required|array|min:1',
            'medicines.*.medicine_id' => 'required|exists:medicines,id',
            'medicines.*.quantity' => 'required|integer|min:1|max:999',
            'medicines.*.dosage' => 'required|string|max:255',
            'medicines.*.instructions' => 'required|string|max:500',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        
        try {
            $visit = Visit::findOrFail($request->visit_id);
            
            // Calculate total amount and validate stock
            $totalAmount = 0;
            foreach ($request->medicines as $medicineData) {
                $medicine = Medicine::findOrFail($medicineData['medicine_id']);
                
                // Check if stock is sufficient
                if ($medicine->stock < $medicineData['quantity']) {
                    return back()->withErrors(['error' => "Stok obat {$medicine->name} tidak mencukupi. Stok tersedia: {$medicine->stock}"]);
                }
                
                $totalAmount += $medicine->price * $medicineData['quantity'];
            }
            
            // Create prescription
            $prescription = Prescription::create([
                'visit_id' => $visit->id,
                'doctor_id' => $visit->doctor_id,
                'prescription_number' => 'RX' . date('Ymd') . str_pad(Prescription::count() + 1, 3, '0', STR_PAD_LEFT),
                'prescription_date' => now()->toDateString(),
                'total_amount' => $totalAmount,
                'instructions' => $request->notes,
                'status' => 'ACTIVE',
                'payment_status' => 'UNPAID',
            ]);
            
            // Create prescription items
            foreach ($request->medicines as $medicineData) {
                $medicine = Medicine::findOrFail($medicineData['medicine_id']);
                
                PrescriptionItem::create([
                    'prescription_id' => $prescription->id,
                    'medicine_id' => $medicine->id,
                    'quantity' => $medicineData['quantity'],
                    'dosage' => $medicineData['dosage'],
                    'usage_instructions' => $medicineData['instructions'],
                    'unit_price' => $medicine->price,
                    'subtotal' => $medicine->price * $medicineData['quantity'],
                ]);
                
                // Update medicine stock
                $medicine->decrement('stock', $medicineData['quantity']);
            }
            
            DB::commit();
            
            // Redirect based on user role
            $redirectRoute = Auth::user()->isDokter() ? 'dokter.prescriptions.show' : 'prescriptions.show';
            return redirect()->route($redirectRoute, $prescription)
                ->with('success', 'Resep berhasil dibuat.');
                
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Prescription creation error: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withErrors(['error' => 'Terjadi kesalahan saat membuat resep: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $prescription = Prescription::with(['visit.patient', 'visit.doctor', 'items.medicine'])
            ->findOrFail($id);
        
        // Check authorization: if user is a doctor, they can only access their own prescriptions
        $user = Auth::user();
        if ($user->isDokter()) {
            $doctor = Doctor::where('user_id', $user->id)->first();
            if (!$doctor || $prescription->doctor_id !== $doctor->id) {
                abort(403, 'Anda tidak memiliki akses ke halaman ini.');
            }
        }
            
        return view('admin.prescriptions.show', compact('prescription'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $prescription = Prescription::with(['visit.patient', 'visit.doctor', 'items.medicine'])
            ->findOrFail($id);
        
        // Check authorization: if user is a doctor, they can only access their own prescriptions
        $user = Auth::user();
        if ($user->isDokter()) {
            $doctor = Doctor::where('user_id', $user->id)->first();
            if (!$doctor || $prescription->doctor_id !== $doctor->id) {
                abort(403, 'Anda tidak memiliki akses ke halaman ini.');
            }
        }
            
        $medicines = Medicine::where('stock', '>', 0)->orderBy('name')->get();
        
        return view('admin.prescriptions.edit', compact('prescription', 'medicines'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $prescription = Prescription::with('items')->findOrFail($id);
        
        // Check authorization: if user is a doctor, they can only update their own prescriptions
        $user = Auth::user();
        if ($user->isDokter()) {
            $doctor = Doctor::where('user_id', $user->id)->first();
            if (!$doctor || $prescription->doctor_id !== $doctor->id) {
                abort(403, 'Anda tidak memiliki akses ke halaman ini.');
            }
        }
        
        $request->validate([
            'medicines' => 'required|array|min:1',
            'medicines.*.medicine_id' => 'required|exists:medicines,id',
            'medicines.*.quantity' => 'required|integer|min:1',
            'medicines.*.dosage' => 'required|string',
            'medicines.*.instructions' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        
        try {
            // Restore stock from old items
            foreach ($prescription->items as $item) {
                $item->medicine->increment('stock', $item->quantity);
            }
            
            // Delete old items
            $prescription->items()->delete();
            
            // Calculate new total amount
            $totalAmount = 0;
            foreach ($request->medicines as $medicineData) {
                $medicine = Medicine::findOrFail($medicineData['medicine_id']);
                $totalAmount += $medicine->price * $medicineData['quantity'];
            }
            
            // Update prescription
            $prescription->update([
                'total_amount' => $totalAmount,
                'notes' => $request->notes,
            ]);
            
            // Create new prescription items
            foreach ($request->medicines as $medicineData) {
                $medicine = Medicine::findOrFail($medicineData['medicine_id']);
                
                PrescriptionItem::create([
                    'prescription_id' => $prescription->id,
                    'medicine_id' => $medicine->id,
                    'quantity' => $medicineData['quantity'],
                    'dosage' => $medicineData['dosage'],
                    'usage_instructions' => $medicineData['instructions'],
                    'unit_price' => $medicine->price,
                    'subtotal' => $medicine->price * $medicineData['quantity'],
                ]);
                
                // Update medicine stock
                $medicine->decrement('stock', $medicineData['quantity']);
            }
            
            DB::commit();
            
            // Redirect based on user role - check which route was used
            $user = Auth::user();
            if ($user->isDokter()) {
                // Try dokter route first, fallback to compatibility route
                try {
                    return redirect()->route('dokter.prescriptions.show', $prescription)
                        ->with('success', 'Resep berhasil diperbarui.');
                } catch (\Exception $e) {
                    return redirect()->route('prescriptions.show.dokter', $prescription)
                        ->with('success', 'Resep berhasil diperbarui.');
                }
            }
            
            return redirect()->route('prescriptions.show', $prescription)
                ->with('success', 'Resep berhasil diperbarui.');
                
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Terjadi kesalahan saat memperbarui resep.']);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $prescription = Prescription::with('items')->findOrFail($id);
        
        // Check authorization: if user is a doctor, they can only delete their own prescriptions
        $user = Auth::user();
        if ($user->isDokter()) {
            $doctor = Doctor::where('user_id', $user->id)->first();
            if (!$doctor || $prescription->doctor_id !== $doctor->id) {
                abort(403, 'Anda tidak memiliki akses ke halaman ini.');
            }
        }
        
        DB::beginTransaction();
        
        try {
            // Restore stock
            foreach ($prescription->items as $item) {
                $item->medicine->increment('stock', $item->quantity);
            }
            
            $prescription->delete();
            
            DB::commit();
            
            // Redirect based on user role
            $redirectRoute = Auth::user()->isDokter() ? 'dokter.prescriptions.index' : 'prescriptions.index';
            return redirect()->route($redirectRoute)
                ->with('success', 'Resep berhasil dihapus.');
                
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Terjadi kesalahan saat menghapus resep.']);
        }
    }

    /**
     * Print prescription
     */
    public function print(string $id)
    {
        $prescription = Prescription::with(['visit.patient', 'visit.doctor', 'items.medicine'])
            ->findOrFail($id);
        
        // Check authorization: if user is a doctor, they can only print their own prescriptions
        $user = Auth::user();
        if ($user->isDokter()) {
            $doctor = Doctor::where('user_id', $user->id)->first();
            if (!$doctor || $prescription->doctor_id !== $doctor->id) {
                abort(403, 'Anda tidak memiliki akses ke halaman ini.');
            }
        }
            
        return view('admin.prescriptions.print', compact('prescription'));
    }

    /**
     * Dispense prescription
     */
    public function dispense(Request $request, string $id)
    {
        $prescription = Prescription::findOrFail($id);
        
        // Check authorization: if user is a doctor, they can only dispense their own prescriptions
        $user = Auth::user();
        if ($user->isDokter()) {
            $doctor = Doctor::where('user_id', $user->id)->first();
            if (!$doctor || $prescription->doctor_id !== $doctor->id) {
                abort(403, 'Anda tidak memiliki akses ke halaman ini.');
            }
        }
        
        $prescription->update([
            'status' => 'DISPENSED',
            'dispensed_at' => now(),
            'dispensed_by' => Auth::user()->name ?? 'System',
        ]);

        // Redirect based on user role
        $redirectRoute = Auth::user()->isDokter() ? 'dokter.prescriptions.show' : 'prescriptions.show';
        return redirect()->route($redirectRoute, $prescription)
            ->with('success', 'Resep berhasil diserahkan.');
    }

    /**
     * Show patient's prescriptions
     */
    public function pasienPrescriptions()
    {
        $user = Auth::user();
        
        $prescriptions = Prescription::whereHas('visit', function($query) use ($user) {
            $query->where('patient_id', $user->id);
        })->with(['doctor', 'visit', 'items.medicine'])
        ->orderBy('created_at', 'desc')
        ->paginate(10);
        
        // Get statistics
        $totalPrescriptions = Prescription::whereHas('visit', function($query) use ($user) {
            $query->where('patient_id', $user->id);
        })->count();
        
        $paidPrescriptions = Prescription::whereHas('visit', function($query) use ($user) {
            $query->where('patient_id', $user->id);
        })->where('payment_status', 'PAID')->count();
        
        $unpaidPrescriptions = Prescription::whereHas('visit', function($query) use ($user) {
            $query->where('patient_id', $user->id);
        })->where('payment_status', 'UNPAID')->count();
        
        $totalAmount = Prescription::whereHas('visit', function($query) use ($user) {
            $query->where('patient_id', $user->id);
        })->where('payment_status', 'PAID')->sum('total_amount');

        return view('pasien.prescriptions', compact(
            'prescriptions',
            'totalPrescriptions',
            'paidPrescriptions',
            'unpaidPrescriptions',
            'totalAmount'
        ));
    }
}