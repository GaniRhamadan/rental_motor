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
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
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
                    <th>Aksi</th>
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

                        <!-- Kolom Aksi Edit & Hapus Masuk di Sini -->
                        <td>
                            @if($motor->status != 'disewa')
                                <!-- Tombol Edit -->
                                <button class="btn btn-sm btn-warning fw-bold text-dark me-1" data-bs-toggle="modal" data-bs-target="#editMotorModal{{ $motor->id }}">
                                    Edit
                                </button>

                                <!-- Tombol Hapus -->
                                <form action="/owner/motors/{{ $motor->id }}/delete-web" method="POST" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus unit motor ini?')">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-danger fw-bold">Hapus</button>
                                </form>

                                <!-- Modal Popup Edit Motor -->
                                <div class="modal fade" id="editMotorModal{{ $motor->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form action="/owner/motors/{{ $motor->id }}/update-web" method="POST" class="modal-content">
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold">Edit Data Motor: {{ $motor->merek }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body text-start">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Merek Motor</label>
                                                    <input type="text" name="merek" class="form-control" value="{{ $motor->merek }}" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Kapasitas CC</label>
                                                    <select name="tipe_cc" class="form-select" required>
                                                        <option value="100" {{ $motor->tipe_cc == '100' ? 'selected' : '' }}>100 cc</option>
                                                        <option value="125" {{ $motor->tipe_cc == '125' ? 'selected' : '' }}>125 cc</option>
                                                        <option value="150" {{ $motor->tipe_cc == '150' ? 'selected' : '' }}>150 cc</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Nomor Plat Polisi</label>
                                                    <input type="text" name="no_plat" class="form-control" value="{{ $motor->no_plat }}" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-primary btn-sm fw-bold">Simpan Perubahan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @else
                                <span class="badge bg-secondary">Sedang Disewa</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">Belum ada motor yang didaftarkan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
