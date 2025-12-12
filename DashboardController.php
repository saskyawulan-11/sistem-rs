<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Visit;
use App\Models\Medicine;
use App\Models\Prescription;
use App\Models\Payment;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        
        // Statistics for today
        $todayVisits = Visit::whereDate('visit_date', $today)->count();
        $todayPatients = Visit::whereDate('visit_date', $today)->distinct('patient_id')->count();
        $todayDoctors = Visit::whereDate('visit_date', $today)->distinct('doctor_id')->count();
        $todayPayments = Payment::whereDate('created_at', $today)->where('status', 'PAID')->sum('amount');
        
        // Waiting patients
        $waitingPatients = Visit::whereDate('visit_date', $today)
            ->where('status', 'WAITING')
            ->with(['patient', 'doctor'])
            ->orderBy('queue_number')
            ->get();
            
        // Recent visits
        $recentVisits = Visit::with(['patient', 'doctor'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
            
        // Low stock medicines
        $lowStockMedicines = Medicine::whereRaw('stock <= min_stock')
            ->orderBy('stock')
            ->limit(5)
            ->get();
            
        // Monthly statistics
        $monthlyVisits = Visit::whereMonth('visit_date', $today->month)
            ->whereYear('visit_date', $today->year)
            ->count();
            
        $monthlyRevenue = Payment::whereMonth('created_at', $today->month)
            ->whereYear('created_at', $today->year)
            ->where('status', 'PAID')
            ->sum('amount');

        return view('dashboard.admin', compact(
            'todayVisits',
            'todayPatients', 
            'todayDoctors',
            'todayPayments',
            'waitingPatients',
            'recentVisits',
            'lowStockMedicines',
            'monthlyVisits',
            'monthlyRevenue'
        ));
    }

    public function dokterDashboard()
    {
        $user = auth()->user();
        $today = Carbon::today();
        
        // Get doctor's visits for today
        $todayVisits = Visit::where('doctor_id', $user->id)
            ->whereDate('visit_date', $today)
            ->count();
            
        $waitingVisits = Visit::where('doctor_id', $user->id)
            ->whereDate('visit_date', $today)
            ->where('status', 'WAITING')
            ->with(['patient'])
            ->orderBy('queue_number')
            ->get();
            
        $examiningVisits = Visit::where('doctor_id', $user->id)
            ->whereDate('visit_date', $today)
            ->where('status', 'EXAMINING')
            ->with(['patient'])
            ->get();
            
        $completedVisits = Visit::where('doctor_id', $user->id)
            ->whereDate('visit_date', $today)
            ->where('status', 'COMPLETED')
            ->with(['patient'])
            ->get();

        return view('dashboard.dokter', compact(
            'todayVisits',
            'waitingVisits',
            'examiningVisits',
            'completedVisits'
        ));
    }

    public function perawatDashboard()
    {
        $today = Carbon::today();
        
        // Statistics for today
        $todayVisits = Visit::whereDate('visit_date', $today)->count();
        $waitingPatients = Visit::whereDate('visit_date', $today)
            ->where('status', 'WAITING')
            ->with(['patient', 'doctor'])
            ->orderBy('queue_number')
            ->get();
            
        $examiningPatients = Visit::whereDate('visit_date', $today)
            ->where('status', 'EXAMINING')
            ->with(['patient', 'doctor'])
            ->get();

        return view('dashboard.perawat', compact(
            'todayVisits',
            'waitingPatients',
            'examiningPatients'
        ));
    }

    public function pasienDashboard()
    {
        $user = auth()->user();
        
        // Get patient's visits
        $recentVisits = Visit::where('patient_id', $user->id)
            ->with(['doctor'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        $upcomingVisits = Visit::where('patient_id', $user->id)
            ->where('visit_date', '>=', today())
            ->where('status', 'WAITING')
            ->with(['doctor'])
            ->get();
            
        $prescriptions = Prescription::whereHas('visit', function($query) use ($user) {
            $query->where('patient_id', $user->id);
        })->with(['doctor', 'items.medicine'])
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();

        return view('dashboard.pasien', compact(
            'recentVisits',
            'upcomingVisits',
            'prescriptions'
        ));
    }
}
