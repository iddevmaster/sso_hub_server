@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm mb-5">
                <div class="card-body bg-white">
                    <p class="mb-0 pb-1 fs-5 border-bottom"><span><i class="bi bi-browser-edge"></i></span> Web Informations</p>

                    <div class="pt-3 container text-center">
                        <div class="row row-cols-sm-2 row-cols-md-4">
                            <a href="https://dronettc.com/" target="_BLANK" class="link-underline link-underline-opacity-0 link-dark">
                                <div class="service p-2 rounded">
                                    <img src="/imgs/logoiddrives.png" width="50" alt="">
                                    <p class="mb-0">DroneTTC</p>
                                </div>
                            </a>
                            <a href="https://iddrives.co.th/" target="_BLANK" class="link-underline link-underline-opacity-0 link-dark">
                                <div class="service p-2 rounded">
                                    <img src="/imgs/logoiddrives.png" width="50" alt="">
                                    <p class="mb-0">IDDrives</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body bg-white">
                    <p class="mb-0 pb-1 fs-5 border-bottom"><span><i class="bi bi-star-fill"></i></span> Service</p>

                    <div class="pt-3 container text-center">
                        <div class="row row-cols-sm-2 row-cols-md-4">
                            <a href="https://saraban.iddrives.co.th/" target="_BLANK" class="link-underline link-underline-opacity-0 link-dark">
                                <div class="service p-2 rounded">
                                    <img src="/imgs/logoiddrives.png" width="50" alt="">
                                    <p class="mb-0">Saraban</p>
                                </div>
                            </a>
                            <a href="https://i-prompt.iddrives.co.th/" target="_BLANK" class="link-underline link-underline-opacity-0 link-dark">
                                <div class="service p-2 rounded">
                                    <img src="/imgs/logoiddrives.png" width="50" alt="">
                                    <p class="mb-0">I-Prompt</p>
                                </div>
                            </a>
                            <a href="https://kstplus.iddrives.co.th/" target="_BLANK" class="link-underline link-underline-opacity-0 link-dark">
                                <div class="service p-2 rounded">
                                    <img src="/imgs/logoiddrives.png" width="50" alt="">
                                    <p class="mb-0">KSTplus</p>
                                </div>
                            </a>
                            <a href="https://hrc.iddrives.co.th/" target="_BLANK" class="link-underline link-underline-opacity-0 link-dark">
                                <div class="service p-2 rounded">
                                    <img src="/imgs/logoiddrives.png" width="50" alt="">
                                    <p class="mb-0">HRC</p>
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
