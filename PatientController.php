<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\Visit;
use App\Models\Doctor;
use App\Models\Prescription;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PatientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $patients = Patient::with(['user'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        $totalPatients = Patient::count();
        $newPatientsToday = Patient::whereDate('created_at', today())->count();
        $activePatients = Patient::whereHas('visits', function($query) {
            $query->where('visit_date', '>=', today()->subDays(30));
        })->count();
        
        return view('admin.patients.index', compact(
            'patients',
            'totalPatients',
            'newPatientsToday',
            'activePatients'
        ));
    }

    /**
     * Patient listing for doctors (restricted to their own patients).
     */
    public function dokterPatients()
    {
        $doctor = Doctor::where('user_id', Auth::id())->first();

        if (!$doctor) {
            abort(404, 'Dokter tidak ditemukan');
        }

        $baseQuery = Patient::with(['user'])
            ->whereHas('visits', function($query) use ($doctor) {
                $query->where('doctor_id', $doctor->id);
            })
            ->orderBy('created_at', 'desc');

        $patients = (clone $baseQuery)->paginate(15);
        $totalPatients = (clone $baseQuery)->count();
        $newPatientsToday = (clone $baseQuery)->whereDate('created_at', today())->count();
        $activePatients = (clone $baseQuery)->whereHas('visits', function($query) use ($doctor) {
            $query->where('doctor_id', $doctor->id)
                  ->where('visit_date', '>=', today()->subDays(30));
        })->count();

        return view('admin.patients.index', compact(
            'patients',
            'totalPatients',
            'newPatientsToday',
            'activePatients'
        ));
    }

    /**
     * Patient listing for nurses.
     */
    public function perawatPatients()
    {
        $baseQuery = Patient::with(['user'])
            ->orderBy('created_at', 'desc');

        $patients = (clone $baseQuery)->paginate(15);
        $totalPatients = (clone $baseQuery)->count();
        $newPatientsToday = (clone $baseQuery)->whereDate('created_at', today())->count();
        $activePatients = (clone $baseQuery)->whereHas('visits', function($query) {
            $query->where('visit_date', '>=', today()->subDays(30));
        })->count();

        return view('admin.patients.index', compact(
            'patients',
            'totalPatients',
            'newPatientsToday',
            'activePatients'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.patients.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Create user account (generate a unique username)
        $baseUsername = Str::slug(strtolower(explode('@', $request->email)[0] ?? $request->name), '');
        $usernameCandidate = $baseUsername ?: Str::slug($request->name, '');
        $username = $usernameCandidate;
        $i = 1;
        while (\App\Models\User::where('username', $username)->exists()) {
            $username = $usernameCandidate . $i;
            $i++;
        }

        // Create user
        $user = \App\Models\User::create([
            'name' => $request->name,
            'username' => $username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'pasien',
        ]);

        // Create patient record (Patient model maps date_of_birth -> birth_date and gender values)
        Patient::create([
            'user_id' => $user->id,
            'phone' => $request->phone,
            'address' => $request->address,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
        ]);

        return redirect()->route('patients.index')
            ->with('success', 'Pasien berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $patient = Patient::with(['user', 'visits.doctor', 'visits.prescriptions.items.medicine'])
            ->findOrFail($id);
            
        $totalVisits = $patient->visits()->count();
        $totalPrescriptions = $patient->visits()->withCount('prescriptions')->get()->sum('prescriptions_count');
        $totalPayments = Payment::whereHas('visit', function($query) use ($patient) {
            $query->where('patient_id', $patient->user_id);
        })->where('status', 'PAID')->sum('amount');
        
        $recentVisits = $patient->visits()
            ->with(['doctor'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        return view('admin.patients.show', compact(
            'patient',
            'totalVisits',
            'totalPrescriptions',
            'totalPayments',
            'recentVisits'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $patient = Patient::with('user')->findOrFail($id);
        return view('admin.patients.edit', compact('patient'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $patient = Patient::with('user')->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($patient->user_id),
            ],
            'phone' => 'required|string|max:20',
            'address' => 'required|string',
            'date_of_birth' => 'required|date',
            'gender' => 'required|in:male,female',
        ]);

        // Ensure user exists for this patient; create if missing
        if (!$patient->user) {
            // Generate unique username for new user
            $baseUsername = Str::slug(strtolower(explode('@', $request->email)[0] ?? $request->name), '');
            $usernameCandidate = $baseUsername ?: Str::slug($request->name, '');
            $username = $usernameCandidate;
            $j = 1;
            while (\App\Models\User::where('username', $username)->exists()) {
                $username = $usernameCandidate . $j;
                $j++;
            }

            $user = \App\Models\User::create([
                'name' => $request->name,
                'username' => $username,
                'email' => $request->email,
                // create a random password; admin should reset or inform patient
                'password' => Hash::make(bin2hex(random_bytes(8))),
                'role' => 'pasien',
            ]);

            $patient->user_id = $user->id;
            $patient->save();
            // Populate relation to avoid null when immediately updating
            $patient->setRelation('user', $user);
        } else {
            // Update existing user account
            $patient->user->update([
                'name' => $request->name,
                'email' => $request->email,
            ]);
        }

        // Update patient record (model mutators will map values appropriately)
        $patient->update([
            'phone' => $request->phone,
            'address' => $request->address,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
        ]);

        return redirect()->route('patients.index')
            ->with('success', 'Data pasien berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $patient = Patient::with('user')->findOrFail($id);
        
        // Delete user account (this will cascade delete patient record)
        if ($patient->user) {
            $patient->user->delete();
        } else {
            // If no linked user, delete patient record directly
            $patient->delete();
        }
        
        return redirect()->route('patients.index')
            ->with('success', 'Pasien berhasil dihapus.');
    }

    /**
     * Show patient medical history
     */
    public function medicalHistory(string $id)
    {
        $patient = Patient::with('user')->findOrFail($id);
        
        $visits = Visit::where('patient_id', $patient->user_id)
            ->with(['doctor', 'prescriptions.items.medicine'])
            ->orderBy('visit_date', 'desc')
            ->paginate(10);
            
        $totalVisits = $visits->total();
        $totalPrescriptions = Prescription::whereHas('visit', function($query) use ($patient) {
            $query->where('patient_id', $patient->user_id);
        })->count();
        
        return view('admin.patients.medical-history', compact(
            'patient',
            'visits',
            'totalVisits',
            'totalPrescriptions'
        ));
    }

    /**
     * Show patient profile page
     */
    public function pasienProfile()
    {
        $user = Auth::user();
        
        // Get patient statistics
        $totalVisits = Visit::where('patient_id', $user->id)->count();
        $totalPrescriptions = Prescription::whereHas('visit', function($query) use ($user) {
            $query->where('patient_id', $user->id);
        })->count();
        $totalPayments = Payment::whereHas('visit', function($query) use ($user) {
            $query->where('patient_id', $user->id);
        })->where('status', 'PAID')->sum('amount');
        
        // Calculate days registered with k format
        $daysRegistered = $user->created_at->diffInDays(now());
        $daysRegistered = max(1, round($daysRegistered)); // Ensure minimum 1 day
        $daysRegisteredFormatted = $this->formatNumberWithK($daysRegistered);
        
        // Get recent activity
        $recentVisits = Visit::where('patient_id', $user->id)
            ->with(['doctor'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        $recentPrescriptions = Prescription::whereHas('visit', function($query) use ($user) {
            $query->where('patient_id', $user->id);
        })->with(['doctor', 'items.medicine'])
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();

        return view('pasien.profile', compact(
            'user',
            'totalVisits',
            'totalPrescriptions',
            'totalPayments',
            'daysRegisteredFormatted',
            'recentVisits',
            'recentPrescriptions'
        ));
    }

    /**
     * Format number with 'k' suffix for thousands
     */
    private function formatNumberWithK($number, $decimals = 1)
    {
        // Round the number to avoid long decimal places
        $number = round($number, 0);
        
        if ($number >= 1000) {
            return number_format($number / 1000, $decimals) . 'k';
        }
        return (string) $number;
    }

    /**
     * Update patient profile
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'current_password' => 'nullable|string',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        // Update basic info
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
        ]);

        // Update password if provided
        if ($request->password) {
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'Password saat ini tidak sesuai']);
            }
            
            $user->update([
                'password' => Hash::make($request->password)
            ]);
        }

        return back()->with('success', 'Profil berhasil diperbarui');
    }
}
