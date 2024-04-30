@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="d-flex justify-content-between my-3">
                    <h1 class="text-center">Users Permission</h1>
                    <div class="d-flex"><button class="btn btn-success align-self-center addBtn">Add</button></div>
                </div>
                <div class="card shadow-sm">
                    <div class="card-body bg-white">
                        <livewire:user-permission />
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>

    </script>
@endsection
