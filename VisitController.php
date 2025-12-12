<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\Patient;
use App\Models\Doctor;
use App\Models\Schedule;
use App\Models\MedicalRecord;
use App\Models\Prescription;
use App\Models\Payment;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;

class VisitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $visits = Visit::with(['patient.user', 'doctor.user'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        $totalVisits = Visit::count();
        $todayVisits = Visit::whereDate('visit_date', today())->count();
        $completedVisits = Visit::where('status', 'COMPLETED')->count();
        $waitingVisits = Visit::where('status', 'WAITING')->count();
        
        return view('admin.visits.index', compact(
            'visits',
            'totalVisits',
            'todayVisits',
            'completedVisits',
            'waitingVisits'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $patients = Patient::with('user')->get();
        // Read optional query param visit_date (admin may set it from UI), default to today
        $visitDate = request()->get('visit_date', today()->toDateString());
        $selectedDoctor = request()->get('selected_doctor');
        $dayOfWeek = Carbon::parse($visitDate)->format('l');
        $dayMap = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa', 
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu'
        ];

        $day = $dayMap[$dayOfWeek] ?? null;

        if ($day) {
            $doctors = Doctor::whereHas('schedules', function($query) use ($day) {
                $query->where('day', $day)
                      ->where('status', 'ACTIVE');
            })->get();
        } else {
            $doctors = collect();
        }

        return view('admin.visits.create', compact('patients', 'doctors', 'visitDate', 'selectedDoctor'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'visit_date' => 'required|date|after_or_equal:today',
            'complaints' => 'nullable|string',
        ]);

        // Check if doctor has schedule for the visit date
        $dayOfWeek = Carbon::parse($request->visit_date)->format('l');
        $dayMap = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa', 
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu'
        ];
        
        $schedule = Schedule::where('doctor_id', $request->doctor_id)
            ->where('day', $dayMap[$dayOfWeek])
            ->where('status', 'ACTIVE')
            ->first();
            
        if (!$schedule) {
            return back()->withErrors(['doctor_id' => 'Dokter tidak memiliki jadwal pada hari tersebut']);
        }

        // Generate queue number for the day
        $todayVisits = Visit::where('doctor_id', $request->doctor_id)
            ->whereDate('visit_date', $request->visit_date)
            ->count();
            
        $queueNumber = $todayVisits + 1;

        // Generate visit number
        $visitNumber = 'V' . date('Ymd') . str_pad($queueNumber, 3, '0', STR_PAD_LEFT);

        $visit = Visit::create([
            'patient_id' => $request->patient_id,
            'doctor_id' => $request->doctor_id,
            'visit_number' => $visitNumber,
            'queue_number' => $queueNumber,
            'visit_date' => $request->visit_date,
            'registration_time' => now(),
            'complaints' => $request->complaints,
            'status' => 'WAITING',
            'payment_status' => 'UNPAID',
        ]);

        return redirect()->route('visits.show', $visit)
            ->with('success', 'Kunjungan berhasil dibuat');
    }

    /**
     * Display the specified resource.
     */
    public function show(Visit $visit)
    {
        $visit->load(['patient.user', 'doctor.user', 'prescription.items.medicine', 'medicalRecord', 'payments']);
        
        return view('admin.visits.show', compact('visit'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Visit $visit)
    {
        $patients = Patient::with('user')->get();
        // Allow admin to pass visit_date via query param to filter doctors; default to visit's date
        $visitDate = request()->get('visit_date', $visit->visit_date->toDateString());
        $selectedDoctor = request()->get('selected_doctor', $visit->doctor_id);

        $dayOfWeek = Carbon::parse($visitDate)->format('l');
        $dayMap = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa', 
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu'
        ];

        $day = $dayMap[$dayOfWeek] ?? null;

        if ($day) {
            $doctors = Doctor::whereHas('schedules', function($query) use ($day) {
                $query->where('day', $day)
                      ->where('status', 'ACTIVE');
            })->get();
        } else {
            $doctors = collect();
        }

        return view('admin.visits.edit', compact('visit', 'patients', 'doctors', 'visitDate', 'selectedDoctor'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Visit $visit)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'required|exists:doctors,id',
            'visit_date' => 'required|date',
            'complaints' => 'nullable|string',
            'diagnosis' => 'nullable|string',
            'treatment_plan' => 'nullable|string',
        ]);

        $visit->update($request->all());

        return redirect()->route('visits.show', $visit)
            ->with('success', 'Kunjungan berhasil diperbarui');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Visit $visit)
    {
        $visit->delete();

        return redirect()->route('visits.index')
            ->with('success', 'Kunjungan berhasil dihapus');
    }

    /**
     * Show patient registration form
     */
    public function showRegistration()
    {
        // If a visit_date is provided (e.g., via query) use it, otherwise default to today
        $visitDate = request()->get('visit_date', today()->toDateString());

        $dayOfWeek = Carbon::parse($visitDate)->format('l');
        $dayMap = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa', 
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu'
        ];

        $day = $dayMap[$dayOfWeek] ?? null;

        // Only include doctors who have an ACTIVE schedule on the selected day
        if ($day) {
            $doctors = Doctor::whereHas('schedules', function($query) use ($day) {
                $query->where('day', $day)
                      ->where('status', 'ACTIVE');
            })->get();
        } else {
            // Fallback to empty collection if day mapping fails
            $doctors = collect();
        }

        return view('visits.register', compact('doctors'));
    }

    /**
     * Process patient registration
     */
    public function register(Request $request)
    {
        $request->validate([
            'medical_record_number' => 'required|string|unique:patients,medical_record_number',
            'name' => 'required|string|max:255',
            'identity_number' => 'required|string|unique:patients,identity_number',
            'gender' => 'required|in:L,P',
            'birth_date' => 'required|date',
            'address' => 'required|string',
            'phone' => 'required|string',
            'insurance_type' => 'required|in:BPJS,MANDIRI',
            'bpjs_number' => 'nullable|string',
            'doctor_id' => 'required|exists:doctors,id',
            'visit_date' => 'required|date|after_or_equal:today',
            'complaints' => 'nullable|string',
            'emergency_contact_name' => 'nullable|string',
            'emergency_contact_phone' => 'nullable|string',
            'allergies' => 'nullable|string',
            'medical_history' => 'nullable|string',
        ]);

        // Create patient
        $patient = Patient::create([
            'medical_record_number' => $request->medical_record_number,
            'name' => $request->name,
            'identity_number' => $request->identity_number,
            'gender' => $request->gender,
            'birth_date' => $request->birth_date,
            'address' => $request->address,
            'phone' => $request->phone,
            'insurance_type' => $request->insurance_type,
            'bpjs_number' => $request->bpjs_number,
            'emergency_contact_name' => $request->emergency_contact_name,
            'emergency_contact_phone' => $request->emergency_contact_phone,
            'allergies' => $request->allergies,
            'medical_history' => $request->medical_history,
            'status' => 'ACTIVE',
        ]);

        // Check doctor schedule
        $dayOfWeek = Carbon::parse($request->visit_date)->format('l');
        $dayMap = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa', 
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu'
        ];
        
        $schedule = Schedule::where('doctor_id', $request->doctor_id)
            ->where('day', $dayMap[$dayOfWeek])
            ->where('status', 'ACTIVE')
            ->first();
            
        if (!$schedule) {
            return back()->withErrors(['doctor_id' => 'Dokter tidak memiliki jadwal pada hari tersebut']);
        }

        // Generate queue number
        $todayVisits = Visit::where('doctor_id', $request->doctor_id)
            ->whereDate('visit_date', $request->visit_date)
            ->count();
            
        $queueNumber = $todayVisits + 1;

        // Generate visit number
        $visitNumber = 'V' . date('Ymd') . str_pad($queueNumber, 3, '0', STR_PAD_LEFT);

        // Create visit
        $visit = Visit::create([
            'patient_id' => $patient->id,
            'doctor_id' => $request->doctor_id,
            'visit_number' => $visitNumber,
            'queue_number' => $queueNumber,
            'visit_date' => $request->visit_date,
            'registration_time' => now(),
            'complaints' => $request->complaints,
            'status' => 'WAITING',
            'payment_status' => 'UNPAID',
        ]);

        return redirect()->route('queue.ticket', $visit)
            ->with('success', 'Pendaftaran berhasil! Silakan ambil tiket antrian.');
    }

    /**
     * Show doctor's visits
     */
    public function dokterVisits()
    {
        $user = auth()->user();
        $doctor = Doctor::where('user_id', $user->id)->first();
        
        if (!$doctor) {
            abort(404, 'Dokter tidak ditemukan');
        }
        
        $visits = Visit::where('doctor_id', $doctor->id)
            ->with(['patient.user', 'prescription', 'payments'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        // Get statistics
        $totalVisits = Visit::where('doctor_id', $doctor->id)->count();
        $todayVisits = Visit::where('doctor_id', $doctor->id)
            ->whereDate('visit_date', today())
            ->count();
        $completedVisits = Visit::where('doctor_id', $doctor->id)
            ->where('status', 'COMPLETED')
            ->count();
        $waitingVisits = Visit::where('doctor_id', $doctor->id)
            ->where('status', 'WAITING')
            ->count();

        return view('admin.visits.index', compact(
            'visits',
            'totalVisits',
            'todayVisits',
            'completedVisits',
            'waitingVisits'
        ));
    }

    /**
     * Show patient's visits
     */
    public function pasienVisits()
    {
        $user = auth()->user();
        
        $visits = Visit::where('patient_id', $user->id)
            ->with(['doctor', 'prescription', 'payments'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        // Get statistics
        $totalVisits = Visit::where('patient_id', $user->id)->count();
        $completedVisits = Visit::where('patient_id', $user->id)
            ->where('status', 'COMPLETED')
            ->count();
        $upcomingVisits = Visit::where('patient_id', $user->id)
            ->where('visit_date', '>=', today())
            ->where('status', 'WAITING')
            ->count();

        return view('pasien.visits', compact(
            'visits',
            'totalVisits',
            'completedVisits',
            'upcomingVisits'
        ));
    }

    /**
     * Show queue ticket
     */
    public function showTicket(Visit $visit)
    {
        return view('visits.ticket', compact('visit'));
    }

    /**
     * Show examination form
     */
    public function examination(Visit $visit)
    {
        $visit->load(['patient.user', 'doctor.user']);
        
        return view('visits.examination', compact('visit'));
    }

    /**
     * Save examination results
     */
    public function saveExamination(Request $request, Visit $visit)
    {
        $request->validate([
            'diagnosis' => 'required|string',
            'treatment_plan' => 'required|string',
            'symptoms' => 'required|string',
            'treatment' => 'required|string',
            'notes' => 'nullable|string',
            'blood_pressure' => 'nullable|numeric',
            'temperature' => 'nullable|numeric',
            'pulse_rate' => 'nullable|integer',
            'weight' => 'nullable|numeric',
            'height' => 'nullable|numeric',
        ]);

        // Update visit
        $visit->update([
            'diagnosis' => $request->diagnosis,
            'treatment_plan' => $request->treatment_plan,
            'status' => 'COMPLETED',
            'completion_time' => now(),
        ]);

        // Create medical record
        MedicalRecord::create([
            'patient_id' => $visit->patient_id,
            'visit_id' => $visit->id,
            'doctor_id' => $visit->doctor_id,
            'symptoms' => $request->symptoms,
            'diagnosis' => $request->diagnosis,
            'treatment' => $request->treatment,
            'notes' => $request->notes,
            'blood_pressure' => $request->blood_pressure,
            'temperature' => $request->temperature,
            'pulse_rate' => $request->pulse_rate,
            'weight' => $request->weight,
            'height' => $request->height,
        ]);

        return redirect()->route('visits.show', $visit)
            ->with('success', 'Hasil pemeriksaan berhasil disimpan');
    }

    /**
     * Generate reports
     */
    public function report(Request $request)
    {
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth());
        $endDate = $request->get('end_date', Carbon::now()->endOfMonth());

        $visits = Visit::with(['patient', 'doctor'])
            ->whereBetween('visit_date', [$startDate, $endDate])
            ->orderBy('visit_date', 'desc')
            ->get();

        $statistics = [
            'total_visits' => $visits->count(),
            'completed_visits' => $visits->where('status', 'COMPLETED')->count(),
            'waiting_visits' => $visits->where('status', 'WAITING')->count(),
            'total_revenue' => $visits->sum('total_cost'),
        ];

        return view('visits.report', compact('visits', 'statistics', 'startDate', 'endDate'));
    }
}
