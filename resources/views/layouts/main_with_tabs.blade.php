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
    @php
    $not_verified_org = false;
    if(!empty($organization_list_share) && !$organization_list_share['verified_at']) {
    $not_verified_org = true;
    }
    @endphp
    <div class="wrapper {{ $not_verified_org ? 'has-top-msg' : ''}}">
        @foreach (['danger', 'warning', 'success', 'info'] as $msg)
        @if(Session::has('alert-' . $msg))
        @if(!\Session::has('class'))
        <div class="toast_msg {{ Session::get('class')}} {{ $msg }}">{!! Session::get('alert-' . $msg) !!}
            <a href="#" class="close_toast"><img src="{{ asset('assets/images/close.svg') }}" alt=""></a>
        </div>
        @endif
        @endif
        @endforeach
        <div class="custom-toast-msg" style="display:none"><span></span>
            <a href="#" class="close_toast"><img src="{{ asset('assets/images/close.svg') }}" alt=""></a>
        </div>
        @if($not_verified_org)
        <div class="header-top-msg">
            <a href="{{ url('organization/new') }}">{{ __('lang.verified_organization_block') }}</a>
        </div>
        @endif
        <!-- header start -->
        <header class="header">
            <div class="left-menu-icon">
                <div class="hamburger-icon">
                    <span></span>
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
            <!-- <div class="search-block">
                <form class="search-form">
                    <div class="form-group">

                        <button class="btn search-btn">
                            <i>
                                <img src="{{ asset('assets/images/search-icon-white.svg') }}" alt="" class="default-icon">
                                <img src="{{ asset('assets/images/search-icon-green.svg') }}" alt="" class="active-icon">
                            </i></button>
                        <input type="text" class="form-control" placeholder="{{ __('lang.search') }}">
                    </div>
                </form>
            </div> -->
            <div class="profile-block">
                <ul class="icon-list">
                    <li class="search-link">
                        <a href="#" title="" class="search-btn"><i><img src="{{ asset('assets/images/search-icon-white.svg') }}" alt=""></i></a>
                    </li>
                    <!-- <li class=" dropdown-wrap help-menu-link">
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
                    </li> -->
                    <!-- <li class="dropdown-wrap">
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
                                        <span class="date-info">12 Nov.</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li> -->
                    <li class="patient-profile dropdown-wrap">
                        <a href="#" title="" class="dropdown-link desktop-icon">
                            <span>{{ucFirst(Auth::user()->workerData()->get()[0]->firstname) }} {{ ucFirst(Auth::user()->workerData()->get()[0]->lastname) }}</span>
                            <em><img src="{{ asset('assets/images/down-arrow-white.svg') }}"></em>
                            <i><img src="{{ !empty(Auth::user()->profile_pic) && file_exists(public_path() .'/storage/' .Auth::user()->profile_pic) ? URL::asset('storage'.Auth::user()->profile_pic) : asset('assets/images/no_photo_available.png') }}"></i>
                        </a>
                        <a href="#" title="" class="dropdown-link more-icon">
                            <span></span>
                            <span></span>
                            <span></span>
                        </a>
                        <div class="dropdown-menu">
                            <h5><i><img src="{{ !empty(Auth::user()->profile_pic) && file_exists(public_path() .'/storage/' .Auth::user()->profile_pic) ? URL::asset('storage'.Auth::user()->profile_pic) : asset('assets/images/no_photo_available.png') }}" alt=""></i>{{ucFirst(Auth::user()->workerData()->get()[0]->firstname) }} {{ ucFirst(Auth::user()->workerData()->get()[0]->lastname) }}</h5>
                            <ul class="icon-listing">
                                <li>
                                    <a href="{{ route('profil.update-profile')}}" title="{{ __('lang.edit profile') }}"><i><img src="{{ asset('assets/images/edit-icon.svg') }}" alt=""></i>{{ __('lang.edit profile') }}</a>
                                </li>
                                <li class="has-radio">
                                    <a href="#" title="{{ __('lang.switch account') }}"><i><img src="{{ asset('assets/images/exchange-icon.svg') }}" alt=""></i>{{ __('lang.switch account') }}</a>
                                    <input name="_token" type="hidden" value="{{ csrf_token() }}" />
                                    <input type="hidden" name="_method" value="PUT" />
                                    <div class="custom-radio">
                                        @foreach ($organization_data as $organization)
                                        <span class="radio"><input type="radio" name="organization_id" value="{{ $organization['id'] }}" {{ \Session::has('organization_id') && decrypt(\Session::get('organization_id')) == $organization['id'] ? 'checked' : '' }}><em>{{ $organization['name'] }}</em><span class="checkmark"></span></span>
                                        @endforeach
                                        <a href="{{ route('organization.new') }}" title="" class="btn btn-green">Neue Organization</a>
                                    </div>
                                </li>
                                <li>
                                    <a href="{{ route('setting.create') }}" title="{{ __('lang.settings') }}"><i><img src="{{ asset('assets/images/settings-icon.svg') }}" alt=""></i>{{ __('lang.settings') }}</a>
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
                                    @if(!empty($organization_list_share->verified_at) && $organization_list_share->owner == Auth::user()->id && \Helper::checkUserHasPermission(2, 1, 'can_create'))
                                    <li>
                                        <a href="javascript:void(0);" title="Patient" class="create_patient"><i><img src="{{ asset('assets/images/multiple-user-icon.svg') }}" alt=""></i>Patient</a>
                                    </li>
                                    @endif
                                    @if(\Helper::checkUserHasPermission(3, 1, 'can_create'))
                                    <li>
                                        <a href="javascript:void(0);" class="upload-patient-document" title="{{ __('lang.document') }}"><i><img src="{{ asset('assets/images/document-gray.svg') }}" alt=""></i>{{ __('lang.document') }}</a>
                                    </li>
                                    @endif
                                    <li>
                                        <a href="#" title="{{ __('lang.message') }}"><i><img src="{{ asset('assets/images/chat-gray.svg') }}" alt=""></i>{{ __('lang.message') }}</a>
                                    </li>
                                    @if(\Helper::checkUserHasPermission(5, 1, 'can_create'))
                                    <li>
                                        <a href="javascript:void(0);" class="create-patient-meeting" title="{{ __('lang.meeting') }}"><i><img src="{{ asset('assets/images/document-gray.svg') }}" alt=""></i>{{ __('lang.meeting') }}</a>
                                    </li>
                                    @endif
                                    <!-- <li>
                                        <form action="{{ url('events') }}" method="POST">
                                            @csrf
                                            <button type="submit" title="{{ __('lang.meeting') }}" class="meeting-event" name="event-get" value="1"><i><img src="{{ asset('assets/images/calendar-gray.svg') }}" alt=""></i>{{ __('lang.meeting') }}</button>
                                        </form>
                                    </li> -->
                                </ul>

                            </div>
                        </div>
                    </li>
                    <!-- <li>
                        <a href="#" class="tooltip" tooltip-txt="Dashboard">
                            <img src="{{ asset('assets/images/dashboard-icon.svg') }}" class="default-icon" alt="">
                            <img src="{{ asset('assets/images/Dashboard-green.svg') }}" class="active-icon" alt="">
                            <span>Dashboard</span>
                        </a>
                    </li> -->
                    <li class="{{ (request()->is('patient*')) ? 'active' : '' }}">
                        <a href="{{ route('patients.index') }}" class="tooltip" tooltip-txt="{{ __('lang.patients') }}">
                            <img src="{{ asset('assets/images/patient-white.svg') }}" class="default-icon" alt="">
                            <img src="{{ asset('assets/images/patient-icon.svg') }}" class="active-icon" alt="">
                            <span>{{ __('lang.patients') }}</span>
                        </a>
                    </li>
                    <li class="{{ (request()->is('documents*')) ? 'active' : '' }}">
                        <a href="{{ route('documents') }}" class="tooltip" tooltip-txt="{{ __('lang.documents') }}">
                            <img src="{{ asset('assets/images/document-icon.svg') }}" class="default-icon" alt="">
                            <img src="{{ asset('assets/images/document-green.svg') }}" class="active-icon" alt="">
                            <span>{{ __('lang.documents') }}</span>
                        </a>
                    </li>
                    <!-- <li>
                        <a href="#" class="tooltip" tooltip-txt="{{ __('lang.news') }}">
                            <img src="{{ asset('assets/images/chat-icon.svg') }}" class="default-icon" alt="">
                            <img src="{{ asset('assets/images/chat-green.svg') }}" class="active-icon" alt="">
                            <span>{{ __('lang.news') }}</span>
                        </a>
                    </li> -->
                    <li class="{{ (request()->is('events*')) ? 'active' : '' }}">
                        <a href="{{ route('organization.meetings') }}" class="tooltip" tooltip-txt="{{ __('lang.events') }}">
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
                    <li class="{{ (request()->segment(1) == 'plan-administration') ? 'active' : '' }}">
                        <a href="{{ route('administration.therapy-plan-templates') }}" class="tooltip" tooltip-txt="{{ __('lang.plan-administration') }}">
                            <img src="{{ asset('assets/images/strategic-plan-white.svg') }}" class="default-icon" alt="">
                            <img src="{{ asset('assets/images/strategic-plan-green.svg') }}" class="active-icon" alt="">
                            <span>{{ __('lang.plan-administration') }}</span>
                        </a>
                    </li>
                    @if( \Session::has('organization_id') && \Helper::checkUserHasPermission(8, 1, 'can_view'))
                    <li class="{{ (request()->segment(1) == 'access-permissions') ? 'active' : '' }}">
                        <a href="{{ route('access-permissions.roles') }}" class="tooltip" tooltip-txt="{{ __('lang.access-permissions') }}">
                            <img src="{{ asset('assets/images/admin-with-gears-white.svg') }}" class="default-icon" alt="">
                            <img src="{{ asset('assets/images/admin-with-gears-green.svg') }}" class="active-icon" alt="">
                            <span>{{ __('lang.access-permissions') }}</span>
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
        </div>
        <!-- left menu block end -->
        <main>
            @yield('content')
        </main>
    </div>
    <div class="overlay"></div>
    <div class="modal add-course fade custom-modal large-modal upload-document" id="upload-document-modal" aria-hidden="true">
        <div class="main-loader outer-loader" style="display:none">
            <i class="edit-loader submit-button-loader custom-loader" ><img src="{{ asset('assets/images/edit-loader.gif') }}" alt=""></i>
        </div>
        <div class="modal-dialog" role="document">
            <div class="modal-content custom-content">
            </div>
        </div>
    </div>
    <!-- Patient anlegen / Create patient -->
    <div class="modal phase-list fade custom-modal" id="create-patient-modal" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>{{ __('lang.create_patient') }}</h2>
                    <button type="button" class="close" aria-label="Close" title="Close">
                        <span aria-hidden="true"><i><img src="{{ asset('assets/images/cross-icon.svg') }} "></i></span>
                    </button>
                </div>
                <form method="POST" id="createPatientForm" class="form-component create-patient-form" novalidate>
                    @csrf
                    <div class="modal-wrap">
                        <div class="modal-container">
                            <div class="row">
                                <div class="col-sm-5">
                                    <div class="form-group label-custom-dropdown @error('salutation') has-error @enderror">
                                        <label class="dropdown-label" for="select"> {{ __('lang.salutation') }}</label>
                                        <select class="custom_dropdown" name="salutation" value="{{ old('salutation') }}">
                                            <option value="3" {{old('salutation') == 3 ? 'selected="selected"' : ''}}>{{ __('lang.not specified') }}</option>
                                            <option value="1" {{old('salutation') == 1 ? 'selected="selected"' : ''}}>{{ __('lang.mr') }}</option>
                                            <option value="2" {{old('salutation') == 2 ? 'selected="selected"' : ''}}>{{ __('lang.mrs') }}</option>
                                        </select>
                                        <span class="error-msg">@error('salutation') {{$message}} @enderror</span>
                                    </div>
                                </div>
                                <div class="col-sm-7">
                                    <div class="form-group label-custom-dropdown @error('title') has-error @enderror">
                                        <label class="dropdown-label" for="select"> {{ __('lang.title') }}</label>
                                        <select class="custom_dropdown" name="title" value="{{ old('title') }}">
                                            <option value="no information" {{old('title') == 'no information' ? 'selected="selected"' : ''}}>{{ __('lang.no information') }}</option>
                                            <option value="dr." {{old('title') == 'dr.' ? 'selected="selected"' : ''}}>Dr.</option>
                                            <option value="dr. med." {{old('title') == 'dr. med.' ? 'selected="selected"' : ''}}>Dr. Med.</option>
                                            <option value="prof." {{old('title') == 'prof.' ? 'selected="selected"' : ''}}>Prof.</option>
                                            <option value="dr. prof." {{old('title') == 'dr. prof.' ? 'selected="selected"' : ''}}>Prof. Dr.</option>
                                            <option value="dr. prof. med." {{old('title') == 'dr. prof. med.' ? 'selected="selected"' : ''}}>Prof. Dr. Med.</option>
                                        </select>
                                        <span class="error-msg">@error('title') {{$message}} @enderror</span>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group @error('firstname') has-error @enderror">
                                        <input id="firstname" type="text" class="form-control" name="firstname" value="{{ old('firstname') }}" placeholder="">
                                        <label>{{ __('lang.firstname') }} <sup>*</sup></label>
                                        <span class="error-msg">@error('firstname') {{$message}} @enderror</span>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group @error('lastname') has-error @enderror">
                                        <input id="lastname" type="text" class="form-control" name="lastname" value="{{ old('lastname') }}" placeholder="">
                                        <label>{{ __('lang.lastname') }} <sup>*</sup></label>
                                        <span class="error-msg">@error('lastname') {{$message}} @enderror</span>
                                    </div>
                                </div>
                                <div class="col-md-12 patient-name-list">
                                    <div class="form-group label-custom-dropdown">
                                        <label class="dropdown-label" for="select"> {{ __('lang.treating_doc') }}</label>
                                        <select class="dropdown_select2" name="patient_doctor" id="patient_doctor">
                                            <option value= "">{{ __('lang.please-select') }}</option>
                                        </select>
                                        <span class="error-msg"></span>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group @error('birthdate') has-error @enderror">
                                        <span class="input-group">
                                            <input id="birthdate" type="text" class="form-control select-date" name="birthdate" value="{{ old('birthdate') }}" placeholder="">
                                            <label>{{ __('lang.Date of birth') }} <sup>*</sup></label>
                                            <span class="error-msg">@error('birthdate') {{$message}} @enderror</span>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group label-custom-dropdown @error('gender') has-error @enderror">
                                        <label class="dropdown-label" for="select"> {{ __('lang.gender') }}</label>
                                        <select class="custom_dropdown" name="gender" value="{{ old('gender') }}">
                                            <option value="M" {{old('gender') == 'M' ? 'selected="selected"' : ''}}>{{ __('lang.male') }}</option>
                                            <option value="F" {{old('gender') == 'F' ? 'selected="selected"' : ''}}>{{ __('lang.female') }}</option>
                                            <option value="MISC" {{old('gender') == 'MISC' ? 'selected="selected"' : ''}}>{{ __('lang.miscellaneous') }}</option>
                                        </select>
                                        <span class="error-msg">@error('gender') {{$message}} @enderror</span>
                                    </div>
                                </div>
                                <div class="col-sm-8">
                                    <div class="form-group @error('street') has-error @enderror">
                                        <input id="street" type="text" class="form-control" name="street" value="{{ old('street') }}" placeholder="">
                                        <label>{{ __('lang.street') }} <sup>*</sup></label>
                                        <span class="error-msg">@error('street') {{$message}} @enderror</span>
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <div class="form-group @error('streetnumber') has-error @enderror">
                                        <input id="streetnumber" type="text" class="form-control" name="streetnumber" value="{{ old('streetnumber') }}" placeholder="">
                                        <label>{{ __('lang.housenumber') }} <sup>*</sup></label>
                                        <span class="error-msg">@error('streetnumber') {{$message}} @enderror</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group @error('postcode') has-error @enderror">
                                        <input id="postcode" type="text" class="form-control" name="postcode" value="{{ old('postcode') }}" placeholder="">
                                        <label>{{ __('lang.postcode short') }} <sup>*</sup></label>
                                        <span class="error-msg">@error('postcode') {{$message}} @enderror</span>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div class="form-group @error('place') has-error @enderror">
                                        <input id="place" type="text" class="form-control" name="place" value="{{ old('place') }}" placeholder="">
                                        <label>{{ __('lang.place') }} <sup>*</sup></label>
                                        <span class="error-msg">@error('place') {{$message}} @enderror</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group @error('phone') has-error @enderror">
                                        <input id="phone" type="text" class="form-control" name="phone" value="{{ old('phone') }}" placeholder="">
                                        <label>{{ __('lang.telephone')}} <sup>*</sup></label>
                                        <span class="error-msg">@error('phone') {{$message}} @enderror</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group @error('fax') has-error @enderror">
                                        <input id="fax" type="text" class="form-control" name="fax" value="{{ old('fax') }}" placeholder="">
                                        <label>{{ __('lang.fax short') }}</label>
                                        <span class="error-msg">@error('fax') {{$message}} @enderror</span>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <input id="mobile" type="text" class="form-control" name="mobile">
                                        <label>{{ __('lang.mobile')}}</label>
                                        <span class="error-msg"></span>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <div class="form-group @error('email') has-error @enderror">
                                        <input id="email" type="text" class="form-control" name="email" value="{{ old('email') }}" placeholder="">
                                        <label>{{ __('lang.email') }} <sup>*</sup></label>
                                        <span class="error-msg">@error('email') {{$message}} @enderror</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group label-custom-dropdown @error('insurance_type') has-error @enderror">
                                        <label class="dropdown-label" for="select"> {{ __('lang.health_insurance_type') }} <sup>*</sup></label>
                                        <select class="custom_dropdown" id="insurance_type_field" name="insurance_type" value="{{ old('insurance_type') }}">
                                            <option value="2" {{old('insurance_type') == '2' ? 'selected="selected"' : ''}}>{{ __('lang.legally') }}</option>
                                            <option value="1" {{old('insurance_type') == '1' ? 'selected="selected"' : ''}}>{{ __('lang.private') }}</option>
                                        </select>
                                        <span class="error-msg">@error('insurance_type') {{$message}} @enderror</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group @error('health_insurance_no') has-error @enderror">
                                        <input id="health_insurance_no" type="text" class="form-control" name="health_insurance_no" value="{{ old('health_insurance_no') }}" placeholder="">
                                        <label>{{ __('lang.insurance_number') }} <sup>*</sup></label>
                                        <span class="error-msg">@error('health_insurance_no') {{$message}} @enderror</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group @error('health_insurance') has-error @enderror">
                                        <input id="health_insurance" type="text" class="form-control" name="health_insurance" value="{{ old('health_insurance') }}" placeholder="">
                                        <label>{{ __('lang.health_insurance') }} <sup>*</sup></label>
                                        <span class="error-msg">@error('health_insurance') {{$message}} @enderror</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group @error('insurance_number') has-error @enderror">
                                        <input id="insurance_number" type="text" class="form-control" name="insurance_number" value="{{ old('insurance_number') }}" placeholder="">
                                        <label>{{ __('lang.health_insurance_number') }} <sup>*</sup></label>
                                        <span class="error-msg">@error('insurance_number') {{$message}} @enderror</span>
                                    </div>
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-group">
                                        <textarea id="note" type="text" class="form-control" name="note"></textarea>
                                        <label>{{ __('lang.note') }}</label>
                                        <span class="error-msg"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <div class="btn-wrap">
                            <div class="switch-wrapper">
                                <div class="switch-outer">
                                    <div class="switch-inner">
                                        <input type="checkbox" class="onoffswitch another-form-open addNewPat" id="switch1">
                                        <label class="switch">
                                            <span class="slider"></span>
                                        </label>
                                    </div>
                                </div>
                                <label for="switch1">{{ __('lang.create-another') }}</label>
                            </div>
                            <button title="" class="btn btn-green" title="{{ __('lang.save') }}" id="create-patient">{{ __('lang.save') }}</button>
                            <a href="#" title="" class="btn btn-transparent btn-lg close" title="{{ __('lang.close') }}">{{ __('lang.close') }}</a>
                            <i class="edit-loader submit-button-loader" style="display:none"><img src="{{ asset('assets/images/edit-loader.gif') }}" alt=""></i>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal phase-list fade custom-modal" id="new-appointment" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content custom-content">
                <div class="main-loader outer-loader" style="display:none">
                    <i class="edit-loader submit-button-loader custom-loader" ><img src="{{ asset('assets/images/edit-loader.gif') }}" alt=""></i>
                </div>
            </div>
        </div>
    </div>
