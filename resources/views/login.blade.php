@extends('app')

@section('title', 'Login - Rental Motor')

@section('content')
    <div class="row justify-content-center mt-5">
        <div class="col-md-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <h4 class="fw-bold text-center mb-4">Masuk ke Akun Anda</h4>

                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form action="/login" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="nama@example.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Kata Sandi</label>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 fw-bold">Masuk</button>
                    </form>

                    <div class="text-center mt-3">
                        <small>Belum punya akun? <a href="/register">Daftar Akun Baru</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
