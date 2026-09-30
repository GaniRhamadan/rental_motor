@extends('app')

@section('title', 'Daftar Akun - Rental Motor')

@section('content')
    <div class="row justify-content-center mt-4">
        <div class="col-md-6">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h4 class="fw-bold text-center mb-4">Registrasi Akun Baru</h4>

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="/register" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nama Lengkap</label>
                            <input type="text" name="name" class="form-control" placeholder="Contoh: Budi Santoso" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="nama@example.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">No. Telepon / WhatsApp</label>
                            <input type="text" name="no_tlpn" class="form-control" placeholder="08123456789" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Daftar Sebagai</label>
                            <select name="role" class="form-select" required>
                                <option value="penyewa">Penyewa Kendaraan</option>
                                <option value="pemilik">Pemilik Kendaraan (Titip Motor)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Kata Sandi</label>
                            <input type="password" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100 fw-bold">Daftar Sekarang</button>
                    </form>

                    <div class="text-center mt-3">
                        <small>Sudah punya akun? <a href="/login">Masuk di sini</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
