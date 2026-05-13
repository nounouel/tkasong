@extends('layouts.master')

@section('content')
<div class="animate__animated p-6" :class="[$store.app.animation]">

    <div class="panel h-full w-full">

        <div class="mb-5 flex items-center justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">
                Edit User
            </h5>

            <a href="{{ route('user.index') }}" class="btn btn-primary">
                Kembali
            </a>
        </div>

        <form action="{{ route('user.update', $user->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-5">
                <label for="name" class="dark:text-white-light">Nama</label>
                <input id="name" type="text" name="name" value="{{ $user->name }}" class="form-input" placeholder="Masukkan Nama" required />
            </div>
            <div class="mb-5">
                <label for="email" class="dark:text-white-light">Email</label>
                <input id="email" type="email" name="email" value="{{ $user->email }}" class="form-input" placeholder="Masukkan Email" required />
            </div>
            <div class="mb-5">
                <label for="password" class="dark:text-white-light">Password (Kosongkan jika tidak ingin mengubah)</label>
                <input id="password" type="password" name="password" class="form-input" placeholder="Masukkan Password Baru" />
            </div>

            <button type="submit" class="btn btn-primary !mt-6">Update</button>
        </form>

    </div>

</div>
@endsection
