@extends('app')

@section('title', 'Katalog Rental Motor')

@section('content')
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

    <h4 class="fw-bold mb-3">Pilihan Motor Tersedia untuk Disewa</h4>
    <div class="row mb-5">
        @forelse($availableMotors as $motor)
            <div class="col-md-4 mb-3">
                <div class="card shadow-sm h-100 border-0">
                    <div class="card-body">
                        <span class="badge bg-success mb-2">Tersedia</span>
                        <h5 class="card-title fw-bold">{{ $motor->merek }} ({{ $motor->tipe_cc }} cc)</h5>
                        <p class="text-muted mb-2">Plat: <strong>{{ $motor->no_plat }}</strong></p>
                        <hr>
                        <div class="small">
                            <div>Harian: <strong>Rp {{ number_format($motor->tarif->tarif_harian ?? 0, 0, ',', '.') }}</strong></div>
                            <div>Mingguan: <strong>Rp {{ number_format($motor->tarif->tarif_mingguan ?? 0, 0, ',', '.') }}</strong></div>
                            <div>Bulanan: <strong>Rp {{ number_format($motor->tarif->tarif_bulanan ?? 0, 0, ',', '.') }}</strong></div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-0 pb-3">
                        <!-- Tombol Buka Modal Sewa -->
                        <button class="btn btn-primary w-100 btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#rentModal{{ $motor->id }}">
                            Sewa Motor Ini
                        </button>

                        <!-- Modal Form Sewa -->
                        <div class="modal fade" id="rentModal{{ $motor->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <form action="/rent/motors/{{ $motor->id }}/book-web" method="POST" class="modal-content">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title fw-bold">Sewa: {{ $motor->merek }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Tanggal Mulai Sewa</label>
                                            <input type="date" name="tanggal_mulai" class="form-control" value="{{ date('Y-m-d') }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Pilih Durasi Sewa</label>
                                            <select name="tipe_durasi" class="form-select" required>
                                                <option value="harian">Harian (Rp {{ number_format($motor->tarif->tarif_harian ?? 0, 0, ',', '.') }})</option>
                                                <option value="mingguan">Mingguan (Rp {{ number_format($motor->tarif->tarif_mingguan ?? 0, 0, ',', '.') }})</option>
                                                <option value="bulanan">Bulanan (Rp {{ number_format($motor->tarif->tarif_bulanan ?? 0, 0, ',', '.') }})</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold">Metode Pembayaran</label>
                                            <select name="metode_pembayaran" class="form-select" required>
                                                <option value="transfer_bank">Transfer Bank</option>
                                                <option value="qris">QRIS Instant</option>
                                                <option value="tunai">Tunai di Tempat</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-success btn-sm fw-bold">Konfirmasi & Sewa</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info">Saat ini belum ada unit motor yang berstatus tersedia.</div>
            </div>
        @endforelse
    </div>

    <div class="card shadow-sm p-4">
        <h5 class="fw-bold mb-3">Riwayat Seluruh Transaksi & Penyewaan</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-dark">
                <tr>
                    <th>Penyewa</th>
                    <th>Unit Motor</th>
                    <th>Durasi Sewa</th>
                    <th>Total Biaya</th>
                    <th>Status Pesanan</th>
                    <th>Status Pembayaran</th>
                </tr>
                </thead>
                <tbody>
                @foreach($allBookings as $b)
                    <tr>
                        <td>{{ $b->penyewa->name ?? '-' }}</td>
                        <td>{{ $b->motor->merek ?? '-' }} ({{ $b->motor->no_plat ?? '-' }})</td>
                        <td>{{ $b->tanggal_mulai }} s/d {{ $b->tanggal_selesai }} ({{ $b->tipe_durasi }})</td>
                        <td><strong>Rp {{ number_format($b->harga, 0, ',', '.') }}</strong></td>
                        <td>
                        <span class="badge {{ $b->status == 'dikonfirmasi' ? 'bg-success' : ($b->status == 'selesai' ? 'bg-primary' : 'bg-warning text-dark') }}">
                            {{ $b->status }}
                        </span>
                        </td>
                        <td>
                        <span class="badge {{ ($b->transaksi && $b->transaksi->status == 'berhasil') ? 'bg-success' : 'bg-danger' }}">
                            {{ $b->transaksi ? $b->transaksi->status : 'belum bayar' }}
                        </span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
