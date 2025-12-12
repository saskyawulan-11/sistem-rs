<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Doctor;
use App\Models\Schedule;

class DoctorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $doctors = [
            [
                'name' => 'Dr. Ahmad Sulaiman',
                'license_number' => 'SIP.001.2020',
                'specialization' => 'Dokter Umum',
                'education' => 'S1 Kedokteran Universitas Indonesia',
                'phone' => '081234567890',
                'email' => 'ahmad.sulaiman@rs.com',
                'address' => 'Jl. Sudirman No. 123, Jakarta',
                'status' => 'ACTIVE',
                'schedules' => [
                    ['day' => 'Senin', 'start_time' => '08:00', 'end_time' => '16:00'],
                    ['day' => 'Selasa', 'start_time' => '08:00', 'end_time' => '16:00'],
                    ['day' => 'Rabu', 'start_time' => '08:00', 'end_time' => '16:00'],
                    ['day' => 'Kamis', 'start_time' => '08:00', 'end_time' => '16:00'],
                    ['day' => 'Jumat', 'start_time' => '08:00', 'end_time' => '16:00'],
                ]
            ],
            [
                'name' => 'Dr. Sarah Wijaya',
                'license_number' => 'SIP.002.2020',
                'specialization' => 'Dokter Bedah',
                'education' => 'S1 Kedokteran Universitas Gadjah Mada',
                'phone' => '081234567891',
                'email' => 'sarah.wijaya@rs.com',
                'address' => 'Jl. Thamrin No. 456, Jakarta',
                'status' => 'ACTIVE',
                'schedules' => [
                    ['day' => 'Senin', 'start_time' => '09:00', 'end_time' => '17:00'],
                    ['day' => 'Rabu', 'start_time' => '09:00', 'end_time' => '17:00'],
                    ['day' => 'Jumat', 'start_time' => '09:00', 'end_time' => '17:00'],
                ]
            ],
            [
                'name' => 'Dr. Budi Santoso',
                'license_number' => 'SIP.003.2020',
                'specialization' => 'Dokter Gigi',
                'education' => 'S1 Kedokteran Gigi Universitas Airlangga',
                'phone' => '081234567892',
                'email' => 'budi.santoso@rs.com',
                'address' => 'Jl. Gatot Subroto No. 789, Jakarta',
                'status' => 'ACTIVE',
                'schedules' => [
                    ['day' => 'Selasa', 'start_time' => '08:00', 'end_time' => '16:00'],
                    ['day' => 'Kamis', 'start_time' => '08:00', 'end_time' => '16:00'],
                    ['day' => 'Sabtu', 'start_time' => '08:00', 'end_time' => '14:00'],
                ]
            ],
            [
                'name' => 'Dr. Maya Indah',
                'license_number' => 'SIP.004.2020',
                'specialization' => 'Dokter Anak',
                'education' => 'S1 Kedokteran Universitas Padjadjaran',
                'phone' => '081234567893',
                'email' => 'maya.indah@rs.com',
                'address' => 'Jl. Sudirman No. 321, Jakarta',
                'status' => 'ACTIVE',
                'schedules' => [
                    ['day' => 'Senin', 'start_time' => '08:00', 'end_time' => '16:00'],
                    ['day' => 'Selasa', 'start_time' => '08:00', 'end_time' => '16:00'],
                    ['day' => 'Rabu', 'start_time' => '08:00', 'end_time' => '16:00'],
                    ['day' => 'Kamis', 'start_time' => '08:00', 'end_time' => '16:00'],
                    ['day' => 'Jumat', 'start_time' => '08:00', 'end_time' => '16:00'],
                ]
            ],
        ];

        foreach ($doctors as $doctorData) {
            $schedules = $doctorData['schedules'];
            unset($doctorData['schedules']);
            
            $doctor = Doctor::create($doctorData);
            
            foreach ($schedules as $scheduleData) {
                Schedule::create([
                    'doctor_id' => $doctor->id,
                    'day' => $scheduleData['day'],
                    'start_time' => $scheduleData['start_time'],
                    'end_time' => $scheduleData['end_time'],
                    'max_patients' => 20,
                    'status' => 'ACTIVE',
                ]);
            }
        }
    }
}
