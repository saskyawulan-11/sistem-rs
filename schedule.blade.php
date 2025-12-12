@extends('layouts.app')

@section('title', 'Jadwal Dokter - Admin')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3">Jadwal Dokter - {{ optional($doctor->user)->name ?? $doctor->name }}</h1>
            <p class="text-muted">Kelola jadwal praktik untuk dokter ini.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">Daftar Jadwal</div>
                <div class="card-body">
                    @if($schedules->isEmpty())
                        <p class="text-muted">Belum ada jadwal untuk dokter ini.</p>
                    @else
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Hari</th>
                                    <th>Mulai</th>
                                    <th>Selesai</th>
                                    <th>Maks Pasien</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($schedules as $schedule)
                                    <tr>
                                        <td>{{ $schedule->day }}</td>
                                        <td>{{ $schedule->start_time }}</td>
                                        <td>{{ $schedule->end_time }}</td>
                                        <td>{{ $schedule->max_patients }}</td>
                                        <td>
                                            <form action="{{ route('doctors.schedule.destroy', [$doctor->id, $schedule->id]) }}" method="POST" style="display:inline">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-sm btn-danger" onclick="return confirm('Hapus jadwal?')">Hapus</button>
                                            </form>
                                            <a href="{{ route('doctors.schedule.edit', [$doctor->id, $schedule->id]) }}" class="btn btn-sm btn-warning">Edit</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">Tambah Jadwal</div>
                <div class="card-body">
                    <form action="{{ route('doctors.schedule.store', $doctor->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="day_of_week" class="form-label">Hari</label>
                            <select name="day_of_week" id="day_of_week" class="form-control">
                                <option value="1">Senin</option>
                                <option value="2">Selasa</option>
                                <option value="3">Rabu</option>
                                <option value="4">Kamis</option>
                                <option value="5">Jumat</option>
                                <option value="6">Sabtu</option>
                                <option value="0">Minggu</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="start_time" class="form-label">Mulai</label>
                            <input type="time" name="start_time" id="start_time" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="end_time" class="form-label">Selesai</label>
                            <input type="time" name="end_time" id="end_time" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="max_patients" class="form-label">Maks Pasien</label>
                            <input type="number" min="1" name="max_patients" id="max_patients" class="form-control" value="20" required>
                        </div>
                        <button class="btn btn-primary">Simpan Jadwal</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <h5>Catatan</h5>
                    <p class="text-muted">Hari disimpan sebagai nama (Senin, Selasa, ...). Gunakan formulir di samping untuk menambah jadwal.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