<div class="modal phase-list fade custom-modal small-modal confirmation-modal" id="closing-warning-modal" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="closing_modal_header">{{ __('lang.close-modal-header') }}</h2>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i><img src="{{ asset('assets/images/cross-icon.svg') }}"></i></span>
                </button>
            </div>
            <div class="modal-wrap">
                <div class="modal-container">
                    <p class="delete_modal_text">{{ __('lang.close-modal-text') }}</p>
                </div>
            </div>
            <div class="modal-footer">
                <div class="btn-wrap">
                    <a href="javascript:void(0);" title="" class="btn red-btn close-all-modal" id="remove-btn" title="{{ __('lang.close-modal-yes') }}">{{ __('lang.close-modal-yes') }}</a>
                    <a href="javascript:void(0);" title="" class="btn btn-transparent btn-lg close" title="{{ __('lang.close') }}" data-dismiss="modal" aria-label="Close">{{ __('lang.close') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
    <script>
        var locale = "{{Config::get('app.locale')}}";
    </script>
    <script>
        // var remove_localstorage = '{{ \Session::has('remove_localstorage ') ? \Session::get('remove_localstorage ') : false }}';
        // if (remove_localstorage) {
        //     localStorage.removeItem('keys'); {
        //         {
        //             var check = '{{ \Session::forget('remove_localstorage ') }}';
        //         }
        //     }
        // }
    </script>
    <script type="text/javascript" src="{{ asset('assets/js/vendor.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/js/main.js') }}"></script>
    <!-- Jquery Validate -->
    <script src="{{ asset('assets/js/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('assets/js/moment.min.js') }}"></script>
    <!-- <script src="{{ asset('assets/js/bootstrap-datepicker.js') }}"></script> -->
    <!-- moment-timezone-data-js -->
    <script src="{{ asset('assets/js/moment-timezone-with-data.js') }}"></script>
    <script src="{{ asset('assets/js/custom.js') }}"></script>
    <script>
        var organization_id = '{{ \Session::has('organization_id') ? decrypt(\Session::get('organization_id')) : '' }}';
        var redirect_url = "{{ url('scan-key') }}";
        var data = JSON.parse(localStorage.getItem('keys'));
        var changed = 0;
        $('#select-doc').on('click', function() {
            $('#patient-document-upload-dropzone').trigger('click');
        });
        $('#birthdate').datepicker({
            format: 'dd.mm.yyyy',
            language:locale,
            // startDate: moment().subtract(99, 'years').toDate(),
            endDate: moment(new Date()).toDate(),
            autoclose: true,
        });
        $('#birthdate').datepicker('setDate', moment(new Date()).subtract(50, 'years').format("DD.MM.YYYY"));
        //to check the validation on change
        $('#birthdate').on("change", function() {
            $('#createPatientForm').validate().element("#birthdate");  
        });
        $('#insurance_type_field').on('change', function() {
           $('#createPatientForm').validate().element("#insurance_number");  
           $('#createPatientForm').validate().element("#health_insurance_no");   
        })
        $(document).ready(function() {
            private_key = '';
            $.each(data, function(key, value) {
                if (organization_id == value.organization_id) {
                    private_key = value.private_key;
                    return 0;
                };
            });
            $('body').on('change', '.modal:not("#add-template-exercise-modal, #secondary-add-course-modal, #edit-record, #change-category, #phase-setting-assign-modal, #new-password-modal, .preview-modal, #exercise-preview-modal, #plan-course-preview-modal, #exercise-course-graph-modal")', function() {
                // console.log($(this));
                changed = 1;
            });
            $('body').on('click', '.modal .close:not(".exe-close-model")', function() {
                if(changed){
                    var modal_id = $(this).closest('.modal').attr('id');
                    $('#closing-warning-modal').attr('data-id', modal_id);
                    $('#closing-warning-modal').modal('show');
                } else {
                    $(this).closest('.modal').find('.error-msg').text('');
                    $(this).closest('.modal').find('.form-group').removeClass('has-error');
                    $(this).closest('.modal').modal('hide');
                    changed = 0;
                }
            });
            $('body').on('click', '.close-all-modal', function(e) {
            	$('.modal').each(function() {
            		if($(this).hasClass('show')) {
            			$(this).modal('hide');
            		}
            	})
                var id = $(this).closest('#closing-warning-modal').attr('data-id');
                changed = 0;
                $('#'+id).find('input, textarea').val('');
                $('#'+id).find('select.custom_dropdown').val('');
                $('#'+id).find('select.custom_dropdown').dropkick('reset');
                $('#'+id).find('input, textarea').focusout();
                $('#'+id).find('select.custom_dropdown').focusout();
                $('#'+id).find('.error-msg').text('');
                $('#'+id).find('.form-group').removeClass('has-error');
                if(id == 'assessment-modal') {
                    e.stopImmediatePropagation();
                }
            })
            getPatientMeetingsData(private_key);

            $(".upload-patient-document").click(function() {
                $('#upload-document-modal .custom-content').empty();
                $('#upload-document-modal').append("<div class='main-loader outer-loader' style='display:none'><i class='edit-loader submit-button-loader custom-loader'><img src='{{ asset('assets/images/edit-loader.gif') }}' alt=''></i></div>");
                getAllPatients();
                if(organization_id != '' && organization_id != undefined && organization_id != null) {
                    $('#upload-document-modal').modal('show');    
                }
                
            });
            //to open the create-patient modal
            $('.create_patient').click(function() {
                if (organization_id) {
                    $('#create-patient-modal').modal('show');
                } else {
                    $('.custom-toast-msg').show();
                    $('.custom-toast-msg').addClass('danger');
                    $('.custom-toast-msg').find('span').text("{{\Lang::get('lang.organization-not-selected-msg')}}");
                    setTimeout(function() {
                        $(".custom-toast-msg").slideUp(800);
                    }, 5000); // 5 secs
                }
            });

            // appointment modal
            $('#new-appointment .custom-content').empty();
            $('#new-appointment .custom-content').append("<div class='main-loader outer-loader' style='display:none'><i class='edit-loader submit-button-loader custom-loader'><img src='{{ asset('assets/images/edit-loader.gif') }}' alt=''></i></div>");
            $(".create-patient-meeting").click(function() {
                $('.selected-patient-name').hide();
                $('.patient-name-list').show();
                $('#new-appointment .modal-header h2').text("{{ __('lang.new_appointment') }}");
                $('form#meetingForm').trigger("reset");
                $("#patient_doctor_id, #schedule_category_code, #patient_name, #materials").val(null).trigger("change");
                $("input[name='meeting_id']").remove();
                $('.reset-button').hide();
                $('div .form-group').removeClass('has-error');
                if(organization_id != '' && organization_id != undefined && organization_id != null) {
                    $('#new-appointment').modal('show');    
                }
                
            });

            $('input[name=organization_id]').on('change', function() {
                $(":input[name=organization_id]").prop("disabled", true);
                var url = "{{ url('setting') }}/" + '{{ Auth::user()->id }}';
                var organization_id_val = $(this).val();
                var token = $('input[name="_token"]').attr('value');
                $.ajax({
                    type: "PUT",
                    url: url,
                    // async: false,
                    data: {
                        'organization_id': organization_id_val,
                    },
                    headers: {
                        'X-CSRF-Token': token
                    },
                    success: function(data) {
                        window.location.href = "{{ url('organization') }}";
                    },
                    error: function(data) {
                        $(":input[name=organization_id]").prop("disabled", false);
                    }
                });
            });

            // $('#createPatientForm input').on('keyup blur click', function () { // fires on every keyup & blur
            //     if ($('#createPatientForm').validate().checkForm()) {                   // checks form for validity
            //         $('#create-patient').prop('disabled', false);        // enables button
            //     } else {
            //         $('#create-patient').prop('disabled', 'disabled');   // disables button
            //     }
            // });

            // create patient form submit
            $("#create-patient").on('click', function(e) {
                $('.form-group').removeClass('has-error');
                $('span.error-msg').html('');
                e.preventDefault(); // avoid to execute the actual submit of the form.
                var form = $('.create-patient-form');
                if ($('#createPatientForm').valid()) {
                    $(".submit-button-loader").show();
                    $("#create-patient").hide();
                    $('a.close').hide();
                    if ($('.addNewPat').prop("checked") == true) {
                        $('.create-patient-form').append('<input type="hidden" name="addNewPatient" value="1" />');
                    }
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        url: "{{ url('/create-patient') }}",
                        data: form.serialize(),
                        type: 'post',
                        success: function(response) {
                            $(".submit-button-loader").hide();
                            $("#create-patient").show();
                            $('a.close').show();
                            if (!response.success) {
                                //to set the server side errors messages
                                $.each(response.errors, function(key, data) {
                                    $('input[name=' + key + ']').closest('.form-group').addClass('has-error');
                                    $('input[name=' + key + ']').parent().find('span.error-msg').html(data);
                                });
                            } else if (response.status == '200') {
                                //reload the page to get the updated data
                                window.location.href = "{{ url('patients') }}";
                                // location.reload();
                            }
                        },
                        error: function(err_res) {
                            if (err_res.status == 400 && err_res.responseJSON.errors != '') {
                                $(".submit-button-loader").hide();
                                $("#create-patient").show();
                                $('a.close').show();
                                $('.custom-toast').show().delay(5000).slideUp(800);
                                $(".modal-wrap").animate({
                                    scrollTop: 0
                                });
                            }
                        }
                    });
                }
            });
        });

        // To get all the patients
        function getAllPatients() {
            $('.main-loader').show();
            var url = "{{ url('patients/all') }}";
            $.ajax({
                type: "POST",
                url: url,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    private_key: private_key,
                    getShowData: true
                },
                success: function(data) {
                    $("#upload-document-modal .custom-content").html(data);
                    $(".custom-scroll-verticle").mCustomScrollbar({
                        axis: "y"
                    });
                    $('.main-loader').hide();
                },
                error: function(data) {
                    $('.main-loader').hide();
                }
            });
        }

        function getPatientMeetingsData(private_key) {
            $('.main-loader').show();
            var url = "{{ url('events') }}";
            $.ajax({
                type: "POST",
                url: url,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    private_key: private_key,
                    isPopup: true,
                },
                success: function(data) {
                    if (data) {
                        $("#new-appointment .custom-content").html(data.html);
                        // append all related organization patient
                        $("#patient_doctor").each(function() {
                            $(this).select2({
                                dropdownParent: $(this).closest('.form-group'),
                                data: data.organizationDoctorName,
                            });
                        });

                        $('.main-loader').hide();
                    }
                },
                error: function(data) {
                    $('.main-loader').hide();
                }
            });
        }
    </script>
    @stack('custom-scripts')
</body>

</html>