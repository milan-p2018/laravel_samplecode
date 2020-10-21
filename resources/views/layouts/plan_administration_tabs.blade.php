<div class="tabbing-block">
    @php $addClass = isset($common_data['showtabs']) && !$common_data['showtabs'] ? 'no-tabs-link' : '' @endphp
    <div class="header-tabbing {{$addClass}}">
        @if(isset($common_data['showtabs']) && !$common_data['showtabs'] && isset($common_data['name']))
            @php
                if(isset($common_data['back_url']) && !empty($common_data['back_url'])) {
                    $back_url = $common_data['back_url'];
                }
            @endphp
            <a href="{{ $back_url }}" class="back-link" title="{{ $common_data['name'] }}">
                <i><img src="{{ asset('assets/images/left-arrow.svg') }}" alt=""></i>
                <span class="heading-left-title">{{ $common_data['name'] }}</span>
            </a>
        @endif
        @if(isset($common_data['showtabs']) && $common_data['showtabs'])
            <ul class="tab-heading-block">
                <li class="{{ (request()->is('plan-administration/therapy-plan-templates*')) ? 'active' : '' }}">
                    <a href="{{ route('administration.therapy-plan-templates') }}" data-tab="tharapy-plan-template-data" title="{{ __('lang.tharapy-plan-template')}}">{{ __('lang.tharapy-plan-template')}}</a>
                </li>
                <li class="{{ (request()->is('plan-administration/assessments*')) ? 'active' : '' }}">
                    <a href="{{ route('administration.assessments') }}" data-tab="assessments-plan" title="Assessments">Assessments</a>
                </li>
                <li class="{{ (request()->is('plan-administration/exercises*')) ? 'active' : '' }}">
                    <a href="{{ route('administration.exercises') }}" data-tab="exercises" title="{{ __('lang.exercises')}}">{{ __('lang.exercises')}}</a>
                </li>
                 <li class="{{ (request()->is('plan-administration/courses*')) ? 'active' : '' }}">
                    <a href="{{ route('administration.courses') }}" data-tab="courses" title="{{ __('lang.course')}}">{{ __('lang.course')}}</a>
                </li>
            </ul>
        @endif
    </div>
    <div class="fixed-content-block content-scroll-only {{ (request()->is('plan-details*')) ? '' : 'content-wrapper-block' }}">
        <div class="inner-content-block">
            <div class="tab-content-block">
                @yield('slot')
            </div>
        </div>
    </div>
</div>