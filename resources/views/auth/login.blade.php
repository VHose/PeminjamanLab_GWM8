@extends('layouts.app') @section('content')
    <div class="card mx-auto" style="max-width:430px">
        <div class="card-body p-4">
            <h1 class="h4">Masuk</h1>
            <form method="post">@csrf <input class="form-control mb-3" name="email" type="email" placeholder="Email"
                    required><input class="form-control mb-3" name="password" type="password" placeholder="Kata sandi"
                    required><button class="btn btn-primary w-100">Masuk</button></form>
            <p class="small mt-3">Belum punya akun? <a href="{{ route('register') }}">Daftar Visitor</a></p>
        </div>
</div>@endsection