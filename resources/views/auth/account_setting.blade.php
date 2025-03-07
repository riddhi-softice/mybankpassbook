@extends('layouts.app')

@section('content')

    <section class="section">
        <div class="row">
            <center>
            <div class="col-lg-6">
                <div class="card">

                    @if(session('error'))
                    <div class="alert alert-danger">
                        {{ session('error') }}
                    </div>
                    @endif


                    <div class="card-body">
                        <h5 class="card-title">Change Admin Settings</h5>

                        <form action="{{ route('post.account_setting') }}" method="POST">
                            @csrf

                            <input type="hidden" class="form-control" name="id" value="{{ session('admin_id')}}" required>

                            <div class="row mb-3">
                                <label for="inputText" class="col-sm-3 col-form-label"> Name</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="name" disabled
                                        value="{{ old('name', $data->name) }}" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="inputText" class="col-sm-3 col-form-label">Email</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" name="email" disabled
                                        value="{{ old('email', $data->email) }}" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="inputText" class="col-sm-3 col-form-label">Old Password</label>
                                <div class="col-sm-9">
                                    <input type="password" class="form-control" name="old_password" value="" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="inputText" class="col-sm-3 col-form-label">New Password</label>
                                <div class="col-sm-9">
                                    <input type="password" class="form-control" name="password" value="" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary">Save</button>
                        </form>

                    </div>
                </div>
            </div>
            </center>
        </div>
    </section>
@endsection

@yield('javascript')

