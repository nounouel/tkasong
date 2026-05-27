@extends('layouts.master')
@section('content')
    <div class="animate__animated p-6" :class="[$store.app.animation]">
        <div class="panel h-full w-full">
            <div class="mb-5 flex flex-col sm:flex-row items-center justify-between gap-3">
                <h5 class="text-lg font-semibold dark:text-white-light">Data User</h5>
                <div class="flex items-center gap-2">
                    <form action="{{ route('user.index') }}" method="GET" class="flex gap-2">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari..." class="form-input w-48 sm:w-auto" />
                        <button type="submit" class="btn btn-secondary">Cari</button>
                    </form>
                    <a href="{{ route('user.create') }}" class="btn btn-primary">Tambah User</a>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-5 rounded bg-success-light p-3 text-success">
                    {{ session('success') }}
                </div>
            @endif

            <div class="table-responsive">
                <table class="table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Username</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $item)
                        <tr class="group text-white-dark hover:text-black dark:hover:text-white-light/90">
                            <td>{{ $users->firstItem() ? $users->firstItem() + $loop->index : $loop->iteration }}</td>
                            <td class="text-black dark:text-white">{{ $item->name }}</td>
                            <td>{{ $item->username }}</td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('user.edit', $item->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                    <form action="{{ route('user.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Yakin hapus data?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center">Data user kosong</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                {{ $users->links() }}
            </div>
        </div>
    </div>
@endsection
