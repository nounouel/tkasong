@extends('layouts.master')

@section('content')
<div class="animate__animated p-6" :class="[$store.app.animation]">

    <div class="panel h-full w-full">

        <div class="mb-5 flex items-center justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">
                Tambah User
            </h5>

            <a href="{{ route('user.index') }}" class="btn btn-primary">
                Kembali
            </a>
        </div>

        <form action="{{ route('user.store') }}" method="POST">
            @csrf
            <div class="mb-5">
                <label for="name" class="dark:text-white-light">Nama</label>
                <input id="name" type="text" name="name" class="form-input" placeholder="Masukkan Nama" required />
            </div>
            <div class="mb-5">
                <label for="email" class="dark:text-white-light">Email</label>
                <input id="email" type="email" name="email" class="form-input" placeholder="Masukkan Email" required />
            </div>
            <div class="mb-5">
                <label for="password" class="dark:text-white-light">Password</label>
                <input id="password" type="password" name="password" class="form-input" placeholder="Masukkan Password" required />
            </div>

            <button type="submit" class="btn btn-primary !mt-6">Simpan</button>
        </form>

    </div>

</div>
@endsection
