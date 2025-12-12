@extends('layouts.app')

@section('title', 'Edit Jadwal Dokter')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h1 class="h3">Edit Jadwal - {{ optional($doctor->user)->name ?? $doctor->name }}</h1>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('doctors.schedule.update', [$doctor->id, $schedule->id]) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="day_of_week" class="form-label">Hari</label>
                            <select name="day_of_week" id="day_of_week" class="form-control">
                                @php
                                    $map = ['0' => 'Minggu','1' => 'Senin','2' => 'Selasa','3' => 'Rabu','4' => 'Kamis','5' => 'Jumat','6' => 'Sabtu'];
                                    $currentIndex = array_search($schedule->day, $map);
                                @endphp
                                @foreach($map as $index => $label)
                                    <option value="{{ $index }}" {{ $currentIndex === (int)$index ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="start_time" class="form-label">Mulai</label>
                            <input type="time" name="start_time" id="start_time" class="form-control" value="{{ $schedule->start_time }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="end_time" class="form-label">Selesai</label>
                            <input type="time" name="end_time" id="end_time" class="form-control" value="{{ $schedule->end_time }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="max_patients" class="form-label">Maks Pasien</label>
                            <input type="number" min="1" name="max_patients" id="max_patients" class="form-control" value="{{ $schedule->max_patients }}" required>
                        </div>

                        <button class="btn btn-primary">Simpan Perubahan</button>
                        <a href="{{ route('doctors.schedule', $doctor->id) }}" class="btn btn-secondary">Batal</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
