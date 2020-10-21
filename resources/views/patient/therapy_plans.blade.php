@extends('layouts.main_with_tabs')

@section('content')
	@component('layouts.patient_tabs')
        @section('slot')
            <div class="full-page-wrapper patient-plan-detail">
                <div class="row custom-row">
                    <div class="col-md-3"></div>
                    <div class="col-md-9">
                        <div class="tab-heading">
                            <div class="left-content">
                                <h2>{{ !empty($indication_name) ? ucfirst($indication_name) : ''}}</h2>
                            </div>
                            <div class="right-content">
                                <a href="#" class="btn red-btn" title="{{ __('lang.end-phase') }}">{{ __('lang.end-phase') }}</a>
                                <a href="#" class="pdf-viewer" title="PDF">
                                    <i class="pdf-img"><img src="{{ asset('assets/images/pdf.svg') }}" alt="PDFIMG"></i>
                                </a>
                                <select class="custom_dropdown">
                                    <option value="Versionserlauf">Versionserlauf</option>
                                    <option value="Versionserlauf">Versionserlauf</option>
                                </select>
                                <!-- <a href="#" class="btn btn-green">Speichern</a> -->
                            </div>
                        </div>
                    </div>
                </div>
                <div class="custom-vertical-tab">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <ul class="nav nav-pills flex-column phase-list-block" id="myTab" role="tablist">
                                @foreach($therapy_plans_phases as $key => $phase)
                                    <li class="nav-item {{ Carbon\Carbon::now()->lt(Carbon\Carbon::parse($phase->start_date))  ? 'disable' : ''}}" data-phase-active="{{$phase->id}}">
                                        <a class="nav-link {{Carbon\Carbon::now()->gte(Carbon\Carbon::parse($phase->start_date)) && Carbon\Carbon::now()->lt(Carbon\Carbon::parse($phase->end_date)) ? 'active' : ''}} {{ Carbon\Carbon::now()->lt(Carbon\Carbon::parse($phase->start_date))  ? 'disable' : ''}}" id="phase-{{ $key + 1 }}" data-toggle="tab"
                                            href="#phase{{ $key + 1 }}" role="tab" aria-controls="phase{{ $key + 1 }}"
                                            aria-selected="true">
                                            @php $process = round((strtotime(date('m/d/Y')) - strtotime(Carbon\Carbon::parse($phase->start_date)->format('m/d/Y')))/(strtotime(Carbon\Carbon::parse($phase->end_date)->format('m/d/Y')) - strtotime(Carbon\Carbon::parse($phase->start_date)->format('m/d/Y')))*100 ); @endphp
                                            <div class="link-title">
                                                <h2>Phase {{ $key + 1 }}</h2>
                                                <em>{{ $process <= '0' ? '0' : ($process >= 100 ? '100' : $process) }}&#37;</em>
                                            </div>
                                            <div class="link-content">
                                                <strong>{{ $phase->name }}</strong>
                                            </div>
                                            <div class="status">
                                                @if(Carbon\Carbon::now()->gt(Carbon\Carbon::parse($phase->end_date)) || $process == 100)
                                                    <span >{{ __('lang.completed') }}</span>
                                                    <i><img src="{{ asset('assets/images/tick.svg') }}" alt=""></i>
                                                @else 
                                                    @if($process <= 0) 
                                                        @php $weeks = $phase->duration; @endphp
                                                    @else 
                                                        @php $weeks = $phase->duration - ceil((Carbon\Carbon::now()->diffInDays(Carbon\Carbon::parse($phase->start_date)))/7); @endphp
                                                    @endif
                                                    <span>{{ $weeks }} {{ __('lang.weeks') }}</span>
                                                    <i><img src="{{ asset('assets/images/Clock.svg') }}" alt=""></i>
                                                @endif
                                            </div>
                                        </a>
                                    </li>
                                @endforeach
                                <li>
                                    <a class="add-tab" href="javascript:void(0)" data-toggle="modal"
                                        data-target="#phase-setting-modal">
                                        <i><img src="{{ asset('assets/images/edit-icon.svg') }}" alt=""></i>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-9">
                            <div class="tab-content" id="myTabContent">
                                @foreach($therapy_plans_phases as $key => $phase)
                                <div class="tab-pane fade show {{ Carbon\Carbon::now()->gte(Carbon\Carbon::parse($phase->start_date)) && Carbon\Carbon::now()->lt(Carbon\Carbon::parse($phase->end_date)) ? 'active' : ''}}" id="phase{{ $key+1 }}" role="tabpanel"
                                    aria-labelledby="phase-{{ $key+1 }}">
                                    @php $process = round((strtotime(date('m/d/Y')) - strtotime(Carbon\Carbon::parse($phase->start_date)->format('m/d/Y')))/(strtotime(Carbon\Carbon::parse($phase->end_date)->format('m/d/Y')) - strtotime(Carbon\Carbon::parse($phase->start_date)->format('m/d/Y')))*100 ); @endphp
                                    <a class="nav-link {{ Carbon\Carbon::now()->lt(Carbon\Carbon::parse($phase->start_date))  ? 'disable' : ''}} visible-md">
                                        <div class="link-title">
                                            <h2>Phase {{ $key + 1 }}</h2>
                                            <em>{{ $process <= '0' ? '0' : ($process >= 100 ? '100' : $process) }}&#37;</em>
                                        </div>
                                        <div class="status">
                                            @if(Carbon\Carbon::now()->gt(Carbon\Carbon::parse($phase->end_date)) || $process == 100)
                                                    <span >{{ __('lang.completed') }}</span>
                                                    <i><img src="{{ asset('assets/images/tick.svg') }}" alt=""></i>
                                                @else 
                                                    @if($process <= 0) 
                                                        @php $weeks = $phase->duration; @endphp
                                                    @else 
                                                        @php $weeks = $phase->duration - ceil((Carbon\Carbon::now()->diffInDays(Carbon\Carbon::parse($phase->start_date)))/7); @endphp
                                                    @endif
                                                    <span>{{ $weeks }} {{ __('lang.weeks') }}</span>
                                                    <i><img src="{{ asset('assets/images/Clock.svg') }}" alt=""></i>
                                                @endif
                                        </div>
                                    </a>
                                    <div class="content-wrapper">
                                        <div class="heading-block has-add-block">
                                            <div class="outer-heading-wrap">
                                                <div class="heading-wrap">
                                                    <div class="left-content">
                                                        <h2><span class="green-text">Phase {{ $key+1 }}</span>
                                                            {{ $phase->name }}
                                                        </h2>
                                                    </div>
                                                    <div class="right-content">
                                                        <h2>{{ __('lang.day') }} {{ Carbon\Carbon::now()->diffInDays(Carbon\Carbon::parse($start_date))}}</h2>
                                                        <div class="date-block">
                                                            <i class="date-img">
                                                                <img src="{{ asset('assets/images/date.svg') }}"
                                                                    alt="DateImg">
                                                            </i>
                                                            <h2>{{ Carbon\Carbon::parse($phase->start_date)->format('d.m.Y') }}</h2>
                                                            <span>{{ __('lang.to') }}</span>
                                                            <h2>{{ Carbon\Carbon::parse($phase->end_date)->format('d.m.Y') }}</h2>
                                                        </div>
                                                    </div>
                                                </div>
                                                @if(!$phase->limitations->isEmpty())
                                                    <div class="detail-div">
                                                        <span>{{ __('lang.aims') }}:</span>
                                                        <p>{{ $phase->phase_objectives }}</p>
                                                    </div>
                                                    <div class="detail-div">
                                                        <span>{{ __('lang.practical-exercises') }}:</span>
                                                        <p>{{ $phase->practical_exercises }}</p>
                                                    </div>
                                                    <div class="limitation-block">
                                                        <div class="heading-block">
                                                            <span>{{ __('lang.limitations') }}:</span>
                                                        </div>
                                                        <div class="chartWrapper" id="chart_wrapper_{{ $phase->id }}">
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                            @if(!$phase->limitations->isEmpty())
                                                <div class="add-block">
                                                    <a href="javascript:void(0)" class="add-tab" data-toggle="modal" data-target="#edit-phase-modal" onclick="openLimitationModal(this);">
                                                        <i><img src="{{ asset('assets/images/edit-icon.svg') }}"
                                                                alt="edit"></i>
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="measurement-block-wrapper">
                                            <div class="block-wrapper doctor-col">
                                                @if($phase->limitations->isEmpty())
                                                    <h2>{{ __('lang.doctor') }}</h2>
                                                    <a href="javascript:void(0)" class="add-tab dark-green has-text" data-toggle="modal" data-target="#edit-phase-modal" onclick="openLimitationModal(this);" >
                                                        <i><img src="{{ asset('assets/images/plus-white.svg') }}"
                                                                alt=""></i>
                                                        <h2>{{ __('lang.edit-phases-limts-text') }}</h2>
                                                    </a>
                                                @endif
                                               
                                            </div>
                                            <div class="block-wrapper physio-col">
                                                <h2>Physio</h2>
                                                <a href="javascript:void(0)" class="add-tab normal-green has-text">
                                                    <i><img src="{{ asset('assets/images/plus-white.svg') }}"
                                                            alt=""></i>
                                                    <h2>{{ __('lang.add-assessment') }}</h2>
                                                </a>
                                                <a href="javascript:void(0)" class="add-tab normal-green has-text">
                                                    <i><img src="{{ asset('assets/images/plus-white.svg') }}"
                                                            alt=""></i>
                                                    <h2>{{ __('lang.add-exercises') }}</h2>
                                                </a>
                                            </div>
                                        </div>
                                       
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            <a class="add-tab visible-md" href="javascript:void(0)" data-toggle="modal"
                                data-target="#phase-setting-modal">
                                <i><img src="{{ asset('assets/images/plus-green.svg') }}" alt=""></i>
                            </a>
                        </div>
                    </div>
                </div>  
            </div>
            <!-- modal -->
            <div class="modal phase-list fade custom-modal" id="phase-setting-modal" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <form class="add-phase-form" method="post">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h2>{{ __('lang.Create therapy plan for text', ['name' => (ucfirst($user->firstname) .' '. ucfirst($user->lastname))] )}}</h2>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true"><i><img src="{{ asset('assets/images/cross-icon.svg') }}"></i></span>
                                </button>
                            </div>
                            <div class="modal-wrap">
                                <div class="toast_msg danger custom-toast" style="display:none;">Please fill all phase details
                                    <a href="#" class="close_toast"><img src="{{ asset('assets/images/close.svg') }}" alt=""></a>
                                </div>
                                <div class="inner-content create-therapy">
                                    <div class="inner-col custom-inner-col">
                                        <div class="form-group phase-wrap no-border">
                                            <select class="custom_dropdown custom_dropdown-secondary select-country" id="indication" name="indication">
                                                @foreach($indication as $data)
                                                    <option value="{{$data->id}}" {{$data->id == $indication_id ? 'selected="selected"' : ''}}>{{$data->name}}</option>
                                                @endforeach
                                            </select>
                                            <div class="form-group">
                                                <div class='input-group date form-group select-date'>
                                                    <input type='text' name="start_date" class="form-control" id="start_date_input"/>
                                                    <span class="input-group-addon">
                                                        <span class="glyphicon glyphicon-time"></span>
                                                    </span>
                                                    <i></i>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                    <?php $total_phases = 0; ?>
                                        @foreach($therapy_plans_phases as $key => $phase)
                                        <?php $total_phases = count($therapy_plans_phases); ?>
                                        <div class="inner-col phase-data" id="additional{{$key}}">
                                            <a class="phase-wrap">
                                                <div class="phase-header">
                                                    <h2><button class="delete-phase"><img src="{{ asset('assets/images/delete.svg') }}" alt=""></button> Phase {{ $key + 1}}</h2>
                                                    <span class="duration"><span class="duration_weeks">{{ $phase->duration }}</span> {{ __('lang.weeks') }} <i><img
                                                                src="{{ asset('assets/images/Clock.svg') }}"></i></span>
                                                </div>
                                                <label id="label_name_{{ $key + 1}}" ondblclick="inlineEdit(this, 'name', '{{$key+1}}')" >{{ $phase->name }}</label>
                                                <div id="input_name_{{ $key + 1}}" class="form-group custom-form-group" style="display:none">
                                                    <input type="text" name="phase[{{$key + 1}}][name]" class="form-control name" placeholder="{{ __('lang.name') }}" value="{{ $phase->name }}" required>
                                                </div>
                                                <input type="text" id="input_dur_{{ $key + 1}}" name="phase[{{$key + 1}}][duration]" class="form-control duration_input" value="{{ $phase->duration }}" required style="display:none">
                                                <input type="hidden" name="phase[{{$key + 1}}][exists_id]" class="form-control" value="{{ $phase->id }}">
                                                <input type="hidden" class="form-control" id="total_phase_count" value="{{ count($therapy_plans_phases) }}">
                                                <div class="btn-wrap">
                                                    <button class="btn caribbean-green-btn" title="+ 1 {{ __('lang.week') }}" onclick="addOneWeek(this, event, 'add-phase-model')">+ 1 {{ __('lang.week') }}</button>
                                                    <button class="btn caribbean-green-btn" title="- 1 {{ __('lang.week') }}" onclick="removeOneWeek(this, event, 'add-phase-model')">- 1 {{ __('lang.week') }}</button>
                                                </div>
                                            </a>
                                        </div>
                                        @endforeach
                                    <!-- <div class="clone-div"></div> -->

                                    <div class="inner-col add-btn">
                                        <a href="javascript:void(0);" class="add-phase-link"><i><img
                                                    src="{{ asset('assets/images/plus-green.svg') }}"></i></a>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <div class="btn-wrap">
                                    <button type="button" title="" class="btn gray-btn save" title="{{ __('lang.save') }}">{{ __('lang.save') }}</button>
                                    <a href="#" title="" class="btn btn-transparent btn-lg close" data-dismiss="modal" aria-label="Close">{{ __('lang.close') }}</a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- side-bar -->
            <div class="slide-div">
                <div class="slide-wrap">
                    <div class="left-block">
                        <a href="#" class="close-btn" title="Close"><i><img src="{{ asset('assets/images/cross-icon.svg') }}"></i></a>
                        <div class="profile-div">
                            <a href="#" class="profile-img"><img src="{{ asset('assets/images/employee-logo4.png') }}"></a>
                            <span>Assessment für</span>
                            <p>Sebastian Angelone</p>
                        </div>
                        <div class="nav-wrap slide-nav">
                            <ul class="nav flex-column outer-nav nav-pills mb-3" id="pills-tab" role="tablist">
                                <li class="nav-item outer-nav-item">
                                    <a class="nav-link active" title="Temperatur" id="pills-temperatur-tab" data-toggle="pill"
                                        href="#pills-temperatur" role="tab" aria-controls="pills-temperatur"
                                        aria-selected="true">
                                        <div class="switch-wrapper">
                                            <div class="switch-outer">
                                                <div class="switch-inner">
                                                    <input type="checkbox" class="onoffswitch" id="switch1" checked>
                                                    <label class="switch">
                                                        <span class="slider"></span>
                                                    </label>
                                                </div>
                                            </div>
                                            <label for="switch1">Temperatur</label>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item outer-nav-item">
                                    <a class="nav-link" title="Kraft | Schnelligkeit" id="pills-kraft-tab" data-toggle="pill" href="#pills-kraft"
                                        role="tab" aria-controls="pills-kraft" aria-selected="false">
                                        <div class="switch-wrapper">
                                            <div class="switch-outer">
                                                <div class="switch-inner">
                                                    <input type="checkbox" class="onoffswitch" id="switch2" checked>
                                                    <label class="switch">
                                                        <span class="slider"></span>
                                                    </label>
                                                </div>
                                            </div>
                                            <label for="switch2">Kraft | Schnelligkeit</label>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item outer-nav-item">
                                    <a class="nav-link" title="Schmerzlevel" id="pills-schmerzlevel-tab" data-toggle="pill"
                                        href="#pills-schmerzlevel" role="tab" aria-controls="pills-schmerzlevel"
                                        aria-selected="false">
                                        <div class="switch-wrapper">
                                            <div class="switch-outer">
                                                <div class="switch-inner">
                                                    <input type="checkbox" class="onoffswitch" id="switch3" checked>
                                                    <label class="switch">
                                                        <span class="slider"></span>
                                                    </label>
                                                </div>
                                            </div>
                                            <label for="switch3">Schmerzlevel</label>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item outer-nav-item">
                                    <a class="nav-link" title="Beweglichkeit" id="pills-beweglichkeit-tab" data-toggle="pill"
                                        href="#pills-beweglichkeit" role="tab" aria-controls="pills-beweglichkeit"
                                        aria-selected="true">
                                        <div class="switch-wrapper">
                                            <div class="switch-outer">
                                                <div class="switch-inner">
                                                    <input type="checkbox" class="onoffswitch" id="switch4" checked>
                                                    <label class="switch">
                                                        <span class="slider"></span>
                                                    </label>
                                                </div>
                                            </div>
                                            <label for="switch4">Beweglichkeit</label>
                                        </div>
                                    </a>
                                </li>

                                <li class="nav-item outer-nav-item">
                                    <a class="nav-link" title="Schwellung" id="pills-schwellung-tab" data-toggle="pill"
                                        href="#pills-schwellung" role="tab" aria-controls="pills-schwellung"
                                        aria-selected="true">
                                        <div class="switch-wrapper">
                                            <div class="switch-outer">
                                                <div class="switch-inner">
                                                    <input type="checkbox" class="onoffswitch" id="switch5" checked>
                                                    <label class="switch">
                                                        <span class="slider"></span>
                                                    </label>
                                                </div>
                                            </div>
                                            <label for="switch5">Schwellung</label>
                                        </div>
                                    </a>
                                </li>
                                <li class="nav-item outer-nav-item">
                                    <a class="nav-link" title="Koordination" id="pills-koordination-tab" data-toggle="pill"
                                        href="#pills-koordination" role="tab" aria-controls="pills-koordination"
                                        aria-selected="true">
                                        <div class="switch-wrapper">
                                            <div class="switch-outer">
                                                <div class="switch-inner">
                                                    <input type="checkbox" class="onoffswitch" id="switch6" checked>
                                                    <label class="switch">
                                                        <span class="slider"></span>
                                                    </label>
                                                </div>
                                            </div>
                                            <label for="switch6">Koordination</label>
                                        </div>
                                    </a>
                                </li>                          
                            </ul>
                            <a href="#" class="add-btn" title="Add"><i><img src="{{ asset('assets/images/plus-green.svg') }}"></i></a>
                        </div>
                    </div>
                    <div class="right-block">
                        <div class="heading-div">
                            <h2>Phase 2</h2>
                            <span>erweiterte Aktivierung</span>
                            <div class="edit-div">
                                <p>Wiedereingliederung in Alltag, Gesellschaft und Beruf. Zum Phasenübertritt in Phase
                                    IV sollten die Patienten mindestens 70% Kraftprozentangaben zur nicht betroffenen
                                    Seite in alle Richtungen aufweisen können</p>
                                <a href="#" class="edit-btn"><i><img src="{{ asset('assets/images/edit-icon.svg') }}"></i></a>
                            </div>

                            <div class="duration-wrap">
                                <div class="left-div">
                                    <i><img src="{{ asset('assets/images/date.svg') }}"></i>
                                    <span>23.11.2019 <em>bis</em> 01.02.2020</span>
                                </div>
                                <div class="right-div">
                                    <div class="btn-wrap">
                                        <a href="#" class="btn caribbean-green-btn" title="+ 1 Woche">+ 1 Woche</a>
                                        <a href="#" class="btn caribbean-green-btn" title="- 1 Woche">- 1 Woche</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-content slide-tab-content" id="pills-tabContent">
                            <div class="tab-pane outer-tab-pane fade active show" id="pills-temperatur" role="tabpanel"
                                aria-labelledby="pills-temperatur-tab">
                                <div class="slide-link visible-md">
                                    <a class="nav-link active show" id="pills-temperatur-tab" data-toggle="pill"
                                        href="#pills-temperatur" role="tab" aria-controls="pills-temperatur"
                                        aria-selected="true">
                                        <div class="switch-wrapper">
                                            <div class="switch-outer">
                                                <div class="switch-inner">
                                                    <input type="checkbox" class="onoffswitch" id="switch1" checked="">
                                                    <label class="switch">
                                                        <span class="slider"></span>
                                                    </label>
                                                </div>
                                            </div>
                                            <label for="switch1">Temperatur</label>
                                        </div>
                                    </a>
                                </div>
                                <div class="slide-content">
                                    <h3>Temperatur:</h3>
                                    <div class="nav-wrap" data-mcs-theme="dark">
                                        <div class="inner-nav-wrapper">
                                            <div class="content-scrolling">
                                                <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
                                                    <li class="nav-item">
                                                        <a class="nav-link active" title="Sprunghöhe" id="pills-home-tab" data-toggle="pill"
                                                            href="#pills-home" role="tab" aria-controls="pills-home"
                                                            aria-selected="true">
                                                            <div class="switch-wrapper">
                                                                <div class="switch-outer">
                                                                    <div class="switch-inner">
                                                                        <input type="checkbox" class="onoffswitch" id="switch4"
                                                                            checked>
                                                                        <label class="switch">
                                                                            <span class="slider"></span>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                                <label for="switch4">Sprunghöhe</label>
                                                            </div>
                                                        </a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a class="nav-link" title="Sprungweite" id="pills-profile-tab" data-toggle="pill"
                                                            href="#pills-profile" role="tab" aria-controls="pills-profile"
                                                            aria-selected="false">
                                                            <div class="switch-wrapper">
                                                                <div class="switch-outer">
                                                                    <div class="switch-inner">
                                                                        <input type="checkbox" class="onoffswitch" id="switch5"
                                                                            checked>
                                                                        <label class="switch">
                                                                            <span class="slider"></span>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                                <label for="switch5">Sprungweite</label>
                                                            </div>
                                                        </a>
                                                    </li>
                                                    <li class="nav-item">
                                                        <a class="nav-link" title="Side Hop" id="pills-contact-tab" data-toggle="pill"
                                                            href="#pills-contact" role="tab" aria-controls="pills-contact"
                                                            aria-selected="false">
                                                            <div class="switch-wrapper">
                                                                <div class="switch-outer">
                                                                    <div class="switch-inner">
                                                                        <input type="checkbox" class="onoffswitch" id="switch6"
                                                                            checked>
                                                                        <label class="switch">
                                                                            <span class="slider"></span>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                                <label for="switch6">Side Hop</label>
                                                            </div>
                                                        </a>
                                                    </li>
                                                    <li class="nav-item new-item">
                                                        <a class="nav-link" id="" title="" data-toggle="pill" href="" role="tab"
                                                            aria-controls="" aria-selected="true">
                                                            <div class="switch-wrapper">
                                                                <div class="switch-outer">
                                                                    <div class="switch-inner">
                                                                        <input type="checkbox" class="onoffswitch" id="switch6"
                                                                            checked>
                                                                        <label class="switch">
                                                                            <span class="slider"></span>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                                                <!-- <label for="switch6">Koordination</label> -->
                                                                <div class="form-group edit-label">
                                                                    <input type="text" for="switch6" class="form-control" value="Sprunghöhe">
                                                                    <i><img src="{{ asset('assets/images/edit-icon.svg') }}"></i>
                                                                </div>
                                                            </div>
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        <a href="#" title="Add" class="add-nav-link"><img src="{{ asset('assets/images/plus-green.svg') }}"></a>
                                            </div>
                                        <div class="tab-content" id="pills-tabContent">
                                            <div class="tab-pane fade show active" id="pills-home" role="tabpanel"
                                                aria-labelledby="pills-home-tab">
                                                <h4>Assessmentbereich</h4>
                                                <div class="range-div">
                                                    <div class="range-min">
                                                        <!-- <label class="min-val"><i>7</i> cm</label> -->
                                                        <div class="form-group edit-label">
                                                            <input type="text" for="switch6" class="form-control" value="7 cm">
                                                            <i><img src="{{ asset('assets/images/edit-icon.svg') }}"></i>
                                                        </div>
                                                    </div>
                                                    <div class="range-max">
                                                        <!-- <label class="max-val"><i>14</i> cm</label> -->
                                                        <div class="form-group edit-label">
                                                                <input type="text" for="switch6" class="form-control" value="14 cm">
                                                                <i><img src="{{ asset('assets/images/edit-icon.svg') }}"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                                <input id="range" class="range-slider" type="text" />
                                                <h4>Verlauf</h4>
                                                <img src="{{ asset('assets/images/graph.jpg') }}" alt="graph" class="graph-img">
                                                <div class="btn-wrap">
                                                    <a href="#" class="btn red-btn" title="Phase beenden">Phase beenden</a>
                                                    <a href="#" class="btn btn-green" title="Speichern">Speichern</a>
                                                    <a href="#" title="Close"
                                                        class="btn btn-transparent close-btn">Abbrechen</a>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="pills-profile" role="tabpanel"
                                                aria-labelledby="pills-profile-tab">2...</div>
                                            <div class="tab-pane fade" id="pills-contact" role="tabpanel"
                                                aria-labelledby="pills-contact-tab">3...</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane outer-tab-pane fade" id="pills-kraft" role="tabpanel"
                                aria-labelledby="pills-kraft-tab">
                                <div class="slide-link visible-md">
                                    <a class="nav-link active show" title="Kraft | Schnelligkeit" id="pills-temperatur-tab" data-toggle="pill"
                                        href="#pills-temperatur" role="tab" aria-controls="pills-temperatur"
                                        aria-selected="true">
                                        <div class="switch-wrapper">
                                            <div class="switch-outer">
                                                <div class="switch-inner">
                                                    <input type="checkbox" class="onoffswitch" id="switch1" checked="">
                                                    <label class="switch">
                                                        <span class="slider"></span>
                                                    </label>
                                                </div>
                                            </div>
                                            <label for="switch1">Kraft | Schnelligkeit</label>
                                        </div>
                                    </a>
                                </div>
                                <div class="slide-content">
                                    <h3>Kraft | Schnelligkeit:</h3>
                                    <div class="nav-wrap">
                                        <ul class="nav nav-pills mb-3" id="pills-tab2" role="tablist">
                                            <li class="nav-item">
                                                <a class="nav-link active" title="Sprunghöhe" id="pills-height-tab" data-toggle="pill"
                                                    href="#pills-height" role="tab" aria-controls="pills-height"
                                                    aria-selected="true">
                                                    <div class="switch-wrapper">
                                                        <div class="switch-outer">
                                                            <div class="switch-inner">
                                                                <input type="checkbox" class="onoffswitch" id="switch4"
                                                                    checked>
                                                                <label class="switch">
                                                                    <span class="slider"></span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <label for="switch4">Sprunghöhe</label>
                                                    </div>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" title="Sprungweite" id="pills-distance-tab" data-toggle="pill"
                                                    href="#pills-distance" role="tab" aria-controls="pills-distance"
                                                    aria-selected="false">
                                                    <div class="switch-wrapper">
                                                        <div class="switch-outer">
                                                            <div class="switch-inner">
                                                                <input type="checkbox" class="onoffswitch" id="switch5"
                                                                    checked>
                                                                <label class="switch">
                                                                    <span class="slider"></span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <label for="switch5">Sprungweite</label>
                                                    </div>
                                                </a>
                                            </li>
                                            <li class="nav-item">
                                                <a class="nav-link" title="Side Hop" id="pills-Hop-tab" data-toggle="pill"
                                                    href="#pills-Hop" role="tab" aria-controls="pills-Hop"
                                                    aria-selected="false">
                                                    <div class="switch-wrapper">
                                                        <div class="switch-outer">
                                                            <div class="switch-inner">
                                                                <input type="checkbox" class="onoffswitch" id="switch6"
                                                                    checked>
                                                                <label class="switch">
                                                                    <span class="slider"></span>
                                                                </label>
                                                            </div>
                                                        </div>
                                                        <label for="switch6">Side Hop</label>
                                                    </div>
                                                </a>
                                            </li>
                                        </ul>
                                        <div class="tab-content" id="pills-tabContent">
                                            <div class="tab-pane fade show active" id="pills-height" role="tabpanel"
                                                aria-labelledby="pills-height-tab">
                                                <h4>Assessmentbereich</h4>
                                                <div class="range-div">
                                                    <div class="range-min">
                                                        <!-- <label class="min-val"><i>7</i> cm</label> -->
                                                        <div class="form-group edit-label">
                                                                <input type="text" for="switch6" class="form-control" value="7 cm">
                                                                <i><img src="{{ asset('assets/images/edit-icon.svg') }}"></i>
                                                        </div>
                                                    </div>
                                                    <div class="range-max">
                                                        <!-- <label class="max-val"><i>14</i> cm</label> -->
                                                        <div class="form-group edit-label">
                                                                <input type="text" for="switch6" class="form-control" value="14 cm">
                                                                <i><img src="{{ asset('assets/images/edit-icon.svg') }}"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                                <input id="range2" class="range-slider" type="text" />
                                                <h4>Verlauf</h4>
                                                <img src="{{ asset('assets/images/graph.jpg') }}" alt="graph" class="graph-img">
                                                <div class="btn-wrap">
                                                    <a href="#" class="btn red-btn" title="Phase beenden">Phase beenden</a>
                                                    <a href="#" class="btn btn-green" title="Speichern">Speichern</a>
                                                    <a href="#" title="Close"
                                                        class="btn btn-transparent close-btn">Abbrechen</a>
                                                </div>
                                            </div>
                                            <div class="tab-pane fade" id="pills-distance" role="tabpanel"
                                                aria-labelledby="pills-distance-tab">2...</div>
                                            <div class="tab-pane fade" id="pills-Hop" role="tabpanel"
                                                aria-labelledby="pills-Hop-tab">3...</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane outer-tab-pane fade" id="pills-schmerzlevel" role="tabpanel"
                                aria-labelledby="pills-schmerzlevel-tab">
                                <div class="slide-link visible-md">
                                    <a class="nav-link active show" id="pills-temperatur-tab" data-toggle="pill"
                                        href="#pills-temperatur" role="tab" aria-controls="pills-temperatur"
                                        aria-selected="true">
                                        <div class="switch-wrapper">
                                            <div class="switch-outer">
                                                <div class="switch-inner">
                                                    <input type="checkbox" class="onoffswitch" id="switch1" checked="">
                                                    <label class="switch">
                                                        <span class="slider"></span>
                                                    </label>
                                                </div>
                                            </div>
                                            <label for="switch1">Schmerzlevel</label>
                                        </div>
                                    </a>
                                </div>
                                <div class="slide-content">
                                <h3>Schmerzlevel:</h3>
                                3
                            </div>
                            </div>
                            <div class="tab-pane outer-tab-pane fade" id="pills-beweglichkeit" role="tabpanel"
                                aria-labelledby="pills-beweglichkeit-tab">
                                <div class="slide-link visible-md">
                                    <a class="nav-link active show" id="pills-temperatur-tab" data-toggle="pill"
                                        href="#pills-temperatur" role="tab" aria-controls="pills-temperatur"
                                        aria-selected="true">
                                        <div class="switch-wrapper">
                                            <div class="switch-outer">
                                                <div class="switch-inner">
                                                    <input type="checkbox" class="onoffswitch" id="switch1" checked="">
                                                    <label class="switch">
                                                        <span class="slider"></span>
                                                    </label>
                                                </div>
                                            </div>
                                            <label for="switch1">Beweglichkeit</label>
                                        </div>
                                    </a>
                                </div>
                                <div class="slide-content">
                                <h3>Beweglichkeit:</h3>
                                4
                            </div>
                            </div>
                            <div class="tab-pane outer-tab-pane fade" id="pills-schwellung" role="tabpanel"
                                aria-labelledby="pills-schwellung-tab">
                                <div class="slide-link visible-md">
                                    <a class="nav-link active show" id="pills-temperatur-tab" data-toggle="pill"
                                        href="#pills-temperatur" role="tab" aria-controls="pills-temperatur"
                                        aria-selected="true">
                                        <div class="switch-wrapper">
                                            <div class="switch-outer">
                                                <div class="switch-inner">
                                                    <input type="checkbox" class="onoffswitch" id="switch1" checked="">
                                                    <label class="switch">
                                                        <span class="slider"></span>
                                                    </label>
                                                </div>
                                            </div>
                                            <label for="switch1">Schwellung</label>
                                        </div>
                                    </a>
                                </div>
                                <div class="slide-content">
                                <h3>Schwellung:</h3>
                                5
                                </div>
                            </div>
                            <div class="tab-pane outer-tab-pane fade" id="pills-koordination" role="tabpanel"
                                aria-labelledby="pills-koordination-tab">
                                <div class="slide-link visible-md">
                                    <a class="nav-link active show" id="pills-temperatur-tab" data-toggle="pill"
                                        href="#pills-temperatur" role="tab" aria-controls="pills-temperatur"
                                        aria-selected="true">
                                        <div class="switch-wrapper">
                                            <div class="switch-outer">
                                                <div class="switch-inner">
                                                    <input type="checkbox" class="onoffswitch" id="switch1" checked="">
                                                    <label class="switch">
                                                        <span class="slider"></span>
                                                    </label>
                                                </div>
                                            </div>
                                            <label for="switch1">Koordination</label>
                                        </div>
                                    </a>
                                </div>
                                <div class="slide-content">
                                <h3>Koordination:</h3>
                                6
                                </div>
                            </div>
                        </div>
                        <a href="#" class="add-btn mobile-link" title="Add"><i><img src="{{ asset('assets/images/plus-green.svg') }}"></i></a>
                    </div>
                    
                </div>
            </div>
            <!--Kurse und Aufgaben hinzufügen / Add courses and assignments (Exercise_groups Model) -->
            <div class="modal add-course fade custom-modal large-modal" id="add-course-modal" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2>Phase <span class="active_key"></span> - {{ __('lang.add-cources-and-assignment') }}</h2>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" title="Close">
                        <span aria-hidden="true"><i><img src="{{  asset('assets/images/cross-icon.svg') }}"></i></span>
                        </button>
                        </div>
                        <form method="POST" id="" class="form-component" action="{{ route('save-exercise-groups') }}" novalidate>
                        @csrf   
                        <div class="form-inner-wrapper">
                            <div class="modal-wrap">
                                    <div class="toast_msg danger custom-toast" style="display:none;">{{ __('lang.no-active-phase-found') }}
                                        <a href="#" class="close_toast"><img src="{{ asset('assets/images/close.svg') }}" alt=""></a>
                                    </div>
                                <div class="modal-container">
                                    <div class="filter-block">
                                        <div class="form-group search">
                                            <input type="search" class="form-control" id="search-groups" placeholder="{{ __('lang.search') }}">
                                            <i><img src="{{  asset('assets/images/search-black.svg') }}"></i>
                                        </div>
                                        <a href="#" title="" class="filter-btn"><i><img src="{{  asset('assets/images/filter.svg') }}"></i></a>
                                    </div>
                                    <input type="hidden" class="active_phase" name="active_phase" value="">
                                    <div class="draggable-wrap">

                                        <div class="draggable-block draggable-scrolling">
                                            <div class="outer-draggable-list">
                                                <div class="exercise-groups draggable-list">
                                                </div>
                                            </div>

                                        </div>
                                        <div class="droppable-block">
                                            <div class="exercise-group-droppable droppable-list" id="div1">
                                                <h4>
                                                    Drag content here
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="btn-wrap">
                                <button type="submit" title="" class="btn btn-green" title="{{ __('lang.save') }}">{{ __('lang.save') }}</button>
                                <a href="javascript:void(0);" title="" class="btn btn-transparent btn-lg close" title="{{ __('lang.close') }}" data-dismiss="modal" aria-label="Close">{{ __('lang.close') }}</a>
                            </div>
                        </div>
                        </form>
                    </div>
                </div>
            </div>

            <!--Phase exercise list -->
            <div class="modal add-course fade custom-modal large-modal" id="add-exercise-modal" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2><span id="exercise-name"></span> - {{ __('lang.add-exercises') }}</h2>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close" title="Close">
                                <span aria-hidden="true"><i><img src="{{ asset('assets/images/cross-icon.svg') }}"></i></span>
                            </button>
                        </div>
                        <form method="POST" id="exercises-form" class="form-component" action="{{ route('save-exercise') }}" novalidate>
                        @csrf 
                        <div class="form-inner-wrapper">
                            <div class="modal-wrap">
                                <div class="toast_msg danger custom-toast" style="display:none;">{{ __('lang.exercise-fields-error') }}
                                    <a href="#" class="close_toast"><img src="{{ asset('assets/images/close.svg') }}" alt=""></a>
                                </div>
                                <div class="modal-container">
                                    <div class="filter-block">
                                        <div class="form-group search">
                                            <input type="search" class="form-control" id="search-exercises"  placeholder="{{ __('lang.search') }}">
                                            <i><img src="{{ asset('assets/images/search-black.svg') }}"></i>
                                        </div>
                                        <a href="#" title="" class="filter-btn"><i><img
                                                    src="{{ asset('assets/images/filter.svg') }}"></i></a>
                                        <div class="dropdown-list-wrap">
                                            <div class="dropdown-list outer-dropdown-list">
                                                <!-- <a href="#" title="close" class="close-filter"><i><img src="{{ asset('assets/images/cross-icon.svg') }}"></i></a> -->
                                                <div class="dropdown-wrap">
                                                    <label>{{ __('lang.estimated_course_time') }}</label>
                                                    <div>
                                                        <select class="custom_dropdown" name="estimated_course_time" id="estimated_time">
                                                            <option value="10">10</option>
                                                            <option value="20">20</option>
                                                            <option value="30">30</option>
                                                        </select>
                                                        <span>min</span>
                                                    </div>
                                                </div>
                                                <div class="dropdown-wrap">
                                                    <label>{{ __('lang.round') }}</label>
                                                    <select class="custom_dropdown" name="round">
                                                        <option value="1">1</option>
                                                        <option value="2">2</option>
                                                        <option value="3">3</option>
                                                        <option value="4">4</option>
                                                        <option value="5">5</option>
                                                    </select>
                                                </div>
                                                <div class="dropdown-wrap">
                                                    <label>{{ __('lang.per-week') }}</label>
                                                    <select class="custom_dropdown" name="per_week">
                                                        <option value="1">1 mal</option>
                                                        <option value="2">2 mal</option>
                                                        <option value="3">3 mal</option>
                                                        <option value="4">4 mal</option>
                                                        <option value="5">5 mal</option>
                                                        <option value="6">6 mal</option>
                                                        <option value="7">7 mal</option>
                                                        <option value="8">8 mal</option>
                                                        <option value="9">9 mal</option>
                                                        <option value="10">10 mal</option>

                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="draggable-wrap">
                                        <div class="draggable-block draggable-scrolling">
                                            <div class="draggable-title before-drag">
                                                <span><i><img src="{{ asset('assets/images/calm.svg') }}"></i> Pause</span>
                                            </div>
                                            <div class="outer-draggable-list">
                                                <div class="phase-exercises-list draggable-list">
                                                </div>
                                                <a class="add-btn add-exe-btn" title="Add"><i><img
                                                            src="{{ asset('assets/images/plus-green.svg') }}"></i></a>
                                                <div id="add-exercise-form" class="add-exercise-form">
                                                    <h3>{{ __('lang.add-exercises') }}</h3>
                                                    <div class="form-group @error('name') has-error @enderror">
                                                        <input type="text" id="name" class="form-control" name="name" value="{{ old('name') }}">
                                                        <label>{{ __('lang.exercise-name') }}<sup>*</sup></label>
                                                        <span class="error-msg">@error('name') {{$message}} @enderror</span>
                                                    </div>
                                                    <div class="form-group @error('duration') has-error @enderror">
                                                        <select class="custom_dropdown" name="duration" id="duration">
                                                        <option value>{{ __('lang.duration') }}</option>
                                                            @for ($i = 1; $i <= 25; $i++)
                                                            <option value="{{ $i }}" {{ old('duration') == '$i' ? 'selected="selected"' : '' }}>{{$i}}</option>
                                                            @endfor
                                                        </select>
                                                        <span class="error-msg">@error('duration') {{$message}} @enderror</span>
                                                    </div>
                                                    <div class="form-group @error('type') has-error @enderror">
                                                        <select class="custom_dropdown" name="type" id="type">
                                                            <option value>Type</option>
                                                            <option value="1" {{ old('type') == '1' ? 'selected="selected"' : '' }}>{{ __('lang.repetition') }}</option>
                                                            <option value="2" {{ old('type') == '2' ? 'selected="selected"' : '' }}>{{ __('lang.duration') }}</option>
                                                        </select>
                                                        <span class="error-msg">@error('type') {{$message}} @enderror</span>
                                                    </div>
                                                    <div class="form-group @error('material') has-error @enderror">
                                                        <select class="custom_dropdown" name="material" id="material">
                                                            <option value>Material</option>
                                                            <option value="towel" {{ old('material') == 'towel' ? 'selected="selected"' : '' }}>{{ __('lang.towel') }}</option>
                                                            <option value="mat" {{ old('material') == 'mat' ? 'selected="selected"' : '' }}>{{ __('lang.mat') }}</option>
                                                            <option value="stool" {{ old('material') == 'stool' ? 'selected="selected"' : '' }}>{{ __('lang.stool') }}</option>
                                                        </select>
                                                        <span class="error-msg">@error('material') {{$message}} @enderror</span>
                                                    </div>
                                                    <div class="form-group @error('description') has-error @enderror">
                                                        <textarea class="form-control" name="description" id="description" >{{old('description')}}</textarea>
                                                        <label>{{ __('lang.description') }}<sup>*</sup></label>
                                                        <span class="error-msg">@error('description') {{$message}} @enderror</span>
                                                    </div>
                                                    <input type="hidden" name="exercise_group_id" id="phase-exercise-group-id">
                                                    <div title="Add Photo" class="add-photo ">
                                                        <i>
                                                            <input type="file" name="exerciseImage" id="exerciseImage" id="file">
                                                            <img src="{{ asset('assets/images/camera.svg') }}" alt="Add Photo" id="preview_image">
                                                            <span class="error-msg">@error('exerciseImage') {{$message}} @enderror</span>
                                                        </i>
                                                        
                                                    </div>
                                                    <div class="btn-wrap">
                                                        <button type="btn" class="btn btn-green save-exercise">{{ __('lang.save') }}</button>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                        <div class="droppable-block">
                                            <div class="phase-exercises-droppable-list droppable-list">
                                                <h4>
                                                    Drag content here
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="btn-wrap">
                                <button type="submit" class="btn btn-green submit-btn" title="{{ __('lang.save') }}">{{ __('lang.save') }}</button>
                                <a href="#" title="" class="btn btn-transparent btn-lg close" title="{{ __('lang.close') }}"
                                    data-dismiss="modal" aria-label="Close">{{ __('lang.close') }}</a>
                            </div>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
            <!-- edit-phase-modal -->
            <div class="modal phase-list fade custom-modal x-large-modal" id="edit-phase-modal" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <input type="hidden" name="row_count_for_limitation" id="row_count_for_limitation" value= "0">
                        <form method="POST" id="update-phase-setting" class="form-component" novalidate>
                        @csrf 
                            <div class="modal-header">
                                <div class="title-div">
                                    <button type="button" class="nav-btn previous_btn" style="display:none;"><i><img src="{{ asset('assets/images/back.svg') }}"></i></button>
                                    <h2>Phase <span class="active_phase_key"></span> {{ __('lang.edit') }}</h2>
                                    <button type="button" class="nav-btn next_btn" style="display:none;"><i><img src="{{ asset('assets/images/next.svg') }}"></i></button>
                                </div>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">
                                        <i>
                                            <img src="{{ asset('assets/images/cross-icon.svg') }}">
                                        </i>
                                    </span>
                                </button>
                            </div>
                            <div class="modal-wrap">
                                <div class="toast_msg danger custom-phase-toast" style="display:none;"><span class="error-message"></span>
                                    <a href="#" class="close_toast"><img src="{{ asset('assets/images/close.svg') }}" alt=""></a>
                                </div>
                                <div class="inner-content create-therapy append-phase-details">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <div class="btn-wrap">
                                    <button type="button" title="" class="btn gray-btn update-phase" title="{{ __('lang.save') }}">{{ __('lang.save') }}</button>
                                    <a href="#" title="" class="btn btn-transparent btn-lg close" title="{{ __('lang.close') }}" data-dismiss="modal" aria-label="Close">{{ __('lang.close') }}</a>
                                    <!-- <i class="edit-loader submit-button-loader"><img src="{{ asset('assets/images/edit-loader.gif') }}"
                                            alt=""></i> -->
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
		@endsection
    @endcomponent
