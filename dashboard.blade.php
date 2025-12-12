@extends('layouts.app')

@section('title', 'Dashboard - Sistem Rawat Jalan RS')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h2 mb-0" style="color: var(--primary-brown); font-weight: 700;">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
            </h1>
            <p class="text-muted">Selamat datang di Sistem Rawat Jalan Rumah Sakit</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="stats-number">{{ $todayVisits }}</div>
                <div class="stats-label">
                    <i class="fas fa-stethoscope me-2"></i>Kunjungan Hari Ini
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="stats-number">{{ $todayPatients }}</div>
                <div class="stats-label">
                    <i class="fas fa-users me-2"></i>Pasien Hari Ini
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="stats-number">{{ $todayDoctors }}</div>
                <div class="stats-label">
                    <i class="fas fa-user-md me-2"></i>Dokter Aktif
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="stats-number">Rp {{ number_format($todayPayments, 0, ',', '.') }}</div>
                <div class="stats-label">
                    <i class="fas fa-money-bill-wave me-2"></i>Pendapatan Hari Ini
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Statistics -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="stats-card">
                <div class="stats-number">{{ $monthlyVisits }}</div>
                <div class="stats-label">
                    <i class="fas fa-calendar-alt me-2"></i>Total Kunjungan Bulan Ini
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="stats-card">
                <div class="stats-number">Rp {{ number_format($monthlyRevenue, 0, ',', '.') }}</div>
                <div class="stats-label">
                    <i class="fas fa-chart-line me-2"></i>Pendapatan Bulan Ini
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Waiting Patients -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>Pasien Menunggu
                    </h5>
                </div>
                <div class="card-body">
                    @if($waitingPatients->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>No. Antrian</th>
                                        <th>Nama Pasien</th>
                                        <th>Dokter</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($waitingPatients as $visit)
                                    <tr>
                                        <td>
                                            <span class="badge bg-primary fs-6">{{ $visit->queue_number }}</span>
                                        </td>
                                        <td>{{ $visit->patient->name }}</td>
                                        <td>{{ $visit->doctor->name }}</td>
                                        <td>
                                            @if($visit->status == 'WAITING')
                                                <span class="badge bg-warning">Menunggu</span>
                                            @else
                                                <span class="badge bg-info">Sedang Diperiksa</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                            <p class="mt-3 text-muted">Tidak ada pasien yang menunggu</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Visits -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>Kunjungan Terbaru
                    </h5>
                </div>
                <div class="card-body">
                    @if($recentVisits->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Waktu</th>
                                        <th>Pasien</th>
                                        <th>Dokter</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentVisits as $visit)
                                    <tr>
                                        <td>{{ $visit->registration_time->format('H:i') }}</td>
                                        <td>{{ $visit->patient->name }}</td>
                                        <td>{{ $visit->doctor->name }}</td>
                                        <td>
                                            @if($visit->status == 'COMPLETED')
                                                <span class="badge bg-success">Selesai</span>
                                            @elseif($visit->status == 'EXAMINING')
                                                <span class="badge bg-info">Sedang Diperiksa</span>
                                            @else
                                                <span class="badge bg-warning">Menunggu</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-info-circle text-info" style="font-size: 3rem;"></i>
                            <p class="mt-3 text-muted">Belum ada kunjungan hari ini</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Low Stock Medicines -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>Obat Stok Menipis
                    </h5>
                </div>
                <div class="card-body">
                    @if($lowStockMedicines->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Kode</th>
                                        <th>Nama Obat</th>
                                        <th>Stok</th>
                                        <th>Min. Stok</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($lowStockMedicines as $medicine)
                                    <tr>
                                        <td>{{ $medicine->code }}</td>
                                        <td>{{ $medicine->name }}</td>
                                        <td>{{ $medicine->stock }}</td>
                                        <td>{{ $medicine->min_stock }}</td>
                                        <td>
                                            @if($medicine->stock == 0)
                                                <span class="badge bg-danger">Habis</span>
                                            @else
                                                <span class="badge bg-warning">Menipis</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle text-success" style="font-size: 3rem;"></i>
                            <p class="mt-3 text-muted">Semua stok obat dalam kondisi baik</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
