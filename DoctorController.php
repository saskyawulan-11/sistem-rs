<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Schedule;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DoctorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $doctors = Doctor::with(['user', 'schedules'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        $totalDoctors = Doctor::count();
        // Map numeric dayOfWeek (0=Sunday..6=Saturday) to schedule 'day' enum
        $days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
        $todayName = $days[now()->dayOfWeek];

        $activeDoctors = Doctor::whereHas('schedules', function($query) use ($todayName) {
            $query->where('day', $todayName);
        })->count();
        $todayVisits = Visit::whereDate('visit_date', today())
            ->whereHas('doctor')
            ->count();
        
        return view('admin.doctors.index', compact(
            'doctors',
            'totalDoctors',
            'activeDoctors',
            'todayVisits'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.doctors.create');
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
            'specialization' => 'required|string|max:255',
            'license_number' => 'required|string|max:50|unique:doctors,license_number',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Generate unique username and create user account
        $baseUsername = Str::slug(strtolower(explode('@', $request->email)[0] ?? $request->name), '');
        $usernameCandidate = $baseUsername ?: Str::slug($request->name, '');
        $username = $usernameCandidate;
        $i = 1;
        while (\App\Models\User::where('username', $username)->exists()) {
            $username = $usernameCandidate . $i;
            $i++;
        }

        $user = \App\Models\User::create([
            'name' => $request->name,
            'username' => $username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'dokter',
        ]);

        // Create doctor record
        Doctor::create([
            'user_id' => $user->id,
            'phone' => $request->phone,
            'specialization' => $request->specialization,
            'license_number' => $request->license_number,
        ]);

        return redirect()->route('doctors.index')
            ->with('success', 'Dokter berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $doctor = Doctor::with(['user', 'schedules', 'visits.patient'])
            ->findOrFail($id);
            
        $totalVisits = $doctor->visits()->count();
        $todayVisits = $doctor->visits()->whereDate('visit_date', today())->count();
        $monthlyVisits = $doctor->visits()
            ->whereMonth('visit_date', now()->month)
            ->whereYear('visit_date', now()->year)
            ->count();
            
        $recentVisits = $doctor->visits()
            ->with(['patient'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();
            
        return view('admin.doctors.show', compact(
            'doctor',
            'totalVisits',
            'todayVisits',
            'monthlyVisits',
            'recentVisits'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $doctor = Doctor::with('user')->findOrFail($id);
        return view('admin.doctors.edit', compact('doctor'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $doctor = Doctor::with('user')->findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($doctor->user_id),
            ],
            'phone' => 'required|string|max:20',
            'specialization' => 'required|string|max:255',
            'license_number' => 'required|string|max:50|unique:doctors,license_number,' . $doctor->id,
        ]);

        // Ensure user account exists. If missing, create one and attach to doctor.
        if (! $doctor->user) {
            // Generate unique username
            $baseUsername = Str::slug(strtolower(explode('@', $request->email)[0] ?? $request->name), '');
            $usernameCandidate = $baseUsername ?: Str::slug($request->name, '');
            $username = $usernameCandidate;
            $i = 1;
            while (\App\Models\User::where('username', $username)->exists()) {
                $username = $usernameCandidate . $i;
                $i++;
            }

            $user = \App\Models\User::create([
                'name' => $request->name,
                'username' => $username,
                'email' => $request->email,
                'password' => Hash::make(Str::random(12)),
                'role' => 'dokter',
            ]);

            $doctor->user_id = $user->id;
            $doctor->save();
            // Ensure the relation is populated - avoid cached null relation
            $doctor->setRelation('user', $user);
        }

        // Update user account
        $doctor->user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        // Update doctor record
        $doctor->update([
            'phone' => $request->phone,
            'specialization' => $request->specialization,
            'license_number' => $request->license_number,
        ]);

        return redirect()->route('doctors.index')
            ->with('success', 'Data dokter berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $doctor = Doctor::with('user')->findOrFail($id);
        // If a linked user exists, delete it (will cascade-delete doctor if FK constrained).
        if ($doctor->user) {
            $doctor->user->delete();
        } else {
            // No linked user - delete doctor record directly.
            $doctor->delete();
        }
        
        return redirect()->route('doctors.index')
            ->with('success', 'Dokter berhasil dihapus.');
    }

    /**
     * Show doctor schedule management
     */
    public function schedule(string $id)
    {
        $doctor = Doctor::with(['user', 'schedules'])->findOrFail($id);
        // Order schedules by weekday order (Senin..Minggu) then start_time
        $order = "FIELD(day, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu')";
        $schedules = $doctor->schedules()
            ->orderByRaw($order)
            ->orderBy('start_time')
            ->get();
            
        return view('admin.doctors.schedule', compact('doctor', 'schedules'));
    }

    /**
     * Store doctor schedule
     */
    public function storeSchedule(Request $request, string $id)
    {
        $doctor = Doctor::findOrFail($id);
        
        $request->validate([
            'day_of_week' => 'required|integer|between:0,6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'max_patients' => 'required|integer|min:1',
        ]);

        // Map day_of_week index to day name as stored in DB
        $days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
        $dayName = $days[$request->day_of_week] ?? null;

        Schedule::create([
            'doctor_id' => $doctor->id,
            'day' => $dayName,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'max_patients' => $request->max_patients,
        ]);

        return redirect()->route('doctors.schedule', $doctor->id)
            ->with('success', 'Jadwal dokter berhasil ditambahkan.');
    }

    /**
     * Update doctor schedule
     */
    public function updateSchedule(Request $request, string $id, string $scheduleId)
    {
        $schedule = Schedule::where('doctor_id', $id)->findOrFail($scheduleId);
        
        $request->validate([
            'day_of_week' => 'required|integer|between:0,6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'max_patients' => 'required|integer|min:1',
        ]);

        // Map incoming day_of_week to DB 'day'
        $data = $request->all();
        if (isset($data['day_of_week'])) {
            $days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
            $data['day'] = $days[$data['day_of_week']] ?? $data['day_of_week'];
            unset($data['day_of_week']);
        }

        $schedule->update($data);

        return redirect()->route('doctors.schedule', $id)
            ->with('success', 'Jadwal dokter berhasil diperbarui.');
    }

    /**
     * Delete doctor schedule
     */
    public function destroySchedule(string $id, string $scheduleId)
    {
        $schedule = Schedule::where('doctor_id', $id)->findOrFail($scheduleId);
        $schedule->delete();

        return redirect()->route('doctors.schedule', $id)
            ->with('success', 'Jadwal dokter berhasil dihapus.');
    }

    /**
     * Show edit form for a schedule
     */
    public function editSchedule(string $id, string $scheduleId)
    {
        $doctor = Doctor::with(['user'])->findOrFail($id);
        $schedule = Schedule::where('doctor_id', $id)->findOrFail($scheduleId);
        return view('admin.doctors.schedule-edit', compact('doctor', 'schedule'));
    }
}