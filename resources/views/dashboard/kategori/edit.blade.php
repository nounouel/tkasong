@extends('layouts.master')

@section('content')
<div class="animate__animated p-6" :class="[$store.app.animation]">

    <div class="panel h-full w-full">

        <div class="mb-5 flex items-center justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">
                Edit Kategori
            </h5>

            <a href="{{ route('kategori.index') }}"
               class="btn btn-primary">
                Kembali
            </a>
        </div>

        @if ($errors->any())
            <div class="mb-5 rounded bg-danger-light p-3 text-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('kategori.update', $kategori->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="mb-5">
                <label for="nama_kategori" class="dark:text-white-light">Nama Kategori</label>
                <input id="nama_kategori" type="text" name="nama_kategori" value="{{ old('nama_kategori', $kategori->nama_kategori) }}" class="form-input" placeholder="Masukkan Nama Kategori" required />
            </div>

            <button type="submit" class="btn btn-primary !mt-6">Update</button>
        </form>

    </div>

</div>
@endsection
