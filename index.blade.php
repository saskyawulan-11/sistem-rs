@extends('layouts.app')

@section('title', 'Manajemen Pasien - Sistem Rawat Jalan RS')

@section('content')
@php
    $isAdmin = auth()->check() && auth()->user()->isAdmin();
@endphp
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h2 mb-0" style="color: var(--primary-brown); font-weight: 700;">
                <i class="fas fa-users me-2"></i>Manajemen Pasien
            </h1>
            <p class="text-muted">Kelola data pasien dan riwayat medis</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="stats-number">
                    @php
                        echo $totalPatients >= 1000 ? number_format($totalPatients / 1000, 1) . 'k' : $totalPatients;
                    @endphp
                </div>
                <div class="stats-label">
                    <i class="fas fa-users me-2"></i>Total Pasien
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="stats-number">
                    @php
                        echo $newPatientsToday >= 1000 ? number_format($newPatientsToday / 1000, 1) . 'k' : $newPatientsToday;
                    @endphp
                </div>
                <div class="stats-label">
                    <i class="fas fa-user-plus me-2"></i>Pasien Baru Hari Ini
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="stats-number">
                    @php
                        echo $activePatients >= 1000 ? number_format($activePatients / 1000, 1) . 'k' : $activePatients;
                    @endphp
                </div>
                <div class="stats-label">
                    <i class="fas fa-heartbeat me-2"></i>Pasien Aktif (30 hari)
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="stats-card">
                <div class="stats-number">
                    <i class="fas fa-chart-line me-2"></i>
                </div>
                <div class="stats-label">
                    <i class="fas fa-trending-up me-2"></i>Pertumbuhan
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                @if($isAdmin)
                    <div>
                        <a href="{{ route('patients.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Tambah Pasien
                        </a>
                        <button class="btn btn-success" onclick="exportPatients()">
                            <i class="fas fa-download me-2"></i>Export Data
                        </button>
                    </div>
                @endif
                <div>
                    <input type="text" class="form-control" placeholder="Cari pasien..." id="searchInput">
                </div>
            </div>
        </div>
    </div>

    <!-- Patients Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>Daftar Pasien
                    </h5>
                </div>
                <div class="card-body">
                    @if($patients->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama</th>
                                        <th>Email</th>
                                        <th>Telepon</th>
                                        <th>Tanggal Lahir</th>
                                        <th>Jenis Kelamin</th>
                                        <th>Tanggal Daftar</th>
                                        @if($isAdmin)
                                            <th>Aksi</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($patients as $index => $patient)
                                    <tr>
                                        <td>{{ $patients->firstItem() + $index }}</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2">
                                                    {{ substr(optional($patient->user)->name ?? ($patient->name ?? 'P'), 0, 1) }}
                                                </div>
                                                <div>
                                                    <strong>{{ optional($patient->user)->name ?? ($patient->name ?? 'N/A') }}</strong>
                                                    <br>
                                                    <small class="text-muted">ID: {{ $patient->id }}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ optional($patient->user)->email ?? '-' }}</td>
                                        <td>{{ $patient->phone }}</td>
                                        <td>{{ \Carbon\Carbon::parse($patient->date_of_birth)->format('d/m/Y') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $patient->gender === 'male' ? 'primary' : 'pink' }}">
                                                {{ $patient->gender === 'male' ? 'Laki-laki' : 'Perempuan' }}
                                            </span>
                                        </td>
                                        <td>{{ $patient->created_at->format('d/m/Y H:i') }}</td>
                                        @if($isAdmin)
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('patients.show', $patient->id) }}" class="btn btn-sm btn-info" title="Lihat Detail">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('patients.edit', $patient->id) }}" class="btn btn-sm btn-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="{{ route('patients.medical-history', $patient->id) }}" class="btn btn-sm btn-success" title="Riwayat Medis">
                                                        <i class="fas fa-history"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-danger" onclick="deletePatient({{ $patient->id }})" title="Hapus">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <div class="d-flex justify-content-center">
                            {{ $patients->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Belum ada data pasien</h5>
                            <p class="text-muted">Klik tombol "Tambah Pasien" untuk menambahkan pasien pertama</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus pasien ini?</p>
                <p class="text-danger"><strong>Perhatian:</strong> Tindakan ini tidak dapat dibatalkan!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function deletePatient(patientId) {
    document.getElementById('deleteForm').action = `/patients/${patientId}`;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

function exportPatients() {
    // Implement export functionality
    alert('Fitur export akan segera tersedia');
}

// Search functionality
document.getElementById('searchInput').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});
</script>
@endpush
