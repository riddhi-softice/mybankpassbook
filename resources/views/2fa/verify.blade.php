@extends('auth.layout')

@section('content')
    <div class="container">
        <section class="section register min-vh-100 d-flex flex-column align-items-center justify-content-center py-4">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-4 col-md-6 d-flex flex-column align-items-center justify-content-center">

                        <div class="d-flex justify-content-center py-4">
                            <a href="index.html" class="logo d-flex align-items-center w-auto">
                                <img src="{{ asset('public/assets/img/logo.png') }}" alt="">
                                <span class="d-none d-lg-block">Hindi English Translate</span>
                            </a>
                        </div>

                        <div class="card mb-3">

                            @if(session('error'))
                                <div class="alert alert-danger">
                                    {{ session('error') }}
                                </div>
                            @endif
                            @if(session('success'))
                                <div class="alert alert-success">
                                    {{ session('success') }}
                                </div>
                            @endif

                            <div class="card-body text-center">

                                <div class="pt-4 pb-2">
                                    <h5 class="card-title">Two-Factor Authentication Verification</h5>
                                </div>

                                <form class="row g-3 needs-validation" action="{{ route('2fa.verify') }}" method="POST">
                                    @csrf

                                    <div class="col-12">
                                        <!--<label for="verify_code" class="form-label">One Time Password</label>-->
                                        <div class="input-group has-validation">
                                            <input type="text" name="verify_code" placeholder="Enter Verification Code" class="form-control" id="verify_code" required>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <button class="btn btn-primary w-100" type="submit">Verify</button>
                                    </div>
                                </form>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@section('javascript')
    <!-- Include any additional JavaScript files or scripts here -->
@endsection
