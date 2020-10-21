<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0 ,user-scalable=no">
        <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}" />
        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @yield('title')
        <link href="{{ asset('assets/css/main.css') }}" rel="stylesheet">
        <link href="{{ asset('assets/css/custom.css') }}" rel="stylesheet">
    </head>
    <body>
        
        @if (session('resent'))
            <div class="toast_msg">{{ __('lang.A fresh verification link has been sent to your email address') }}
                <a href="#" class="close_toast"><img src="{{ asset('assets/images/close.svg') }}" alt=""></a>
            </div>
        @endif
        @foreach (['danger', 'warning', 'success', 'info'] as $msg)
            @if(Session::has('alert-' . $msg))
                <div class="toast_msg {{ $msg }}">{{ Session::get('alert-' . $msg) }}
                    <a href="#" class="close_toast"><img src="{{ asset('assets/images/close.svg') }}" alt=""></a>
                </div>
            @endif
        @endforeach
        <div class="registration_steps_wrapper">
            <div class="registration_steps_outer">

                <div class="registration_steps_sidebar">
                    <div class="app-logo"><span>ti</span>phy</div>
                    <p>Die Therapie Software</p>
                    <ul class="registration_progress">
                        <li class="active">{{ __('lang.personal account created') }}</li>
                        <li class="{{ !empty(Auth::user()->email_verified_at) ? 'active' : ''}}">{{ __('lang.email confirmed') }}</li>
                        @if(!isset($ConfirmationStepShow) || $ConfirmationStepShow)
                        <li class = "{{ App\User::find(Auth::id())->workerData()->exists() ? 'active' : '' }}">{{ __('lang.complete personal account') }}</li>
                        @endif
                    </ul>
                </div>

                <div class="registration_steps_content">
                    @yield('content')
                </div>
            </div>
        </div>

        <script>    
            var locale = "{{Config::get('app.locale')}}";
        </script>
        <script type="text/javascript" src="{{ asset('assets/js/vendor.js') }}"></script>
        <script type="text/javascript" src="{{ asset('assets/js/main.js') }}"></script>
        <!-- Jquery Validate -->
        <script src="{{ asset('assets/js/jquery.validate.min.js') }}"></script>
        <script src="{{ asset('assets/js/moment.min.js') }}"></script>
        <script src="{{ asset('assets/js/custom.js') }}"></script>
        @stack('custom-scripts')
    </body>
</html>
