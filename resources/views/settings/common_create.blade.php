@extends('layouts.app')

@section('content')
    <div class="pagetitle">
        <h1>Common Setting</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            </ol>
        </nav>
    </div>

    <section class="section">
        <form action="{{ route('change_setting') }}" method="POST" enctype="multipart/form-data">
            @csrf


            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            
            <div class="row">

                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Admob Ads Setting</h5>

                            @foreach ($settings as $setting)

                                @if ($setting->setting_key == "Interstitial")
                                    <div class="row mb-3">
                                        <label for="inputText" class="col-sm-2 col-form-label">Interstitial</label>
                                        <div class="col-sm-2">
                                            <button type="button" class="btn btn-success" onclick="addTextField('Interstitial')">Add New</button>
                                        </div>
                                    </div>

                                    <div id="Interstitial">
                                        @foreach (explode(',',$setting->setting_value) as $key=>$value)
                                            <div class="row mb-3">
                                                <label for="inputText" class="col-sm-2 col-form-label"></label>
                                                <div class="col-sm-8">
                                                    <input type="text" class="form-control" name="Interstitial[]" value="{{ $value }}" required>
                                                </div>
                                                <div class="col-sm-2">
                                                    <button type="button" class="btn btn-danger" onclick="removeTextField(this)">Remove</button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if ($setting->setting_key == "CollapseBanner")
                                    &nbsp; <hr>
                                    <div class="row mb-3">
                                        <label for="inputText" class="col-sm-2 col-form-label">CollapseBanner</label>
                                        <div class="col-sm-2">
                                            <button type="button" class="btn btn-success" onclick="addTextField('CollapseBanner')">Add New</button>
                                        </div>
                                    </div>

                                    <div id="CollapseBanner">
                                        @foreach (explode(',',$setting->setting_value) as $key=>$value)
                                            <div class="row mb-3">
                                                <label for="inputText" class="col-sm-2 col-form-label"></label>
                                                <div class="col-sm-8">
                                                    <input type="text" class="form-control" name="CollapseBanner[]" value="{{ $value }}" required>
                                                </div>
                                                <div class="col-sm-2">
                                                    <button type="button" class="btn btn-danger" onclick="removeTextField(this)">Remove</button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if ($setting->setting_key == "BigBanner")
                                    &nbsp; <hr>
                                    <div class="row mb-3">
                                        <label for="inputText" class="col-sm-2 col-form-label">BigBanner</label>
                                        <div class="col-sm-2">
                                            <button type="button" class="btn btn-success" onclick="addTextField('BigBanner')">Add New</button>
                                        </div>
                                    </div>

                                    <div id="BigBanner">
                                        @foreach (explode(',',$setting->setting_value) as $key=>$value)
                                            <div class="row mb-3">
                                                <label for="inputText" class="col-sm-2 col-form-label"></label>
                                                <div class="col-sm-8">
                                                    <input type="text" class="form-control" name="BigBanner[]" value="{{ $value }}" required>
                                                </div>
                                                <div class="col-sm-2">
                                                    <button type="button" class="btn btn-danger" onclick="removeTextField(this)">Remove</button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if ($setting->setting_key == "SmallBanner")
                                    &nbsp; <hr>
                                    <div class="row mb-3">
                                        <label for="inputText" class="col-sm-2 col-form-label">SmallBanner</label>
                                        <div class="col-sm-2">
                                            <button type="button" class="btn btn-success" onclick="addTextField('SmallBanner')">Add New</button>
                                        </div>
                                    </div>

                                    <div id="SmallBanner">
                                        @foreach (explode(',',$setting->setting_value) as $key=>$value)
                                            <div class="row mb-3">
                                                <label for="inputText" class="col-sm-2 col-form-label"></label>
                                                <div class="col-sm-8">
                                                    <input type="text" class="form-control" name="SmallBanner[]" value="{{ $value }}" required>
                                                </div>
                                                <div class="col-sm-2">
                                                    <button type="button" class="btn btn-danger" onclick="removeTextField(this)">Remove</button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                @if ($setting->setting_key == "MedBanner")
                                    &nbsp; <hr>
                                    <div class="row mb-3">
                                        <label for="inputText" class="col-sm-2 col-form-label">MedBanner</label>
                                        <div class="col-sm-2">
                                            <button type="button" class="btn btn-success" onclick="addTextField('MedBanner')">Add New</button>
                                        </div>
                                    </div>

                                    <div id="MedBanner">
                                        @foreach (explode(',',$setting->setting_value) as $key=>$value)
                                            <div class="row mb-3">
                                                <label for="inputText" class="col-sm-2 col-form-label"></label>
                                                <div class="col-sm-8">
                                                    <input type="text" class="form-control" name="MedBanner[]" value="{{ $value }}" required>
                                                </div>
                                                <div class="col-sm-2">
                                                    <button type="button" class="btn btn-danger" onclick="removeTextField(this)">Remove</button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                @if ($setting->setting_key == "AppOpen")
                                    &nbsp; <hr>
                                    <div class="row mb-3">
                                        <label for="inputText" class="col-sm-2 col-form-label">AppOpen</label>
                                        <div class="col-sm-2">
                                            <button type="button" class="btn btn-success" onclick="addTextField('AppOpen')">Add New</button>
                                        </div>
                                    </div>

                                    <div id="AppOpen">
                                        @foreach (explode(',',$setting->setting_value) as $key=>$value)
                                            <div class="row mb-3">
                                                <label for="inputText" class="col-sm-2 col-form-label"></label>
                                                <div class="col-sm-8">
                                                    <input type="text" class="form-control" name="AppOpen[]" value="{{ $value }}" required>
                                                </div>
                                                <div class="col-sm-2">
                                                    <button type="button" class="btn btn-danger" onclick="removeTextField(this)">Remove</button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                                @if ($setting->setting_key == "BackInter")
                                    &nbsp; <hr>
                                    <div class="row mb-3">
                                        <label for="inputText" class="col-sm-2 col-form-label">BackInter</label>
                                        <div class="col-sm-2">
                                            <button type="button" class="btn btn-success" onclick="addTextField('BackInter')">Add New</button>
                                        </div>
                                    </div>

                                    <div id="BackInter">
                                        @foreach (explode(',',$setting->setting_value) as $key=>$value)
                                            <div class="row mb-3">
                                                <label for="inputText" class="col-sm-2 col-form-label"></label>
                                                <div class="col-sm-8">
                                                    <input type="text" class="form-control" name="BackInter[]" value="{{ $value }}" required>
                                                </div>
                                                <div class="col-sm-2">
                                                    <button type="button" class="btn btn-danger" onclick="removeTextField(this)">Remove</button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif


                            @endforeach
                        </div>
                    </div>
                </div>

            </div>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </section>
