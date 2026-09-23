@extends('app')

@section('title', 'Dashboard Pemilik Motor')

@section('content')
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm p-4 bg-primary text-white">
                <h5>Total Pendapatan Bagi Hasil Anda</h5>
                <h2 class="fw-bold">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</h2>
                <small>Akun Pemilik: {{ $owner->name ?? 'Belum ada pemilik' }} ({{ $owner->email ?? '-' }})</small>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm p-4">
                <h5>Total Motor Dititipkan</h5>
                <h2 class="fw-bold text-secondary">{{ $motors->count() }} Unit</h2>
                <small class="text-muted">Status motor diperbarui otomatis oleh sistem</small>
            </div>
        </div>
    </div>
    <div class="card shadow-sm p-4 mb-4">
        <h5 class="fw-bold mb-3">Titipkan / Tambah Unit Motor Baru</h5>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <form action="/owner/motors/web" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Merek Motor</label>
                    <input type="text" name="merek" class="form-control" placeholder="Contoh: Yamaha NMAX 155" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Kapasitas CC</label>
                    <select name="tipe_cc" class="form-select" required>
                        <option value="100">100 cc</option>
                        <option value="125">125 cc</option>
                        <option value="150">150 cc</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-bold">Nomor Plat Polisi</label>
                    <input type="text" name="no_plat" class="form-control" placeholder="Contoh: D 5678 ABC" required>
                </div>
            </div>
            <button type="submit" class="btn btn-success fw-bold">Daftarkan Motor</button>
        </form>
    </div>

    <div class="card shadow-sm p-4">
        <h5 class="fw-bold mb-3">Motor Milik Anda</h5>
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead class="table-dark">
                <tr>
                    <th>Plat Nomor</th>
                    <th>Merek</th>
                    <th>Kapasitas Mesin</th>
                    <th>Status Unit</th>
                    <th>Tarif Harian</th>
                </tr>
                </thead>
                <tbody>
                @forelse($motors as $motor)
                    <tr>
                        <td><strong>{{ $motor->no_plat }}</strong></td>
                        <td>{{ $motor->merek }}</td>
                        <td>{{ $motor->tipe_cc }} cc</td>
                        <td>
                        <span class="badge {{ $motor->status == 'tersedia' ? 'bg-success' : ($motor->status == 'disewa' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                            {{ $motor->status }}
                        </span>
                        </td>
                        <td>{{ $motor->tarif ? 'Rp ' . number_format($motor->tarif->tarif_harian, 0, ',', '.') : 'Menunggu verifikasi' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">Belum ada motor yang didaftarkan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
