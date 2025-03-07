@extends('layouts.app')

@section('content')
    <div class="pagetitle">
        <h1>Chat Coin Setting</h1>
        <nav>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
            </ol>
        </nav>
    </div>

    <section class="section">

        <form action="{{ route('change_setting') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Chat Coin Setting</h5>

                            @foreach ($settings as $setting)

                                @if ($setting->setting_key == "default_coin")
                                    <div class="row mb-3">
                                        <label for="inputText" class="col-sm-4 col-form-label">Default</label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" name="default_coin"  value="{{ old('setting_value', $setting->setting_value) }}" placeholder="How many default coin user get?" required>
                                        </div>
                                    </div>
                                @endif

                                @if ($setting->setting_key == "per_msg_coin")
                                <div class="row mb-3">
                                    <label for="inputText" class="col-sm-4 col-form-label">Send Message</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="per_msg_coin" value="{{ old('name', $setting->setting_value) }}" placeholder="How many coin cut per message send?" required>
                                    </div>
                                </div>
                                @endif
                                @if ($setting->setting_key == "per_voice_msg_coin")
                                <div class="row mb-3">
                                    <label for="inputText" class="col-sm-4 col-form-label">Send Voice Message</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="per_voice_msg_coin" value="{{ old('name', $setting->setting_value) }}" placeholder="How many coin cut per voice message send?" required>
                                    </div>
                                </div>
                                @endif
                                @if ($setting->setting_key == "per_img_coin")
                                <div class="row mb-3">
                                    <label for="inputText" class="col-sm-4 col-form-label">Send Image  </label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="per_img_coin" value="{{ old('name', $setting->setting_value) }}" placeholder="How many coin cut per image send?" required>
                                    </div>
                                </div>
                                @endif



                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Watch Video Setting</h5>
                            @foreach ($settings as $setting)

                                @if ($setting->setting_key == "per_day_watch_video_time")
                                <div class="row mb-3">
                                    <label for="inputText" class="col-sm-4 col-form-label">Video Watch Count </label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="per_day_watch_video_time" value="{{ old('name', $setting->setting_value) }}" placeholder="How many time watch video?" required >
                                    </div>
                                </div>
                                @endif

                                @if ($setting->setting_key == "per_watch_get")
                                <div class="row mb-3">
                                    <label for="inputText" class="col-sm-4 col-form-label">Per Day Watch Get Coin</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="per_watch_get" value="{{ old('name', $setting->setting_value) }}" placeholder="How many coin getper watch video?" required >
                                    </div>
                                </div>
                                @endif

                                @if ($setting->setting_key == "per_day_watch_hint_count")
                                <div class="row mb-3">
                                    <label for="inputText" class="col-sm-4 col-form-label">Per Day Watch Hint</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="per_day_watch_hint_count" value="{{ old('name', $setting->setting_value) }}" placeholder="How many time watch Hint get?" required >
                                    </div>
                                </div>
                                @endif

                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Quick Translator Setting</h5>
                            @foreach ($settings as $setting)



                                @if ($setting->setting_key == "translate_char")
                                <div class="row mb-3">
                                    <label for="inputText" class="col-sm-4 col-form-label">Translater Character</label>
                                    <div class="col-sm-8">
                                        <input type="text" class="form-control" name="translate_char" value="{{ old('name', $setting->setting_value) }}" placeholder="How many default character?" required >
                                    </div>
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
