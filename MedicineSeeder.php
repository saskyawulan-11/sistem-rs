<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Medicine;

class MedicineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $medicines = [
            [
                'code' => 'OBT001',
                'name' => 'Paracetamol 500mg',
                'description' => 'Obat penurun demam dan pereda nyeri',
                'category' => 'Analgesik',
                'unit' => 'Tablet',
                'price' => 5000,
                'stock' => 100,
                'min_stock' => 20,
                'status' => 'AVAILABLE'
            ],
            [
                'code' => 'OBT002',
                'name' => 'Amoxicillin 500mg',
                'description' => 'Antibiotik untuk infeksi bakteri',
                'category' => 'Antibiotik',
                'unit' => 'Kapsul',
                'price' => 15000,
                'stock' => 50,
                'min_stock' => 10,
                'status' => 'AVAILABLE'
            ],
            [
                'code' => 'OBT003',
                'name' => 'Omeprazole 20mg',
                'description' => 'Obat untuk sakit maag dan asam lambung',
                'category' => 'Antasida',
                'unit' => 'Kapsul',
                'price' => 12000,
                'stock' => 75,
                'min_stock' => 15,
                'status' => 'AVAILABLE'
            ],
            [
                'code' => 'OBT004',
                'name' => 'Cetirizine 10mg',
                'description' => 'Obat anti alergi',
                'category' => 'Antihistamin',
                'unit' => 'Tablet',
                'price' => 8000,
                'stock' => 60,
                'min_stock' => 12,
                'status' => 'AVAILABLE'
            ],
            [
                'code' => 'OBT005',
                'name' => 'Ibuprofen 400mg',
                'description' => 'Obat anti inflamasi dan pereda nyeri',
                'category' => 'Anti Inflamasi',
                'unit' => 'Tablet',
                'price' => 7000,
                'stock' => 80,
                'min_stock' => 16,
                'status' => 'AVAILABLE'
            ],
            [
                'code' => 'OBT006',
                'name' => 'Metformin 500mg',
                'description' => 'Obat untuk diabetes melitus',
                'category' => 'Antidiabetik',
                'unit' => 'Tablet',
                'price' => 25000,
                'stock' => 40,
                'min_stock' => 8,
                'status' => 'AVAILABLE'
            ],
            [
                'code' => 'OBT007',
                'name' => 'Amlodipine 5mg',
                'description' => 'Obat untuk tekanan darah tinggi',
                'category' => 'Antihipertensi',
                'unit' => 'Tablet',
                'price' => 18000,
                'stock' => 35,
                'min_stock' => 7,
                'status' => 'AVAILABLE'
            ],
            [
                'code' => 'OBT008',
                'name' => 'Simvastatin 20mg',
                'description' => 'Obat untuk menurunkan kolesterol',
                'category' => 'Antilipidemik',
                'unit' => 'Tablet',
                'price' => 22000,
                'stock' => 30,
                'min_stock' => 6,
                'status' => 'AVAILABLE'
            ],
            [
                'code' => 'OBT009',
                'name' => 'Loratadine 10mg',
                'description' => 'Obat anti alergi generasi kedua',
                'category' => 'Antihistamin',
                'unit' => 'Tablet',
                'price' => 10000,
                'stock' => 45,
                'min_stock' => 9,
                'status' => 'AVAILABLE'
            ],
            [
                'code' => 'OBT010',
                'name' => 'Ranitidine 150mg',
                'description' => 'Obat untuk sakit maag dan tukak lambung',
                'category' => 'Antasida',
                'unit' => 'Tablet',
                'price' => 9000,
                'stock' => 55,
                'min_stock' => 11,
                'status' => 'AVAILABLE'
            ],
        ];

        foreach ($medicines as $medicine) {
            Medicine::create($medicine);
        }
    }
}
