<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'email' => 'admin@rs.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone' => '081234567890',
                'address' => 'Jl. Sudirman No. 123, Jakarta',
                'status' => 'active',
            ],
            [
                'name' => 'Dr. Ahmad Sulaiman',
                'username' => 'dokter.ahmad',
                'email' => 'ahmad.sulaiman@rs.com',
                'password' => Hash::make('password'),
                'role' => 'dokter',
                'phone' => '081234567891',
                'address' => 'Jl. Thamrin No. 456, Jakarta',
                'status' => 'active',
            ],
            [
                'name' => 'Dr. Sarah Wijaya',
                'username' => 'dokter.sarah',
                'email' => 'sarah.wijaya@rs.com',
                'password' => Hash::make('password'),
                'role' => 'dokter',
                'phone' => '081234567892',
                'address' => 'Jl. Gatot Subroto No. 789, Jakarta',
                'status' => 'active',
            ],
            [
                'name' => 'Suster Maya Indah',
                'username' => 'perawat.maya',
                'email' => 'maya.indah@rs.com',
                'password' => Hash::make('password'),
                'role' => 'perawat',
                'phone' => '081234567893',
                'address' => 'Jl. Sudirman No. 321, Jakarta',
                'status' => 'active',
            ],
            [
                'name' => 'Suster Budi Santoso',
                'username' => 'perawat.budi',
                'email' => 'budi.santoso@rs.com',
                'password' => Hash::make('password'),
                'role' => 'perawat',
                'phone' => '081234567894',
                'address' => 'Jl. Thamrin No. 654, Jakarta',
                'status' => 'active',
            ],
            [
                'name' => 'John Doe',
                'username' => 'pasien.john',
                'email' => 'john.doe@example.com',
                'password' => Hash::make('password'),
                'role' => 'pasien',
                'phone' => '081234567895',
                'address' => 'Jl. Sudirman No. 111, Jakarta',
                'status' => 'active',
            ],
            [
                'name' => 'Jane Smith',
                'username' => 'pasien.jane',
                'email' => 'jane.smith@example.com',
                'password' => Hash::make('password'),
                'role' => 'pasien',
                'phone' => '081234567896',
                'address' => 'Jl. Thamrin No. 222, Jakarta',
                'status' => 'active',
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
