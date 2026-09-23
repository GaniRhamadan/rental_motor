@extends('app')

@section('title', 'Admin Dashboard - Rental Motor')

@section('content')
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm text-center p-3">
                <h6 class="text-muted">Total Unit Motor</h6>
                <h3 class="fw-bold">{{ $totalMotor }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm text-center p-3">
                <h6 class="text-muted">Total Omset</h6>
                <h3 class="fw-bold text-success">Rp {{ number_format($totalOmset, 0, ',', '.') }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm text-center p-3">
                <h6 class="text-muted">Bagi Hasil Admin</h6>
                <h3 class="fw-bold text-primary">Rp {{ number_format($totalAdmin, 0, ',', '.') }}</h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm text-center p-3">
                <h6 class="text-muted">Bagi Hasil Pemilik</h6>
                <h3 class="fw-bold text-info">Rp {{ number_format($totalPemilik, 0, ',', '.') }}</h3>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <!-- Chart.js: Statistik Durasi Sewa -->
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm p-4">
                <h5 class="fw-bold mb-3">Statistik Durasi Penyewaan</h5>
                <canvas id="durationChart" height="200"></canvas>
            </div>
        </div>
        <!-- Chart.js: Komposisi Bagi Hasil -->
        <div class="col-md-6 mb-3">
            <div class="card shadow-sm p-4">
                <h5 class="fw-bold mb-3">Komposisi Bagi Hasil</h5>
                <canvas id="revenuePieChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Daftar Motor & Verifikasi -->
    <div class="card shadow-sm p-4 mb-4">
        <h5 class="fw-bold mb-3">Daftar Motor & Status</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                <tr>
                    <th>Plat</th>
                    <th>Merek</th>
                    <th>CC</th>
                    <th>Pemilik</th>
                    <th>Status</th>
                    <th>Tarif Harian / Mingguan / Bulanan</th>
                    <th>Aksi</th>
                </tr>
                </thead>
                <tbody>
                @foreach($motors as $motor)
                    <tr>
                        <td><strong>{{ $motor->no_plat }}</strong></td>
                        <td>{{ $motor->merek }}</td>
                        <td>{{ $motor->tipe_cc }} cc</td>
                        <td>{{ $motor->pemilik->name ?? '-' }}</td>
                        <td>
                        <span class="badge {{ $motor->status == 'tersedia' ? 'bg-success' : ($motor->status == 'disewa' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                            {{ $motor->status }}
                        </span>
                        </td>
                        <td>
                            @if($motor->tarif)
                                Rp {{ number_format($motor->tarif->tarif_harian, 0, ',', '.') }} /
                                Rp {{ number_format($motor->tarif->tarif_mingguan, 0, ',', '.') }} /
                                Rp {{ number_format($motor->tarif->tarif_bulanan, 0, ',', '.') }}
                            @else
                                <span class="text-muted">Belum ada tarif</span>
                            @endif
                        </td>
                        <td>
                            @if($motor->status == 'menunggu_verifikasi')
                                <!-- Tombol Buka Form Verifikasi -->
                                <button class="btn btn-sm btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#verifyModal{{ $motor->id }}">
                                    Verifikasi & Atur Tarif
                                </button>

                                <!-- Modal Popup Input Tarif -->
                                <div class="modal fade" id="verifyModal{{ $motor->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form action="/admin/motors/{{ $motor->id }}/verify-web" method="POST" class="modal-content">
                                            @csrf
                                            <div class="modal-header">
                                                <h5 class="modal-title fw-bold">Verifikasi Motor: {{ $motor->merek }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Tarif Harian (Rp)</label>
                                                    <input type="number" name="tarif_harian" class="form-control" placeholder="Contoh: 75000" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Tarif Mingguan (Rp)</label>
                                                    <input type="number" name="tarif_mingguan" class="form-control" placeholder="Contoh: 450000" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Tarif Bulanan (Rp)</label>
                                                    <input type="number" name="tarif_bulanan" class="form-control" placeholder="Contoh: 1500000" required>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-success btn-sm fw-bold">Konfirmasi & Aktifkan Unit</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @else
                                <span class="text-muted small">Sudah diverifikasi</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card shadow-sm p-4 mb-4">
        <h5 class="fw-bold mb-3">Pesanan Sewa Masuk (Perlu Konfirmasi Admin)</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                <tr>
                    <th>Penyewa</th>
                    <th>Motor</th>
                    <th>Durasi Sewa</th>
                    <th>Total Biaya</th>
                    <th>Status Pembayaran</th>
                    <th>Status Pesanan</th>
                    <th>Aksi Admin</th>
                </tr>
                </thead>
                <tbody>
                @foreach($bookings as $b)
                    <tr>
                        <td>{{ $b->penyewa->name ?? '-' }}</td>
                        <td><strong>{{ $b->motor->merek ?? '-' }}</strong> ({{ $b->motor->no_plat ?? '-' }})</td>
                        <td>{{ $b->tanggal_mulai }} s/d {{ $b->tanggal_selesai }} ({{ $b->tipe_durasi }})</td>
                        <td><strong>Rp {{ number_format($b->harga, 0, ',', '.') }}</strong></td>
                        <td>
                        <span class="badge {{ ($b->transaksi && $b->transaksi->status == 'berhasil') ? 'bg-success' : 'bg-danger' }}">
                            {{ $b->transaksi ? 'Sudah Dibayar (' . $b->transaksi->metode_pembayaran . ')' : 'Belum Dibayar' }}
                        </span>
                        </td>
                        <td>
                        <span class="badge {{ $b->status == 'dikonfirmasi' ? 'bg-success' : ($b->status == 'selesai' ? 'bg-primary' : 'bg-warning text-dark') }}">
                            {{ $b->status }}
                        </span>
                        </td>
                        <td>
                            @if($b->status == 'menunggu_pembayaran')
                                <!-- Tombol Konfirmasi Sewa (Ubah motor jadi disewa & hitung bagi hasil) -->
                                <form action="/admin/bookings/{{ $b->id }}/confirm-web" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success fw-bold">Konfirmasi Sewa</button>
                                </form>
                            @elseif($b->status == 'dikonfirmasi')
                                <!-- Tombol Motor Dikembalikan (Selesai sewa & motor jadi tersedia lagi) -->
                                <form action="/admin/bookings/{{ $b->id }}/return-web" method="POST" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-info text-white fw-bold">Motor Kembali</button>
                                </form>
                            @else
                                <span class="text-muted small">Sewa Selesai</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // 1. Bar Chart Durasi Sewa
        const ctxDuration = document.getElementById('durationChart').getContext('2d');
        new Chart(ctxDuration, {
            type: 'bar',
            data: {
                labels: ['Harian', 'Mingguan', 'Bulanan'],
                datasets: [{
                    label: 'Jumlah Penyewaan',
                    data: [{{ $chartData['harian'] }}, {{ $chartData['mingguan'] }}, {{ $chartData['bulanan'] }}],
                    backgroundColor: ['#0d6efd', '#198754', '#ffc107']
                }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });

        // 2. Pie Chart Bagi Hasil
        const ctxRevenue = document.getElementById('revenuePieChart').getContext('2d');
        new Chart(ctxRevenue, {
            type: 'doughnut',
            data: {
                labels: ['Admin (20%)', 'Pemilik (80%)'],
                datasets: [{
                    data: [{{ $totalAdmin }}, {{ $totalPemilik }}],
                    backgroundColor: ['#0d6efd', '#0dcaf0']
                }]
            },
            options: { responsive: true }
        });
    </script>
@endpush
