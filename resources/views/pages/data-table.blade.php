@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 mb-4">
                <div class="d-flex justify-content-between">
                    <h1 class="text-center my-3">Courses</h1>
                    @role('staff')
                        <div class="d-flex"><button class="btn btn-success align-self-center addBtn" addType="course">Add</button></div>
                    @endrole
                </div>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <table class="table table-hover display nowrap w-100" id="courseTable">
                            <thead>
                                <tr class="table-dark">
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($courses as $index => $course)
                                    <tr>
                                        <th>{{ $course->code }}</th>
                                        <td>{{ $course->name }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-warning editBtn" editType="course" value="{{ $course->name }}" editId="{{ $course->id }}"><i class="bi bi-gear"></i></button>
                                            <button class="btn btn-sm btn-danger delBtn" delType="course" delId="{{ $course->id }}"><i class="bi bi-trash3"></i></button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @role('admin')
            <div class="col-md-8 mb-4">
                <div class="d-flex justify-content-between">
                    <h1 class="text-center my-3">Agencies</h1>
                    <div class="d-flex"><button class="btn btn-success align-self-center addBtn" addType="agn">Add</button></div>
                </div>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <table class="table table-hover display nowrap w-100" id="agnTable">
                            <thead>
                                <tr class="table-dark">
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Prefix</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($agns as $index => $agn)
                                    <tr>
                                        <th>{{ $index + 1 }}</th>
                                        <td>{{ $agn->name }}</td>
                                        <td>{{ $agn->prefix ?? '-' }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-warning editBtn" editType="agn" value="{{ $agn->name }}" eprefix="{{ $agn->prefix }}" editId="{{ $agn->id }}"><i class="bi bi-gear"></i></button>
                                            <button class="btn btn-sm btn-danger delBtn" delType="agn" delId="{{ $agn->id }}"><i class="bi bi-trash3"></i></button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-8 mb-4">
                <div class="d-flex justify-content-between">
                    <h1 class="text-center my-3">Branches</h1>
                    <div class="d-flex"><button class="btn btn-success align-self-center addBtn" addType="brn">Add</button></div>
                </div>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <table class="table table-hover display" id="brnTable">
                            <thead>
                                <tr class="table-dark">
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Agency</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($brns as $index => $brn)
                                    <tr>
                                        <th>{{ $index + 1 }}</th>
                                        <td>{{ $brn->name }}</td>
                                        <td>{{ optional($brn->getAgn)->name }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-warning editBtn" editType="brn" value="{{ $brn->name }}" aid="{{ $brn->agn }}" editId="{{ $brn->id }}"><i class="bi bi-gear"></i></button>
                                            <button class="btn btn-sm btn-danger delBtn" delType="brn" delId="{{ $brn->id }}"><i class="bi bi-trash3"></i></button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- <div class="col-md-8 mb-4">
                <div class="d-flex justify-content-between">
                    <h1 class="text-center my-3">Permissions</h1>
                    <div class="d-flex"><button class="btn btn-success align-self-center addBtn" addType="perm">Add</button></div>
                </div>
                <div class="card shadow-sm">
                    <div class="card-body bg-white">
                        <table class="table table-hover display" id="permTable">
                            <thead>
                                <tr class="table-dark">
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Users</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($perms as $index => $perm)
                                    <tr>
                                        <th>{{ $index + 1 }}</th>
                                        <td>{{ $perm->name }}</td>
                                        <td>{{ App\Models\User::with('roles')->get()->filter(fn ($user) => $user->roles->where('name', 'Super Admin')->toArray())->count() }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-danger delBtn" delType="perm" delId="{{ $perm->name }}"><i class="bi bi-trash3"></i></button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div> --}}
            <div class="col-md-8 mb-4">
                <div class="d-flex justify-content-between">
                    <h1 class="text-center my-3">Role</h1>
                    <div class="d-flex"><button class="btn btn-success align-self-center addBtn" addType="role">Add</button></div>
                </div>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <table class="table table-hover display" id="roleTable">
                            <thead>
                                <tr class="table-dark">
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Users</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($roles as $index => $role)
                                    <tr>
                                        <th>{{ $index + 1 }}</th>
                                        <td>{{ $role->name }}</td>
                                        <td>{{ App\Models\User::with('roles')->get()->filter(fn ($user) => $user->roles->where('name', $role->name)->toArray())->count() }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-danger delBtn" delType="role" delId="{{ $role->name }}"><i class="bi bi-trash3"></i></button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endrole
        </div>
    </div>


    <script>
        $(document).ready(function () {
            $(".addBtn").click(function() {
                const atype = $(this).attr("addType");
                if (atype === 'agn') {
                    Swal.fire({
                        title: 'Agency',
                        html: `
                            <input id="agnname" class="swal2-input" placeholder="Enter agency name">
                            <input id="prefix" class="swal2-input" placeholder="Enter prefix">
                        `,
                        showCancelButton: true,
                        preConfirm: () => {
                            const agn_name = document.getElementById("agnname").value;
                            const agn_prefix = document.getElementById("prefix").value;
                            if (agn_name && agn_prefix) {
                                $.ajax({
                                    url: "/data/add",
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                    },
                                    data: { addType:atype ,agnname:agn_name, prefix:agn_prefix},
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
                                Swal.showValidationMessage(`Please enter input.`);
                            }
                        },
                        showLoaderOnConfirm: true,
                        allowOutsideClick: () => !Swal.isLoading()
                    });
                } else if (atype === 'course') {
                    Swal.fire({
                        title: 'Course',
                        html: `
                            <div class="mb-3">
                                <input type="text" maxlength="100" class="form-control" maxlength="100" id="cname" placeholder="Course name">
                            </div>
                        `,
                        showCancelButton: true,
                        preConfirm: () => {
                            const cname = document.getElementById("cname").value;
                            if (cname) {
                                $.ajax({
                                    url: "/data/add",
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                    },
                                    data: { addType:atype, cname:cname},
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
                                Swal.showValidationMessage(`Please enter name and code.`);
                            }
                        },
                        showLoaderOnConfirm: true,
                        allowOutsideClick: () => !Swal.isLoading()
                    });
                } else if (atype === 'brn') {
                    Swal.fire({
                        title: 'Branch',
                        html: `
                            <div class="mb-3">
                                <input type="text" maxlength="100" class="form-control" maxlength="100" id="bname" placeholder="Branch name">
                            </div>
                            <select class="form-select" aria-label="Default select example" id="agn">
                                <option value="" selected disabled>Select agency</option>
                                @foreach ($agns as $agn)
                                    <option value="{{ $agn->id }}">{{ $agn->name }}</option>
                                @endforeach
                            </select>
                        `,
                        showCancelButton: true,
                        preConfirm: () => {
                            const bname = document.getElementById("bname").value;
                            const bagn = document.getElementById("agn").value;
                            if (bname && bagn) {
                                $.ajax({
                                    url: "/data/add",
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                    },
                                    data: { addType:atype, bName:bname, bAgn: bagn},
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
                                Swal.showValidationMessage(`Please enter name and select agency.`);
                            }
                        },
                        showLoaderOnConfirm: true,
                        allowOutsideClick: () => !Swal.isLoading()
                    });
                } else if (atype === 'perm') {
                    Swal.fire({
                        title: 'Permission',
                        input: "text",
                        inputLabel: "Enter permission name",
                        showCancelButton: true,
                        preConfirm: (permName) => {
                            if (permName) {
                                $.ajax({
                                    url: "/data/add",
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                    },
                                    data: { addType:atype ,permName:permName},
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
                                Swal.showValidationMessage(`Please enter permission name.`);
                            }
                        },
                        showLoaderOnConfirm: true,
                        allowOutsideClick: () => !Swal.isLoading()
                    });
                } else if (atype === 'role') {
                    Swal.fire({
                        title: 'Role',
                        input: "text",
                        inputLabel: "Enter role name",
                        showCancelButton: true,
                        preConfirm: (roleName) => {
                            if (roleName) {
                                $.ajax({
                                    url: "/data/add",
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                    },
                                    data: { addType:atype ,roleName:roleName},
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
                                Swal.showValidationMessage(`Please enter permission name.`);
                            }
                        },
                        showLoaderOnConfirm: true,
                        allowOutsideClick: () => !Swal.isLoading()
                    });
                }
            });

            $(document).on("click", ".editBtn", function() {
                const edittype = $(this).attr("editType");
                const editId = $(this).attr("editId");
                const name = $(this).val();
                if (edittype === 'agn') {
                    const eprefix = $(this).attr("eprefix");
                    Swal.fire({
                        title: 'Agency',
                        html: `
                            <input id="agnname" class="swal2-input" value="${name}" placeholder="Enter agency name">
                            <input id="prefix" class="swal2-input" value="${eprefix}" placeholder="Enter prefix">
                        `,
                        showCancelButton: true,
                        preConfirm: () => {
                            const agn_name = document.getElementById("agnname").value;
                            const agn_prefix = document.getElementById("prefix").value;
                            if (agn_name && agn_prefix) {
                                $.ajax({
                                    url: "/data/update",
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                    },
                                    data: { addType:edittype ,agnname:agn_name, eid:editId, prefix:agn_prefix},
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
                                Swal.showValidationMessage(`Please enter agency name.`);
                            }
                        },
                        showLoaderOnConfirm: true,
                        allowOutsideClick: () => !Swal.isLoading()
                    });
                } else if (edittype === 'course') {
                    Swal.fire({
                        title: 'Branch',
                        html: `
                            <div class="mb-3">
                                <input type="text" maxlength="100" value="${name}" class="form-control" maxlength="100" id="cname" placeholder="Course name">
                            </div>
                        `,
                        showCancelButton: true,
                        preConfirm: () => {
                            const cname = document.getElementById("cname").value;
                            if (cname) {
                                $.ajax({
                                    url: "/data/update",
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                    },
                                    data: { addType:edittype, cname:cname, eid:editId},
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
                                Swal.showValidationMessage(`Please enter name and select agency.`);
                            }
                        },
                        showLoaderOnConfirm: true,
                        allowOutsideClick: () => !Swal.isLoading()
                    });
                } else if (edittype === 'brn') {
                    const agnid = $(this).attr("aid");
                    Swal.fire({
                        title: 'Branch',
                        html: `
                            <div class="mb-3">
                                <input type="text" class="form-control" maxlength="100" id="bname" value="${name}" placeholder="Branch name">
                            </div>
                            <select class="form-select" aria-label="Default select example" id="agn">
                                <option value="" selected disabled>Select agency</option>
                                @foreach ($agns as $agn)
                                    <option value="{{ $agn->id }}" ${agnid == "{{ $agn->id }}" ? 'selected' : ''}>{{ $agn->name }}</option>
                                @endforeach
                            </select>
                        `,
                        showCancelButton: true,
                        preConfirm: () => {
                            const bname = document.getElementById("bname").value;
                            const bagn = document.getElementById("agn").value;
                            if (bname && bagn) {
                                $.ajax({
                                    url: "/data/update",
                                    method: 'POST',
                                    headers: {
                                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                    },
                                    data: { addType:edittype, bName:bname, bAgn: bagn, eid:editId},
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
                                Swal.showValidationMessage(`Please enter name and select agency.`);
                            }
                        },
                        showLoaderOnConfirm: true,
                        allowOutsideClick: () => !Swal.isLoading()
                    });
                }
            });

            $(document).on("click", ".delBtn", function() {
                const delType = $(this).attr("delType");
                const delId = $(this).attr("delId");
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
                                url: "/data/delete",
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                },
                                data: { deltype:delType ,delid:delId},
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
