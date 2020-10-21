@extends('layouts.main_with_tabs')

@section('content')
@component('layouts.patient_tabs')
@section('slot')
<div class="custom-block center-align-loader" style="display:none">
    <i class="edit-loader custom-loader"><img src="{{ asset('assets/images/edit-loader.gif') }}" alt=""></i>
</div>
<div data-id="stammdaten" class="tab-inner-content active">
    <div class="row calendar-header">
        <div class="col-lg-3 left-col">
            @if(!empty($permission_array) && $permission_array['can_create'])
            <a href="#" title="{{ __('lang.new_appointment') }}" class="btn btn-green new-event-popup"><i><img
                src="{{ asset('assets/images/plus-white.svg') }}"></i>{{ __('lang.new_appointment') }}</a>
            @endif
        </div>
        <div class="col-lg-9 right-col">
            <div class="right-col-wrap">
                <div class="checkbox-wrap">
                    @foreach($species as $specie)
                    <div class="secondary-checkbox {{ __('lang.class_'.$specie->language_code) }}-check">
                        <div class="outer-check">
                            <span>
                                <input type="checkbox" name="{{ $specie->language_code }}_category_type" value="{{ $specie->language_code }}" checked>

                                <label>{{$specie->name}}</label>
                            </span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-3">
            <div class="appointment-list-wrap">
                <div class="datepicker-wrap">
                    <div id="datepicker"></div>
                </div>
                <div class="appointment-list-outer">
                    <div class="heading-div">
                        <h3>{{ __('lang.all_appointments') }}</h3>
                        <span>{{ $common_data['user_name'] }}</span>
                    </div>
                    <div class="appointment-list-inner">
                        <ul class="appointment-list">
                            <li class="heading">
                                <span class="date dateHeader">{{ __('lang.date') }}</span>
                                <span class="status"><em></em></span>
                                <span class="appointment-detail">
                                    {{ __('lang.meeting') }}
                                </span>
                            </li>

                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-9 calender-wrapper">
            <div id='full_Calendar'></div>
            <div class="display-on-dayselect">
                <div id="datepicker"></div>
            </div>
            <div class="event-detail">
                <div class="event-title">
                    <span id="organization_name"></span>
                </div>
                <div class="event-description">
                    <h3 id="event_title"></h3>
                    <ul class="description-list">
                        <li>
                            <i><img src="{{ asset('assets/images/user-fill.png') }}"></i>
                            <span id="patient_name">{{ $common_data['user_name'] }}</span>
                        </li>
                        <li>
                            <i><img src="{{ asset('assets/images/clock-gray2.svg') }}"></i>
                            <span id="event_duration"></span>
                        </li>
                        <li class="reminder-block">
                            <i><img src="{{ asset('assets/images/alarm.svg') }}"></i>
                            <span id="event_reminder"></span>
                        </li>
                        <li>
                            <i><img src="{{ asset('assets/images/note - gray.svg') }}"></i>
                            <span id="event_description"></span>
                        </li>
                        <li>
                            <i><img src="{{ asset('assets/images/doctor-gray.svg') }}"></i>
                            <span class="doctor_name"></span>
                        </li>
                    </ul>
                </div>
                <div class="action-div">
                    @if(!empty($permission_array) && $permission_array['can_delete'])
                        <button class="btn  border-btn" title="{{ __('lang.delete') }}" data-toggle="modal" data-target="#delete-appointment"><i><img src="{{ asset('assets/images/delete.svg') }}"></i>{{ __('lang.delete') }}</button>
                    @endif
                    @if(!empty($permission_array) && $permission_array['can_update'])
                        <button class="btn border-btn" data-toggle="modal" data-target="#new-appointment" title="{{ __('lang.edit') }}"><i><img src="{{ asset('assets/images/edit-icon.svg') }}"></i>{{ __('lang.edit') }}</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <!-- delete event modal -->
    <div class="modal phase-list fade custom-modal small-modal" id="delete-appointment" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>{{ __('lang.delete_appointment') }}</h2>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i><img src="{{ asset('assets/images/cross-icon.svg') }}"></i></span>
                    </button>
                </div>
                <div class="modal-wrap">
                    <div class="modal-container">
                        <p>{{ __('lang.confirm_delete_event') }}</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="btn-wrap">
                        <form method="post" action="{{ route('patients.delete-meeting') }}" id="meetingDeleteForm">
                            <input name="_token" type="hidden" value="{{ csrf_token() }}" />
                            <button type="submit" title="" class="btn red-btn" title="{{ __('lang.clear') }}">{{ __('lang.clear') }}</button>
                        </form>
                        <a href="#" title="" class="btn btn-transparent btn-lg close" title="{{ __('lang.close') }}" data-dismiss="modal" aria-label="Close">{{ __('lang.close') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@endcomponent
@endsection
@push('custom-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.17.1/moment-with-locales.min.js"></script>
<script src="{{ asset('assets/js/moment-timezone-with-data.js') }}"></script>
<script type="text/javascript">
$('.center-align-loader').show();
$('body').addClass('show-overlay');
    var organization_id = '{{ \Session::has('organization_id') ? decrypt(\Session::get('organization_id')) : '' }}';
    var data = JSON.parse(localStorage.getItem('keys'));
    $.each(data, function(key, value) {
        if (organization_id == value.organization_id) {
            private_key = value.private_key;
            return 0;
        };
    });
    if (typeof private_key == 'undefined' || !private_key) {
        window.location.href = '/scan-key';
    }
    var id = "{{ $id }}";
    var url = "{{ url('patients/events/') }}/" + id;
    var token = $('input[name="_token"]').attr('value');
    var isDataRefetch = false;
    var isUpdated = false;
    var updateMessage = '';
    var events = null;
    var isLeftScroll = true;
    var viewType = '';
    var prevViewType = 'month';
    var lableLeftSide = '';
    var classArray = {
        'surgery_date': 'orange',
        'practice_schedule': 'green',
        'phase_start': 'yellow',
        'physio_appoinment': 'caribbean-green'
    }
    moment.locale(locale);

    // get patient event function
    var getTasks = function(fetchInfo, successCallback, failureCallback, callback) {
        var selectedCategory = [];
        if (events) {
            filterArray = [];
            $.each($("input[name*='_category_type']:checked"), function() {
                filterArray.push($(this).val());
            });
            var newEvents = events.filter(function(item) {
                return (filterArray.includes(item.schedule_category_code))
            })
            callback(newEvents);
        }
        //run the ajax call onlu once
        if (!isDataRefetch) {
            $.ajax({
                type: "post",
                url: url,
                data: {
                    private_key: private_key,
                    selectedCategory: selectedCategory,
                },
                headers: {
                    'X-CSRF-Token': token
                },
                success: function(response) {
                    isDataRefetch = true;
                    events = response;
                    filterArray = [];
                    $.each($("input[name*='_category_type']:checked"), function() {
                        filterArray.push($(this).val());
                    });
                    var newEvents = events.filter(function(item) {
                        return (filterArray.includes(item.schedule_category_code))
                    })
                    callback(newEvents); //pass the event data to fullCalendar via the provided callback function
                    // show the update event message if events are refecthed.
                    if (isUpdated) {
                        $('.custom-toast-msg').addClass('success');
                        $('.custom-toast-msg').find('span').text(updateMessage);
                        $('.custom-toast-msg').show().delay(5000).slideUp(800);
                        isUpdated = false;
                        updateMessage = '';
                    }
                    $('.center-align-loader').hide();
                    $('body').removeClass('show-overlay');
                },
                error: function(response) {
                    // failureCallback(response); //inform fullCalendar of the error via the provided callback function
                }
            });
        }
    }

    // full calendar
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('full_Calendar');
        var gridType = 'dayGridMonth';
        var forWeekRemoveLeft = true;
        var currentViewEvents = [];
        var calendar = $('#full_Calendar').fullCalendar({
            plugins: ['interaction', 'dayGrid', 'timeGrid', 'list'],
            header: {
                left: 'today',
                center: 'prev,title,next',
                right: 'month,agendaWeek'
            },
            slotEventOverlap:false,
            navLinks: true, // can click day/week names to navigate views
            defaultView: 'month',
            weekNumbers: false,
            weekNumbersWithinDays: true,
            weekNumberCalculation: 'ISO',
            firstDay: 1,
            slotLabelFormat: 'HH:mm',
            scrollTime: '00:00',
            editable: true,
            buttonText: {
                today: "{{ __('lang.today') }}",
                month: "{{ __('lang.month') }}",
                week: "{{ __('lang.week') }}",
                day: "{{ __('lang.day') }}",
            },
            views: {
                month: {
                    columnFormat: 'dddd' // set format for month here
                },
                week: {
                    columnHeaderHtml: function(mom) {
                        return '<span>' + mom.format('dddd') + '<em>' + mom.format('D') + '</em></span>';
                    }
                },
                day: {
                    titleFormat: 'D. MMMM YYYY',
                }
            },
            
            eventResize: function( event, delta, revertFunc, jsEvent, ui, view ) { 
                const eventData = {...event};
                if (event.schedule_category_code == 'phase_start') {
                    revertFunc();
                } else {
                    updateEvents(eventData);
                }
            },
            eventDrop: function( event, delta, revertFunc, jsEvent, ui, view ) {
                const eventData = {...event};
                if (event.schedule_category_code == 'phase_start') {
                    revertFunc();
                } else {
                    updateEvents(eventData);
                }
            },
            dayClick: function (date, allDay, jsEvent, view) {
                if (viewType == 'month') {
                    $(".fc-selected-day").removeClass("fc-selected-day");
                    $("td[data-date="+date.format('YYYY-MM-DD')+"]").addClass("fc-selected-day");
                    setTimeout(() => {
                        $('#datepicker').datepicker('setDate', moment(date).format('MM/DD/YYYY'));
                    }, 500);
                }
            },
            eventClick: function(info, jsEvent, view) {
                var cor = getOffset(jsEvent.currentTarget);

                // focus selected event at left side menu
                $('.appointment-detail').removeClass('event-active');
                $("[data-event=" + info.id + "]").addClass('event-active');

                var childPos = $(".event-active").offset();
                var parentPos = $(".appointment-list").offset();
                if (childPos) {
                    childOffset = {
                        top: childPos.top - parentPos.top,
                        left: childPos.left - parentPos.left
                    }
                } else {
                    childOffset = {
                        top: parentPos.top,
                        left: parentPos.left
                    }
                }
                if (isLeftScroll) {
                    $(".appointment-list").scrollTop(childOffset.top - 71);
                }
                isLeftScroll = true;
                moment.locale(locale);
                var dateStartMoment = moment(info.startDate, 'D.M.Y');
                var dateEndMoment = moment(info.endDate, 'D.M.Y');
                var differenceInDays = dateEndMoment.diff(dateStartMoment, 'days');
                var durationStart = dateStartMoment.format('dddd');
                durationStart = durationStart.replace('.', '') + ", {{ __('lang.date_the') }} " + dateStartMoment.format('DD.MM.YYYY');
                var durationEnd = dateEndMoment.format('dddd');
                durationEnd = durationEnd.replace('.', '') + ", {{ __('lang.date_the') }} " + dateEndMoment.format('DD.MM.YYYY');
                if (info.startDate == info.endDate) {
                    var duration = durationStart + " - " + info.startTiming + " {{ __('lang.to') }} " + info.endTiming;
                    var duration_date = durationStart;
                } else {
                    var duration = durationStart + " - " + info.startTiming + " {{ __('lang.to') }} " + durationEnd + " - " + info.endTiming;
                    var duration_date = durationStart + " {{ __('lang.to') }} " + durationEnd;

                }
                $(".exe_materials").val(info.materials);
                // Then refresh
                $(".exe_materials").multiselect("refresh");

                // selected event tooltip set
                $('.reminder-block').hide();
                if (info.reminderText) {
                    $('.reminder-block').show();
                }
                $('#organization_name').text(info.orgName);
                $('#event_title').text(info.title);
                $('#event_duration').text(info.is_full_day ? duration_date : duration);
                $('#event_reminder').text(info.reminderText);
                $('#event_description').text(info.description);
                $('.doctor_name').text(info.doctor_name);
                $('#new-appointment .modal-header h2').text("{{ __('lang.update_appointment') }}");

                // restrict actions for phase start category
                if (info.schedule_category_code == 'phase_start') {
                    $('.action-div').hide();
                    $('.action-div .btn').attr('disabled', 'true');
                    $('.action-div .btn').removeAttr('data-toggle');
                } else {
                    $('.action-div').show();
                    $('.action-div .btn').removeAttr('disabled');
                    $('.action-div .btn').attr('data-toggle', 'modal');
                }
                // open tootltip
                var get_parent_offset = $(".fc-view-container").offset();

                // get offset right of clender 
                var calendar_right_offset = ($(window).width() - (get_parent_offset.left + $(
                    ".fc-view-container").outerWidth()));

                // get distance from right click 
                var totale_right_click_offset = $(window).width() - (cor.x +
                    calendar_right_offset);
                var tootip_height = $(".event-detail").height();

                var cal_bottom_offset = $(".fc-view-container").offset().top + $(
                    ".fc-view-container").height();


                var window_height = $(window).height();
                if (totale_right_click_offset < $(".event-detail").width()) {
                    $(".event-detail").css({
                        "top": (cor.y - get_parent_offset.top) + 10 + 55,
                        "left": "auto",
                        "right": totale_right_click_offset + 30,
                        // "bottom": "auto",
                    });
                    $(".event-detail").removeClass("open-modal-bottom");
                    $(".event-detail").addClass("open-modal-right");
                    if (cor.y > ($(window).height() - tootip_height)) {
                        var container_height = $(".fc-view-container").height();
                        $(".event-detail").css({
                            "top": "auto",
                            "bottom": (window_height - cor.y) + (
                                cal_bottom_offset - window_height) - 40
                        })
                        $(".event-detail").addClass("open-modal-bottom");
                    }
                } else {
                    $(".event-detail").removeClass("open-modal-right");
                    $(".event-detail").removeClass("open-modal-bottom");

                    $(".event-detail").css({
                        "top": (cor.y - get_parent_offset.top) + 10 + 55,
                        "left": (cor.x - get_parent_offset.left) + 30,
                        "right": "auto",
                        "bottom": "auto",
                    });
                    if (cor.y > ($(window).height() - tootip_height)) {
                        var container_height = $(".fc-view-container").height();
                        $(".event-detail").css({
                            "top": "auto",
                            "bottom": (window_height - cor.y) + (
                                cal_bottom_offset - window_height) - 40
                        })
                        $(".event-detail").addClass("open-modal-bottom");
                    }
                }
                $(".event-detail").fadeIn();
                $("body,html").addClass("event-detail-open");
                $('body,html').removeClass('more-popover');

                // selected event data set in edit popup
                for (let [key, value] of Object.entries(info)) {
                    $("#" + key).val(value);
                    $("textarea#" + key).addClass('onfocus');
                    $("#" + key).trigger('change');
                }
                $("input[name='startDate']").val(info.startDate);
                $("input[name='endDate']").val(info.endDate);
                if(info.is_full_day) {
                    $("#is_full_day").prop('checked', true);
                    $("#new-appointment").addClass("switch-checked");
                    $('.diffInDays').text(differenceInDays + 1);
                } else {
                    $("#is_full_day").prop('checked', false);
                    $("#new-appointment").removeClass("switch-checked");
                    $('.diffInDays').text(1);
                }
                $("#patient_doctor_id").val(info.patient_doctor_id).trigger('change');
                $('.reset-button').show();
                $('.form-submit-button').prop('disabled', false);
                $("#title").val(info.title).addClass('onfocus');
                $("input[name='meeting_id'], input[name='user_id']").remove();
                $('div .form-group').removeClass('has-error');
                $('.selected-patient-name label').text("{{ $common_data['user_name'] }}");
                $('.selected-patient-name').show();
                $('.patient-name-list').hide();
                $('#meetingForm').append('<input type="hidden" name="user_id" value="{{ $id }}" />');
                $('#meetingForm, #meetingDeleteForm').append('<input type="hidden" name="meeting_id" id="meeting_id" value=' + info.id + ' />');
            },

            windowResize: function() {
                $('body,html').removeClass('more-popover');
                $(".fc-more").click(function() {
                    $("body,html").toggleClass("more-popover");
                });
            },

            eventAfterAllRender: function(info) {
                // identify the selected view (day/view/month)
                var view = $('#full_Calendar').fullCalendar('getView');
                viewType = view.name;
                // var date = $('#datepicker').datepicker('getDate');
                var date = $('#full_Calendar').fullCalendar('getDate').format();
                var today = new Date();
                if (!date) {
                    // select(hightlight) today in full calendar
                    date = today;
                }
                if (viewType == 'agendaWeek') {
                    $('#datepicker .datepicker .active.day').closest('tr').addClass('active-week');
                } else {
                    if ((prevViewType == 'agendaWeek'|| prevViewType == 'agendaDay') && viewType == 'month') {
                        // setTimeout(() => {
                            if (today.getMonth()+1 == +moment(date).format('M')) {
                                date = new Date();
                                $('#datepicker').datepicker('setDate', moment(date).format('MM/DD/YYYY'));
                                $('#datepicker').datepicker('setDate', moment(date).format('MM/DD/YYYY'));
                            } else {
                                date = moment(date).format('YYYY-MM-') + '01';
                                $('#datepicker').datepicker('setDate', moment(date).format('MM/DD/YYYY'));
                            }
                            // $(".fc-selected-day").removeClass("fc-selected-day");
                            // $("td[data-date="+moment(date).format('YYYY-MM-DD')+"]").addClass("fc-selected-day");
                        // }, 50);
                    }
                    if (viewType == 'month') {
                        $(".fc-selected-day").removeClass("fc-selected-day");
                        $("td[data-date="+moment(date).format('YYYY-MM-DD')+"]").addClass("fc-selected-day");
                    }
                    if (moment(today).format('DD/MM/YYYY') == moment(date).format('DD/MM/YYYY')) {
                        $('#datepicker .datepicker .today').addClass('active');
                    }
                    $('#datepicker .datepicker .active-week').removeClass('active-week');
                }
                // multiple days view filter
                currentViewEvents = currentViewEvents.filter((thing, index) => {
                    const _thing = thing.id;
                    return index === currentViewEvents.findIndex(obj => {
                        return obj.id === _thing;
                    });
                });
                if (viewType == 'month') {
                    currentViewEvents = currentViewEvents.filter(function(item) {
                        return (item.startDate == moment(date).format('DD.MM.YYYY'))
                    });
                } 
                if (viewType == 'agendaWeek') {
                    weekViewAdjustment(new Date(moment(date).format('YYYY, MM, DD')));
                }

                createLeftSideView(currentViewEvents, info.title);
                currentViewEvents = [];
                prevViewType = viewType;

                $(".fc-more").click(function() {
                    $("body,html").toggleClass("more-popover");
                });

                // today button click 
                $('.fc-today-button').click(function() {
                    $('#datepicker').datepicker('setDate', moment().format('MM/DD/YYYY'));
                    if (viewType == 'agendaWeek') {
                        weekViewAdjustment(new Date());
                    } else {
                        var currentViewEvents = events.filter(function(item) {
                            return (item.startDate == moment().format('DD.MM.YYYY'));
                        });
                        createLeftSideView(currentViewEvents, moment().format('dddd, DD.MM.YYYY'));
                    }
                });

                $(document).on('click', '.appointment-detail', function() {
                    var eventId = $(this).attr('data-event');
                    isLeftScroll = false;

                    // for partial event display
                    var container = $(".appointment-list");
                    var contHeight = container.height();
                    var contTop = container.scrollTop();
                    var contBottom = contTop + contHeight;

                    var elemTop = $($(this)).offset().top - container.offset().top;
                    var elemBottom = elemTop + $($(this)).height();

                    var isTotal = (elemTop >= 0 && elemBottom <= contHeight);

                    $('#' + $(this).data('event') + '.fc-event').trigger('click');

                    $(".event-detail").removeClass('open-modal-right');
                    $(".event-detail").removeClass('open-modal-bottom');

                    if ($(window).height() - $(this).position().top < $(".event-detail").height()) {
                        if (!isTotal) {
                            $(".appointment-list").scrollTop($(".appointment-list").scrollTop() + (elemBottom - contHeight))
                        }
                        $(".event-detail").addClass('open-modal-bottom');
                        $(".event-detail").css({
                            "top": $(this).position().top - $(".event-detail").height() + $(this).height() + 50,
                            "left": "-40px",
                            "right": "auto",
                            "bottom": "auto",
                        });
                    } else {
                        if (!isTotal) {
                            $(".appointment-list").scrollTop($(".appointment-list").scrollTop() + elemTop)
                        }
                        $(".event-detail").css({
                            "top": $(this).position().top - 25,
                            "left": "-40px",
                            "right": "auto",
                            "bottom": "auto",
                        });
                    }
                })

            },

            eventRender: function(info, element) {
                element.attr('id', info.id);
                if (!currentViewEvents.includes(info)) {
                    currentViewEvents.push(info);
                }
                return true;
            },

            locale: locale,
            eventLimit: true, // allow "more" link when too many events
            eventSources: [{
                events: getTasks
            }],
        });

        // on category change get new events data
        $("input[name*=_category_type]").on('change', function() {
            filterEvent();
        });

        $(".appointment-detail").on('click', function() {
            var eventId = $(this).attr('id');
            var events = $('#calendar').fullCalendar('clientEvents', eventId);
            if (events.length > 0) {
                $("#calendar").fullCalendar.trigger('eventClick', events[0]);
            }
        });
    });
    var startDate;
    var endDate;
    var toCal_startDate;
    var toCal_endDate;
    var weekOfStart = 0;
    var view = $('#full_Calendar').fullCalendar('getView');

    // initialize datepicker
    $("#datepicker").datepicker({
        language:locale,
        format: 'mm/dd/yyyy',
        todayHighlight: true,
        showOtherMonths: true,
        selectOtherMonths: true,
        weekStart: 1,
    });
    $('#datepicker').on('changeDate', function(e) {
        if(moment(e.date).format('MM/DD/YYYY') == moment().format('MM/DD/YYYY')) {
            $('#datepicker .datepicker .today').addClass('active');
        } else {
            $('#datepicker .datepicker .today').removeClass('active');
        }
        if (viewType == 'month') {
            $('#full_Calendar').fullCalendar('gotoDate', e.date);
            $(".fc-selected-day").removeClass("fc-selected-day");
            $("td[data-date="+moment(e.date).format('YYYY-MM-DD')+"]").addClass("fc-selected-day");
            var currentViewEvents = events.filter(function(item) {
                return (item.startDate == moment(e.date).format('DD.MM.YYYY'));
            });
            createLeftSideView(currentViewEvents, moment(e.date).format('dddd, DD.MM.YYYY'));
        }

        if (viewType == 'agendaWeek') {
            weekViewAdjustment(e.date);
        }
    });

    // add/remove hover class for whole week in datepicker
    $('#datepicker').on('mousemove', '.table-condensed tr', function () {
        if (viewType == 'agendaWeek') {
            $(this).find('td').addClass('week-hover');
        }
    });
    $('#datepicker').on('mouseleave', '.table-condensed tr', function () {
        if (viewType == 'agendaWeek') {
            $(this).nextAll().find('td').removeClass('week-hover');
            $(this).find('td').removeClass('week-hover')
        }
    });

    setTimeout(function() {
        $(".fc-prev-button  , .fc-next-button").click(function() {
            var date = $('#full_Calendar').fullCalendar('getDate').format();
            if (viewType == 'month' && moment().month() == moment(date).month()) {
                $('#datepicker').datepicker('setDate', moment().format('MM/DD/YYYY'));
                $(".fc-selected-day").removeClass("fc-selected-day");
                $("td[data-date="+moment().format('YYYY-MM-DD')+"]").addClass("fc-selected-day");
            } else {
                $('#datepicker').datepicker('setDate', moment(date).format('MM/DD/YYYY'));
                $(".fc-selected-day").removeClass("fc-selected-day");
                $("td[data-date="+moment(date).format('YYYY-MM-DD')+"]").addClass("fc-selected-day");
            }
        });
    }, 2000);


    // function to update event time and date when drag&dropped or resize
    function updateEvents(event) {
        const eventData = {};
        eventData.meeting_id = event.id;
        eventData.startDate = event.start.format('DD.MM.YYYY');
        eventData.startTiming = event.start.format('HH:mm');
        if (event.end) {
            eventData.endDate = event.end.format('DD.MM.YYYY');
            eventData.endTiming = event.end.format('HH:mm');
        } else {
            eventData.endDate = event.startDate;
            event.endTiming = event.startTiming;
        }
        eventData.title = event.title;
        eventData.patient_name = event.patient_name;
        eventData.user_id = parseInt("{{ $id }}");
        eventData.description = event.description;
        eventData.schedule_category_code = event.schedule_category_code;
        eventData.patient_doctor_id = event.patient_doctor_id;
        eventData.place = event.place;
        eventData.materials = event.materials || [];
        if (event.is_full_day) {
            eventData.is_full_day = event.is_full_day;
        }
        eventData.timezone = moment.tz.guess();
        eventData.return_type = 'json';
        $.ajax({
            type: "post",
            url: "{{ route('patients.store-meeting') }}",
            data: JSON.stringify(eventData),
            contentType : 'application/json;charset=utf-8',
            headers: {
                'X-CSRF-Token': "{{ csrf_token() }}"
            },
            success: function(response) {
                // if update sucess then refetch the event.
                $('.center-align-loader').show();
                $('body').addClass('show-overlay');
                isDataRefetch = false;
                isUpdated = true;
                updateMessage = response.success;
                events = null;
                $('#full_Calendar').fullCalendar('refetchEvents');
            },
            error: function(response) {
                // console.log('error', response);
            }
        });
    }

    // week adjustment
    function weekViewAdjustment(date) {
        var weekOfStart = 1;
        var dayAdjustment = date.getDay() - weekOfStart;
        if (dayAdjustment < 0) {
            dayAdjustment += 7;
        }
        // start and end date of selected week
        startDate = new Date(date.getFullYear(), date.getMonth(), date.getDate() - dayAdjustment);
        endDate = new Date(date.getFullYear(), date.getMonth(), date.getDate() - dayAdjustment + 6);
        $('#datepicker .datepicker .active.day').closest('tr').addClass('active-week');

        var currentViewEvents = events.filter(function(item) {
            return (new Date(moment(item.startDate, 'DD.MM.YYYY')) >= new Date(startDate) && new Date(moment(item.startDate, 'DD.MM.YYYY')) <= new Date(endDate));
        });
        $('#full_Calendar').fullCalendar('gotoDate', startDate);
        lableLeftSide = moment(startDate).format('DD.MM') + ' - ' + moment(endDate).format('DD.MM.YYYY');
        createLeftSideView(currentViewEvents, lableLeftSide);
    }

    function createLeftSideView(currentViewEvents, title) {
        $('.appointment-list li.appoinment-list-events').remove();
        filterArray = [];
        $.each($("input[name*='_category_type']:checked"), function() {
            filterArray.push($(this).val());
        });

        // add filters
        currentViewEvents = currentViewEvents.filter((item) => {
            return filterArray.includes(item.schedule_category_code);
        });

        if (currentViewEvents.length) {
            currentViewEvents.forEach(element => {
                var patientEvent = element;
                forWeekRemoveLeft = false;
                var dateStartMoment = moment(patientEvent.startDate, 'D.M.Y');
                var durationStart = dateStartMoment.format('DD');
                var durationStartDay = dateStartMoment.format('dd').replace('.', '');


                $('.dateHeader').text('Datum');
                var dateList = '<em>' + durationStart + '</em>' + '<em>' + durationStartDay + '</em>';
                var dateTimeUpper = '<em class="time">' + patientEvent['startTiming'] + ' - ' + patientEvent['endTiming'] + '</em>';

                leftEvent = '<li class="appoinment-list-events ' + patientEvent['schedule_category_code'] + '-block ' + classArray[patientEvent['schedule_category_code']] + '">' +
                    '<span class="date">' + dateList +
                    '</span>' +
                    '<span class="status"><em class="' + classArray[patientEvent['schedule_category_code']] + '"></em></span>' +
                    '<button href="#" class="appointment-detail" data-event="' + patientEvent['id'] + '">' +
                    '<label>' +
                    '<span class="meeting-name">' + patientEvent['orgName'] + '</span>' +
                    '</label>' +
                    dateTimeUpper +
                    '<em>' + element['title'] + '</em>' +
                    '</button>' +
                    '</li>';
                $('.appointment-list').append(leftEvent);
            });
        } else {
            $('.appointment-list').append('<li class="appoinment-list-events"><span class="no-events">' + "{{ __('lang.no_events') }}" + ' ' + title + '</span></li>');
        }
        return true;
    }

    // search + category filter
    function filterEvent() {
        $('#full_Calendar').fullCalendar('refetchEvents');
    }

    // get offset
    function getOffset(el) {
        var _x = el.offsetWidth / 2;
        var _y = el.offsetHeight / 2;
        while (el && !isNaN(el.offsetLeft) && !isNaN(el.offsetTop)) {
            _x += el.offsetLeft - el.scrollLeft;
            _y += el.offsetTop - el.scrollTop;
            el = el.offsetParent;
        }
        return {
            y: _y,
            x: _x
        };
    }

    $(document).ready(function() {

        $('.new-event-popup').on('click', function() {
            var assign_doc = "{{ $assign_doc }}";
            $('.selected-patient-name label').text("{{ $common_data['user_name'] }}");
            $('.selected-patient-name').show();
            $('.patient-name-list').hide();
            $('#new-appointment .modal-header h2').text("{{ __('lang.new_appointment') }}");
            $('form#meetingForm').trigger("reset");
            $("#patient_doctor_id, #schedule_category_code, #patient_name, #materials").val(null).trigger("change");
            $(".exe_materials").val(null);
            // Then refresh
            $(".exe_materials").multiselect("refresh");
            $("#patient_doctor_id").val(assign_doc).trigger("change");
            $("input[name='meeting_id']").remove();
            $('#meetingForm').append('<input type="hidden" name="user_id" value="{{ $id }}" />');
            $('.reset-button').hide();
            $('div .form-group').removeClass('has-error');
            $("#new-appointment").removeClass("switch-checked");
            $('#new-appointment').modal('show');
        });

        //hide it when clicking anywhere else except the popup and the trigger
        $(document).on('click touch', function(event) {
            if (!$(event.target).parents().addBack().is('.fc-event, .appointment-detail')) {
                $('.event-detail').fadeOut();
                $("body,html").removeClass("event-detail-open");
                $('.appointment-detail').removeClass('event-active');
            }
        });

        // Stop propagation to prevent hiding "#tooltip" when clicking on it
        $('.event-detail').on('click touch', function(event) {
            if (!$(event.target).addBack().is('button')) {
                event.stopPropagation();
            }
        });
    });
</script>
@endpush