@endsection
@push('custom-scripts')
<script type="text/javascript">
	$(document).ready(function() {
        var draggable_target_class;
        var droppable_target_class;
        var key = 0;
        var date = "{{$start_date}}";
        var update_phase_url = "";
		if(date == '' ) {
			date = moment(new Date()).format("MM-DD-YYYY");
		} else {
			date = moment(new Date(date)).format("MM-DD-YYYY");
		}
		var $datepicker = $('.select-date');
		$datepicker.datepicker({'autoclose':'close'});
		$datepicker.datepicker('setDate', date);

		//jquery code to add the phase additon box on plus button
		var phaseIndex = $('.inner-col .phase-data').index();
		var phaseLength = $('.inner-content .phase-data').length;
		var phaseClone = $(".inner-col.additional-div").clone(true);
        var total_phases = parseInt($('#total_phase_count').val());
		$(".add-phase-link").click(function () {
			var offsetTop = $(".inner-content").innerHeight()
			$(".modal-wrap").animate({
				scrollTop : offsetTop 
			});
			var phase_box = '';
			var total_index = "{{$total_phases + 1}}";
            if(isNaN(total_phases)) {
                total_phases = 0;
            }
			phase_box += '<div class="inner-col phase-data " id="additional{{$total_phases + 1}}">' + 
				'<a class="phase-wrap add-phase">' + 
					'<div class="phase-header">' +
						'<h2><button class="delete-phase"><img src="{{ asset("assets/images/delete.svg") }}" alt=""></button> Phase <i class="count"> '+ (total_phases + 1) +'</i></h2>' + 
						'<div class="form-group">' + 
							'<select class="custom_dropdown custom_dropdown-secondary duration duration_class" id="" name="phase[' + (total_phases + 1) + '][duration]" >' + 
								'<option value="">Dauer</option>' ;
            for(i = 1; i < 11; i++) {
                phase_box += '<option value="'+i+'">'+i+' {{ __("lang.week") }}</option>';
            }
			phase_box += '</select>'+
						'</div>' + 
					'</div>' + 
					'<div class="add-phase-form">' + 
						'<div class="form-group">' + 
							'<input type="text" name="phase['+ ( total_phases + 1 ) + '][name]" class="form-control name" placeholder="{{ __("lang.name") }}" >' + 
						'</div>' +
					'</div>' +
				'</a>' +
				'</div>	';
			if(phaseLength == 0) {
				var parent = $('.modal-wrap .inner-content .custom-inner-col');
			} else {
				var parent = $(".inner-col.phase-data").last();
			}
			$(phase_box).insertAfter(parent).attr("id","additional"+ (total_phases + 1)).show().each(function(){
			$(this).find(".phase-header h2 i").html(total_phases + 1);
			$(this).find(".dk-select").remove();
				$(this).find(".custom_dropdown").dropkick({
					mobile: true
				});
				$(this).find('.phase-header select.duration_class').attr('name', "phase["+( total_phases + 1)+"][duration]");
				$(this).find('.add-phase-form .name').attr('name', "phase["+( total_phases + 1)+"][name]");
				// $(this).find('.add-phase-form .description').attr('name', "phase["+( phaseLength + 1)+"][description]");
			});
			phaseIndex ++;
            phaseLength ++;
            total_phases++;
        });

        //to view the image
        $("#exerciseImage").change(function() {
            readURL(this);
        });

        //check the private key validation as per the active organization
        $('.save-exercise').on('click', function(event){
            event.preventDefault();
            var formData = new FormData();
            //to append the form data to save the exercises
            formData.append('name', $("#name").val());
            formData.append('description', $("#description").val());
            formData.append('duration', $("#duration").val());
            formData.append('type', $("#type").val());
            formData.append('material', $("#material").val());
            formData.append('exerciseImage', $("#exerciseImage").prop('files')[0]);
            formData.append('exercise_group_id', $("#phase-exercise-group-id").val());
            //ajax call to save the exercises
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                async: true,
                dataType: "json",
                url: "{{ url('/save-phase-exercises') }}",
                data: formData,
                type: 'post',
                processData: false,
                contentType: false,
                success:function(response){
                    $('.add-exe-btn').show();
                    $('#add-exercise-form').hide();
                    $('#preview_image').attr('src',"{{ asset('assets/images/camera.svg') }}");
                    //get all the exercises listing
                    getExercises($('#phase-exercise-group-id').val());
                    $('#name').val('');
                    $('.custom-toast').hide();
                    //to set the form-field to empty
                    $('#add-exercise-form select, #add-exercise-form textarea').each(
                        function(index){  
                            $(this).val('');
                        }
                    );
                }, 
                error: function(err_res) {
                    if(err_res.status == 400 && err_res.responseJSON.errors != '') {
                        $('.custom-toast').show().delay(5000).slideUp(800);
                        $(".modal-wrap").animate({
                            scrollTop : 0 
                        });
                    }
                }
            });
        });

        //to show the input in limitation modal
        $('body').on('click', '.heading-div .edit-icon', function(e) {
            $(this).parents(".edit-label").addClass("edit-txt");
            var span_txt = $(this).siblings(".input-txt").text();
            $(this).siblings(".form-control.phase_title").val(span_txt);
            $(this).siblings(".form-control.phase_title").focus();
        });

        //to show the input in limitation modal
        $('body').on('click', '.limitation-list .btn-wrap .edit-btn', function(e) {
            $(this).parents("tr").addClass("edit-data");
        });
        //to remove the row in limitation modal
        $("body").delegate(".limitation-list .btn-wrap .delete-btn", "click", function() {
            $(this).parents("tr").remove();
            var remaining_rows = $(".limitation-list table tbody tr").length;        
            if(remaining_rows) {
                $('.limitation-list').removeClass('empty-record-table');
            } else {
                $('.limitation-list').addClass('empty-record-table');
            }
        });

        //to remove the phase in phase setting modal
        $("body").delegate(".phase-wrap .delete-phase", "click", function() {
            $(this).closest(".phase-data").remove();
            phaseLength = $('.inner-content .phase-data').length;
        });

        //to add the row for limitation
        $('body').on('click', '.limitation-block .add-limitation-btn', function(e) {
            var total_duration  = parseInt($(this).attr('data-duration'));
            var total_row = parseInt($('#row_count_for_limitation').val()) + 1;
            $('#row_count_for_limitation').val(total_row);
            var new_row = "";
            new_row += '<tr class="edit-data new-row" data-limitation_id=' + total_row + '>';
            new_row += '<td>';
            new_row += '<div class="wrap-div">';
            new_row += '<div class="week-label">';
            new_row += '<span>1. {{ __("lang.week") }}</span>';
            new_row += '<div class="form-group">';
            new_row += '<select class="custom_dropdown start_week_dropdown current_week_'+total_row+'"  name="limitation['+total_row+'][start_week]">';
            for (i = 1; i <= total_duration; i++) {
                new_row += '<option value="'+i+'">'+i+' {{ __("lang.week") }}</option>';
            }
            new_row += ' </select>';
            new_row += ' </div>';
            new_row += ' </div>';
            new_row += ' <div class="{{ __("lang.day") }}-label">';
            new_row += '<span>{{ __("lang.day") }} 3</span>';
            new_row += '<div class="form-group">';
            new_row += '<select class="custom_dropdown start_day_dropdown current_day_'+total_row+'" name="limitation['+total_row+'][start_day]">';
            for (i = 1; i < 8; i++) {
                new_row += '<option value="'+i+'">{{ __("lang.day") }} '+ i +'</option>';
            }
            new_row += '</select>';
            new_row += '</div>';
            new_row += '</div>';
            new_row += '</div>';
            new_row += '</td>';
            new_row += '<td>';
            new_row += '<div class="wrap-div">';
            new_row += '<div class="week-label">';
            new_row += '<span>1. {{ __("lang.week") }}</span>';
            new_row += '<div class="form-group">';
            new_row += '<select class="custom_dropdown end_week_dropdown current_week_'+total_row+'" name="limitation['+total_row+'][end_week]">';
            for (i = 1; i <= total_duration; i++) {
                new_row += '<option value="'+i+'">'+ i +' {{ __("lang.week") }} </option>';
            }
            new_row += '</select>';
            new_row += '</div>';
            new_row += '</div>';
            new_row += '<div class="{{ __("lang.day") }}-label">';
            new_row += '<span>{{ __("lang.day") }} 3</span>';
            new_row += '<div class="form-group">';
            new_row += '<select class="custom_dropdown end_day_dropdown current_day_'+total_row+'"  name="limitation['+total_row+'][end_day]">';
            for (i = 1; i < 8; i++) {
                new_row += '<option value="'+i+'">{{ __("lang.day") }} '+i+'</option>';
            }
            new_row += '</select>';
            new_row += '</div>';
            new_row += '</div>';
            new_row += '</div>';
            new_row += '</td>';
            new_row += '<td class="limitation-description">';
            new_row += '<div class="form-group">';
            new_row += '<input type="text" class="form-control" name="limitation['+total_row+'][name]" value="">';
            new_row += '<i class="edit-icon"><img src="{{ asset("assets/images/edit-icon.svg") }}" alt=""></i>';
            new_row += '</div>';
            new_row += '</td>';
            new_row += '<td>';
            new_row += '<div class="btn-wrap">';
            new_row += '<a href="#" title="edit" class="edit-btn"><i><img src="{{ asset("assets/images/edit-icon.svg") }}"></i></a>';
            new_row += '<a href="#" title="delete" class="delete-btn"><i><img src="{{ asset("assets/images/delete.svg") }}"></i></a>';
            new_row += '</div>';
            new_row += '</td>';
            new_row += '</tr>';
            $(this).parents(".limitation-block").find(".limitation-list table tbody").append(new_row);

            dropkickReInit(total_row);


            var remaining_rows = $(".limitation-list table tbody tr").length;        
            if(remaining_rows) {
                $('.limitation-list').removeClass('empty-record-table');
            } else {
                $('.limitation-list').addClass('empty-record-table');
            }
            $(".custom_dropdown").dropkick({
                mobile: true
            });
        });
        //to load the chart
        loadChart($(".phase-list-block li .nav-link.active").parent().attr("data-phase-active"));
        
    });
    
    //to save the updated phase details and limitation
    $('.update-phase').on('click', function() {
        var form = $('#update-phase-setting');
        form.validate({
            ignore:":hidden" ,
            rules: {
                phase_name: {
                    required: true,
                },
                week_duration: {
                    required: true,
                },
                end_date: {
                    required: true,
                },
                phase_objectives: {
                    required: true,
                },
                practical_exercises: {
                    required: true,
                },
            },
            highlight: function (element) { // hightlight error inputs
				$(element)
					.parents('div.form-group').addClass('has-error') // set error class to the control group
			},
			unhighlight: function (element) { // un-hightlight error inputs
				$(element)
					.parents('div .form-group').removeClass('has-error'); 
			},
			errorPlacement: function(error, element) {
				element.parents('div .form-group').addClass('has-error');
			},
			success: function() {
				// console.log(form.serializeArray());
			},
        });
        $('input[name^="limitation"]').filter('input[name$="[name]"]').each(function() {
			$(this).rules("add", {
				required: true,
			});
        });
        if(form.valid()) {
			$.ajax({
				headers: {
					'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
				},
				type: "POST",
                url: update_phase_url,
                dataType: "json",
				data: form.serialize(),
				success: function( data ) {
                    if(data.success != '' && data.status == 200) {
                        location.reload();
                    }
				},
				error : function(data) {
					if(data.status == 400 && data.responseJSON.errors != '') {
                        $('.error-message').text(data.responseJSON.errors);
						$('.custom-phase-toast').show().delay(5000).slideUp(800);
						$(".modal-wrap").animate({
							scrollTop : 0 
						});
					}
				}
			});
        }
        
    })

	//action perfomed on save button
	$('.save').on('click', function() {
		var form = $('.add-phase-form');
		form.validate({
			ignore:":hidden" ,
			rules: {
				indication: { 
					required: true,
				},
				start_date: {
					required: true,
				},
			},
			highlight: function (element) { // hightlight error inputs
				$(element)
					.parents('div.form-group').addClass('has-error') // set error class to the control group
			},
			unhighlight: function (element) { // un-hightlight error inputs
				$(element)
					.parents('div .form-group').removeClass('has-error'); 
			},
			errorPlacement: function(error, element) {
				element.parents('div .form-group').addClass('has-error');
			},
			success: function() {
				// console.log(form.serializeArray());
			},
		});

		//validation changes of dynamic generated input
		$('select[name^="phase"]').filter('select[name$="[duration]"]').each(function() {
			$(this).rules("add", {
				required: true,
			});
		});
		$('input[name^="phase"]').filter('input[name$="[name]"]').each(function() {
			$(this).rules("add", {
				required: true,
			});
		});

		//Ajax call to save the phase data
		if(form.valid()) {
			var id =  "{{ $id }}";
			var url = "{{ url('save-therapy-plans') }}";
			$.ajax({
				headers: {
					'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
				},
				type: "POST",
				url: url,
				data: { id:id, data:form.serialize() }, 
				success: function( data ) {
					location.reload();
				},
				error : function(data) {
					if(data.status == 400 && data.responseJSON.errors != '') {
						$('.custom-toast').show().delay(5000).slideUp(800);
						$(".modal-wrap").animate({
							scrollTop : 0 
						});
					}
				}
			});
		}
	});

    function dropkickReInit(total_row) {
        //to select the end week on selection on start week
        $(".start_week_dropdown.current_week_"+total_row).dropkick({
            change: function () {
                console.log(total_row);
                value = this.value;
                var _this = $("select.end_week_dropdown.current_week_"+total_row);
                $(_this).val(value);

                // prevent smaller value than start date
                $("select.end_week_dropdown.current_week_"+total_row+" option").removeAttr('disabled');
                $("select.end_week_dropdown.current_week_"+total_row+" option").each(function()
                {
                    if($(this).val() < value) {
                        $(this).attr('disabled', 'disabled');
                    }
                });
                $('select').dropkick('refresh');
            }
        });
        //to select the end day on selection on start day
        $(".start_day_dropdown.current_day_"+total_row).dropkick({
            change: function () {
                value = this.value;
                var _this = $("select.end_day_dropdown.current_day_"+total_row);
                $(_this).val(value);

                // prevent smaller value than start date
                $("select.end_day_dropdown.current_day_"+total_row+" option").removeAttr('disabled');
                $("select.end_day_dropdown.current_day_"+total_row+" option").each(function()
                {
                    if($(this).val() < value) {
                        $(this).attr('disabled', 'disabled');
                    }
                });
                $('select').dropkick('refresh');
            }
        });

        //to select the end day on selection on start day
        $(".end_week_dropdown.current_week_"+total_row).dropkick({
            change: function () {
                value = this.value;
                var _this = $("select.start_week_dropdown.current_week_"+total_row);
                $(_this).val();
                if(value > $(_this).val()) {
                    $("select.end_day_dropdown.current_day_"+total_row+" option").removeAttr('disabled');
                } else {
                    $("select.end_day_dropdown.current_day_"+total_row+" option").each(function()
                    {
                        if($(this).val() < value) {
                            $(this).attr('disabled', 'disabled');
                        }
                    });
                }
                $('select').dropkick('refresh');
            }
        });
    }

	//function to inline Edit
	function inlineEdit(data, type, key) {
		$(data).hide();
		$('#input_'+type+'_'+key).show();
		$('#input_'+type+'_'+key).children('.'+type).focus();
		var offsetTop = $(".inner-content").innerHeight()
	}

	//function to add one week to phase
	function addOneWeek(data, e, type) {
		e.preventDefault();
        if(type == 'add-phase-model') {
            var count = parseInt($(data).parent().parent().find('.phase-header span .duration_weeks').text());
            $(data).parent().parent().find('.phase-header span .duration_weeks').empty('');
            $(data).parent().parent().find('.phase-header span .duration_weeks').text(parseInt(count) + 1);
            $(data).parent().parent().find('.duration_input').attr('value', parseInt(count) + 1);
        } else if (type == 'phase-setting-model') {
            var week_duration = parseInt($('#week_duration').val()) + 1;
            var end_date = $('#end_date').val();
            var updated_date = moment(end_date).add(1, 'w');
            $('#week_duration').val(week_duration);
            $('#end_date').val(updated_date);
            $('.end_date').text(updated_date.format("D.M.YYYY"));
        }
		
	}

	//function to remove one week to phase
	function removeOneWeek(data, e, type) {
		e.preventDefault();
        if(type == 'add-phase-model') {
            var count = parseInt($(data).parent().parent().find('.phase-header span .duration_weeks').text());
            if(count >= 2) {
                $(data).parent().parent().find('.phase-header span .duration_weeks').empty('');
                $(data).parent().parent().find('.phase-header span .duration_weeks').text(parseInt(count) - 1);
                $(data).parent().parent().find('.duration_input').attr('value', parseInt(count) - 1);
            }
        } else if (type == 'phase-setting-model') {
            var week_dur = $('#week_duration').val();
            if(week_dur >= 2) {
                var week_duration = parseInt(week_dur) - 1;
                var end_date = $('#end_date').val();
                var updated_date = moment(end_date).subtract(1, 'w');
                $('#week_duration').val(week_duration);
                $('#end_date').val(updated_date);
                $('.end_date').text(updated_date.format("D.M.YYYY"));
            }
        }
    }

    //function to open the limitation modal
    function openLimitationModal(data) {
        var phase_active_index = $(".phase-list-block li .nav-link.active").parent().index();
        var phase_data_id = $(".phase-list-block li .nav-link.active").parent().attr("data-phase-active");
        $('.active_phase_key').text(phase_active_index + 1); // to set the title of the exercises group modal
        $('.active_phase').val(phase_data_id); // to set the value of the current phase
        $('.append-phase-details').empty();
        $('#edit-phase-modal').show();
        getPhaseDetails(phase_data_id);
    }

    //function to get the phase details
    function getPhaseDetails(phase_data_id) {
        
        $('#row_count_for_limitation').val(0);
        var user_id = "{{ $id }}";
        var url = "{{ url('get-phase-details') }}" + '/' + phase_data_id ;
        //load the exercise groups data using ajax
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: "POST",
            url: url,
            data: {
                user_id: user_id
            },
            success: function( data ) {
                $('.previous_btn').hide();
                $('.next_btn').hide();
                if (data.status == 200 && data.success != '') {
                    update_phase_url = "{{ url('save-phase-details') }}" + '/' + phase_data_id ;
                    $('.append-phase-details').empty();
                    var data = data.success;
                    var phase_key = '';
                    var previous_phase_id = '';
                    var next_phase_id = '';
                    $.each( data.ids, function( key, value ) {
                        if(value == data.id) {
                            phase_key = key;
                        }
                    });
                    if(data.ids[phase_key-1] != '' && data.ids[phase_key-1] != undefined) {
                        previous_phase_id = data.ids[phase_key-1];
                        $('.previous_btn').show();
                        $(".previous_btn").attr("onclick","getPhaseDetails("+previous_phase_id+")");
                    }
                    if(data.ids[phase_key+1] != '' && data.ids[phase_key+1] != undefined) {
                        next_phase_id = data.ids[phase_key+1];
                        $('.next_btn').show();
                        $(".next_btn").attr("onclick","getPhaseDetails("+next_phase_id+")");
                    }
                    $('.active_phase_key').text(phase_key + 1);
                    //to append the phase modal on phase change
                    var html = '';
                    html += '<div class="heading-div">';
                    html += '<h2>Phase <span class="active_phase_key">'+( phase_key + 1 )+'</span></h2>';
                    html += '<div class="form-group edit-label">';
                    html += '<span class="input-txt phase_name">'+data.name+'</span>';
                    html += '<input type="text" class="form-control phase_title" name="phase_name" value="'+data.name+'">';
                    html += '<i class="edit-icon"><img src="{{ asset("assets/images/edit-icon.svg") }}" alt=""></i>';
                    html += '<input type="hidden" class="form-control" name="week_duration" id="week_duration" value="'+data.duration+'">';
                    html += '<input type="hidden" class="form-control" name="start_date" id="start_date" value="'+data.start_date+'">';
                    html += '<input type="hidden" class="form-control" name="end_date" id="end_date" value="'+data.end_date+'">';
                    html += '</div>';
                    html += '<div class="duration-wrap">';
                    html += '<div class="left-div">';
                    html += '<i><img src="{{ asset("assets/images/date.svg") }}"></i>';
                    html += '<span><span class="start_date">'+moment(data.start_date).format("D.M.YYYY")+'</span> <em> {{ __("lang.to") }}</em> <span class="end_date"> '+moment(data.end_date).format("D.M.YYYY")+'</span></span>';
                    html += '</div>';
                    html += '<div class="right-div">';
                    html += '<div class="btn-wrap">';
                    html += '<a href="#" class="btn caribbean-green-btn" onclick="addOneWeek(this, event, \'phase-setting-model\')" title="+ 1 {{ __("lang.week") }}">+ 1 {{ __("lang.week") }}</a>';
                    html += '<a href="#" class="btn caribbean-green-btn" onclick="removeOneWeek(this, event, \'phase-setting-model\')" title="- 1 {{ __("lang.week") }}">- 1 {{ __("lang.week") }}</a>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    html += '<span class="title">{{ __("lang.phase_objectives") }}:</span>';
                    html += '<div class="form-group edit-label edit-content">';
                    html += '<p class="input-txt">'+(data.phase_objectives != null ? data.phase_objectives : '')+'</p>';
                    html += '<textarea class="form-control phase_title" name="phase_objectives" value="'+(data.phase_objectives != null ? data.phase_objectives : '')+'">'+(data.phase_objectives != null ? data.phase_objectives : '')+'</textarea>';
                    html += '<i class="edit-icon"><img src="{{ asset("assets/images/edit-icon.svg") }}" alt=""></i>';
                    html += '</div>';
                    html += '<span class="title">{{ __("lang.practical-exercises") }}:</span>';
                    html += '<div class="form-group edit-label edit-content">';
                    html += '<p class="input-txt">'+(data.practical_exercises != null ? data.practical_exercises : '')+'</p>',
                    html += '<textarea class="form-control phase_title" name="practical_exercises" value="'+(data.practical_exercises != null ? data.practical_exercises : '')+'">'+(data.practical_exercises != null ? data.practical_exercises : '')+'</textarea>',
                    html += '<i class="edit-icon"><img src="{{ asset("assets/images/edit-icon.svg") }}" alt=""></i>',
                    html += '</div>',
                    html += '</div>',
                    html += '<div class="limitation-block">',
                    html += '<div class="heading-block">',
                    html += '<div class="left-content">',
                    html += '<spa class="title">{{ __("lang.limitations") }}:</span>',
                    html += '</div>',
                    html += '<div class="right-content">',
                    html += '<a href="#" title="" class="btn caribbean-green-btn add-limitation-btn" data-duration="'+data.duration+'"><i><img src="{{ asset("assets/images/plus-white.svg") }}"></i>{{ __("lang.add-limitations") }}</a>',
                    html += '</div>',
                    html += ' </div>',
                    html += '<div class="limitation-list empty-record-table">',
                    html += '<table>',
                    html += '<thead><tr><th>{{ __("lang.week-of") }}</th><th>{{ __("lang.week-to") }}</th><th>{{ __("lang.limitations") }}</th><th></th></tr></thead>',
                    html += '<tbody>';
                    //to append the limitations in the table
                    if(data.limitations.length) {
                       var limitations = data.limitations;
                       var row_count = 0;
                       $.each( limitations, function( key, value ) {
                            row_count = row_count + 1;
                            $('#row_count_for_limitation').val(row_count);
                            html += '<tr data-limitation_id = '+(key + 1)+' >',
                            html += '<td><div class="wrap-div">',
                            html += '<div class="week-label">',
                            html += '<span>'+value.start_week+'. {{ __("lang.week") }}</span>',
                            html += '<input type="hidden" class="form-control" name="limitation['+key+'][existing_id]" value="'+value.id+'">';
                            html += ' <div class="form-group">',
                            html += '<select class="custom_dropdown limitation_dropdown start_week_dropdown current_week_'+key+'" id="start_week_select" name="limitation['+key+'][start_week]">';
                            for (i = 1; i <= parseInt(data.duration); i++) {
                                html += '<option value="'+i+'">'+i+'. {{ __("lang.week") }}</option>';
                            }
                            html += '</select></div></div>',
                            html += '<div class="tag-label">',
                            html += '<span>{{ __("lang.day") }} '+value.start_day+'</span>',
                            html += '<div class="form-group">',
                            html += '<select class="custom_dropdown limitation_dropdown start_day_dropdown current_day_'+key+'" name="limitation['+key+'][start_day]">';
                            for (i = 1; i < 8; i++) {
                                html += '<option value="'+i+'">{{ __("lang.day") }} '+ i +'</option>';
                            }
                            html += '</select></div></div></div></td>',
                            html += '<td><div class="wrap-div">',
                            html += '<div class="week-label">',
                            html += '<span>'+value.end_week+'. {{ __("lang.week") }}</span>',
                            html += ' <div class="form-group">',
                            html += '<select class="custom_dropdown limitation_dropdown end_week_dropdown current_week_'+key+'" name="limitation['+key+'][end_week]">';
                            for (i = 1; i <= parseInt(data.duration); i++) {
                                html += '<option value="'+i+'">'+i+'. {{ __("lang.week") }}</option>';
                            }
                            html += '</select></div></div>',
                            html += '<div class="tag-label">',
                            html += '<span>{{ __("lang.day") }} '+value.end_day+'</span>',
                            html += '<div class="form-group">',
                            html += '<select class="custom_dropdown limitation_dropdown end_day_dropdown current_day_'+key+'" name="limitation['+key+'][end_day]">';
                            for (i = 1; i < 8; i++) {
                                html += '<option value="'+i+'">{{ __("lang.day") }} '+i+'</option>';
                            }
                            html += '</select></div></div></div></td>',
                            html += '<td class="limitation-description">',
                            html += '<span class="detail-label">'+value.name+'</span>',
                            html += '<div class="form-group">',
                            html += '<input type="text" class="form-control" name="limitation['+key+'][name]" value="'+value.name+'">',
                            html += '<i class="edit-icon"><img src="{{ asset("assets/images/edit-icon.svg") }}" alt=""></i>',
                            html += '</div></td>',
                            html += '<td>',
                            html += '<div class="btn-wrap">',
                            html += '<a href="#" title="edit" class="edit-btn" class="edit-btn"><i><img src="{{ asset("assets/images/edit-icon.svg") }}"></i></a>',
                            html += '<a href="#" title="delete" class="delete-btn" class="edit-btn"><i><img src="{{ asset("assets/images/delete.svg") }}"></i></a>',
                            html += '</div></td>',
                            html += '</tr>';
                        });
                    }
                    
                    html += '</tbody>',
                    html += '</table>',
                    html += '<div class="no-record"> No Record found </div>',
                    html += '</div>',
                    html += '</div>',
                    $('.append-phase-details').append(html);
                    //to set the value of the limitations                     
                    $.each( limitations, function( key, value ) {
                        $('select[name="limitation['+key+'][start_week]"]').val(value.start_week);
                        $('select[name="limitation['+key+'][start_day]"]').val(value.start_day);
                        $('select[name="limitation['+key+'][end_week]"]').val(value.end_week);
                        $('select[name="limitation['+key+'][end_day]"]').val(value.end_day);
                        dropkickReInit(key);
                    });
                    var remaining_rows = $(".limitation-list table tbody tr").length;        
                    if(remaining_rows) {
                        $('.limitation-list').removeClass('empty-record-table');
                    } else {
                        $('.limitation-list').addClass('empty-record-table');
                    }
                    $(".custom_dropdown").dropkick({mobile:!0});
                }
            },
            error : function(data) {
                if(data.status == 400 && data.responseJSON.errors != '') {
                    $('.custom-toast').show().delay(5000).slideUp(800);
                    $(".modal-wrap").animate({
                        scrollTop : 0 
                    });
                }
            }
        });
    }

    //function to load the exercise groups  using ajax call on phase selected
    function getExerciseGroups() {
        $('.draggable-block').find('.' + draggable_target_class + '.draggable-list').empty();
        $('.' + droppable_target_class + ".droppable-list").removeClass("dropping-list");
        $('.' + droppable_target_class + '.droppable-list').find('.dragged-item').remove();
        $('.' + droppable_target_class + '.droppable-list').find('.draggable-title').remove();
        var phase_active_index = $(".phase-list-block li .nav-link.active").parent().index();
        var phase_data_id = $(".phase-list-block li .nav-link.active").parent().attr("data-phase-active");
        $('.active_key').text(phase_active_index + 1); // to set the title of the exercises group modal
        $('.active_phase').val(phase_data_id); // to set the value of the current phase
        var url = "{{ url('get-exercise-groups') }}";
        //load the exercise groups data using ajax
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: "POST",
            url: url,
            data: { 'phase_id':phase_data_id}, 
            success: function( data ) {
                var exercises_data = data;
                if(exercises_data.selected.length > 0) {
                    $('.' + droppable_target_class + ".droppable-list").addClass("dropping-list"); //if selected array is not empty then add this    
                }
                //get all the exercises and set in the exercise group listing
                $.each( exercises_data.all, function( key, value ) {
                    var exercises_html = '';
                    var selected_array = exercises_data.selected_exe_array; //selected exercises array
                    exercises_html += '<span class="list-title">'+key.toUpperCase()+'</span>';
                    $.each( value, function( key, data ) {
                        if(selected_array.includes(data.id)) {
                            //if exercises is selected then add class inactive for hide
                            exercises_html += '<div href="#" class="draggable-item inactive" data-attr = "'+ data.id +'">';
                        } else {
                            exercises_html += '<div href="#" class="draggable-item" data-attr = "'+ data.id +'">';   
                        }
                        exercises_html += '<i><img src="{{ url("/storage")}}'+data.image+'" alt=""></i>';
                        exercises_html += '<span class="title">'+ data.group_name +'</span>';
                        exercises_html += '</div>';
                    });
                    $('.draggable-block').find('.' + draggable_target_class + '.draggable-list').append(exercises_html);
                });
                //show the selected exercises in the dropped box
                $.each( exercises_data.selected, function( key, value ) {
                    var html = "";
                    html += '<div title="" class="dragged-item"  data-id=' + value.exercise_group_list.id + '>';
                    html +=
                        '<a href="#" title="delete" class="delete-btn"><img src="{{ asset('assets/images/delete.svg') }}"></a>';
                    html += '<div class="left-div">';
                    html += '<i><img src="{{ url("/storage")}}' + value.exercise_group_list.image + '" alt=""></i>';
                    html += '</div>';
                    html += '<div class="right-div">';
                    html += '<label>' + value.exercise_group_list.group_name + '</label>';
                    html += '<input type="hidden" name="exercise['+value.exercise_group_list.id+'][id]" value="'+value.exercise_group_list.id+'"/>';
                    html += '<div class="dropdown-list">';
                    html += '<select class="custom_dropdown select_'+value.exercise_group_list.id+'" name="exercise['+value.exercise_group_list.id+'][per_week]">';
                    html += '<option value="1">1 x per week</option>';
                    html += '<option value="2">2 x per week</option>';
                    html += '<option value="3">3 x per week</option>';
                    html += '<option value="4">4 x per week</option>';
                    html += '<option value="5">5 x per week</option>';
                    html += '<option value="6">6 x per week</option>';
                    html += '<option value="7">7 x per week</option>';
                    html += '<option value="8">8 x per week</option>';
                    html += '<option value="9">9 x per week</option>';
                    html += '<option value="10">10 x per week</option>';
                    html += '</select>';
                    html += '<select class="custom_dropdown" name="exercise['+value.exercise_group_list.id+'][estimated_course_time]">';
                    html += '<option value="10">10 Minutes</option>';
                    html += '<option value="20">20 Minutes</option>';
                    html += '<option value="30">30 Minutes</option>';
                    html += '</select>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                    $('.' + droppable_target_class + '.droppable-list').append(html);
                    //to set the value of estimated_course_time and per_week
                    $('select[name="exercise['+value.exercise_group_list.id+'][per_week]"]').val(value.per_week);
                    $('select[name="exercise['+value.exercise_group_list.id+'][estimated_course_time]"]').val(value.estimated_course_time);
                    $(".custom_dropdown").dropkick({mobile:!0});
                });
                //to apply the draggable js
                addDraggable();
                //show/hode the label fro by default selected exercise groups/exercises
                showHidePhaseLabel();
            },
            error : function(data) {
                if(data.status == 400 && data.responseJSON.errors != '') {
                    $('.custom-toast').show().delay(5000).slideUp(800);
                    $(".modal-wrap").animate({
                        scrollTop : 0 
                    });
                }
            }
        });
    }

    //function to get the exercises of exercise gropus
    function getExercises(exercise_group_id) {
        $('#phase-exercise-group-id').val(exercise_group_id);
        $('.draggable-block').find('.' + draggable_target_class + '.draggable-list').empty();
        $('.' + droppable_target_class + ".droppable-list").removeClass("dropping-list");
        $('.' + droppable_target_class + '.droppable-list').find('.dragged-item').remove();
        $('.' + droppable_target_class + '.droppable-list').find('.draggable-title').remove();
        var phase_active_index = $(".phase-list-block li .nav-link.active").parent().index();
        var phase_data_id = $(".phase-list-block li .nav-link.active").parent().attr("data-phase-active");
        $('.active_key').text(phase_active_index + 1); // to set the title of the exercises group modal
        $('.active_phase').val(phase_data_id); // to set the value of the current phase
        var url = "{{ url('get-exercises') }}";
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            type: "POST",
            url: url,
            data: { 'exercise_group_id':exercise_group_id}, 
            success: function( data ) {
                var exercises = data;
                if(exercises.selected.length > 0) {
                    $('.' + droppable_target_class + ".droppable-list").addClass("dropping-list"); //if selected array is not empty then add this    
                }
                //get all the exercises and set in the exercises listing for already selected
                $.each( exercises.all_exercises, function( key, value ) {
                    var exercises_html = '';
                    exercises_html += '<span class="list-title">'+key.toUpperCase()+'</span>';
                    $.each( value, function( key, data ) {
                        exercises_html += '<div href="#" class="draggable-item" data-attr = "'+ data.id +'">';   
                        exercises_html += '<i><img src="'+data.image_url+'" alt=""></i>';
                        exercises_html += '<span class="title">'+ data.name +'</span>';
                        exercises_html += '</div>';
                    });
                   
                    $('.draggable-block').find('.' + draggable_target_class + '.draggable-list').append(exercises_html);
                });
                //show the selected exercises in the dropped box
                $.each( exercises.selected, function( key, value ) {
                    var html = "";
                    if(value.phase_exercise_list_id) {
                        //added for exercises
                        html += '<div title="" class="dragged-item"  data-id=' + value.phase_exercise_group_id + '>';
                        html += '<a href="#" title="delete" class="delete-btn"><img src="{{ asset('assets/images/delete.svg') }}"></a>';
                        html += '<div class="left-div">';
                        html += '<i><img src="{{ url("/storage")}}' + value.phase_exercise_list.image + '" alt=""></i>';
                        html += '</div>';
                        html += '<div class="right-div">';
                        html += '<label>' + value.phase_exercise_list.name + '</label>';
                        html += '<input type="hidden" name="exercise['+key+'][id]" value="'+value.phase_exercise_list.id+'"/>';
                        html += '<input type="hidden" name="exercise['+key+'][exist_id]" value="'+value.id+'"/>';
                        html += '<div class="dropdown-list">';
                        html += '<select class="custom_dropdown select_'+value.phase_exercise_list.id+'" name="exercise['+key+'][duration]">';
                        for(var i = 1; i <= 25 ; i++) {
                            html += '<option value="'+i+'">'+i+'</option>';    
                        }
                        html += '</select>';
                        html += '<select class="custom_dropdown" name="exercise['+key+'][type]">';
                        html += '<option value="1">{{ __('lang.repetition') }}</option>';
                        html += '<option value="2">{{ __('lang.duration') }}</option>';
                        html += '</select>';
                        html += '</div>';
                        html += '</div>';
                        html += '</div>';
                        html += '</div>';
                    }
                    else {
                        //added for pauses
                        html += '<div class="draggable-title">';
                        html += '<a href="#" title="delete" class="delete-btn"><img src="{{ asset("assets/images/delete.svg") }}"></a>';
                        html += '<span><i><img src="{{ asset("assets/images/calm.svg") }}"></i> Pause</span>';
                        html += '<div class="left-div">';
                        html += '</div>';
                        html += '<div class="dropdown-list">';
                        html += '<input type="hidden" name="exercise['+key+'][exist_id]" value="'+value.id+'"/>';
                        html += '<select class="custom_dropdown duration" name="exercise['+key+'][pause_time]">';
                        html += '<option value="10">10</option>';
                        html += '<option value="20">20</option>';
                        html += '<option value="30">30</option>';
                        html += '</select>';
                        html += 'seconds';
                        html += '</select>';
                        html += '</div>';
                    }
                    $('.' + droppable_target_class + '.droppable-list').append(html);
                    //to set the value of estimated_course_time and per_week
                    $('select[name="exercise['+key+'][duration]"]').val(value.duration);
                    $('select[name="exercise['+key+'][type]"]').val(value.type);
                    $('select[name="exercise['+key+'][pause_time]"]').val(value.duration);
                    $(".custom_dropdown").dropkick({mobile:!0});
                });

                //to set the value og estiimated_course_time, round and per_week
                $('select[name="estimated_course_time"] option[value="'+exercises.all_data.estimated_course_time+'"]').attr("selected", true);
                $('select[name="per_week"] option[value="'+exercises.all_data.per_week+'"]').attr("selected", true);
                $('select[name="round"] option[value="'+exercises.all_data.round+'"]').attr("selected", true);
                $('select').dropkick('refresh');

                //to apply the draggable js
                addDraggableForPhaseExercises();
                //show/hode the label fro by default selected exercise groups/exercises
                showHidePhaseLabel();
            },
            error : function(data) {
                if(data.status == 400 && data.responseJSON.errors != '') {
                    $('.custom-toast').show().delay(5000).slideUp(800);
                    $(".modal-wrap").animate({
                        scrollTop : 0 
                    });
                }
            }
        });
    }

    // call on ajax success after exercise groups are fetched
    function addDraggable() {
        $('.draggable-item').draggable({
            revert: true,
            placeholder: true,
            droptarget: '.droppable-list',
            drop: function(evt, droptarget) {
                $('.' + droppable_target_class + ".droppable-list").addClass("dropping-list");
                if(droppable_target_class == "exercise-group-droppable") {
                    $(this).addClass("inactive");
                }
                // $(this).appendTo(droptarget).draggable('destroy');
                var exercise_group_list_id = $(this).attr("data-attr");
                var this_img = $(this).find("i").children("img").attr("src");
                var this_title = $(this).children(".title").text();
                var html = "";
                html += '<div href="#" title="" class="dragged-item" data-id=' + exercise_group_list_id + '>';
                html += '<a href="#" title="delete" class="delete-btn"><img src="{{ asset('assets/images/delete.svg') }}"></a>';
                html += '<div class="left-div">';
                html += '<i><img src="' + this_img + '" alt=""></i>';
                html += '</div>';
                html += '<div class="right-div">';
                html += '<label>' + this_title + '</label>';
                html += '<input type="hidden" name="exercise['+exercise_group_list_id+'][id]" value="'+exercise_group_list_id+'"/>';
                html += '<div class="dropdown-list">';
                html += '<select class="custom_dropdown" name="exercise['+exercise_group_list_id+'][per_week]">';
                html += '<option value="1">1 x per week</option>';
                html += '<option value="2">2 x per week</option>';
                html += '<option value="3">3 x per week</option>';
                html += '<option value="4">4 x per week</option>';
                html += '<option value="5">5 x per week</option>';
                html += '<option value="6">6 x per week</option>';
                html += '<option value="7">7 x per week</option>';
                html += '<option value="8">8 x per week</option>';
                html += '<option value="9">9 x per week</option>';
                html += '<option value="10">10 x per week</option>';
                html += '</select>';
                html += '<select class="custom_dropdown" name="exercise['+exercise_group_list_id+'][estimated_course_time]">';
                html += '<option value="10">10 Minutes</option>';
                html += '<option value="20">20 Minutes</option>';
                html += '<option value="30">30 Minutes</option>';
                html += '</select>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
                $('.' + droppable_target_class + '.droppable-list').append(html); //append the html after exercise group dropped
                $(".custom_dropdown").dropkick({
                    mobile: true
                });
                showHidePhaseLabel();
            }
            
        });
        //function to remove the deleted the exercise and pause
        deleteData();
    }

    // call on ajax success after exercises are fetched
    function addDraggableForPhaseExercises() {
        $('.draggable-item').draggable({
            revert: true,
            placeholder: true,
            droptarget: '.droppable-list',
            drop: function(evt, droptarget) {
                key = getKey() + 1;
                setKey(key);
                $('.' + droppable_target_class + ".droppable-list").addClass("dropping-list");
                if(droppable_target_class == "exercise-group-droppable") {
                    $(this).addClass("inactive");
                }
                // $(this).appendTo(droptarget).draggable('destroy');
                var exercise_group_list_id = $(this).attr("data-attr");
                var this_img = $(this).find("i").children("img").attr("src");
                var this_title = $(this).children(".title").text();
                var html = "";
                html += '<div href="#" title="" class="dragged-item" data-id=' + exercise_group_list_id + '>';
                html += '<a href="#" title="delete" class="delete-btn"><img src="{{ asset('assets/images/delete.svg') }}"></a>';
                html += '<div class="left-div">';
                html += '<i><img src="' + this_img + '" alt=""></i>';
                html += '</div>';
                html += '<div class="right-div">';
                html += '<label>' + this_title + '</label>';
                html += '<input type="hidden" name="exercise['+key+'][id]" value="'+exercise_group_list_id+'"/>';
                html += '<div class="dropdown-list">';
                html += '<select class="custom_dropdown duration" name="exercise['+key+'][duration]">';
                for(var i = 1; i <= 25 ; i++) {
                    html += '<option value="'+i+'">'+i+'</option>';    
                }
                html += '</select>';
                html += '<select class="custom_dropdown type" name="exercise['+key+'][type]">';
                html += '<option value="1">{{ __('lang.repetition') }}</option>';
                html += '<option value="2">{{ __('lang.duration') }}</option>';
                html += '</select>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
                $('.' + droppable_target_class + '.droppable-list').append(html); //append the html after exercise group dropped
                $(".custom_dropdown").dropkick({
                    mobile: true
                });
                showHidePhaseLabel();
            }
        });

        //for adding pause 
        $('.draggable-block .draggable-title').draggable({
            revert: true,
            placeholder: true,
            droptarget: '.droppable-list',
            drop: function (evt, droptarget) {
                key = getKey() + 1;
                setKey(key);
                $('.' + droppable_target_class + ".droppable-list").addClass("dropping-list");
                var this_img = $(this).find("i").children("img").attr("src");
                var this_title = $(this).children(".title").text();
                var html = "";
                html += '<div class="draggable-title asdasd">';
                html += '<a href="#" title="delete" class="delete-btn"><img src="{{ asset("assets/images/delete.svg") }}"></a>';
                html += '<span><i><img src="{{ asset("assets/images/calm.svg") }}"></i> Pause</span>';
                html += '<div class="left-div">';
                html += '</div>';
                html += '<div class="dropdown-list">';
                html += '<select class="custom_dropdown duration" name="exercise['+key+'][pause_time]">';
                html += '<option value="10">10</option>';
                html += '<option value="20">20</option>';
                html += '<option value="30">30</option>';
                html += '</select>';
                html += 'seconds';
                html += '</select>';
                html += '</div>';
                $('.droppable-list').append(html);
                $(".custom_dropdown").dropkick({
                    mobile: true
                });
            }
        });
        //function to remove the deleted the exercise and pause 
        deleteData();
    }

    //function to remove the deleted the exercise and pause
    function deleteData() {
        // remove tthe exercises group from selected area
        $(document).on('click', '.' + droppable_target_class + '.droppable-list .dragged-item .delete-btn', function (e) {
            var this_data_id = $(this).parent().attr("data-id");
            $(this).parent(".dragged-item").remove();
            $('.' + draggable_target_class + ".draggable-list .draggable-item").each(function () {
                if ($(this).attr("data-attr") == this_data_id) {
                    $(this).removeClass("inactive");
                }
            }); 
            var dragged_element = $('.' + droppable_target_class + ".droppable-list.dropping-list .dragged-item").length;
            var dragged_title = $('.' + droppable_target_class + ".droppable-list .draggable-title").length;
            if(dragged_element == 0 && dragged_title == 0){
                $('.' + droppable_target_class + ".droppable-list").removeClass("dropping-list");
            }
            
            showHidePhaseLabel();
        });

        //reset the pause after remove
        $(document).on('click', '.' + droppable_target_class + '.droppable-list .draggable-title .delete-btn', function(e) {
            $(this).parent(".draggable-title").remove();
            var dragged_element = $('.' + droppable_target_class + ".droppable-list.dropping-list .dragged-item").length;
            var dragged_title = $('.' + droppable_target_class + ".droppable-list .draggable-title").length;
            if(dragged_element == 0 && dragged_title == 0){
                $('.' + droppable_target_class + ".droppable-list").removeClass("dropping-list");
            }
            showHidePhaseLabel();
        });
    }

    //function to show/hide the label in exercise groups list
    function showHidePhaseLabel() {
        var phase_title_letter = [];
        $('.' + draggable_target_class + ".draggable-list .draggable-item").each(function () {
            if (!$(this).hasClass("inactive")) {
                var string_char = $(this).find(".title").text().charAt(0);
                phase_title_letter.push(string_char.toLowerCase());
            }
        });
        $(".list-title").each(function () {
            var title = $(this).text().toLowerCase();
            if (!phase_title_letter.includes(title)) {
                $(this).hide();
            } else {
                $(this).show();
            }
        });
    }


    //function to set global variable from model
    function setGlobalVarible(draggable_class, droppable_class, element) {
        draggable_target_class = draggable_class;
        droppable_target_class = droppable_class;
        if(draggable_class == 'exercise-groups') {
            var search_id = "search-groups";
            getExerciseGroups();
        } else if(draggable_class == 'phase-exercises-list') {
            $('#exercise-name').html($(element).parent().parent().find('.exercise-group-name').text());
            var search_id = "search-exercises";
            var exercise_group_id = $(element).attr('data-exercise-groups-id');
            getExercises(exercise_group_id);
        }

        //search functionality for exercise groups/phase exercuises
        $('#'+search_id).bind('keyup', function() {
            showHidePhaseLabel();
            var input, filter, ul, li, a, i, txtValue;
            input = $("#"+search_id);
            filter = input.val().toUpperCase();
            ul = $('.draggable-block').find('.'+draggable_target_class+'.draggable-list .draggable-item').not('.inactive');
            //get all the dragable list item to search the entered character
            for (i = 0; i < ul.length; i++) {
                a = ul[i].getElementsByTagName("span")[0];
                txtValue = a.textContent || a.innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    $(ul).eq(i).removeClass("hidelabel");
                } else {
                    $(ul).eq(i).addClass("hidelabel");
                }
            }
            //remove the lable as per value search
            var phase_title_letter1 = [];
            $('.'+draggable_target_class+".draggable-list .draggable-item").not('.inactive').each(function () {
                if (!$(this).hasClass("hidelabel")) {
                    var label_char = $(this).find(".title").text().charAt(0);
                    phase_title_letter1.push(label_char.toLowerCase());
                }
            });
            $(".list-title").each(function () {
                var title = $(this).text().toLowerCase();
                if (!phase_title_letter1.includes(title)) {
                    $(this).hide();
                } else {
                    $(this).show();
                }
            });
        }); 
    }

    //function to read the image url and view
    function readURL(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
            $('#preview_image').attr('src', e.target.result);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    //function to get the current key for exercise key
    function getKey() {
        // key = $('.' + droppable_target_class + ".droppable-list .dragged-item, .droppable-list .draggable-title").length;
        key =  $('.' + droppable_target_class + '.droppable-list > div').length;
        return key;
    }

    //function to set the key for exercise key
    function setKey(data) {
        key = data;
    }
    // function to change the active phase
    $(".phase-list-block li .nav-link").on('click', function() {
        active_phase_id = $(this).parent().attr("data-phase-active");
        loadChart(active_phase_id);
    });

    //function to load the chart
    function loadChart(active_phase_id = '') {
        $('#chart_wrapper_'+active_phase_id).empty();
        var therapy_plan_details = {!! json_encode($therapy_plans_phases) !!};
        var limitations = '';
        var phase_details;
        $.each( therapy_plan_details, function( key, value ) {
            if(value.id == active_phase_id) {
                limitations = value.limitations;
                phase_details = value;
            }
        });
        var width = parseInt(phase_details.duration) * 120 ;
        var chart_html = "";
        chart_html += '<div class="chartAreaWrapper">';
        chart_html += '<canvas id="bar-chart-horizontal_'+active_phase_id+'" height="150" width="'+width+'"></canvas>';
        chart_html += '</div>';
        chart_html += '<button class="prev"> <i><img src="{{ asset('assets/images/back.svg') }}"></i></button>';
        chart_html += '<button class="next"> <i><img src="{{ asset('assets/images/next.svg') }}"></i></button>';
        $('#chart_wrapper_'+active_phase_id).append(chart_html);
       
        if(limitations.length) {
            var initial_date = moment(phase_details.start_date);
            var data = [];
            var labels = [];
            var dateofvisit = moment(phase_details.start_date);
            var today = moment();
            var current_day = today.diff(dateofvisit, 'days');
            $.each( limitations, function( idnex, limitation_value ) {
                var start_value = (limitation_value.start_week - 1) * 7 + (limitation_value.start_day - 1);
                var end_value = (limitation_value.end_week - 1) * 7 + (limitation_value.end_day);
                labels.push(limitation_value.name.charAt(0).toUpperCase() + limitation_value.name.slice(1));
                data.push([start_value, end_value]);
            })
            // bar-chart
            var bar_chart_id = 'bar-chart-horizontal_'+ active_phase_id;
            var ctx = document.getElementById(bar_chart_id);
            var barChartData1 = {
                labels: labels,
                datasets: [{
                    backgroundColor: "#D13B02",
                    data: data,
                }]
            };
            var myBarChart = new Chart(document.getElementById(bar_chart_id), {
                type: 'horizontalBar',
                data: barChartData1,
                options: {
                    lineAtIndex: current_day,
                    align: 'center',
                    responsive: false,
                    scaleOverride : true,
                    scaleSteps : 2,
                    scaleStepWidth : 5,
                    legend: {
                        display: false,
                    },
                    scales: {
                        xAxes: [{
                            position: 'top',
                            ticks: {
                                min: 0,
                                max: parseInt(phase_details.duration)*7,
                                stepSize: 7,
                                fixedStepSize: 7,
                                padding: 10,
                                // precision: 1,
                                userCallback: function (value, index, values) {
                                    initial = 0;
                                    if (index > 0) {
                                        return "W" + index;
                                    } else {
                                        return "";
                                    }
                                },
                                fontColor: '#D13B02',
                            },
                            gridLines: {
                                drawTicks: false,
                                borderDash: [4, 2],
                                color: "#aaa",
                                // offsetGridLines : true,
                            }
                        }],
                        yAxes: [{
                            display: false,
                            ticks: {
                                mirror: true
                            }
                        }]
                    },
                    tooltips: {
                        enabled: false
                    },
                    events: [],
                    animation: {
                        duration: 1,
                        onComplete() {
                            const chartInstance = this.chart;
                            const ctx = chartInstance.ctx;
                            const dataset = this.data.datasets[0];
                            const meta = chartInstance.controller.getDatasetMeta(0);
                            
                            var bar_width = [];
                            var x_position = [];
                            var y_poistion = [];
                            var bar_title_width;
                            var rectangleSet = false;

                            this.data.datasets.forEach(function (dataset, i) {
                                var meta = chartInstance.controller.getDatasetMeta(i);
                                meta.data.forEach(function (bar, index) {
                                    bar_width[index] = bar._model.x - bar._model.base;
                                    x_position[index] = bar._model.x + 5;
                                    y_poistion[index] = bar._model.y + 1;
                                });
                            });

                            // Dispaly bar label as per bar length
                            Chart.helpers.each(meta.data.forEach((bar, index) => {
                                // debugger;
                                const label = this.data.labels[index];
                                const labelPositionX = 20;
                                const labelWidth = ctx.measureText(label).width + labelPositionX;

                                ctx.textBaseline = 'middle';
                                ctx.textAlign = 'left';
                                var html_calc = '<span class="chart_wrapper_'+ active_phase_id +' label-of-graph' + index +'">' + label + '</span>';
                                $("body").append(html_calc);

                                bar_title_width = $(".chart_wrapper_"+ active_phase_id +".label-of-graph" + index).width();

                                if (bar_width[index] < bar_title_width) {
                                    ctx.fillStyle = '#000';
                                    ctx.fillText(label, x_position[index]  , y_poistion[index]);
                                } else {
                                    ctx.fillStyle = '#fff';
                                    ctx.fillText(label, x_position[index] - bar_width[index] , y_poistion[index]);
                                }
                            }));


                            //scroll graph 
                            // var total_width = $("#"+bar_chart_id).width();
                            setTimeout(function(){
                                var total_width = $("#"+bar_chart_id).width();
                                var scroll = $("#chart_wrapper_"+ active_phase_id + " .chartAreaWrapper").width();

                                if ((total_width - scroll) > scroll) {
                                    $('body').on('click', '.next', function(e) {
                                        var next_scroll = $(".chartAreaWrapper").scrollLeft() + $(
                                            ".chartAreaWrapper").width();
                                        $('.chartAreaWrapper').animate({
                                            scrollLeft: next_scroll
                                        }, 200);
                                    });
                                    $('body').on('click', '.prev', function(e) {
                                        var prev_scroll = $(".chartAreaWrapper").scrollLeft() - $(
                                            ".chartAreaWrapper").width();
                                        $('.chartAreaWrapper').animate({
                                            scrollLeft: prev_scroll
                                        }, 200);
                                    });
                                } else {
                                    $('body').on('click', '.next', function(e) {
                                        $('.chartAreaWrapper').animate({
                                            scrollLeft: total_width - scroll
                                        }, 200);
                                    });
                                    $('body').on('click', '.prev', function(e) {
                                        $('.chartAreaWrapper').animate({
                                            scrollLeft: 0
                                        }, 200);
                                    });
                                }
                            },500);
                        },
                    }
                },
            });

            //to show the horizontal bar
            var originalLineDraw = Chart.controllers.horizontalBar.prototype.draw;
            Chart.helpers.extend(Chart.controllers.horizontalBar.prototype, {
                draw: function () {
                    originalLineDraw.apply(this, arguments);

                    var chart = this.chart;
                    var ctx = chart.chart.ctx;

                    var index = chart.config.options.lineAtIndex;
                    if (index) {

                        var xaxis = chart.scales['x-axis-0'];
                        var yaxis = chart.scales['y-axis-0'];

                        var x1 = xaxis.getPixelForValue(index);
                        var y1 = 20;

                        var x2 = xaxis.getPixelForValue(index);
                        var y2 = yaxis.height + 50;

                        ctx.save();
                        ctx.beginPath();
                        ctx.moveTo(x1, y1);
                        ctx.strokeStyle = 'red';
                        ctx.setLineDash([6, 3]);
                        ctx.lineWidth = 1.5;
                        ctx.lineTo(x2, y2);
                        ctx.stroke();
                        ctx.restore();
                    }
                }
            });
        }
    }
</script>
@endpush