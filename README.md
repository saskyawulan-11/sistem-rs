# Sistem Rawat Jalan Rumah Sakit

Sistem informasi rawat jalan rumah sakit yang komprehensif untuk mengelola seluruh proses pelayanan pasien rawat jalan, dari pendaftaran hingga pembayaran.

## 🏥 Fitur Utama

### 📋 Modul Pasien
- Pendaftaran pasien baru
- Cetak kartu peserta
- Integrasi BPJS dan Mandiri
- Riwayat kunjungan pasien

### 🏥 Modul Rawat Jalan
- Pendaftaran antrian otomatis
- Sistem antrian real-time
- Display antrian dengan suara
- Pencatatan keluhan dan diagnosis

### 👨‍⚕️ Modul Dokter
- Data dokter dan spesialisasi
- Jadwal praktik dokter
- Manajemen antrian per dokter

### 💊 Modul Resep Obat
- Pembuatan resep oleh dokter
- Cetak resep untuk farmasi
- Status pembayaran resep
- Integrasi dengan payment gateway

### 💳 Modul Pembayaran
- Pembayaran mandiri
- Integrasi BPJS otomatis
- Payment gateway integration
- Status transaksi real-time

### 📊 Modul Laporan
- Laporan kunjungan pasien
- Laporan penggunaan obat
- Statistik pendapatan
- Analisis data

## 🎨 Tema & Desain

- **Warna**: Coklat, putih, hitam (sesuai permintaan)
- **Font**: Inter (modern, tebal, jelas)
- **UI**: Modern, menarik, cantik
- **Responsive**: Mobile-friendly

## 🚀 Instalasi

### Prerequisites
- PHP 8.2+
- MySQL 8.0+
- Composer
- Node.js & NPM

### Langkah Instalasi

1. **Clone Repository**
```bash
git clone <repository-url>
cd rs
```

2. **Install Dependencies**
```bash
composer install
npm install
```

3. **Setup Environment**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Konfigurasi Database**
Edit file `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rs
DB_USERNAME=root
DB_PASSWORD=
```

5. **Jalankan Migration & Seeder**
```bash
php artisan migrate:fresh --seed
```

6. **Build Assets**
```bash
npm run build
```

7. **Jalankan Server**
```bash
php artisan serve
```

## 📱 Cara Penggunaan

### 1. Pendaftaran Pasien
- Akses `/register` untuk pendaftaran publik
- Isi form data pasien lengkap
- Pilih dokter dan tanggal kunjungan
- Sistem otomatis generate nomor antrian
- Cetak tiket antrian

### 2. Manajemen Antrian
- Akses `/queue` untuk staff
- Lihat antrian per dokter
- Panggil pasien dengan suara
- Update status pemeriksaan

### 3. Display Antrian
- Akses `/queue-display` untuk tampilan publik
- Tampilan real-time nomor antrian
- Auto-refresh setiap 30 detik
- Suara panggilan otomatis

### 4. Pemeriksaan Dokter
- Akses `/visits/{id}/examination`
- Catat diagnosis dan pengobatan
- Buat resep obat
- Update status kunjungan

### 5. Pembayaran
- Integrasi BPJS otomatis
- Payment gateway untuk mandiri
- Status pembayaran real-time
- Cetak bukti pembayaran

## 🗂️ Struktur Database

### Tabel Utama
- `patients` - Data pasien
- `doctors` - Data dokter
- `schedules` - Jadwal dokter
- `visits` - Kunjungan pasien
- `medicines` - Data obat
- `prescriptions` - Resep obat
- `prescription_items` - Item resep
- `payments` - Data pembayaran
- `medical_records` - Rekam medis

## 🔧 Konfigurasi

### Payment Gateway
Untuk integrasi payment gateway, edit file `.env`:
```env
MIDTRANS_SERVER_KEY=your_server_key
MIDTRANS_CLIENT_KEY=your_client_key
MIDTRANS_MERCHANT_ID=your_merchant_id
```

### BPJS Integration
Untuk integrasi BPJS, edit file `.env`:
```env
BPJS_USERNAME=your_username
BPJS_PASSWORD=your_password
BPJS_URL=https://api.bpjs-kesehatan.go.id
```

## 📊 Dashboard

Dashboard menampilkan:
- Statistik kunjungan hari ini
- Pasien menunggu
- Obat stok menipis
- Pendapatan real-time
- Grafik kunjungan bulanan

## 🔐 Keamanan

- Authentication Laravel
- Role-based access control
- CSRF protection
- Input validation
- SQL injection prevention

## 📈 Monitoring

- Log aktivitas sistem
- Error tracking
- Performance monitoring
- Backup otomatis

## 🐛 Troubleshooting

### Common Issues

1. **Migration Error**
```bash
php artisan migrate:fresh --seed
```

2. **Permission Error**
```bash
chmod -R 755 storage bootstrap/cache
```

3. **Composer Error**
```bash
composer dump-autoload
```

## 📞 Support

Untuk bantuan teknis:
- Email: support@rs.com
- Phone: +62-21-1234567
- Documentation: `/docs`

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🤝 Contributing

1. Fork the project
2. Create feature branch
3. Commit changes
4. Push to branch
5. Create Pull Request

---

**Sistem Rawat Jalan RS** - Solusi lengkap untuk manajemen rawat jalan rumah sakit modern.