@endsection

@yield('javascript')
<script>
    /* static function set --
     function addTextField() {
        var additionalFields = document.getElementById('Interstitial');
        var newField = document.createElement('div');
        newField.className = 'row mb-3';
        newField.innerHTML = `
            <label for="inputText" class="col-sm-2 col-form-label"></label>
            <div class="col-sm-8">
                <input type="text" class="form-control" name="Interstitial[]" required>
            </div>
            <div class="col-sm-2">
                <button type="button" class="btn btn-danger" onclick="removeTextField(this)">Remove</button>
            </div>
        `;
        additionalFields.appendChild(newField);
    } */

    function addTextField(value) {
        // Ensure 'Interstitial' is quoted, assuming it's the ID of the element
        var additionalFields = document.getElementById(value);

        // Create a new div for the additional field
        var newField = document.createElement('div');
        newField.className = 'row mb-3';

        // Set the inner HTML of the new div, including the dynamic value
        newField.innerHTML = `
            <label for="inputText" class="col-sm-2 col-form-label"></label>
            <div class="col-sm-8">
                <input type="text" class="form-control" name="${value}[]" required>
            </div>
            <div class="col-sm-2">
                <button type="button" class="btn btn-danger" onclick="removeTextField(this)">Remove</button>
            </div>
        `;

        // Append the new field to the existing element with ID 'Interstitial'
        additionalFields.appendChild(newField);
    }


    function removeTextField(button) {
        var parentDiv = button.parentNode.parentNode;
        parentDiv.parentNode.removeChild(parentDiv);
    }
</script>
