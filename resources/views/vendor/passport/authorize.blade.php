<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }} - Authorization</title>
    <link rel="icon" type="image/x-icon" href="/imgs/logo.png">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Styles -->
    <link href="{{ asset('/css/app.css') }}" rel="stylesheet">

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Scripts -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    <style>
        #bgid {
            background-image: url('/imgs/bg.jpg');
            background-size: cover;
        }
        #main {
            background-color:rgba(255, 255, 255, 0.6);
        }
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
</head>
<body id="bgid" class="vh-100">
    <div id="app" class="h-100">
        <main class="py-4 h-100" id="main">
            <div class="container h-100">
                <div class="d-flex justify-content-center align-items-center w-100 h-100">
                    <div class="card shadow w-md-25">
                        <div class="card-body">
                            <div class="text-center py-3">
                                <img src="/imgs/logo.png" width="100" alt="">
                                <h1 class="m-0 fw-bold"><span style="color: #F15A29">ID</span> Drives</h1>
                                <p class="mb-0"><span class="fw-bold" style="color: #F15A29">One</span> account for <span class="fw-bold"
                                        style="color: #F15A29">all</span> apps.</p>
                            </div>
                            <div class="mb-3 text-center">
                                <p class="fs-4">ลงทะเบียนด้วยชื่อผู้ใช้</p>
                                <!-- Authorize Button -->
                                <form method="post" action="{{ route('passport.authorizations.approve') }}">
                                    @csrf

                                    <input type="hidden" name="state" value="{{ $request->state }}">
                                    <input type="hidden" name="client_id" value="{{ $client->getKey() }}">
                                    <input type="hidden" name="auth_token" value="{{ $authToken }}">
                                    <button type="submit" class="btn-approve btn btn-outline-primary w-100">
                                        <div class="d-flex justify-content-center text-start w-100 gap-4">
                                            <div class="fs-2 text-center">
                                                <i class="bi bi-person-circle"></i>
                                            </div>
                                            <div class="">
                                                <h5 class="mb-0">{{ auth()->user()->name }}</h5>
                                                <p class="mb-0" style="max-width: 200px;">ฝ่าย: {{ optional(auth()->user()->getDpm)->name }} สาขา: {{ optional(auth()->user()->getBrn)->name }}</p>
                                            </div>
                                        </div>
                                    </button>
                                </form>
                            </div>
                            <div class="d-flex justify-content-center gap-2 mb-2">
                                <div class="" style="text-align: center">
                                    <form method="post" action="{{ route('different-account') }}" style="display: inline-block;">
                                        @csrf

                                        <input type="hidden" name="current_url" value="{{ $request->fullUrl() }}">
                                        @php
                                            $fname =  explode(' ', auth()->user()->name);
                                        @endphp
                                        <button type="submit" class="btn btn-sm btn-success btn-block" style="width: auto">Not {{ $fname ? $fname[0] : '' }}? Login again</button>
                                    </form>
                                </div>
                            </div>
                            <div class="d-flex justify-content-center" id="copyright">
                                <i class="bi bi-c-circle"> </i>
                                <p class="m-0">{{ now()->format('Y') }} iddrives .co.ltd</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
