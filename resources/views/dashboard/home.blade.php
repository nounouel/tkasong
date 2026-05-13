@extends('layouts.master')
@section('content')
    <div class="animate__animated p-6" :class="[$store.app.animation]">
        <div class="panel h-full w-full">
            <div class="mb-5 flex items-center justify-between">
                <h5 class="text-lg font-semibold dark:text-white-light">User Table</h5>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th class="ltr:rounded-l-md rtl:rounded-r-md">User</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th class="ltr:rounded-r-md rtl:rounded-l-md">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="group text-white-dark hover:text-black dark:hover:text-white-light/90">
                            <td class="min-w-[150px] text-black dark:text-white">
                                <div class="flex items-center">
                                    <img class="h-8 w-8 rounded-md object-cover ltr:mr-3 rtl:ml-3" src="{{ asset('assets/images/profile-9.jpeg') }}" alt="avatar">
                                    <span class="whitespace-nowrap">Luke Ivory</span>
                                </div>
                            </td>
                            <td>luke@example.com</td>
                            <td>Admin</td>
                            <td><span class="badge bg-success shadow-md dark:group-hover:bg-transparent">Active</span></td>
                        </tr>
                        <tr class="group text-white-dark hover:text-black dark:hover:text-white-light/90">
                            <td class="text-black dark:text-white">
                                <div class="flex items-center">
                                    <img class="h-8 w-8 rounded-md object-cover ltr:mr-3 rtl:ml-3" src="{{ asset('assets/images/profile-10.jpeg') }}" alt="avatar">
                                    <span class="whitespace-nowrap">Andy King</span>
                                </div>
                            </td>
                            <td>andy@example.com</td>
                            <td>User</td>
                            <td><span class="badge bg-secondary shadow-md dark:group-hover:bg-transparent">Inactive</span></td>
                        </tr>
                        <tr class="group text-white-dark hover:text-black dark:hover:text-white-light/90">
                            <td class="text-black dark:text-white">
                                <div class="flex items-center">
                                    <img class="h-8 w-8 rounded-md object-cover ltr:mr-3 rtl:ml-3" src="{{ asset('assets/images/profile-12.jpeg') }}" alt="avatar">
                                    <span class="whitespace-nowrap">Laurie Fox</span>
                                </div>
                            </td>
                            <td>laurie@example.com</td>
                            <td>Editor</td>
                            <td><span class="badge bg-success shadow-md dark:group-hover:bg-transparent">Active</span></td>
                        </tr>
                        <tr class="group text-white-dark hover:text-black dark:hover:text-white-light/90">
                            <td class="text-black dark:text-white">
                                <div class="flex items-center">
                                    <img class="h-8 w-8 rounded-md object-cover ltr:mr-3 rtl:ml-3" src="{{ asset('assets/images/profile-9.jpeg') }}" alt="avatar">
                                    <span class="whitespace-nowrap">Ryan Collins</span>
                                </div>
                            </td>
                            <td>ryan@example.com</td>
                            <td>User</td>
                            <td><span class="badge bg-secondary shadow-md dark:group-hover:bg-transparent">Inactive</span></td>
                        </tr>
                        <tr class="group text-white-dark hover:text-black dark:hover:text-white-light/90">
                            <td class="text-black dark:text-white">
                                <div class="flex items-center">
                                    <img class="h-8 w-8 rounded-md object-cover ltr:mr-3 rtl:ml-3" src="{{ asset('assets/images/profile-10.jpeg') }}" alt="avatar">
                                    <span class="whitespace-nowrap">Irene Collins</span>
                                </div>
                            </td>
                            <td>irene@example.com</td>
                            <td>Admin</td>
                            <td><span class="badge bg-success shadow-md dark:group-hover:bg-transparent">Active</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
