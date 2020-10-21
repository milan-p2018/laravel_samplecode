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
        <!-- <link href="{{ asset('assets/css/bootstrap-datepicker.css') }}" rel="stylesheet"> -->
    </head>
    <body>
        
        <div class="page-wrapper">
        @foreach (['danger', 'warning', 'success', 'info'] as $msg)
            @if(Session::has('alert-' . $msg))
                <div class="toast_msg {{ $msg }}">{!! Session::get('alert-' . $msg) !!}
                    <a href="#" class="close_toast"><img src="{{ asset('assets/images/close.svg') }}" alt=""></a>
                </div>
            @endif
        @endforeach
            <!-- header start -->
            <header class="header">		
                <div class="left-menu-icon">
                    <div class ="hamburger-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
                <div class="search-block">
                    <form class="search-form">
                        <div class="form-group">
                            
                            <button class="btn search-btn">
                                <i>
                                    <img src="{{ asset('assets/images/search-icon-white.svg') }}" alt="" class="default-icon">
                                    <img src="{{ asset('assets/images/search-icon-green.svg') }}" alt="" class="active-icon">
                                </i>
                            </button>
                            <input type="text" class="form-control" placeholder="{{ __('lang.search') }}">							
                        </div>
                    </form>
                </div>
                <div class="profile-block">
                    <ul class="icon-list">
                        <li class="search-link">
                            <a href="#" title="" class="search-btn"><i><img src="{{ asset('assets/images/search-icon-white.svg') }}" alt=""></i></a>
                        </li>
                        <li class=" dropdown-wrap help-menu-link">
                            <a href="#" title="" class="dropdown-link"><i><img src="{{ asset('assets/images/help-icon.svg') }}" alt=""></i></a>
                            <div class="dropdown-menu help-menu">
                                <h5>{{ __('lang.help') }}</h5>
                                <ul>								
                                    <li>
                                        <a href="#" title="{{ __('lang.chat with us') }}">{{ __('lang.chat with us') }}</a>
                                    </li>
                                    <li>
                                        <a href="#" title="{{ __('lang.whats new') }}">{{ __('lang.whats new') }}</a>
                                    </li>
                                    <li>
                                        <a href="#" title="Tutorial">Tutorial</a>
                                    </li>
                                    <li>
                                        <a href="#" title="{{ __('lang.user guide') }}" class="share-link">{{ __('lang.user guide') }} <em><img src="{{ asset('assets/images/share-icon.svg') }}" alt=""></em></a>
                                    </li>
                                    <li>
                                        <a href="#" title="{{ __('lang.contact us') }}">{{ __('lang.contact us') }}</a>
                                    </li>
                                    <li>
                                        <a href="#" title="{{ __('lang.write review about us text') }}" class="share-link">{{ __('lang.write review about us text') }} <em><img src="{{ asset('assets/images/share-icon.svg') }}" alt=""></em></a>
                                    </li>
                                </ul>
                                <ul>
                                    <li>
                                        <a href="#" title="Android App" class="share-link"><span><i><img src="{{ asset('assets/images/android-icon.svg') }}" alt=""></i>Android App
                                        </span><em><img src="{{ asset('assets/images/share-icon.svg') }}" alt=""></em></a>
                                    </li>
                                    <li>
                                        <a href="#" title="iOS App" class="share-link"><span><i><img src="{{ asset('assets/images/apple-icon.svg') }}" alt=""></i>iOS App
                                        </span><em><img src="{{ asset('assets/images/share-icon.svg') }}" alt=""></em></a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        <li class="dropdown-wrap">
                            <a href="#" title="" class="dropdown-link notification-link">
                                <i><img src="{{ asset('assets/images/notification-icon.svg') }}" alt=""><span>10</span></i>								
                            </a>
                            <div class="dropdown-menu notification-menu">
                                <h5>{{ __('lang.release phases')}}</h5>
                                <ul class="notification-list">								
                                    <li>
                                        <div class="notification-wrap">
                                            <em><img src="{{ asset('assets/images/patient1.png') }}" alt=""></em>
                                            <div class="notification-inner">
                                                <label>Phase 1</label>
                                                <span>Lorem ipsum dolor sit amet</span>
                                            </div>
                                            <div class="btn-wrap">
                                                <a href="#" title=""><i><img src="{{ asset('assets/images/cross-icon-red.svg') }}" alt=""></i></a>
                                                <a href="#" title=""><i><img src="{{ asset('assets/images/right-icon.svg') }}" alt=""></i></a>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="notification-wrap">
                                            <em><img src="{{ asset('assets/images/patient2.png') }}" alt=""></em>
                                            <div class="notification-inner">
                                                <label>Phase 2</label>
                                                <span>Lorem ipsum dolor sit amet</span>
                                            </div>
                                            <div class="btn-wrap">												
                                                <a href="#" title=""><i><img src="{{ asset('assets/images/right-icon.svg') }}" alt=""></i></a>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="notification-wrap">
                                            <em><img src="{{ asset('assets/images/patient3.png') }}" alt=""></em>
                                            <div class="notification-inner">
                                                <label>Phase 3</label>
                                                <span>Lorem ipsum dolor sit amet</span>
                                            </div>
                                            <div class="btn-wrap">												
                                                <a href="#" title=""><i><img src="{{ asset('assets/images/right-icon.svg') }}" alt=""></i></a>
                                            </div>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="notification-wrap">
                                            <em><img src="{{ asset('assets/images/patient3.png') }}" alt=""></em>
                                            <div class="notification-inner">
                                                <label>Phase 4</label>
                                                <span>Lorem ipsum dolor sit amet</span>
                                            </div>
                                            <div class="btn-wrap">												
                                                <a href="#" title=""><i><img src="{{ asset('assets/images/right-icon.svg') }}" alt=""></i></a>
                                            </div>
                                        </div>
                                    </li>

                                </ul>
                                <h5>{{ __('lang.new messages') }}</h5>
                                <ul class="notification-list new-msg-list">								
                                    <li>
                                        <a href="#" class="notification-wrap">
                                            <em><img src="{{ asset('assets/images/patient1.png') }}" alt=""></em>
                                            <div class="notification-inner">
                                                <label>Phase 1</label>
                                                <span>Lorem ipsum dolor sit amet</span>
                                            </div>
                                            <span class="date-info">12 Nov.</span>
                                        </a>
                                    </li>
                                    <li class="active">
                                        <a href="#" class="notification-wrap">
                                            <em><img src="{{ asset('assets/images/patient2.png') }}" alt=""></em>
                                            <div class="notification-inner">
                                                <label>Phase 2</label>
                                                <span>Lorem ipsum dolor sit amet</span>
                                            </div>
                                            <span class="date-info">12 Nov.</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" class="notification-wrap">
                                            <em><img src="{{ asset('assets/images/patient3.png') }}" alt=""></em>
                                            <div class="notification-inner">
                                                <label>Phase 3</label>
                                                <span>Lorem ipsum dolor sit amet</span>
                                            </div>
                                            <span class="date-info">12 Nov.</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" class="notification-wrap">
                                            <em><img src="{{ asset('assets/images/patient4.png') }}" alt=""></em>
                                            <div class="notification-inner">
                                                <label>Phase 4</label>
                                                <span>Lorem ipsum dolor sit amet</span>
                                            </div>
                                            <span class="date-info" >12 Nov.</span>
                                        </a>
                                    </li>																	
                                </ul>
                            </div>
                        </li>						
                        <li class="patient-profile dropdown-wrap">
                            <a href="#" title="" class="dropdown-link desktop-icon">
                                <span>{{ucFirst(Auth::user()->workerData()->get()[0]->firstname) }}  {{ ucFirst(Auth::user()->workerData()->get()[0]->lastname) }}</span>
                                <em><img src="{{ asset('assets/images/down-arrow-white.svg') }}"></em>
                                <i><img src="{{ asset('assets/images/profile-image.png') }}"></i>
                            </a>							
                            <a href="#" title="" class="dropdown-link more-icon">
                                <span></span>
                                <span></span>
                                <span></span>
                            </a>
                            <div class="dropdown-menu">
                                <h5><i><img src="{{ asset('assets/images/profile-image.png') }}" alt=""></i>{{ucFirst(Auth::user()->workerData()->get()[0]->firstname) }}  {{ ucFirst(Auth::user()->workerData()->get()[0]->lastname) }}</h5>
                                <ul class="icon-listing">								
                                    <li>
                                        <a href="{{ route('profil.update-profile', Crypt::encrypt(Auth::user()->workerData()->get()[0]->id))}}" title="{{ __('lang.edit profile') }}"><i><img src="{{ asset('assets/images/edit-icon.svg') }}" alt=""></i>{{ __('lang.edit profile') }}</a>
                                    </li>
                                    <li>
                                        <a href="#" title="{{ __('lang.switch account') }}"><i><img src="{{ asset('assets/images/exchange-icon.svg') }}" alt=""></i>{{ __('lang.switch account') }}</a>
                                    </li>
                                    <li>
                                        <a href="#" title="{{ __('lang.settings') }}"><i><img src="{{ asset('assets/images/settings-icon.svg') }}" alt=""></i>{{ __('lang.settings') }}</a>
                                    </li>
                                    <li>
                                        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" title="{{ __('lang.sign out') }}"><i><img src="{{ asset('assets/images/logout-icon.svg') }}" alt=""></i>{{ __('lang.sign out') }}</a>
                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                            @csrf
                                        </form>
                                    </li>
                                </ul>
                            </div>						
                        </li>
                    </ul>				
                </div>			
            </header>
            <!-- header end -->
            <!-- left menu block -->
            <div class="left-menu-block">
                <div class="top-menu-listing">
                    <ul class="top-menu-items">
                        <li class="clickable-menu-wrapper menu-icon">
                        <a href="#" class=" hamburger-block clickable-menu-link"><i class="icon-wrap"><img src="{{ asset('assets/images/white-plus-icon.svg') }}" alt="plus-icon"></i>
                            </a>
                            <div class="clickable-menu-block lg">
                                <div class="left-block">
                                    <h5>{{ __('lang.new') }}</h5>
                                    <ul class="icon-listing">
                                        <li>
                                            <a href="#" title="Patient"><i><img src="{{ asset('assets/images/multiple-user-icon.svg') }}" alt=""></i>Patient</a>
                                        </li>
                                        <li>
                                            <a href="#" title="{{ __('lang.document') }}"><i><img src="{{ asset('assets/images/document-gray.svg') }}" alt=""></i>{{ __('lang.document') }}</a>
                                        </li>
                                        <li>
                                            <a href="#" title="{{ __('lang.message') }}"><i><img src="{{ asset('assets/images/chat-gray.svg') }}" alt=""></i>{{ __('lang.message') }}</a>
                                        </li>
                                        <li>
                                            <a href="#" title="{{ __('lang.meeting') }}"><i><img src="{{ asset('assets/images/calendar-gray.svg') }}" alt=""></i>{{ __('lang.meeting') }}</a>
                                        </li>
                                    </ul>

                                </div>							
                            </div>
                        </li>
                        <li>
                            <a href="#" class="tooltip" tooltip-txt="Dashboard">
                                <img src="{{ asset('assets/images/dashboard-icon.svg') }}" class="default-icon" alt="">
                                <img src="{{ asset('assets/images/Dashboard-green.svg') }}" class="active-icon" alt="">
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="{{ (request()->is('patient*')) ? 'active' : '' }}">
                            <a href="{{ route('patients.index') }}" class="tooltip" tooltip-txt="{{ __('lang.patients') }}">
                                <img src="{{ asset('assets/images/patient-white.svg') }}" class="default-icon" alt="">
                                <img src="{{ asset('assets/images/patient-icon.svg') }}" class="active-icon" alt="">
                                <span>{{ __('lang.patients') }}</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="tooltip" tooltip-txt="{{ __('lang.documents') }}">
                                <img src="{{ asset('assets/images/document-icon.svg') }}" class="default-icon" alt="">
                                <img src="{{ asset('assets/images/document-green.svg') }}" class="active-icon" alt="">
                                <span>{{ __('lang.documents') }}</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="tooltip" tooltip-txt="{{ __('lang.news') }}">
                                <img src="{{ asset('assets/images/chat-icon.svg') }}" class="default-icon" alt="">
                                <img src="{{ asset('assets/images/chat-green.svg') }}" class="active-icon" alt="">
                                <span>{{ __('lang.news') }}</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="tooltip" tooltip-txt="{{ __('lang.events') }}">
                                <img src="{{ asset('assets/images/calender-icon.svg') }}" class="default-icon" alt="">
                                <img src="{{ asset('assets/images/calender-green.svg') }}" class="active-icon" alt="">
                                <span>{{ __('lang.events') }}</span>
                            </a>
                        </li>
                        <li class="{{ (request()->segment(1) == 'organization' || request()->segment(1) == 'doctors' || request()->segment(1) == 'employee') ? 'active' : '' }}">
                            <a href="{{ route('organization.index') }}" class="tooltip" tooltip-txt="{{ __('lang.organization') }}">
                                <img src="{{ asset('assets/images/praxis-icon.svg') }}" class="default-icon" alt="">
                                <img src="{{ asset('assets/images/Praxis-green.svg') }}" class="active-icon" alt="">
                                <span>{{ __('lang.organization') }}</span>
                            </a>
                        </li>
                    </ul>
                </div>			
            </div>
            <!-- left menu block end -->
            @yield('content')
        </div>
        <div class="overlay"></div>
        <script>    
            var locale = "{{Config::get('app.locale')}}";
        </script>
        <script>
            // var remove_localstorage = '{{ \Session::has('remove_localstorage') ? \Session::get('remove_localstorage') : false }}';
            // if(remove_localstorage) {
            //     localStorage.removeItem('keys');
            //     {{ \Session::forget('remove_localstorage') }}
            // }
        </script>
        <script type="text/javascript" src="{{ asset('assets/js/vendor.js') }}"></script>
        <script type="text/javascript" src="{{ asset('assets/js/main.js') }}"></script>
        <!-- Jquery Validate -->
        <script src="{{ asset('assets/js/jquery.validate.min.js') }}"></script>
        <script src="{{ asset('assets/js/moment.min.js') }}"></script>
        <!-- <script src="{{ asset('assets/js/bootstrap-datepicker.js') }}"></script> -->
        <script src="{{ asset('assets/js/custom.js') }}"></script>
        @stack('custom-scripts')
    </body>
</html>
