@extends('layouts.main_with_tabs')

@section('content')
<div class="tabbing-block">
    <div class="header-tabbing">
        <a href="{{ route('patients.index') }}" class="back-link" title="Sebastian Angelone">
            <i><img src="{{ asset('assets/images/left-arrow.svg') }}" alt=""></i>
            <span class="change-new-name heading-left-title">{{ ucfirst($user->firstname) }} {{ ucfirst($user->lastname) }}</span>
        </a>
        <ul class="tab-heading-block">
            <li class="active">
                <span data-tab="basedata" title="{{ __('lang.basedata')}}">{{ __('lang.basedata')}}</>
            </li>
            <li class="{{ (request()->is('patients/therapy-plans*')) ? 'active' : '' }}">
                <a href="{{ $common_data['therapy_plan_link'] }}" data-tab="treatment-plan" title="{{ __('lang.treatement_plan') }}">{{ __('lang.treatement_plan') }}</a>
            </li>
            <li>
                <a href="{{ $common_data['documents_link'] }}" data-tab="documents" title="{{ __('lang.documents')}}">{{ __('lang.documents')}}</a>
            </li>
            <li class="{{ (request()->is('patients/events*')) ? 'active' : '' }}">
                <a href="{{ $common_data['event_data_link'] }}" data-tab="term" title="{{ __('lang.events')}}">{{ __('lang.events')}}</a>
            </li>
            <!-- <li>
                <a href="#" data-tab="news" title="{{ __('lang.news')}}">{{ __('lang.news')}}</a>
            </li> -->
        </ul>
    </div>
    <div class="fixed-content-block">
        <div class="inner-content-block">
            <div class="tab-content-block">
                <div data-id="basedata" class="tab-inner-content active">
                </div>
                <div data-id="therapy-plans" class="tab-inner-content"></div>
                <div data-id="documents" class="tab-inner-content">
                    <p>documents</p>
                </div>
                <div data-id="events" class="tab-inner-content">
                    <p>events</p>
                </div>
                <div data-id="news" class="tab-inner-content">
                    <p>news</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@push('custom-scripts')
<script type="text/javascript">
    $(document).ready(function() {
        var active = '{{ Request::get("type") }}';
        var organization_id = '{{ \Session::has('organization_id') ? decrypt(\Session::get('organization_id')) : '' }}';

        var data = JSON.parse(localStorage.getItem('keys'));
        $.each(data, function(key, value) {
            if (organization_id == value.organization_id) {
                private_key = value.private_key;
                return 0;
            };
        });
        if (active != null && active != '') {
            $("ul.tab-heading-block a[data-tab='" + active + "']").closest(".tabbing-block").find(".tab-inner-content").removeClass("active");
            $("ul.tab-heading-block a[data-tab='" + active + "']").closest(".tabbing-block").find(".tab-inner-content[data-id='" + active + "']").addClass("active");;
            $("ul.tab-heading-block a[data-tab='" + active + "']").closest("li").addClass("active");
            $("ul.tab-heading-block a[data-tab='" + active + "']").closest("li").siblings().removeClass("active");
        }
        getAllPlans('basedata');

        function getAllPlans(type) {
            var id = "{{ $id }}";
            var getDataUrl = "get-" + type;
            var url = "{{ url('get-basedata') }}/" + id;
            $.ajax({
                type: "get",
                url: url,
                data: {
                    private_key: private_key,
                    getShowData: true
                },
                success: function(data) {
                    if(!data) window.location.href = '/patients';
                    $(".tab-inner-content[data-id='" + type + "']").html(data.html);
                    $(".change-new-name").text(data.new_patient_name);
                    customVerticalTab();
                    $(".content-scrolling").mCustomScrollbar({
                        axis: "x"
                    });
                    $(".select-date").datepicker({
                        autoclose: true,
                        todayHighlight: true,
                        container: '#sel-date',
                        endDate: new Date,
                    }).datepicker();
                    $(".select-date").click(function() {
                        if ($('.datepicker').is(':visible')) {
                            $('.select-date').addClass("rotate-arrow");
                        }
                    });
                    $(".select-date").focusout(function() {
                        if (!$('.datepicker').is(':visible')) {
                            $('.select-date').removeClass("rotate-arrow");
                        }
                    });
                    $('.select-date').change(function() {
                        $('.select-date').removeClass("rotate-arrow");
                    });
                    $(".toast-relative-align .close").click(function() {
                        $(this).closest(".toast-relative-align").slideUp('slow');
                    });
                    $(".custom_dropdown").dropkick({
                        mobile: true
                    });
                },
                error: function(data) {
                    if (data.status == 400 && data.responseJSON.errors != '') {
                        $('.custom-toast').show();
                        $(".modal-wrap").animate({
                            scrollTop: 0
                        });
                    }
                }
            });
        }
    });
</script>
@endpush