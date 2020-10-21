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
        @if($errors->has('email') || $errors->has('password'))
            <div class="toast_msg danger">{{ $errors->has('email') ? $errors->first('email') : $errors->first('password') }}
                <a href="#" class="close_toast"><img src="{{ asset('assets/images/close.svg') }}" alt=""></a>
            </div>
        @endif
        @if (session('status'))
            <div class="toast_msg {{ Session::has('class') ? Session::get('class') : 'success' }}">{{ session('status') }}
                <a href="#" class="close_toast"><img src="{{ asset('assets/images/close.svg') }}" alt=""></a>
            </div>
            {{session()->forget('status')}}
        @endif
        @if (Session::has('custom-status'))
            <div class="toast_msg {{ Session::has('class') ? Session::get('class') : 'success' }}">{{ session('custom-status') }}
                <a href="#" class="close_toast"><img src="{{ asset('assets/images/close.svg') }}" alt=""></a>
            </div>
            {{Session::forget('custom-status')}}
        @endif
        
        <div class="login-signup-wrapper">
            <div class="login-signup-outer {{ Request::is('register') ? 'signup-page' : '' }}">
                <div class="app-logo center"><span>ti</span>phy</div>
                <p>Die Therapie Software</p>

                    @yield('content')

            </div>
        </div>
        <script>    
            var locale = "{{Config::get('app.locale')}}";
        </script>
        <script type="text/javascript" src="{{ asset('assets/js/vendor.js') }}"></script>
        <script type="text/javascript" src="{{ asset('assets/js/main.js') }}"></script>
        <!-- Jquery Validate -->
        <script src="{{ asset('assets/js/jquery.validate.min.js') }}"></script>
        <script src="{{ asset('assets/js/custom.js') }}"></script>
        @stack('custom-scripts')
    </body>
</html>
