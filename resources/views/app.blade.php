<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Rental Motor')</title>
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/">Rental Motor</a>
        <div class="navbar-nav ms-auto align-items-center">
            @auth('web')
                <span class="navbar-text me-3 text-white">
                    Halo, <strong>{{ auth('web')->user()->name }}</strong> ({{ ucfirst(auth('web')->user()->role) }})
                </span>

                @if(auth('web')->user()->role == 'admin')
                    <a class="nav-link" href="/admin/dashboard">Admin Panel</a>
                @elseif(auth('web')->user()->role == 'pemilik')
                    <a class="nav-link" href="/owner/dashboard">Motor Saya</a>
                @else
                    <a class="nav-link" href="/rent/dashboard">Katalog & Riwayat Sewa</a>
                @endif

                <form action="/logout" method="POST" class="d-inline ms-2">
                    @csrf
                    <button type="submit" class="btn btn-outline-light btn-sm">Keluar</button>
                </form>
            @else
                <a class="nav-link me-2" href="/rent/dashboard">Katalog Sewa</a>
                <a class="btn btn-outline-light btn-sm me-2" href="/login">Masuk</a>
                <a class="btn btn-primary btn-sm" href="/register">Daftar</a>
            @endauth
        </div>
    </div>
</nav>

<div class="container">
    @yield('content')
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
