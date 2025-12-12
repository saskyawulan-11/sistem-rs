<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\PrescriptionItem;
use Illuminate\Http\Request;

class MedicineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $medicines = Medicine::orderBy('name')
            ->paginate(15);
            
        $totalMedicines = Medicine::count();
        $lowStockMedicines = Medicine::whereRaw('stock <= min_stock')->count();
        $outOfStockMedicines = Medicine::where('stock', 0)->count();
        $totalValue = Medicine::sum(\DB::raw('stock * price'));
        
        return view('admin.medicines.index', compact(
            'medicines',
            'totalMedicines',
            'lowStockMedicines',
            'outOfStockMedicines',
            'totalValue'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.medicines.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:100',
            'unit' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'min_stock' => 'required|integer|min:0',
            'expiry_date' => 'nullable|date|after:today',
            'manufacturer' => 'nullable|string|max:255',
            'batch_number' => 'nullable|string|max:100',
        ]);

        Medicine::create($request->all());

        return redirect()->route('medicines.index')
            ->with('success', 'Obat berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $medicine = Medicine::findOrFail($id);
        
        $prescriptionItems = PrescriptionItem::where('medicine_id', $medicine->id)
            ->with(['prescription.visit.patient', 'prescription.visit.doctor'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        $totalPrescribed = PrescriptionItem::where('medicine_id', $medicine->id)
            ->sum('quantity');
            
        $recentPrescriptions = PrescriptionItem::where('medicine_id', $medicine->id)
            ->with(['prescription.visit.patient'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        return view('admin.medicines.show', compact(
            'medicine',
            'prescriptionItems',
            'totalPrescribed',
            'recentPrescriptions'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $medicine = Medicine::findOrFail($id);
        return view('admin.medicines.edit', compact('medicine'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $medicine = Medicine::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:100',
            'unit' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'min_stock' => 'required|integer|min:0',
            'expiry_date' => 'nullable|date|after:today',
            'manufacturer' => 'nullable|string|max:255',
            'batch_number' => 'nullable|string|max:100',
        ]);

        $medicine->update($request->all());

        return redirect()->route('medicines.index')
            ->with('success', 'Data obat berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $medicine = Medicine::findOrFail($id);
        
        // Check if medicine is used in prescriptions
        $prescriptionCount = PrescriptionItem::where('medicine_id', $medicine->id)->count();
        
        if ($prescriptionCount > 0) {
            return redirect()->route('medicines.index')
                ->with('error', 'Obat tidak dapat dihapus karena sudah digunakan dalam resep.');
        }
        
        $medicine->delete();
        
        return redirect()->route('medicines.index')
            ->with('success', 'Obat berhasil dihapus.');
    }

    /**
     * Show stock alert page
     */
    public function stockAlert()
    {
        $lowStockMedicines = Medicine::whereRaw('stock <= min_stock')
            ->orderBy('stock')
            ->paginate(15);
            
        $outOfStockMedicines = Medicine::where('stock', 0)
            ->orderBy('name')
            ->get();
            
        $expiringMedicines = Medicine::where('expiry_date', '<=', now()->addDays(30))
            ->where('expiry_date', '>', now())
            ->orderBy('expiry_date')
            ->get();
            
        return view('admin.medicines.stock-alert', compact(
            'lowStockMedicines',
            'outOfStockMedicines',
            'expiringMedicines'
        ));
    }

    /**
     * Update medicine stock
     */
    public function updateStock(Request $request, string $id)
    {
        $medicine = Medicine::findOrFail($id);
        
        $request->validate([
            'stock' => 'required|integer|min:0',
            'min_stock' => 'required|integer|min:0',
        ]);

        $medicine->update([
            'stock' => $request->stock,
            'min_stock' => $request->min_stock,
        ]);

        return redirect()->route('medicines.show', $medicine->id)
            ->with('success', 'Stok obat berhasil diperbarui.');
    }

    /**
     * Generate medicine reports
     */
    public function report(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        $medicines = Medicine::withCount(['prescriptionItems as total_prescribed' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }])
        ->withSum(['prescriptionItems as total_quantity' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }], 'quantity')
        ->orderBy('total_prescribed', 'desc')
        ->get();

        $statistics = [
            'total_medicines' => $medicines->count(),
            'low_stock_medicines' => $medicines->where('stock', '<=', 'min_stock')->count(),
            'out_of_stock_medicines' => $medicines->where('stock', 0)->count(),
            'total_prescribed' => $medicines->sum('total_prescribed'),
        ];

        return view('admin.medicines.report', compact('medicines', 'statistics', 'startDate', 'endDate'));
    }
}