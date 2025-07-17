@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                {{-- <div class="card shadow-sm mb-5">
                <div class="card-body">
                    <p class="mb-0 pb-1 fs-5 border-bottom"><span><i class="bi bi-browser-edge"></i></span> Web Informations</p>

                    <div class="pt-3 container text-center">
                        <div class="row row-cols-sm-2 row-cols-md-4">
                            <a href="https://dronettc.com/" target="_BLANK" class="link-underline link-underline-opacity-0">
                                <div class="service p-2 rounded">
                                    <img src="/imgs/logoiddrives.png" width="50" alt="">
                                    <p class="mb-0">DroneTTC</p>
                                </div>
                            </a>
                            <a href="https://iddrives.co.th/" target="_BLANK" class="link-underline link-underline-opacity-0 ">
                                <div class="service p-2 rounded">
                                    <img src="/imgs/logoiddrives.png" width="50" alt="">
                                    <p class="mb-0">IDDrives</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div> --}}
                {{-- @if (request()->has('login_sso_error'))
                    <div class="alert alert-danger">
                        {{ request()->query('login_sso_error') }}
                    </div>
                @endif --}}
                @if (request()->has('login_sso_error'))
                    <div id="sso-alert" class="alert alert-danger" role="alert">
                        {{ request()->query('login_sso_error') }}
                    </div>

                    <script>
                        setTimeout(function () {
                            // Hide the alert
                            const alert = document.getElementById('sso-alert');
                            if (alert) {
                                alert.style.display = 'none';
                            }

                            // Remove query parameter from URL without reloading
                            const url = new URL(window.location.href);
                            url.searchParams.delete('login_sso_error');
                            window.history.replaceState({}, document.title, url.pathname + url.search);
                        }, 3000);
                    </script>
                @endif


                <div class="card shadow-sm">
                    <div class="card-body">
                        <p class="mb-0 pb-1 fs-5 border-bottom"><span><i class="bi bi-star-fill"></i></span> Service</p>

                        <div class="pt-3 container text-center">
                            <div class="row row-cols-sm-2 row-cols-md-4">
                                <a href="http://127.0.0.1:8000/sso/login" class="link-underline link-underline-opacity-0">
                                    <div class="service p-2 rounded">
                                        <img src="/imgs/logoiddrives.png" width="50" alt="">
                                        <p class="mb-0">ระบบจัดการความรู้ออนไลน์</p>
                                        <p class="mb-0">Hub-Training</p>
                                    </div>
                                </a>
                                <a href="https://checkbefore.trainingzenter.com/sso/{{ $user->username }}/{{ $user->prefix . ' ' . $user->name . ' ' . $user->lname }}/{{ $course_type }}/{{ $send_branch ?? 'idmskk' }}"
                                    target="_BLANK" class="link-underline link-underline-opacity-0">
                                    <div class="service p-2 rounded">
                                        <img src="/imgs/logoiddrives.png" width="50" alt="">
                                        <p class="mb-0">Check-Before Driving</p>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <style>
        .service:hover {
            background-color: #F0F0F0;
        }
    </style>
@endsection
