@extends('layouts.app')

@section('content')
    <div class="">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="d-flex justify-content-between my-3">
                    <h1 class="text-center">Customers</h1>
                    <div class="d-flex gap-2">
                        <div class="d-flex"><button class="btn btn-success align-self-center addBtn" data-toggle="tooltip" title="Add Customer"><i class="bi bi-plus-square"></i></button></div>
                        @role('admin')
                            <a class="d-flex" href="{{ route('deleted-customers') }}"><button class="btn btn-secondary align-self-center" data-toggle="tooltip" title="Deleted customers"><i class="bi bi-recycle"></i></button></a>
                            <div class="d-flex"><button class="btn btn-primary align-self-center importBtn" data-bs-toggle="modal" data-bs-target="#exampleModal" data-toggle="tooltip" title="Import from file"><i class="bi bi-arrow-down-square"></i></button></div>
                        @endrole

                        <!-- Modal -->
                        <div class="modal fade" id="exampleModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h1 class="modal-title fs-5" id="exampleModalLabel">Modal title</h1>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form id="uploadForm" enctype="multipart/form-data">
                                        @csrf
                                        <div class="modal-body" id="modalBody">
                                            <input type="file" name="file" id="excel_file" accept=".xls, .xlsx">
                                            <input type="hidden" name="import_data" id="import_data">
                                            <div id="excel_data" class="mt-2"></div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <button type="button" class="btn btn-primary" id="clearTable">Clear</button>
                                            <button type="submit" class="btn btn-success">Save changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card shadow-sm">
                    <div class="card-body bg-white table-responsive">
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
                                        <th class="text-start">{{ $user->citizen_id }}</th>
                                        <td>{{ $user->full_name }}</td>
                                        <td class="text-start">{{ is_array($user->brn) ? $user->brn[0] : ''}}</td>
                                        <td>{{ $user->course }}</td>
                                        <td >
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#userdetail{{ $index }}" data-toggle="tooltip" title="Detail"><i class="bi bi-person-vcard"></i></button>
                                            <!-- Modal -->
                                            <div class="modal fade" id="userdetail{{ $index }}" tabindex="-1" aria-labelledby="userdetail{{ $index }}Label" aria-hidden="true">
                                                <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h1 class="modal-title fs-5" id="staticBackdropLabel">Customer Detail</h1>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row row-cols-2 text-wrap">
                                                            <div class="col"><b>Citizen ID:</b> {{ $user->citizen_id }}</div>
                                                            <div class="col"><b>Name:</b> {{ $user->full_name }}</div>
                                                            <div class="col"><b>Gender:</b> {{ $user->gender }}</div>
                                                            <div class="col"><b>Address:</b> {{ $user->address }}</div>
                                                            <div class="col"><b>Province:</b> {{ $user->province }}</div>
                                                            <div class="col"><b>DoB:</b> {{ $user->dob }}</div>
                                                            <div class="col"><b>Phone:</b> {{ $user->phone }}</div>
                                                            <div class="col"><b>Course:</b> {{ $user->course }}</div>
                                                            <div class="col-12"><b>Branch: </b> {{ is_array($user->brn) ? $user->brn[0] . ' / ' . $user->brn[1] : ''}}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                                </div>
                                            </div>
                                            <button class="btn btn-sm btn-warning editBtn" custom="{{ $user }}" data-toggle="tooltip" title="Edit"><i class="bi bi-gear"></i></button>
                                            <button class="btn btn-sm btn-danger delBtn" value="{{ $user->citizen_id }}" data-toggle="tooltip" title="Delete"><i class="bi bi-trash3"></i></button>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.2/xlsx.full.min.js"></script>
    <script>
        const excel_file = document.getElementById('excel_file');
        const clear = document.getElementById('clearTable');
        const excel_data = document.getElementById('excel_data');
        const import_data = document.getElementById('import_data');
        let store_data;
        clear.addEventListener('click', () => {
            excel_data.innerHTML = "";
        })

        excel_file.addEventListener('change', (event) => {
            if(!['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'].includes(event.target.files[0].type))
            {
                excel_data.innerHTML = '<div class="alert alert-danger">Only .xlsx or .xls file format are allowed</div>';
                excel_file.value = '';

                return false;
            }

            var reader = new FileReader();

            reader.readAsArrayBuffer(event.target.files[0]);

            reader.onload = function(event){

                var data = new Uint8Array(reader.result);

                var work_book = XLSX.read(data, {type:'array'});

                var sheet_name = work_book.SheetNames;

                var sheet_data = XLSX.utils.sheet_to_json(work_book.Sheets[sheet_name[0]], {header:1});

                if(sheet_data.length > 0)
                {
                    store_data = JSON.stringify(sheet_data);
                    import_data.value = store_data;
                    var table_output = '<div style="height:200px;"><table class="table table-striped table-bordered text-nowrap">';

                    for(var row = 0; row < sheet_data.length; row++)
                    {

                        table_output += '<tr>';

                        for(var cell = 0; cell < sheet_data[row].length; cell++)
                        {

                            if(row == 0)
                            {

                                table_output += '<th>'+sheet_data[row][cell]+'</th>';

                            }
                            else
                            {

                                table_output += '<td>'+sheet_data[row][cell]+'</td>';

                            }

                        }

                        table_output += '</tr>';

                    }

                    table_output += '</table></div>';
                    excel_data.innerHTML = table_output;
                }

                excel_file.value = '';

            }

        });


        $(document).ready(function () {
            $('[data-toggle="tooltip"]').tooltip();

            $('#uploadForm').submit(async (event) => {
                event.preventDefault();
                if (store_data) {
                    array = JSON.parse(store_data);
                    array.shift();
                    for (let i = 0; i < array.length; i += 100) {
                        $('#excel_data').html(`<span class="spinner-border text-info spinner-border-sm me-2" aria-hidden="true"></span><span role="status"> Saving...(${(i * 100 / array.length | 0)}%)</span>`);
                        const chunk = array.slice(i, Math.min(i + 100, array.length));

                        await $.ajax({
                            url: "/customer/import",
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            data: {data:JSON.stringify(chunk)},
                            success: function(response) {
                                if (!response.success) { // Adjust success check based on your API's response format
                                    $('#excel_data').html('<div class="alert alert-danger w-100" role="alert"><i class="bi bi-x-circle"></i> Upload data unsuccess!</div>');
                                    console.error('API request failed: ', response); // Adjust error handling
                                } else {
                                    console.log(`Successfully sent chunk ${i / 100 + 1}: `, response);
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Error sending data to API: ', error);
                                $('#excel_data').html('<div class="alert alert-danger w-100" role="alert"><i class="bi bi-x-circle"></i> Upload data unsuccess!</div>');
                            }
                        });
                    }
                    $('#excel_data').html('<div class="alert alert-success w-100" role="alert"><i class="bi bi-check-circle"></i> Upload data success!</div>');
                } else {
                    $('#excel_data').html('<div class="alert alert-warning w-100" role="alert"><i class="bi bi-exclamation-triangle"></i> Please upload file!</div>');
                }


            })

            // Add Customer Button
            $(".addBtn").click(function() {
                Swal.fire({
                    title: 'Add Customer',
                    html: `
                        <div class="mb-3">
                            <input type="text" class="form-control" maxlength="13" id="cid" placeholder="* Citizen ID (1-13 digits)" required>
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control" maxlength="13" id="pass" placeholder="* Password (8-13 digits)" required>
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control" maxlength="100" id="name" placeholder="* Name">
                        </div>
                        <select class="form-select mb-3" aria-label="selectCourse" id="gend">
                            <option value="" selected disabled>Select Gender</option>
                            <option value="ชาย" >ชาย</option>
                            <option value="หญิง" >หญิง</option>
                            <option value="เพศทางเลือก" >เพศทางเลือก</option>
                        </select>
                        <div class="mb-3">
                            <input type="text" class="form-control" maxlength="100" id="addr" placeholder="Address">
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control" maxlength="100" id="prov" placeholder="Province">
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control" maxlength="100" id="dob" placeholder="Date of Birth">
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control" maxlength="100" id="phone" placeholder="Phone">
                        </div>
                        <select class="form-select" aria-label="selectCourse" id="course">
                            <option value="" selected disabled>* Select course</option>
                            <option value="หลักสูตรเรียนขับรถจักรยานยนต์">หลักสูตรเรียนขับรถจักรยานยนต์</option>
                            <option value="หลักสูตรเรียนขับรถยนต์(มีพื้นฐาน)">หลักสูตรเรียนขับรถยนต์ (มีพื้นฐาน)</option>
                            <option value="หลักสูตรเรียนขับรถยนต์(ไม่มีพื้นฐาน)">หลักสูตรเรียนขับรถยนต์ (ไม่มีพื้นฐาน)</option>
                            <option value="หลักสูตรเรียนขับขี่รถขนส่ง(ท.2)">หลักสูตรเรียนขับขี่รถขนส่ง(ท.2)</option>
                            <option value="หลักสูตรเรียนขับขี่รถขนส่ง(ท.3)">หลักสูตรเรียนขับขี่รถขนส่ง(ท.3)</option>
                        </select>
                    `,
                    showCancelButton: true,
                    preConfirm: () => {
                        const cid = document.getElementById("cid").value;
                        const pass = document.getElementById("pass").value;
                        const name = document.getElementById("name").value;
                        const gend = document.getElementById("gend").value;
                        const addr = document.getElementById("addr").value;
                        const prov = document.getElementById("prov").value;
                        const dob = document.getElementById("dob").value;
                        const course = document.getElementById("course").value;
                        const phone = document.getElementById("phone").value;
                        if (cid && pass && name && course) {
                            $.ajax({
                                url: "/customer/store",
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                },
                                data: { id:cid, name:name, gend:gend, pass:pass, addr:addr, prov:prov, dob:dob, course:course, phone:phone},
                                success: function (response) {
                                    console.log(response);
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
                                    console.log(error.responseJSON.message);
                                    Swal.showValidationMessage(error.responseJSON.message);
                                    Swal.disableLoading();
                                    Swal.enableButtons();
                                    Swal.enableInput();
                                }
                            });
                            Swal.disableButtons();
                            Swal.disableInput();
                            Swal.showLoading();
                            return false; // Prevent closing modal on preConfirm
                        } else {
                            Swal.showValidationMessage(`Please enter all * field.`);
                        }
                    },
                    showLoaderOnConfirm: true,
                    allowOutsideClick: () => false
                });
            });

            $(document).on("click", ".editBtn", function() {
                const custom = JSON.parse($(this).attr("custom"));
                Swal.fire({
                    title: 'Edit Customer',
                    html: `
                    <div class="mb-3">
                            <input type="text" class="form-control" maxlength="13" value="${custom.citizen_id}" id="cid" placeholder="* Citizen ID (1-13 digits)">
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control" maxlength="13" id="pass" placeholder="Password (8-13 digits) หากใช้รหัสผ่านเดิมไม่ต้องกรอก">
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control" maxlength="100" value="${custom.full_name}" id="name" placeholder="* Name">
                        </div>
                        <select class="form-select mb-3" aria-label="selectCourse" id="gend">
                            <option value="" selected disabled>Select Gender</option>
                            <option value="ชาย" ${custom.gender == 'ชาย' ? 'selected' : ''}>ชาย</option>
                            <option value="หญิง" ${custom.gender == 'หญิง' ? 'selected' : ''}>หญิง</option>
                            <option value="เพศทางเลือก" ${custom.gender == 'เพศทางเลือก' ? 'selected' : ''}>เพศทางเลือก</option>
                        </select>
                        <div class="mb-3">
                            <input type="text" class="form-control" maxlength="100" value="${custom.address}" id="addr" placeholder="Address">
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control" maxlength="100" value="${custom.province}" id="prov" placeholder="Province">
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control" maxlength="100" id="dob" value="${custom.dob}" placeholder="Date of Birth">
                        </div>
                        <div class="mb-3">
                            <input type="text" class="form-control" maxlength="100" id="phone" value="${custom.phone}" placeholder="Phone">
                        </div>
                        <select class="form-select" aria-label="selectCourse" id="course">
                            <option value="" selected disabled>* Select course</option>
                            <option value="หลักสูตรเรียนขับรถจักรยานยนต์" ${custom.course == 'หลักสูตรเรียนขับรถจักรยานยนต์' ? 'selected' : ''}>หลักสูตรเรียนขับรถจักรยานยนต์</option>
                            <option value="หลักสูตรเรียนขับรถยนต์(มีพื้นฐาน)" ${custom.course == 'หลักสูตรเรียนขับรถยนต์(มีพื้นฐาน)' ? 'selected' : ''}>หลักสูตรเรียนขับรถยนต์ (มีพื้นฐาน)</option>
                            <option value="หลักสูตรเรียนขับรถยนต์(ไม่มีพื้นฐาน)" ${custom.course == 'หลักสูตรเรียนขับรถยนต์(ไม่มีพื้นฐาน)' ? 'selected' : ''}>หลักสูตรเรียนขับรถยนต์ (ไม่มีพื้นฐาน)</option>
                            <option value="หลักสูตรเรียนขับขี่รถขนส่ง(ท.2)" ${custom.course == 'หลักสูตรเรียนขับขี่รถขนส่ง(ท.2)' ? 'selected' : ''}>หลักสูตรเรียนขับขี่รถขนส่ง(ท.2)</option>
                            <option value="หลักสูตรเรียนขับขี่รถขนส่ง(ท.3)" ${custom.course == 'หลักสูตรเรียนขับขี่รถขนส่ง(ท.3)' ? 'selected' : ''}>หลักสูตรเรียนขับขี่รถขนส่ง(ท.3)</option>
                        </select>
                    `,
                    showCancelButton: true,
                    preConfirm: (agnName) => {
                        const cid = document.getElementById("cid").value;
                        const pass = document.getElementById("pass").value;
                        const name = document.getElementById("name").value;
                        const gend = document.getElementById("gend").value;
                        const addr = document.getElementById("addr").value;
                        const prov = document.getElementById("prov").value;
                        const dob = document.getElementById("dob").value;
                        const course = document.getElementById("course").value;
                        const phone = document.getElementById("phone").value;
                        if (cid && name) {
                            $.ajax({
                                url: "/customer/update",
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                },
                                data: { oid:custom.citizen_id, id:cid, name:name, gend:gend, pass:pass, addr:addr, prov:prov, dob:dob, course:course, phone:phone},
                                success: function (response) {
                                    console.log(response);
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
                                    console.log(error.responseJSON.message);
                                    Swal.showValidationMessage(error.responseJSON.message);
                                    Swal.disableLoading();
                                    Swal.enableButtons();
                                    Swal.enableInput();
                                }
                            });
                            Swal.disableButtons();
                            Swal.disableInput();
                            Swal.showLoading();
                            return false; // Prevent closing modal on preConfirm
                        } else {
                            Swal.showValidationMessage(`Please enter all * field.`);
                        }
                    },
                    showLoaderOnConfirm: true,
                    allowOutsideClick: () => !Swal.isLoading()
                });
            });

            $(document).on("click", ".delBtn", function() {
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
                            url: "/customer/delete",
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
        })
    </script>
@endsection
