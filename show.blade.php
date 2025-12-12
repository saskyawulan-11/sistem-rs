@extends('layouts.app')

@section('title', 'Detail Obat')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h1 class="h3">Detail Obat - {{ $medicine->name }}</h1>
            <p class="text-muted">Informasi lengkap obat dan penggunaan dalam resep.</p>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5>{{ $medicine->name }}</h5>
                    <p class="mb-1"><strong>Kategori:</strong> {{ $medicine->category }}</p>
                    <p class="mb-1"><strong>Unit:</strong> {{ $medicine->unit }}</p>
                    <p class="mb-1"><strong>Harga:</strong> Rp {{ number_format($medicine->price, 0, ',', '.') }}</p>
                    <p class="mb-1"><strong>Stok:</strong> {{ $medicine->stock }}</p>
                    <p class="mb-1"><strong>Minimum Stok:</strong> {{ $medicine->min_stock }}</p>
                    <p class="mb-1"><strong>Batch:</strong> {{ $medicine->batch_number }}</p>
                    <p class="mb-1"><strong>Pabrikan:</strong> {{ $medicine->manufacturer }}</p>
                    <p class="mb-1"><strong>Kadaluarsa:</strong> {{ $medicine->expiry_date?->toDateString() }}</p>

                    <a href="{{ route('medicines.edit', $medicine->id) }}" class="btn btn-warning mt-2">Edit</a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">Riwayat Resep Terbaru</div>
                <div class="card-body">
                    @if($recentPrescriptions->isEmpty())
                        <p class="text-muted">Belum ada resep yang menggunakan obat ini.</p>
                    @else
                        <ul class="list-group">
                            @foreach($recentPrescriptions as $item)
                                <li class="list-group-item">
                                    <strong>{{ optional($item->prescription->visit->patient->user)->name ?? optional($item->prescription->visit->patient)->name }}</strong>
                                    <div><small class="text-muted">Tanggal: {{ $item->created_at->toDateString() }} | Kunjungan: {{ $item->prescription->visit->visit_number }}</small></div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">Semua Penggunaan Obat ({{ $prescriptionItems->total() }})</div>
                <div class="card-body">
                    @if($prescriptionItems->isEmpty())
                        <p class="text-muted">Tidak ada penggunaan obat yang tercatat.</p>
                    @else
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nama Pasien</th>
                                    <th>Kuantitas</th>
                                    <th>Tanggal</th>
                                    <th>Kunjungan</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($prescriptionItems as $item)
                                    <tr>
                                        <td>{{ $item->id }}</td>
                                        <td>{{ optional($item->prescription->visit->patient->user)->name ?? optional($item->prescription->visit->patient)->name }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ $item->created_at->toDateString() }}</td>
                                        <td>{{ $item->prescription->visit->visit_number }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                        {{ $prescriptionItems->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
