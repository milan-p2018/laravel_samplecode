<div class="tabbing-block">
    @php
        $add_class = 0;
    @endphp
    @if((!isset($common_data['showtabs'])))
        @php
            if(array_key_exists('add_class', $common_data) && $common_data['add_class'] == 1) {
                $add_class = 1;
            }
        @endphp
    <div class="header-tabbing">
        @if(isset($common_data['back_link_history']))
        <a href="{{ url($common_data['back_link_history']) }}" class="back-link" title="{{ $common_data['user_name'] }}">
            @else
            <a href="{{ route('patients.index') }}" class="back-link" title="{{ $common_data['user_name'] }}">
                @endif
                <i><img src="{{ asset('assets/images/left-arrow.svg') }}" alt=""></i>
                <span class="heading-left-title change-new-name">{{ $common_data['user_name'] }}</span>
            </a>
            @if(!isset($common_data['showtabs']) || $common_data['showtabs'])
            <ul class="tab-heading-block">
                <li class="{{ (request()->is('patients/base-data*')) ? 'active' : '' }}">
                    <a href="{{ $common_data['base_data_link'] }}" data-tab="base-data" title="{{ __('lang.basedata')}}">{{ __('lang.basedata')}}</a>
                </li>
                <li class="{{ (request()->is('patients/therapy-plans*')) ? 'active' : '' }}">
                    <a href="{{ $common_data['therapy_plan_link'] }}" data-tab="treatment-plan" title="{{ __('lang.treatement_plan') }}">{{ __('lang.treatement_plan') }}</a>
                </li>
                <li class="{{ (request()->is('patients/documents*')) ? 'active' : '' }}">
                    <a href="{{ $common_data['documents_link'] }}" data-tab="documents" title="{{ __('lang.documents')}}">{{ __('lang.documents')}}</a>
                </li>
                <li class="{{ (request()->is('patients/events*')) ? 'active' : '' }}">
                    <a href="{{ $common_data['event_data_link'] }}" data-tab="term" title="{{ __('lang.events')}}">{{ __('lang.events')}}</a>
                </li>
                <!-- <li>
                    <a href="#" data-tab="news" title="{{ __('lang.news')}}">{{ __('lang.news')}}</a>
                </li> -->
            </ul>
            @endif
    </div>
    @endif

    <div class="{{ (request()->is('patients/therapy-plans*')) ? '' : 'content-wrapper-block' }} {{ (request()->is('patients/events*')) ? '' : 'fixed-content-block' }}  {{ $add_class ? 'content-wrapper-block' : ''}}">
        <div class="inner-content-block">
            <div class="tab-content-block">
                @yield('slot')
            </div>
        </div>
    </div>
</div>