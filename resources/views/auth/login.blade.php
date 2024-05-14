@extends('layouts.guest')

@section('content')
    <div class="container h-100">
        <div class="d-flex justify-content-center align-items-center w-100 h-100">
            <div class="card shadow w-md-25">
                <div class="card-body">
                    <div class="text-center py-3">
                        <img src="/imgs/logo.png" width="100" alt="">
                        <h1 class="m-0 fw-bold">Smart <span style="color: #F15A29">HUB</span></h1>
                        <p><span class="fw-bold" style="color: #F15A29">Training</span>zenter</p>
                    </div>
                    <div class="mb-3">
                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <div class="form-floating mb-3">
                                <input type="text" class="form-control @error('email') is-invalid @enderror"
                                    value="" name="email" required autofocus autocomplete="email" id="email"
                                    placeholder="Username">
                                <label for="email">เลขบัตรประชาชน</label>
                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            <div class="form-floating mb-3">
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                    id="password" placeholder="Password" value="" name="password" required
                                    autocomplete="current-password">
                                <label for="password">รหัสผ่าน</label>
                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                            <div class="w-100">
                                <button type="submit" class="btn w-100" id="submitbtn">
                                    เข้าสู่ระบบ
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="d-flex justify-content-center" id="copyright">
                        <i class="bi bi-c-circle"> </i>
                        <p class="m-0">{{ now()->format('Y') }} iddrives.co.ltd</p>
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
