@extends('layouts.main_with_tabs')

@section('content')
<div class="tabbing-block">
    <!-- <ul class="tab-heading-block">
        <li>
            <a href="#" data-tab="praxis" title="{{ __('lang.practice')}}">{{ __('lang.practice')}}</a>
        </li>
        <li>
            <a href="#" data-tab="arzte" title="{{ __('lang.doctors') }}">{{ __('lang.doctors') }}</a>
        </li>
        <li class="active">
            <a href="#" data-tab="mitarbeiter" title="{{ __('lang.employee')}}">{{ __('lang.employee')}}</a>
        </li>
        <li>
            <a href="#" data-tab="therapieplanvorlagen" title="{{ __('lang.treatment plan templates')}}">{{ __('lang.treatment plan templates')}}</a>
        </li>
    </ul> -->
    <div class="content-wrapper-block fixed-content-block">
        <div class="inner-content-block">
            <div class="tab-content-block">
                <div data-id="praxis" class="tab-inner-content">
                </div>
                <div data-id="arzte" class="tab-inner-content"></div>
                <div data-id="mitarbeiter" class="tab-inner-content active">
                    <div class="employee-sorting">
                        <div class="add-employee-btn">
                            @if(!empty($organization_verified_status->verified_at) && $organization_verified_status->owner == Auth::user()->id && !empty($permission_array) && $permission_array['can_create'])
                            <button type="button" class="btn btn-green dark" data-toggle="modal" data-target="#create-patient-modal">{{ __('lang.create_patient') }}</button>
                            @endif
                        </div>
                        <div class="all-employee-sorting">
                            <div class="all-employee-detail pdf-icon search-datatable">
                                <button class="btn icon-btn export_btn"><i><img src="{{ asset('assets/images/download-icon.svg') }}"></i></button>
                                <!-- <select class="custom_dropdown asc custom_dropdown-secondery sorting-dropdown" onchange="sortByName(this)">
                                    <option value="0" class="ascending">{{ __('lang.names') }} (A-Z)</option>
                                    <option value="1" class="descending">{{ __('lang.names') }} (Z-A)</option>
                                </select> -->
                                <!-- <button class="btn icon-btn search-btn"><i><img src="{{ asset('assets/images/search-icon.svg') }}"></i></button> -->
                                <!-- <div class="btn-group">
                                    <button type="button" class="btn icon-btn"><i><img class="normal-icon" src="{{ asset('assets/images/grid-table-icon.svg') }}"><img class="active-icon" src="{{ asset('assets/images/grid-table-active-icon.svg') }}"></i></button>
                                    <button type="button" class="btn icon-btn active">
                                        <div class="grid-list"><span></span><span></span><span></span></div>
                                    </button>
                                </div> -->
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive patient-table">
                        <table class="collapsible-table secondary-table" id="employee-list">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th><span>Name</span></th>
                                    <th><span>{{ __('lang.date-of-birth-short') }}</span></th>
                                    <th><span>{{ __('lang.address') }}</span></th>
                                    <th><span>{{ __('lang.postcode short') }}</span></th>
                                    <th><span>{{ __('lang.place') }}</span></th>
                                    <th><span>{{ __('lang.health-insurance-type') }}</span></th>
                                    <th><span>Status</span></th>
                                    <th></th>
                                    <th></th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
                <div data-id="therapieplanvorlagen" class="tab-inner-content">
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal phase-list fade custom-modal small-modal" id="edit-record" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form>
                <div class="modal-header">
                    <h2>{{ __('lang.confirm_patient') }}</h2>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i><img src="{{ asset('assets/images/cross-icon.svg') }}"></i></span>
                    </button>
                </div>
                <div class="modal-wrap">
                    <div class="modal-container">

                        <div class="form-group">
                            <input type="text" id="patient_name_id" class="form-control onfocus" readonly>
                            <label>{{ __('lang.name') }}</label>
                        </div>
                        <div class="form-group label-custom-dropdown">
                            <label class="dropdown-label" for="select"> {{ __('lang.treating_doc') }}</label>
                            <select class="dropdown_select2" name="patient_doctor_id_select" id="patient_doctor_id_select">
                                @foreach($doctorList as $doc)
                                <option value="{{$doc->id}}">{{$doc->fullname}}</option>
                                @endforeach
                            </select>
                            <span class="error-msg select-doctor-error"></span>
                        </div>


                    </div>
                </div>
                <div class="modal-footer">
                    <div class="btn-wrap">
                        <a href="#" title="" class="btn btn-green  verifyPatient" title="{{ __('lang.confirm') }}">{{ __('lang.confirm') }}</a>
                        <a href="#" title="" class="btn btn-transparent btn-lg close" title="{{ __('lang.close') }}" data-dismiss="modal" aria-label="Close">{{ __('lang.close') }}</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@push('custom-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.6.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/1.6.1/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-3-typeahead/4.0.2/bootstrap3-typeahead.min.js"></script>
<script type="text/javascript">
    var private_key = '';
    //to validate the birthday
    $('#birthdate').datepicker({
        format: 'dd.mm.yyyy',
        startDate: moment().subtract(99, 'years').toDate(),
        endDate: moment().subtract(18, 'years').toDate(),
        autoclose: true,
    });
    var address = 'Address'
    var born_in = 'Born in';
    var gender_male = 'Male';
    var gender_female = 'Female';
    var dataSrc = [];
    var gender_miscellaneous = 'Miscellaneous';
    if (locale == 'de') {
        address = 'Adresse';
        born_in = 'Geboren in';
        gender_male = 'Männlich';
        gender_female = 'Weiblich';
        gender_miscellaneous = 'Divers';
    }

    function format(d) {
        var profile_pic_url = "{{ asset('assets/images/no_photo_available.png') }}";
        var mobile = '-';
        var health_insurance_type = '-';
        if (Boolean(d.user.profile_pic)) {
            var result = doesFileExist("{{ url('/storage')  }}" + d.user.profile_pic);
            if (result) {
                profile_pic_url = Boolean(d.user.profile_pic) && result == true ? "{{ url('/storage')  }}" + d.user.profile_pic : "{{ asset('assets/images/no_photo_available.png') }}";
            }
        }
        if(d.mobile != '' && d.mobile != null) {
            mobile = d.mobile;
        }
        if(d.insurance_type == 1) {
            health_insurance_type = "{{ __('lang.private')}}";
        } else if(d.insurance_type == 2) {
            health_insurance_type = "{{ __('lang.legal')}}";
        }
        var url = '{{ route("patients.base-data", "id") }}';
        url = url.replace('id', d.patient_enc_id);
        var html = '';
        html += '<table cellpadding="8" class="employee-full-detail" cellspacing="0" border="0" style="padding-left:80px;">' +
                '<tr>' +
                '<td>' +
                '<div id="wedding_website" class="accordian-body card-layout-accordian">' +
                '<div class="employee-detail-wrapper">' +
                '<div class="employee-name detail-block">' +
                '<div class="detail-wrap">' +
                '<div class="employee-logo">' +
                '<img src="' + profile_pic_url + '" title="">' +
                '</div>' +
                '<h3>' + (d.name).charAt(0).toUpperCase() + (d.name).slice(1) + ' ' + (d.lastname).charAt(0).toUpperCase() + (d.lastname).slice(1) + '</h3>' +
                '</div>' +
                '</div>' +
                '<div class="detail-block">' +
                '<div class="detail-wrap">' +
                '<ul class="employee-detail-list">' +
                '<li><i><img src="{{ asset("assets/images/phone.svg") }}" alt="phone"></i><span><a href="tel:'+d.phone+'">'+d.phone+'</a></span></li>' +
                '<li><i><img src="{{ asset("assets/images/mobile.svg") }}" alt="moblie"></i><span><a href="tel:'+ mobile +'">'+ mobile +'</a></span></li>' +
                '<li><i><img src="{{ asset("assets/images/envelope.svg") }}" alt="mail"></i><span><a href="mailto:'+d.user.email+'">'+d.user.email+'</a></span></li>' +
                '</ul>' +
                '</div>' +
                '</div>' +
                '<div class="detail-block">' +
                '<div class="detail-wrap">' +
                '<ul class="employee-detail-list">' +
                '<li><i><img src="{{ asset("assets/images/calender-green.svg") }}" alt="phone"></i><span>{{ __("lang.born-on")}}: '+d.bday+'</span></li>' +
                '<li><i><img src="{{ asset("assets/images/health-insurance.svg") }}" alt="moblie"></i><span>{{ __("lang.health-insurance-type-sec") }}: '+health_insurance_type+'</span></li>' +
                '<li><i><img src="{{ asset("assets/images/doctor-green.svg") }}" alt="moblie"></i><span>{{ __("lang.doctor")}}: '+ ((d.assign_doctor_name != undefined) ? d.assign_doctor_name : "-") +'</span></li>' +
                '</ul>' +
                '</div>' +
                '</div>'+
                '<div class="detail-block">' +
                '<div class="detail-wrap">' +
                '<ul class="employee-detail-list">' +
                '<li><i><img src="{{ asset("assets/images/list.svg") }}" alt="phone"></i><span>{{ __("lang.therapy-plan-status")}}: '+ ( d.active_therapy_plan == 1 ? "{{ __('lang.active') }}" : "{{ __('lang.inactive') }}") +'</span></li>' +
                '<li><i><img src="{{ asset("assets/images/clock-green.svg") }}" alt="moblie"></i><span>{{ __("lang.next-appointment")}}: '+(d.next_appointment != undefined ? moment(d.next_appointment).format("DD.MM.YYYY") : "-")+'</span></li>' +
                '<li><i><img src="{{ asset("assets/images/document-green.svg") }}" alt="mail"></i><span>{{ __('lang.documents') }}: '+ (d.uploaded_document_count)+'</span></li>' +
                '</ul>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</td>' +
                '</tr>' +
                '</table>';
                return html;
    }

    //function to check the file exist or not
    function doesFileExist(urlToFile) {
        var xhr = new XMLHttpRequest();
        xhr.open('HEAD', urlToFile, false);
        xhr.send();

        if (xhr.status == "404") {
            return false;
        } else {
            return true;
        }
    }

    function documentsSearchFilter(){
        setTimeout(function(){
            $("#employee-list_filter").addClass("document-datafilter");
            $(".document-datafilter label").prepend( "<i class='cross-icon'></i>" );
            var document_list = $("#employee-list_filter");
            // $(document_list).prependTo(".all-employee-detail.search-datatable");
            $(".all-employee-detail.search-datatable").append(document_list);
        },500);
    }

    $(document).ready(function() {
        $('.secondary-table').addClass('no-data');
        documentsSearchFilter();
        $(".dropdown_select2").each(function() {
            $(this).select2({
                dropdownParent: $(this).closest('.form-group'),
                tags: true
            });
        });

        $('.dataTables_filter').hide();
        var openRows = new Array();
        $.fn.dataTable.ext.errMode = 'none';
        var organization_id = '{{ \Session::has('organization_id') ? decrypt(\Session::get('organization_id')) : '' }}';
        var redirect_url = "{{ url('scan-key') }}";
        var data = JSON.parse(localStorage.getItem('keys'));
        $.each(data, function(key, value) {
            if (organization_id == value.organization_id) {
                private_key = value.private_key;
                return 0;
            };
        });
        var update_url = '{{url("get-patient-listing")}}';
        var table = $('#employee-list').DataTable({
            "initComplete": function(settings, json) {
                if(json.data.length) {
                    $('.secondary-table').removeClass('no-data');
                }
                $('body').removeClass('show-datatable-overlay');
                $('body').remove('datatable-overlay');
                var api = this.api();
                //for autosuggestion in the search column
                api.cells('tr', [3, 1, 2]).every(function() {
                    // Get cell data as plain text
                    var data = $('<div>').html(this.data()).text();
                    
                    if (dataSrc.indexOf(data) === -1) {
                        dataSrc.push(data);
                    }
                });

                // $(".patient-table #employee-list_wrapper").mCustomScrollbar({
                //     axis: "xy",
                // });
                $(".patient-table #employee-list_wrapper").mCustomScrollbar({
                    axis: "xy",
                });

                //To redirect the patient details page if user click on the expanded view
                $('body').on('click', '.employee-full-detail', function() {
                    var patient_details_url = $(this).closest('.table-accordian-body').prev('tr').find('.edit-link').attr('href');
                    if(patient_details_url != '' && patient_details_url != undefined && patient_details_url != null) {
                        window.location.href = patient_details_url;    
                    }
                })
                
                // Sort dataset alphabetically
                dataSrc.sort();
                $('.dataTables_filter input[type="search"]', api.table().container())
                    .typeahead({
                        source: dataSrc,
                        afterSelect: function(value) {
                            api.cells('tr', [3, 1, 2]).search(value).draw();
                        }
                    });

                    // search js
                    $(".document-datafilter .form-control").on("change paste keyup focus", function() {
                        if($(this).val()) {
                            $(this).addClass("input-focus");
                            $(this).parent("label").addClass("label-focus");
                        } else {
                            $(this).removeClass("input-focus");
                            $(this).parent("label").removeClass("label-focus");
                        }
                        });

                        $(document).on('click', function (event) {
                        if (!$(event.target).closest('.document-datafilter .form-control').length) {
                            if($(".document-datafilter .form-control").val().length <= 0){
                            $(".document-datafilter .form-control").removeClass("input-focus"); 
                            $(".document-datafilter label").removeClass("label-focus");
                            } 
                        }
                        });

                        var tableData = $('#employee-list').DataTable();
                        $(".cross-icon").click(function(){
                            tableData.search('').draw();
                            $('.form-control').val('');
                    });
            },
            "bProcessing": true,
            "columnDefs": [{
                "targets": [8],
                "visible": false,
                "searchable": false
            }, ],
            'orderFixed': [8, 'asc'],
            "order": [
                [8, 'asc']
            ],
            "bServerSide": false,
            "bRetrieve": true,
            "bLengthChange": false,
            "dom": 'Bfrtip',
            // "scrollY": "calc(100vh - 170px)",
            "buttons": [{
                extend: 'excelHtml5',
                text: 'Export Excel',
                title: '',
                filename: 'report - ' + moment().format("DD-MMM-YYYY"),
                exportOptions: {
                    modifier: {
                        page: 'current'
                    },
                    columns: 'th:not(:first-child)'
                },
                action: function(e, dt, button, config) {
                    $.fn.DataTable.ext.buttons.excelHtml5.action.call(this, e, dt, button, config);
                },
            }, ],
            ajax: {
                url: update_url,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                type: "POST",
                data: {
                    private_key: private_key
                },
                beforeSend: function() {
                    $('body').addClass('show-datatable-overlay');
                    $('body').append('<div class="datatable-overlay"></div>');
                },
                error: function(jqXHR, exception) {
                    $('body').removeClass('show-datatable-overlay');
                    $('body').remove('datatable-overlay');
                    window.location.href = redirect_url;
                    $('#employee-list_processing').hide();
                    var message = locale == "de" ? 'Ungültige Anforderung' : 'Bad request';
                    $('#employee-list tbody')
                        .empty()
                        .append('<tr><td colspan="12" class="dataTables_empty">' + message + '</td></tr>')
                },
            },
            "createdRow": function(row, data, dataIndex) {
                var data = table.rows().data();
                $('#count').text(' (' + data.length + ')');
            },
            columns: [{
                    "orderable": false,
                    "data": null,
                    "defaultContent": '',
                    "render": function(data, type, row) {
                        var profile_pic_url = "{{ asset('assets/images/no_photo_available.png') }}";
                        if (row.user && Boolean(row.user.profile_pic)) {
                            var result = doesFileExist("{{ url('/storage')  }}" + row.user.profile_pic);
                            if (result) {
                                profile_pic_url = Boolean(row.user.profile_pic) && result == true ? "{{ url('/storage')  }}" + row.user.profile_pic : "{{ asset('assets/images/no_photo_available.png') }}";
                            }
                        }
                        return '<div class="img-wrapper"><img src="' + profile_pic_url + '" title="Logo"></div';
                    }
                },
                {
                    "data": "name",
                    "render": function(data, type, row) {
                        return (row.name).charAt(0).toUpperCase() + (row.name).slice(1) + ' ' + (row.lastname).charAt(0).toUpperCase() + (row.lastname).slice(1);
                    }
                },
                {
                    "data": "bday",
                    "render": function(data, type, row) {
                        return moment(row.bday, 'DD.MM.YYYY').format('DD.MM.YYYY');
                    }
                },
                {
                    "data": "street",
                    "render": function(data, type, row) {
                        return  row.street + ' ' + row.streetnumber;
                    }
                },
                {
                    "data": "postcode"
                },
                {
                    "data": "place"
                },
                {
                    "data": "insurance_type",
                    "render": function(data, type, row) {
                        return (row.insurance_type) == '1' ? "{{ __('lang.private') }}" : "{{ __('lang.legally') }}";
                    }
                },
                {
                    "data": null, 
                    "render": function ( data, type, row ) {
                                var logged_user_id = "{{Auth::user()->id}}";
                                var url = '{{ route("patients.base-data", "id") }}';
                                url = url.replace('id', data.patient_enc_id);
                                var plan_url = '{{ route("patients.therapy-plans", "id") }}';
                                // Assigned therapy plan for patient
                                plan_url = plan_url.replace('id', data.patient_enc_id);
                                if(data.show_in_confirmed == 0) {
                                    return '<div class="btn-wrap"><a href="'+plan_url+'" onclick="event.stopPropagation();" class="treatment-link"><img src="{{ asset("assets/images/expand.svg") }}" class="treatment-expand">{{ __("lang.treatment-preview") }}</a></div>';
                                }
                                if(!data.verified_flag) {
                                    if(logged_user_id == row.organization.owner) {
                                        var name = (row.name).charAt(0).toUpperCase() + (row.name).slice(1) + ' ' + (row.lastname).charAt(0).toUpperCase() + (row.lastname).slice(1);
                                        return '<div class="btn-wrap"><button type="button" onclick="event.stopPropagation(); verifyProfile(' + "'" + data.user.id + "'" + ',' + "'" + name + "'" + ')" class="btn btn-green dark confirm-btn">{{ __("lang.to-confirm") }}</button></div>';
                                    }
                                    return '';
                                } else if(!data.user.email_verified_at) {
                                    return '<div class="btn-wrap"><span class="yellow-btn custom-pending-btn">{{ __("lang.pending") }}</span></div>'
                                } else if(data.verified_flag) {
                                    return '<div class="btn-wrap"></div>'
                                } else if(logged_user_id == row.organization.owner){
                                    var name = (row.name).charAt(0).toUpperCase() + (row.name).slice(1) + ' ' + (row.lastname).charAt(0).toUpperCase() + (row.lastname).slice(1);
                                    return '<div class="btn-wrap"><button type="button" onclick="event.stopPropagation(); verifyProfile(' + "'" + data.user.id + "'" + ',' + "'" + name + "'" + ')" class="btn btn-green dark confirm-btn">{{ __("lang.to-confirm") }}</button></div>'
                                }
                                return '';
                            }
                },
                { "data": "show_in_confirmed" },
                { 
                    "data": null, 
                    "render": function ( data, type, row ) {
                                var logged_user_id = "{{Auth::user()->id}}";
                                var url = '{{ route("patients.base-data", "id") }}';
                                url = url.replace('id', data.patient_enc_id);
                                if(!data.verified_flag) {
                                    if(logged_user_id == row.organization.owner) {
                                        var name = (row.name).charAt(0).toUpperCase() + (row.name).slice(1) + ' ' + (row.lastname).charAt(0).toUpperCase() + (row.lastname).slice(1);
                                        return '<div class=""><a href="'+url+'" onclick="event.stopPropagation();" title="{{ __("lang.view") }}" class="edit-link"><img src="{{ asset("assets/images/edit-icon.svg") }} " alt="Edit"></a></div>';
                                    }
                                    return '';
                                } else if(!data.user.email_verified_at) {
                                    return '<div class=""><a href="'+url+'" onclick="event.stopPropagation();" title="{{ __("lang.view") }}" class="edit-link"><img src="{{ asset("assets/images/edit-icon.svg") }} " alt="Edit"></a></div>'
                                } else if(data.verified_flag) {
                                    return '<div class=""><a href="'+url+'" onclick="event.stopPropagation();" title="{{ __("lang.view") }}" class="edit-link"><img src="{{ asset("assets/images/edit-icon.svg") }} " alt="Edit"></a></div>'
                                } else if(logged_user_id == row.organization.owner){
                                    var name = (row.name).charAt(0).toUpperCase() + (row.name).slice(1) + ' ' + (row.lastname).charAt(0).toUpperCase() + (row.lastname).slice(1);
                                    return '<div class=""><a href="'+url+'" onclick="event.stopPropagation();" title="{{ __("lang.view") }}" class="edit-link"><img src="{{ asset("assets/images/edit-icon.svg") }} " alt="Edit"></a></div>'
                                }
                                return '';
                            }
                },
            ],
            drawCallback: function(settings) {
                var api = this.api();
                var rows = api.rows({
                    page: 'current'
                }).nodes();
                var last = null;
                api.column(8, {
                    page: 'current'
                }).data().each(function(group, i) {
                    if (group == 1) {
                        $(rows).eq(i).addClass('accordion-toggle not-confirmed-detail');
                    } else if(group == 0){
                        $(rows).eq(i).addClass('accordion-toggle treatment-request-detail');
                    } else {
                        $(rows).eq(i).addClass('accordion-toggle');
                    }
                    if (last !== group) {
                        if (group) {
                            if (group == 2) {
                                $(rows).eq(i).before(
                                    '<tr class="yet-patients"><td colspan="12"><span class="border-left"></span>{{__("lang.patients")}}</td></tr>'
                                );
                            } else {
                                $(rows).eq(i).before(
                                    '<tr class="not-yet-confirmed"><td colspan="12"><span class="border-left"></span>{{__("lang.not-yet-confirmed")}}</td></tr>'
                                );
                            }
                        } else {
                            $(rows).eq(i).before(
                                '<tr class="not-yet-confirmed treatment-request"><td colspan="12"><span id="treatment-request" class="treatment-request border-left"></span>{{__("lang.treatement-request")}}</td></tr>'
                            );
                        }

                        last = group;
                    }
                });
            },
            "paging": false,
            "info": false,
            "language": {
                "search": "",
                "searchPlaceholder": locale == "de" ? "Suche..." : 'Search...',
                "processing": '<img class="center-loader" src="{{ asset("assets/images/edit-loader.gif") }}" alt="">',
                "zeroRecords": locale == "de" ? "Keine Aufzeichnungen gefunden" : 'No records found',
            }
        });
        table.on('search.dt', function() {
            $('#count').text(' (' + table.rows({
                filter: 'applied'
            }).nodes().length + ')');
        })
        $('.buttons-excel').css('display', 'none');

        $('#employee-list tbody').on('click', 'tr.accordion-toggle', function() {
            var tr = $(this).closest('tr');
            var row = table.row(tr);

            if (row.child.isShown()) {
                // This row is already open - close it
                row.child.hide();
                tr.removeClass('shown');
                openRows.pop(tr);
            } else {
                // Open this row
                row.child(format(row.data())).show();
                tr.addClass('shown');
                tr.next('tr').addClass('table-accordian-body');
                closeOpenedRows(table, tr);
                openRows.push(tr);
            }
        });

        //function to close all the opened rows
        function closeOpenedRows(table, selectedRow) {
            $.each(openRows, function(index, openRow) {
                // not the selected row!
                if ($.data(selectedRow) !== $.data(openRow)) {
                    var rowToCollapse = table.row(openRow);
                    rowToCollapse.child.hide();
                    openRow.removeClass('shown');
                    // remove from list
                    var index = $.inArray(selectedRow, openRows);
                    openRows.splice(index, 1);
                }
            });
        }
        $(".export_btn").on("click", function() {
            $('.buttons-excel').click()
        });

        //to open the modal bydefault
        if ($(".add-employee-btn").length) {
            var keep_model_open = '{{ $keep_model_open }}';
            if (keep_model_open) {
                $(".add-employee-btn button").click();
            }
        }

    });
    //function to sort by name the data into table name 
    function sortByName(data) {
        data.value == 0 ? $('#employee-list').DataTable().order([1, 'asc']).draw() : $('#employee-list').DataTable().order([1, 'desc']).draw();
    }

    //function to verify open modal to select doctor 
    function verifyProfile(id, name, checkAssignDoc) {
        $('#edit-record').modal('show');
        $("#patient_name_id").data('id', id); 
        $("#patient_name_id").val(name);
    }

    //function to verify the user identity
    $(".verifyPatient").on('click', function(e) {
        var token = $('input[name="_token"]').attr('value');
        var id = $("#patient_name_id").data('id');
        var url = "{{ url('edit-patient') }}/" + id;
        var selectedDocId = $('#patient_doctor_id_select').val();
        if (selectedDocId) {
            $(".select-doctor-error").hide();
            $.ajax({
                type: "post",
                url: url,
                data: {
                    'verifyPatient': true,
                    private_key: private_key,
                    selectedDocId: selectedDocId,
                },
                headers: {
                    'X-CSRF-Token': token
                },
                success: function(data) {
                    location.reload();
                },
                error: function(data) {
                    if (data.status == 400 && data.responseJSON.errors != '') {

                    }
                }
            });
        } else {
            $(".select-doctor-error").text('Please select doctor.');
            $(".select-doctor-error").show();
        }
    });
</script>
@endpush