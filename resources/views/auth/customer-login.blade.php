@extends('layouts.guest')

@section('content')
    <div class="container h-100">
        <div class="d-flex justify-content-center align-items-center w-100 h-100">
            <div class="card shadow w-md-25">
                <div class="card-body">
                    <div class="text-center px-3 pt-3">
                        <img src="/imgs/logo.png" width="100" alt="">
                        <h1 class="m-0 fw-bold">Smart <span style="color: #F15A29">HUB</span></h1>
                        <p><span class="fw-bold" style="color: #F15A29">Train</span>ingzenter</p>
                    </div>
                    <div class="mb-3">
                        @if ($errors->any())
                            <div class="d-flex justify-content-center"><span class="badge text-bg-danger mb-1">{{ $errors->first() }}</span></div>
                        @endif
                        <form method="POST" action="{{ route('customer-auth') }}">
                            @csrf
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control @error('citizen_id') is-invalid @enderror"
                                    value="5471600003963" name="citizen_id" required autofocus autocomplete="citizen_id" id="citizen_id"
                                    placeholder="กรุณากรอกหมายเลขประจำตัวประชาชน">
                                <label for="citizen_id">หมายเลขประจำตัวประชาชน</label>
                                @error('citizen_id')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                    id="password" placeholder="Password" value="5471600003963" name="password" required
                                    autocomplete="current-password">
                                <label for="password">รหัสผ่าน</label>
                            </div>
                            <div class="w-100">
                                <button type="submit" class="btn w-100" id="submitbtn">
                                    เข้าสู่ระบบ
                                </button>
                                <p type="submit" class="w-100 my-1">
                                    หรือ <a href="/login">เข้าสู่ระบบด้วยบัญชีเจ้าหน้าที่</a>
                                </p>
                            </div>
                        </form>
                    </div>
                    <div class="d-flex justify-content-center" id="copyright">
                        <i class="bi bi-c-circle"> </i>
                        <p class="m-0">{{ now()->format('Y') }} iddrives .co.ltd</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <style>
        #submitbtn {
            background-color: #FC5F2B;
            color: white;
        }

        #submitbtn:hover {
            background-color: #D15126;
            color: white;
        }
        #copyright{
            width: 300px;
        }
        @media (max-width: 400px) {
            #copyright{
                width: 100%;
            }
        }
    </style>
@endsection
