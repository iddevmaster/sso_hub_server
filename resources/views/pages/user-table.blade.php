@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="d-flex justify-content-between my-3">
                    <h1 class="text-center">All Users</h1>
                    <div class="d-flex"><button class="btn btn-success align-self-center addBtn">Add</button></div>
                </div>
                <div class="card shadow-sm">
                    <div class="card-body bg-white">
                        <table class="table table-hover display" id="usersTable">
                            <thead>
                                <tr class="table-dark">
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Branch</th>
                                    <th>Agency</th>
                                    <th>Role</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($users as $index => $user)
                                    <tr>
                                        <th>{{ $index + 1 }}</th>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ optional($user->getBrn)->name }}</td>
                                        <td>{{ optional($user->getAgn)->name }}</td>
                                        <td>{{ $user->role ?? '-' }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-warning editBtn" eid="{{ $user->id }}" erole="{{ $user->role }}" editName="{{ $user->name }}" editUname="{{ $user->email }}" value="{{ $user->brn }}"><i class="bi bi-gear"></i></button>
                                            <button class="btn btn-sm btn-danger delBtn" value="{{ $user->id }}"><i class="bi bi-trash3"></i></button>
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
            $(".addBtn").click(function() {
                Swal.fire({
                    title: 'Add User',
                    html: `
                        <div class="mb-3">
                            <input type="text" class="form-control" maxlength="100" id="name" placeholder="Name">
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control" maxlength="100" id="uname" placeholder="Username">
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control" maxlength="100" id="pass" placeholder="Password">
                        </div>
                        <select class="form-select mb-3" aria-label="Default select example" id="brn">
                            <option value="" selected disabled>Select department</option>
                            @foreach ($brns as $brn)
                                <option value="{{ $brn->id }}">{{ $brn->name }}</option>
                            @endforeach
                        </select>
                        <select class="form-select" aria-label="selectRole" id="role">
                            <option value="" selected disabled>Select role</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    `,
                    showCancelButton: true,
                    preConfirm: () => {
                        const name = document.getElementById("name").value;
                        const uname = document.getElementById("uname").value;
                        const pass = document.getElementById("pass").value;
                        const brn = document.getElementById("brn").value;
                        const role = document.getElementById("role").value;
                        if (name && uname && pass && brn && role) {
                            $.ajax({
                                url: "/users/store",
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                },
                                data: { name:name, uname:uname, pass:pass, brn:brn, role:role},
                                success: function (response) {
                                    // console.log(response);
                                    Swal.fire({
                                        title: "Success",
                                        // text: "That thing is still around?",
                                        icon: "success"
                                    }).then((res) => {
                                        if (res.isConfirmed) {
                                            window.location.reload();
                                        }
                                    });
                                },
                                error: (error) => {
                                    // console.log(error);
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
                        } else {
                            Swal.showValidationMessage(`Please enter all field.`);
                        }
                    },
                    showLoaderOnConfirm: true,
                    allowOutsideClick: () => !Swal.isLoading()
                });
            });

            $(".editBtn").click(function() {
                const name = $(this).attr("editName");
                const uname = $(this).attr("editUname");
                const eid = $(this).attr("eid");
                const erole = $(this).attr("erole");
                const brn = $(this).val();
                Swal.fire({
                    title: 'Agency',
                    html: `
                        <div class="mb-3">
                            <input type="text" class="form-control" maxlength="100" value="${name}" id="name" placeholder="Name">
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control" maxlength="100" value="${uname}" id="uname" placeholder="Username">
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control" maxlength="100" id="pass" placeholder="Password (หากใช้รหัสเดิมไม่ต้องกรอก)">
                        </div>
                        <select class="form-select mb-3" aria-label="Default select example" id="brn">
                            <option value="" selected disabled>Select department</option>
                            @foreach ($brns as $brn)
                                <option value="{{ $brn->id }}" ${brn == "{{ $brn->id }}" ? 'selected' : ''}>{{ $brn->name }}</option>
                            @endforeach
                        </select>
                        <select class="form-select" aria-label="selectRole" id="role">
                            <option value="" selected disabled>Select role</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role->name }}" ${erole == "{{ $role->name }}" ? 'selected' : ''}>{{ $role->name }}</option>
                            @endforeach
                        </select>
                    `,
                    showCancelButton: true,
                    preConfirm: (agnName) => {
                        const name = document.getElementById("name").value;
                        const uname = document.getElementById("uname").value;
                        const pass = document.getElementById("pass").value;
                        const brn = document.getElementById("brn").value;
                        const role = document.getElementById("role").value;
                        if (name && uname && brn) {
                            $.ajax({
                                url: "/users/update",
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                },
                                data: { name:name, uname:uname, pass:pass, brn:brn, eid:eid, role:role},
                                success: function (response) {
                                    // console.log(response);
                                    Swal.fire({
                                        title: "Success",
                                        // text: "That thing is still around?",
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
                        } else {
                            Swal.showValidationMessage(`Please enter all field.`);
                        }
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
                    preConfirm: (agnName) => {
                        if (agnName) {
                            $.ajax({
                                url: "/users/delete",
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                },
                                data: { delId:delId},
                                success: function (response) {
                                    // console.log(response);
                                    Swal.fire({
                                        title: "Deleted!",
                                        text: "Your file has been deleted.",
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
                        } else {
                            Swal.showValidationMessage(`Please enter agency name.`);
                        }
                    },
                    showLoaderOnConfirm: true,
                    allowOutsideClick: () => !Swal.isLoading()
                });
            });
        });
    </script>
@endsection
