@extends('layouts.app')

@section('content')
    <div class="">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="d-flex justify-content-between my-3">
                    <h1 class="text-center">Deleted Customers</h1>
                    <div class="d-flex gap-2">
                        <a href="{{ route('customerTable') }}" class="d-flex"><button class="btn btn-success align-self-center" data-toggle="tooltip" title="Back"><i class="bi bi-arrow-left"></i> Back</button></a>
                        <div class="d-flex"><button class="btn btn-danger align-self-center clearBtn" data-toggle="tooltip" title="Clear History"><i class="bi bi-database"></i></button></div>
                    </div>
                </div>
                <div class="card shadow-sm">
                    <div class="card-body table-responsive">
                        <table class="table table-hover display nowrap" id="customerTable" style="width:100%;">
                            <thead>
                                <tr class="table-dark">
                                    <th class="text-start">Citizen ID</th>
                                    <th>Name</th>
                                    <th class="text-start">Branch</th>
                                    <th>Course</th>
                                    <th class="text-start">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($customers as $index => $user)
                                    <tr>
                                        <th class="text-start">{{ $user->username }}</th>
                                        <td>{{ $user->name }}</td>
                                        <td class="text-start">{{ $user->brn }}</td>
                                        @php
                                            $courses_list = $user->course ?? [];
                                            $course_name = App\Models\Course::where('id', end($courses_list))->first(['name']);
                                        @endphp
                                        <td>{{ $course_name->name }}</td>
                                        <td >
                                            <button class="btn btn-sm btn-warning reuseBtn" value="{{ $user->id }}" data-toggle="tooltip" title="Reuse"><i class="bi bi-recycle"></i></button>
                                            <button class="btn btn-sm btn-danger delBtn" value="{{ $user->id }}" data-toggle="tooltip" title="Delete permanently"><i class="bi bi-trash3"></i></button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function () {
            $('[data-toggle="tooltip"]').tooltip();

            $(".clearBtn").click(function() {
                const delId = $(this).val();
                Swal.fire({
                    title: "Are you sure?",
                    text: "You won't be able to revert this!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, Clean it!",
                    preConfirm: () => {
                        $.ajax({
                            url: "/customer/clean",
                            method: 'GET',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function (response) {
                                // console.log(response);
                                Swal.fire({
                                    title: "Deleted!",
                                    text: "Customer has been deleted.",
                                    icon: "success"
                                }).then((res) => {
                                    if (res.isConfirmed) {
                                        window.location.reload();
                                    }
                                });
                            },
                            error: (error) => {
                                console.log(error);
                                Swal.fire({
                                    title: "Sorry!",
                                    text: "Something wrong!",
                                    icon: "error"
                                }).then((res) => {
                                    if (res.isConfirmed) {
                                        window.location.reload();
                                    }
                                });
                            }
                        });
                    },
                    showLoaderOnConfirm: true,
                    allowOutsideClick: () => !Swal.isLoading()
                });
            });

            $(".delBtn").click(function() {
                const delId = $(this).val();
                Swal.fire({
                    title: "Are you sure?",
                    text: "You won't be able to revert this!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, delete it!",
                    preConfirm: () => {
                        $.ajax({
                            url: "/customer/delete-permanently",
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            data: { delId:delId},
                            success: function (response) {
                                // console.log(response);
                                Swal.fire({
                                    title: "Deleted!",
                                    text: "Customer has been deleted.",
                                    icon: "success"
                                }).then((res) => {
                                    if (res.isConfirmed) {
                                        window.location.reload();
                                    }
                                });
                            },
                            error: (error) => {
                                console.log(error);
                                Swal.fire({
                                    title: "Sorry!",
                                    text: "Something wrong!",
                                    icon: "error"
                                }).then((res) => {
                                    if (res.isConfirmed) {
                                        window.location.reload();
                                    }
                                });
                            }
                        });
                    },
                    showLoaderOnConfirm: true,
                    allowOutsideClick: () => !Swal.isLoading()
                });
            });

            $(".reuseBtn").click(function() {
                const reuid = $(this).val();
                Swal.fire({
                    title: "Are you sure?",
                    text: "You won't be able to revert this!",
                    icon: "info",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, reuse it!",
                    preConfirm: () => {
                        $.ajax({
                            url: "/customer/reuse",
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            data: { reuid:reuid},
                            success: function (response) {
                                // console.log(response);
                                Swal.fire({
                                    title: "Success!",
                                    text: "Customer has been reused.",
                                    icon: "success"
                                }).then((res) => {
                                    if (res.isConfirmed) {
                                        window.location.reload();
                                    }
                                });
                            },
                            error: (error) => {
                                console.log(error);
                                Swal.fire({
                                    title: "Sorry!",
                                    text: "Something wrong!",
                                    icon: "error"
                                }).then((res) => {
                                    if (res.isConfirmed) {
                                        window.location.reload();
                                    }
                                });
                            }
                        });
                    },
                    showLoaderOnConfirm: true,
                    allowOutsideClick: () => !Swal.isLoading()
                });
            });
        })
    </script>
@endsection
