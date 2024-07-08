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
                    <div class="card-body">
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
                                        <td>{{ $user->prefix . ' ' . $user->name . ' ' . $user->lname }}</td>
                                        <td>{{ $user->username }}</td>
                                        <td>{{ optional($user->getBrn)->name }}</td>
                                        <td>{{ optional($user->getAgn)->name }}</td>
                                        <td>{{ $user->role ?? '-' }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-warning editBtn" eid="{{ $user->id }}" erole="{{ $user->role }}" editPrefix="{{ $user->prefix }}" editlName="{{ $user->lname }}" editName="{{ $user->name }}" editUname="{{ $user->username }}" value="{{ $user->brn }}"><i class="bi bi-gear"></i></button>
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
                        <select class="form-select mb-3" aria-label="selectCourse" id="prefix">
                            <option value="" selected disabled>Select Prefix</option>
                            <option value="นาย" >นาย</option>
                            <option value="นาง" >นาง</option>
                            <option value="นางสาว" >นางสาว</option>
                            <option value="Mr." >Mr.</option>
                            <option value="Ms." >Ms.</option>
                        </select>
                        <div class="mb-3">
                            <input type="text" class="form-control" maxlength="100" id="name" placeholder="*First Name">
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control" maxlength="100" id="lname" placeholder="*Last Name">
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
                                <option value="{{ $brn->brn_id }}">{{ $brn->name }}</option>
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
                        const prefix = document.getElementById("prefix").value;
                        const name = document.getElementById("name").value;
                        const lname = document.getElementById("lname").value;
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
                                data: { name:name, uname:uname, pass:pass, brn:brn, role:role, lname:lname, prefix:prefix},
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
                const ename = $(this).attr("editName");
                const elname = $(this).attr("editlName");
                const eprefix = $(this).attr("editPrefix");
                const euname = $(this).attr("editUname");
                const eid = $(this).attr("eid");
                const erole = $(this).attr("erole");
                const ebrn = $(this).val();
                Swal.fire({
                    title: 'Agency',
                    html: `
                        <select class="form-select mb-3" aria-label="selectCourse" id="prefix">
                            <option value="" selected disabled>Select Prefix</option>
                            <option value="นาย" ${eprefix == 'นาย' ? 'selected' : ''}>นาย</option>
                            <option value="นาง" ${eprefix == 'นาง' ? 'selected' : ''}>นาง</option>
                            <option value="นางสาว" ${eprefix == 'นางสาว' ? 'selected' : ''}>นางสาว</option>
                            <option value="Mr." ${eprefix == 'Mr.' ? 'selected' : ''}>Mr.</option>
                            <option value="Ms." ${eprefix == 'Ms.' ? 'selected' : ''}>Ms.</option>
                        </select>
                        <div class="mb-3">
                            <input type="text" class="form-control" maxlength="100" value="${ename}" id="name" placeholder="*First Name">
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control" maxlength="100" id="lname" value="${elname}" placeholder="*Last Name">
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control" maxlength="100" value="${euname}" id="uname" placeholder="Username">
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control" maxlength="100" id="pass" placeholder="Password (หากใช้รหัสเดิมไม่ต้องกรอก)">
                        </div>
                        <select class="form-select mb-3" aria-label="Default select example" id="brn">
                            <option value="" selected disabled>Select department</option>
                            @foreach ($brns as $brn)
                                <option value="{{ $brn->brn_id }}" ${ebrn == "{{ $brn->brn_id }}" ? 'selected' : ''}>{{ $brn->name }}</option>
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
                        const prefix = document.getElementById("prefix").value;
                        const name = document.getElementById("name").value;
                        const lname = document.getElementById("lname").value;
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
                                data: { name:name, uname:uname, pass:pass, brn:brn, eid:eid, role:role, prefix:prefix, lname:lname},
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
