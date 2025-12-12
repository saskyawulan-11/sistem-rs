@extends('layouts.app')

@section('title', 'Riwayat Medis - Sistem Rawat Jalan RS')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h2 mb-0" style="color: var(--primary-brown); font-weight: 700;">
                        <i class="fas fa-history me-2"></i>Riwayat Medis
                    </h1>
                    <p class="text-muted">Riwayat lengkap kunjungan dan perawatan pasien</p>
                </div>
                <div>
                    <a href="{{ route('patients.show', $patient->id) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Kembali ke Detail
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Patient Info -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-2">
                            <div class="avatar-lg bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; font-size: 1.5rem;">
                                {{ substr(optional($patient->user)->name ?? ($patient->name ?? 'P'), 0, 1) }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h5 class="mb-1">{{ optional($patient->user)->name ?? ($patient->name ?? 'N/A') }}</h5>
                            <p class="text-muted mb-1">{{ optional($patient->user)->email ?? '-' }}</p>
                            <p class="text-muted mb-0">{{ $patient->phone }}</p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="row text-center">
                                <div class="col-4">
                                    <h6 class="mb-0 text-primary">{{ $totalVisits }}</h6>
                                    <small class="text-muted">Total Kunjungan</small>
                                </div>
                                <div class="col-4">
                                    <h6 class="mb-0 text-success">{{ $totalPrescriptions }}</h6>
                                    <small class="text-muted">Total Resep</small>
                                </div>
                                <div class="col-4">
                                    <h6 class="mb-0 text-warning">{{ \Carbon\Carbon::parse($patient->date_of_birth)->age }}</h6>
                                    <small class="text-muted">Usia</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Options -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Tanggal Mulai</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="{{ request('start_date') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">Tanggal Selesai</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="{{ request('end_date') }}">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">Semua Status</option>
                                <option value="WAITING" {{ request('status') === 'WAITING' ? 'selected' : '' }}>Menunggu</option>
                                <option value="EXAMINING" {{ request('status') === 'EXAMINING' ? 'selected' : '' }}>Sedang Diperiksa</option>
                                <option value="COMPLETED" {{ request('status') === 'COMPLETED' ? 'selected' : '' }}>Selesai</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-2"></i>Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Medical History -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-clipboard-list me-2"></i>Riwayat Kunjungan
                    </h5>
                </div>
                <div class="card-body">
                    @if($visits->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Dokter</th>
                                        <th>Keluhan</th>
                                        <th>Diagnosis</th>
                                        <th>Resep</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($visits as $index => $visit)
                                    <tr>
                                        <td>{{ $visits->firstItem() + $index }}</td>
                                        <td>
                                            <div>
                                                <strong>{{ $visit->visit_date->format('d/m/Y') }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $visit->visit_date->format('H:i') }}</small>
                                            </div>
                                        </td>
                                        <td>
                                            <div>
                                                <strong>{{ optional(optional($visit->doctor)->user)->name ?? 'Dokter' }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $visit->doctor->specialization }}</small>
                                            </div>
                                        </td>
                                        <td>{{ Str::limit($visit->complaints, 50) }}</td>
                                        <td>{{ Str::limit($visit->diagnosis, 50) }}</td>
                                        <td>
                                            @if($visit->prescriptions->count() > 0)
                                                <span class="badge bg-success">{{ $visit->prescriptions->count() }} Resep</span>
                                            @else
                                                <span class="badge bg-secondary">Tidak Ada</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-{{ $visit->status === 'COMPLETED' ? 'success' : ($visit->status === 'WAITING' ? 'warning' : 'info') }}">
                                                {{ $visit->status === 'COMPLETED' ? 'Selesai' : ($visit->status === 'WAITING' ? 'Menunggu' : 'Sedang Diperiksa') }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('visits.show', $visit->id) }}" class="btn btn-sm btn-info" title="Lihat Detail">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                @if($visit->prescriptions->count() > 0)
                                                    <a href="{{ route('prescriptions.show', $visit->prescriptions->first()->id) }}" class="btn btn-sm btn-success" title="Lihat Resep">
                                                        <i class="fas fa-prescription"></i>
                                                    </a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="d-flex justify-content-center">
                            {{ $visits->appends(request()->query())->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Belum ada riwayat kunjungan</h5>
                            <p class="text-muted">Pasien belum pernah melakukan kunjungan ke rumah sakit</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Set default date range to last 30 days if not specified
document.addEventListener('DOMContentLoaded', function() {
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    
    if (!startDate.value) {
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
        startDate.value = thirtyDaysAgo.toISOString().split('T')[0];
    }
    
    if (!endDate.value) {
        const today = new Date();
        endDate.value = today.toISOString().split('T')[0];
    }
});
</script>
@endpush
