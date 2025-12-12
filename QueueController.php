<?php

namespace App\Http\Controllers;

use App\Models\Visit;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Carbon\Carbon;

class QueueController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        // Select doctors that are active OR have schedules today OR have visits today
        // This keeps the admin "Manajemen Antrian" in sync with doctor data
            // Map Carbon dayOfWeek (0=Sunday..6=Saturday) to schedule 'day' enum values
            $dayMap = [
                0 => 'Minggu',
                1 => 'Senin',
                2 => 'Selasa',
                3 => 'Rabu',
                4 => 'Kamis',
                5 => 'Jumat',
                6 => 'Sabtu',
            ];

            $todayName = $dayMap[$today->dayOfWeek] ?? null;

            // Select doctors that are active OR have schedules today OR have visits today
            $doctors = Doctor::query()
                ->where('status', 'ACTIVE')
                ->orWhereHas('schedules', function($q) use ($todayName) {
                    if ($todayName) {
                        $q->where('day', $todayName);
                    }
                })
                ->orWhereHas('visits', function($q) use ($today) {
                    $q->whereDate('visit_date', $today);
                })
                ->with(['visits' => function($query) use ($today) {
                    $query->whereDate('visit_date', $today)
                          ->whereIn('status', ['WAITING', 'EXAMINING'])
                          ->orderBy('queue_number')
                          ->with('patient');
                }, 'schedules'])
                ->get();

        return view('queue.index', compact('doctors', 'todayName'));
    }
    
    public function display()
    {
        $today = Carbon::today();
        
        // Get current queue for display
        $currentQueues = Visit::whereDate('visit_date', $today)
            ->whereIn('status', ['WAITING', 'EXAMINING'])
            ->with(['patient', 'doctor'])
            ->orderBy('queue_number')
            ->get()
            ->groupBy('doctor_id');
            
        return view('queue.display', compact('currentQueues'));
    }
    
    public function callNext(Request $request)
    {
        $doctorId = $request->doctor_id;
        $visitId = $request->visit_id ?? null;
        $today = Carbon::today();

        if ($visitId) {
            // Call a specific visit if provided
            $visit = Visit::whereDate('visit_date', $today)
                ->where('id', $visitId)
                ->where('doctor_id', $doctorId)
                ->where('status', 'WAITING')
                ->first();

            if ($visit) {
                $visit->update(['status' => 'EXAMINING']);

                return response()->json([
                    'success' => true,
                    'patient' => $visit->load('patient'),
                    'queue_number' => $visit->queue_number
                ]);
            }

            return response()->json(['success' => false, 'message' => 'Pasien tidak ditemukan atau tidak menunggu']);
        }

        // Get next patient in queue for the doctor
        $nextPatient = Visit::whereDate('visit_date', $today)
            ->where('doctor_id', $doctorId)
            ->where('status', 'WAITING')
            ->orderBy('queue_number')
            ->first();

        if ($nextPatient) {
            $nextPatient->update(['status' => 'EXAMINING']);

            return response()->json([
                'success' => true,
                'patient' => $nextPatient->load('patient'),
                'queue_number' => $nextPatient->queue_number
            ]);
        }

        return response()->json(['success' => false, 'message' => 'No patients in queue']);
    }
    
    public function completeVisit(Request $request)
    {
        $visitId = $request->visit_id;
        
        $visit = Visit::findOrFail($visitId);
        $visit->update([
            'status' => 'COMPLETED',
            'completion_time' => now()
        ]);
        
        return response()->json(['success' => true]);
    }
    
    public function getCurrentQueue()
    {
        $today = Carbon::today();
        
        $queues = Visit::whereDate('visit_date', $today)
            ->whereIn('status', ['WAITING', 'EXAMINING'])
            ->with(['patient', 'doctor'])
            ->orderBy('queue_number')
            ->get()
            ->groupBy('doctor_id');
            
        return response()->json($queues);
    }
    
    /**
     * Show queue management for nurses
     */
    public function perawatQueue()
    {
        $today = Carbon::today();
        
        $waitingPatients = Visit::whereDate('visit_date', $today)
            ->where('status', 'WAITING')
            ->with(['patient', 'doctor'])
            ->orderBy('queue_number')
            ->get();
            
        $examiningPatients = Visit::whereDate('visit_date', $today)
            ->where('status', 'EXAMINING')
            ->with(['patient', 'doctor'])
            ->get();
            
        $completedPatients = Visit::whereDate('visit_date', $today)
            ->where('status', 'COMPLETED')
            ->with(['patient', 'doctor'])
            ->orderBy('completion_time', 'desc')
            ->limit(10)
            ->get();
            
        return view('queue.perawat', compact(
            'waitingPatients',
            'examiningPatients',
            'completedPatients'
        ));
    }
    
    /**
     * Announce patient with voice
     */
    public function announcePatient(Request $request)
    {
        $visitId = $request->visit_id;
        $queueNumber = $request->queue_number;
        $doctorName = $request->doctor_name;
        
        // Return the announcement data for frontend to handle
        return response()->json([
            'success' => true,
            'message' => "Nomor antrian {$queueNumber}, silahkan menuju ke {$doctorName}",
            'queue_number' => $queueNumber,
            'doctor_name' => $doctorName
        ]);
    }

    /**
     * Skip patient in queue
     */
    public function skipPatient(Request $request)
    {
        $visitId = $request->visit_id;
        
        $visit = Visit::findOrFail($visitId);
        
        if ($visit->status === 'WAITING') {
            $visit->update(['status' => 'SKIPPED']);
            
            return response()->json([
                'success' => true,
                'message' => 'Pasien berhasil dilewati'
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Status pasien tidak valid'
        ]);
    }
    
    /**
     * Move patient to different doctor
     */
    public function movePatient(Request $request)
    {
        $request->validate([
            'visit_id' => 'required|exists:visits,id',
            'new_doctor_id' => 'required|exists:doctors,id',
        ]);
        
        $visit = Visit::findOrFail($request->visit_id);
        $newDoctorId = $request->new_doctor_id;
        
        // Get new queue number for the new doctor
        $today = Carbon::today();
        $newQueueNumber = Visit::whereDate('visit_date', $today)
            ->where('doctor_id', $newDoctorId)
            ->whereIn('status', ['WAITING', 'EXAMINING'])
            ->max('queue_number') + 1;
        
        $visit->update([
            'doctor_id' => $newDoctorId,
            'queue_number' => $newQueueNumber,
            'status' => 'WAITING'
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Pasien berhasil dipindahkan'
        ]);
    }
    
    /**
     * Get queue statistics
     */
    public function getQueueStats()
    {
        $today = Carbon::today();
        
        $stats = [
            'total_waiting' => Visit::whereDate('visit_date', $today)
                ->where('status', 'WAITING')->count(),
            'total_examining' => Visit::whereDate('visit_date', $today)
                ->where('status', 'EXAMINING')->count(),
            'total_completed' => Visit::whereDate('visit_date', $today)
                ->where('status', 'COMPLETED')->count(),
            'average_wait_time' => Visit::whereDate('visit_date', $today)
                ->where('status', 'COMPLETED')
                ->whereNotNull('completion_time')
                ->avg(\DB::raw('TIMESTAMPDIFF(MINUTE, registration_time, completion_time)'))
        ];
        
        return response()->json($stats);
    }
}
