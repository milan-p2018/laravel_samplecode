<div class="custom-block center-align-loader" style="display:none">
    <i class="edit-loader custom-loader"><img src="{{ asset('assets/images/edit-loader.gif') }}" alt=""></i>
</div>
<div class="tabbing-block no-tabbing">
    <div class="content-wrapper-block document-template-page">
        <div class="inner-content-block">
            <div class="tab-content-block">
                <div data-id="mitarbeiter" class="tab-inner-content document-list-page active">
                    <div class="all-document-upload-btn">
                        <div>
                            @if(!empty($permission_array) && $permission_array['can_create'])
                            <button type="button" class="btn btn-green add-employee-btn disabled" id="upload-btn">{{ __('lang.upload-document') }}</button>
                            @endif
                        </div>
                    <div class="employee-sorting">
                        <div class="link-block" style="display:none">
                            <span><span class="selectedPatient">Erik Wolf</span><span id="count"></span></span>
                        </div>
                        <div class="all-employee-sorting">
                            <div class="all-employee-detail search-datatable" style="display:none">
                                {{--<button class="btn icon-btn search-btn" open-searchbar="document-list-search">--}}
                                {{--<i><img src="{{ asset('assets/images/search-icon.svg') }}"></i></button>--}}
                                <select class="custom_dropdown sorting-dropdown select-document-type has-white-bg" id="select-category-type">
                                    <option value="" class="all-doc">{{ __('lang.all-documents') }}</option>
                                    @foreach(Config::get('globalConstants.documents-types') as $data)
                                    <option value="{{ $data }}" class="{{ $data }}">{{ __('lang.'.$data) }}</option>
                                    @endforeach
                                </select>
                                <div class="btn-group">
                                    <!-- <button type="button" class="btn icon-btn view-btn" onclick="changeView('grid-zoom', this)">
                                        <i>
                                            <img class="normal-icon" src="{{ asset('assets/images/grid-table2-icon.svg') }}">
                                            <img class="active-icon" src="{{ asset('assets/images/grid-table2-active-icon.svg') }}">
                                        </i>
                                    </button>
                                    <button type="button" class="btn icon-btn view-btn" onclick="changeView('grid', this)">
                                        <i>
                                            <img class="normal-icon" src="{{ asset('assets/images/grid-table-icon.svg') }}">
                                            <img class="active-icon" src="{{ asset('assets/images/grid-table-active-icon.svg') }}">
                                        </i>
                                    </button>
                                    <button type="button" class="btn icon-btn view-btn active" onclick="changeView('list', this)">
                                        <i>
                                            <img class="normal-icon" src="{{ asset('assets/images/grid-list-icon.svg') }}">
                                            <img class="active-icon" src="{{ asset('assets/images/grid-list-active-icon.svg') }}">
                                        </i>
                                    </button> -->
                                </div>
                            </div>
                            @if(!empty($permission_array) && $permission_array['can_delete'])
                                <div class="delete-record" style="display:none;">
                                    <button data-fileids="true" class="btn transparent-btn delete-btn"><span><span class="selected-items"></span> {{ __('lang.document-selected-text') }}</span> <i><img src="{{ asset('assets/images/delete.svg') }}"></i></button>
                                </div>
                            @endif
                        </div>
                    </div>
                    </div>
                    <div class="document-list-wrapper">
                        <div class="left-list-block">
                            <!-- <button type="button" class="btn btn-green add-employee-btn disabled" id="upload-btn">{{ __('lang.upload-document') }}</button> -->
                            <div class="filter-block">
                                <div class="form-group search has-clear-icon">
                                    <input type="search" class="form-control search_val" placeholder="{{ __('lang.search') }} {{ __('lang.patients')}}" id="search-patient">
                                    <i><img src="{{ asset('assets/images/search-icon-gray.svg') }}"></i>
                                    <a href="javascript:void(0);" class="clear-search d-none"><img src="{{ asset('assets/images/cross-icon-gray.svg') }}"></a>
                                </div>
                            </div>
                            <div class="draggable-wrap">
                                <div class="draggable-block custom-scroll-verticle">
                                    <div class="outer-draggable-list">
                                        <div class="patient-list draggable-list">
                                            @foreach($patientData as $key => $value)
                                            <span class="list-title">{{ $key }}</span>
                                            @foreach($value as $data)
                                            <div href="#" class="draggable-item" data-user_id="{{$data['user']->id}}">
                                                <i>
                                                    <img src="{{ !empty($data['user']->profile_pic) && file_exists(public_path() .'/storage/' .$data['user']->profile_pic) ? URL::asset('storage'.$data['user']->profile_pic) : asset('assets/images/no_photo_available.png') }}" alt="">
                                                </i>
                                                <span class="title patient-data" data-id="{{ $data['patient_enc_id'] }}" data-decrypted_id="{{ $data['user']->id }}" data-name="{{ ucFirst($data['name']) }} {{ ucFirst($data['lastname']) }}">{{ ucFirst($data['lastname']) }}, {{ ucFirst($data['name']) }}</span>
                                            </div>
                                            @endforeach
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="table-view-design">
                            

                            <div class="table-responsive data_table document-data-table" style="display:none">
                                <table class="collapsible-table document-table" id="document-list">
                                    <thead>
                                        <tr>
                                            <th>
                                            <div class="checkbox-group">
                                                <em class="minus-box"></em>
                                                <em class="inner-checkbox"><input type="checkbox" id="select_all" /></em>
                                            </div>
                                            </th>
                                            <th><span>{{ __('lang.name') }}</span></th>
                                            <th><span>{{ __('lang.changed') }}</span></th>
                                            <th></th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                            <div class="select-patient-block">
                                <h2>{{ __('lang.refer-patient-text') }}</h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal phase-list fade custom-modal small-modal" id="delete-appointment" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h2>{{ __('lang.document') }} {{ __('lang.remove_doc_head') }}</h2>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i><img src="{{ asset('assets/images/cross-icon.svg') }}"></i></span>
                </button>
            </div>
            <div class="modal-wrap">
                <div class="modal-container">
                    <p class="remove-doc-title">{{ __('lang.remvoe-document-text') }}</p>
                    <div class="modal-media media">
                        <div class="media-left">
                            <img src="{{ asset('assets/images/document.svg') }}" class="media-img">
                        </div>
                        <div class="media-body">
                            <h4 class="single-file-delete-name"></h4>
                            <p class="single-file-delete-date"></p>
                        </div>
                    </div>
                    <div class="custom-list-dropdown">
                        <span class="dropdown-input"></span>
                        <!-- list of all doc name for delete -->
                        <!-- <ul class='dropdown-list'>
                        </ul> -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="btn-wrap">
                    <a href="javascript:void(0);" title="" class="btn red-btn" id="remove-btn" title="{{ __('lang.clear') }}">{{ __('lang.clear') }}</a>
                    <a href="javascript:void(0);" title="" class="btn btn-transparent btn-lg close" title="{{ __('lang.close') }}" data-dismiss="modal" aria-label="Close">{{ __('lang.close') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal phase-list fade custom-modal large-modal upload-document" id="upload-document" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h2>{{ __('lang.upload-document') }}</h2>
                <button type="button" class="close" aria-label="Close" title="Close">
                    <span aria-hidden="true"><i><img src="{{ asset('assets/images/cross-icon.svg') }}"></i></span>
                </button>
            </div>
            <div class="modal-wrap">
                <div class="toast_msg danger custom-toast" style="display:none;"><span id="toast-msg"></span>
                    <a href="#" class="close_toast"><img src="{{ asset('assets/images/close.svg') }}" alt=""></a>
                </div>
                <div class="modal-container custom-class">
                </div>
            </div>
            <div class="modal-footer">
                <div class="btn-wrap">
                    <button class="btn btn-green " id="save-btn-id" title="{{ __('lang.save') }}">{{ __('lang.save') }}</button>
                    <a href="javascript:void(0);" class="btn btn-transparent btn-lg close" title="{{ __('lang.close') }}" aria-label="Close">{{ __('lang.close') }}</a>
                    <i class="edit-loader submit-button-loader document-loader" style="display:none"><img src="{{ asset('assets/images/edit-loader.gif') }}" alt=""></i>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- edit modal -->
<div class="modal phase-list fade custom-modal small-modal" id="edit-record" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h2>{{ __('lang.rename-document') }}</h2>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i><img src="{{ asset('assets/images/cross-icon.svg') }}"></i></span>
                </button>
            </div>
            <div class="modal-wrap">
                <div class="modal-container">
                    <div class="form-group">
                        <input type="text" name="updated_name" id="new_file_name" class="form-control">
                        <label>{{ __('lang.name') }} <sup>*</sup></label>
                        <span class="error-msg"></span>
                    </div>
                    <input type="hidden" name="old_file_name" id="old_file_name">
                    <input type="hidden" name="file_id" id="file_id">
                </div>
            </div>
            <div class="modal-footer">
                <div class="btn-wrap">
                    <a href="javascript:void(0);" title="" class="btn btn-green" id="rename-btn" title="{{ __('lang.save') }}">{{ __('lang.save') }}</a>
                    <a href="javascript:void(0);" title="" class="btn btn-transparent btn-lg close" title="{{ __('lang.close') }}" data-dismiss="modal" aria-label="Close">{{ __('lang.close') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal phase-list fade custom-modal small-modal" id="change-category" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h2>{{ __('lang.change-category') }}</h2>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i><img src="{{ asset('assets/images/cross-icon.svg') }}"></i></span>
                </button>
            </div>
            <div class="modal-wrap">
                <div class="modal-container">
                    <div class="form-group label-custom-dropdown">
                        <label class="dropdown-label" for="select"> {{ __('lang.category') }}</label>
                        <select class="custom_dropdown" name="category" value="{{ old('category') }}" id="new_category">
                            @foreach(Config::get('globalConstants.documents-types') as $data)
                                <option value="{{ $data }}">{{ __('lang.'.$data) }}</option>
                            @endforeach
                        </select>
                        <span class="error-msg">@error('salutation') {{$message}} @enderror</span>
                    </div>
                    <input type="hidden" name="file_id" id="file_id">
                </div>
            </div>
            <div class="modal-footer">
                <div class="btn-wrap">
                    <a href="javascript:void(0);" title="" class="btn btn-green" id="change-category-btn" title="{{ __('lang.save') }}">{{ __('lang.save') }}</a>
                    <a href="javascript:void(0);" title="" class="btn btn-transparent btn-lg close" title="{{ __('lang.close') }}" data-dismiss="modal" aria-label="Close">{{ __('lang.close') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-3-typeahead/4.0.2/bootstrap3-typeahead.min.js"></script>
<script type="text/javascript">
    var patient_id = "{{ \Session::has('patient_id') ? \Session::get('patient_id') : '' }}";
    var patient_name = "{{ \Session::has('patient_name') ? \Session::get('patient_name') : '' }}";
    var patient_decrypted_id = "{{ \Session::has('patient_decr_id') ? \Session::get('patient_decr_id') : '' }}";
    var warning = true;
    var count = 0;
    var dataSrc = [];
    var rows_selected = [];
    var added_row_count = 0;
    var total_count = 0;
    $('#select-doc').on('click', function() {
        $('#patient-document-upload-dropzone').trigger('click');
    });

    function documentsSearchFilter() {
        setTimeout(function() {
            $("#document-list_filter").addClass("document-datafilter");
            $(".document-datafilter label").prepend( "<i class='cross-icon'></i>" );
            var document_list = $("#document-list_filter");
            $(document_list).prependTo(".all-employee-detail.search-datatable");
            $('<i class="edit-loader submit-button-loader more-doc-loader" style="display: none;"><img src="{{ asset("assets/images/edit-loader.gif") }}" alt=""></i>').insertAfter('#document-list');
            // search js
            var tableData = $('#document-list').DataTable();
            $("#document-list_filter .form-control").on("change paste keyup focus", function() {
                    if($(this).val()) {
                        $(this).addClass("input-focus");
                        $(this).parent("label").addClass("label-focus");
                    } else {
                        $(this).removeClass("input-focus");
                        $(this).parent("label").removeClass("label-focus");
                    }
                    tableData.search( this.value ).draw();
                });

                    $(document).on('click', function (event) {
                    if (!$(event.target).closest('#document-list_filter .form-control').length) {
                        if($("#document-list_filter .form-control").val().length <= 0){
                        $("#document-list_filter .form-control").removeClass("input-focus"); 
                        $("#document-list_filter label").removeClass("label-focus");
                        } 
                    }
                    });

                    $(".cross-icon").click(function(){
                        tableData.search('').draw();
                        $('.form-control').val('');
                });
        }, 500);
    }
    $(document).ready(function() {
        documentsSearchFilter();

        //to prevent the Rename-form submit on enter
        var isEnterClicked;
        $("#edit-record").bind("keypress", function(e) {
            if (e.keyCode == 13) {
                // if (isEnterClicked) {
                // return false;
                // }
                // isEnterClicked = true;
                $('#rename-btn').trigger('click');
            }
        });

        //to show the patient selected 
        $('.patient-list .draggable-item').each(function() {
            if ($(this).attr('data-user_id') == patient_decrypted_id) {
                $(this).addClass('selected-element');
            } else {
                $(this).removeClass('selected-element');
            }
        })
        //To show the documents if patient is selected
        if (patient_id != '' && patient_name != '') {

            $('.link-block span.selectedPatient').html(patient_name);
            $('#upload-btn').removeClass('disabled');
            getAllDocuments(patient_id);
        }
        $(".custom_dropdown").dropkick({
            mobile: !0
        });
        $(".employee-sorting .all-employee-sorting .search-btn").click(function() {
            $(this).closest(".tab-content-block").find(".dataTables_filter").insertAfter(".employee-sorting");
            $(this).closest(".tab-content-block").addClass("dataTables_filter_display")
            if ($('.dataTables_filter').hasClass('dataTables_filter_display')) {
                $('.dataTables_filter').removeClass('dataTables_filter_display').slideUp(100);
            } else {
                $('.dataTables_filter').addClass('dataTables_filter_display').slideDown(100);
            }
        });
        var organization_id = '{{ \Session::has('organization_id') ? decrypt(\Session::get('organization_id')) : '' }}';
        var data = JSON.parse(localStorage.getItem('keys'));
        var token = $('input[name="_token"]').attr('value');

        $.each(data, function(key, value) {
            if (organization_id == value.organization_id) {
                private_key = value.private_key;
                return 0;
            };
        });
        //to get the document of specific patient
        $('.draggable-item').on('click', function() {
            $(this).siblings('.draggable-item').removeClass('selected-element');
            $(this).addClass('selected-element');
            if (patient_id != $(this).children('.patient-data').attr('data-id')) {
                $('#upload-btn').removeClass('disabled');
                patient_id = $(this).children('.patient-data').attr('data-id');
                patient_name = $(this).children('.patient-data').attr('data-name');
                $('.link-block span.selectedPatient').html(patient_name);
                getAllDocuments(patient_id);
            }
            documentsSearchFilter();
        })
        //to remove the document listing on back-button
        $('.back-arrow').on('click', function() {
            patient_id = '';
            $("#document-list").dataTable().fnDestroy();
            $('.link-block').hide();
            $('.data_table').hide();
            $('.select-patient-block').show();
            $("#select-category-type").dropkick('reset');
        });

        //search functionality for patients
        $('#search-patient').bind('keyup', function() {
            showHidePhaseLabel();
            var input, filter, ul, li, a, i, txtValue;
            input = $("#search-patient");
            filter = input.val().toUpperCase();
            if(input.val().length) {
                $('.clear-search').removeClass('d-none');
            } else {
                $('.clear-search').addClass('d-none');
            }
            ul = $('.draggable-block').find('.draggable-list .draggable-item').not('.inactive');
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
            $('.draggable-list .draggable-item').not('.inactive').each(function() {
                if (!$(this).hasClass("hidelabel")) {
                    var label_char = $(this).find(".title").text().charAt(0);
                    phase_title_letter1.push(label_char.toLowerCase());
                }
            });
            $(".list-title").each(function() {
                var title = $(this).text().toLowerCase();
                if (!phase_title_letter1.includes(title)) {
                    $(this).hide();
                } else {
                    $(this).show();
                }
            });
        });

        $('body').on('click', '.clear-search', function(e) {
            $('.search_val').val('');
            $(this).addClass('d-none');
            $('#search-patient').trigger('keyup');
        })

        $('.dropdown-input').click(function(){
            $('.dropdown-list').toggle();        
        });
        

    });
    //to remove the dropzone files if user selected
    $("#upload-document").on('hide.bs.modal', function() {
        if (Dropzone.instances.length != 0) {
            Dropzone.forElement("#patient-document-upload-dropzone").removeAllFiles(true);
        }
    });
    //to open the upload document dialoge
    $('#upload-btn').on('click', function() {
        if (patient_id != '' && patient_id != undefined) {
            $('.patient-name').text(patient_name);
            $("#upload-document").modal("show");
            var uploadedDocumentMap = {};
            var upload_url = "{{ url('uploads-documents/id') }}",
                upload_url = upload_url.replace('id', patient_id);
            loadDropZoneArea(patient_id);
        }
    })

    //js for custom scroll
    $(".custom-scroll-verticle").mCustomScrollbar({
        axis: "y"
    });

    //function to get all the documents of the specific patient
    function getAllDocuments(patient_id) {
        $(".document-table .minus-box").removeClass("show-minus-btn");
        $('.table-responsive').removeClass('visible-checkbox');
        var rows_selected = [];
        var start = 0;
        var length = "{{ config('services.record_per_page.RECORDS_PER_PAGE') }}";
        $('.link-block').show();
        $('.custom-block.center-align-loader').show();
        $('body').addClass('show-overlay');
        $('.select-patient-block').hide();
        $('.all-employee-detail').hide();
        $('.data_table').hide();
        $('.delete-record').hide();
        $("#document-list_filter .form-control").val('');
        // to destroy the datatables if exists
        if ($.fn.dataTable.isDataTable('#document-list')) {
            $('#document-list').DataTable().destroy();
            $('#document-list').empty();
        }
        var update_url = '{{url("get-patient-documents")}}/' + patient_id;
        var url = "{{ url('save-documents') }}" + '/' + patient_id;
        $('#patient-document-upload-dropzone').attr('action', url);
        var table = $('#document-list').DataTable({
            "initComplete": function(settings, json) {
                if(json.count == 1 || json.count == 2) {
                    $('.table-responsive').addClass('single-document');
                    $('.table-responsive').addClass('has-white-bg');
                }
                $(".document-list-wrapper .table-view-design .document-table tbody").mCustomScrollbar({
                    axis: "y",
                });
                $(".document-data-table").mCustomScrollbar({
                    axis: "x",
                     scrollbarPosition:"outside"
                });
                
                total_count = json.count;
                var api = this.api();
                var column = api.column(3);
                var types = [];
                //to check the checkbox is selected or not
                $('body').on('click', '.select-checkbox input[type="checkbox"]', function() {
                    if ($(this).prop("checked") == true) {
                        $(this).closest('tr').addClass('selected');
                    } else if ($(this).prop("checked") == false) {
                        $(this).closest('tr').removeClass('selected');
                    }
                });

                $("#select-category-type").dropkick('reset');
                //to filter the documents using category
                $('#select-category-type').on('change', function() {
                    var val = $(this).val();
                    column.search(val).draw()
                });

                //for autosuggestion in the search column
                api.cells('tr', [3, 1, 2]).every(function() {
                    // Get cell data as plain text
                    var data = $('<div>').html(this.data()).text();
                    if (dataSrc.length > 5) {
                        return false;
                    }
                    if (dataSrc.indexOf(data) === -1) {
                        dataSrc.push(data);
                    }
                });
                // Sort dataset alphabetically
                dataSrc.sort();
                $('.dataTables_filter input[type="search"]', api.table().container())
                    .typeahead({
                        source: dataSrc,
                        afterSelect: function(value) {
                            api.cells('tr', [3, 1, 2]).search(value).draw();
                        }
                    });

                    $(".document-table .minus-box").click(function(){
                        $('.all-employee-detail').hide();
                        $('.delete-record').show();
                        if($(".document-table .accordion-toggle").hasClass("show-checkbox")){
                            $(".document-table .accordion-toggle").addClass("show-checkbox  selected");
                            $(".document-table .checkbox-group").addClass(" selected");
                            $('.selected-items').text($(".document-table .accordion-toggle").length);
                            $('.document-table tbody .accordion-toggle input').prop('checked', true);
                        }
                    });

                    $(".document-table .checkbox-group em.inner-checkbox").click(function(){
                        $(".document-table .accordion-toggle").removeClass("show-checkbox  selected");
                        $(".document-table .checkbox-group").removeClass(" selected");
                        $(".document-table .minus-box").removeClass("show-minus-btn");
                        $('.document-table tbody .accordion-toggle input').prop('checked', false);
                        $('.all-employee-detail').show();
                        $('.delete-record').hide();
                        if($(".document-table tbody tr.selected").length == 0) {
                            $('.table-responsive').removeClass('visible-checkbox');
                            $(".document-table .minus-box").removeClass("show-minus-btn remove-dash");
                        }
                    });
                //to open the tooltip for rename, download and remove document options
                $('body').on('click', '.document-table .accordion-toggle .dots-link', function(e) {
                    $('.custom-list-dropdown, .dropdown-list').hide();
                    $('.modal-media.media').hide();
                    if (!($(this).siblings(".clickable-menu").is(':visible'))) {
                        e.stopPropagation();
                        $(".clickable-menu").not($(this).siblings(".clickable-menu")).fadeOut();
                        $(this).siblings(".clickable-menu").fadeToggle();
                        var space_bottom = $(this).siblings(".clickable-menu").height();
                        $(this).closest(".document-table").attr('style', 'margin-bottom:' + space_bottom + 'px !important');
                    }
                });

                //to set the attribute to remove the single document
                $('body').on('click', '.clickable-menu .delete-link', function(e) {
                    $("#delete-appointment").removeAttr('file-name file-id fileids');
                    $("#delete-appointment").attr("file-name", $(this).attr('data-filename'));
                    $("#delete-appointment").attr("file-id", $(this).attr('data-id'));
                    $("#delete-appointment").attr("file-user_id", $(this).attr('data-user-id'));
                    $('.modal-media.media').show();
                    $('.single-file-delete-name').text($(this).attr('data-filename'));
                    $('.single-file-delete-date').text(moment($(this).attr('data-file-date')).format('DD.MM.YYYY'));
                    $('#delete-appointment .remove-doc-title').text("{{ __('lang.remvoe-document-text') }}");
                    $('#delete-appointment h2').text("{{ __('lang.documentText') }} {{ __('lang.remove_doc_head') }}");
                    $('#delete-appointment').modal('show');
                });

                //to set the attribute to remove the multiple document
                $('body').on('click', '.delete-record .delete-btn', function(e) {
                    $("#delete-appointment").removeAttr('file-name file-id fileids');
                    $('.custom-list-dropdown, .dropdown-list').hide();
                    $('.modal-media.media').hide();
                    $("#delete-appointment").attr("fileids", $(this).attr('data-fileids'));
                    var table = $('#document-list').DataTable();
                    if (parseInt($('.selected-items').text()) > 1) {
                        $('.custom-list-dropdown').show();
                        var documentText = "{{ __('lang.documentsText') }} {{ __('lang.selected') }}"; // for multiple
                        $('#delete-appointment .remove-doc-title').text("{{ __('lang.remvoe-documents-text') }}");
                        $('#delete-appointment h2').text("{{ __('lang.documentsText') }} {{ __('lang.remove_doc_head') }}");
                        $('#document-list tbody input[type="checkbox"]:checked').each(function(e) {
                            var $row = $(this).closest('tr');
                            // Get row data
                            var data = table.row($row).data();
                            $('.dropdown-list').append('<li><a href="#">'+data.filename+'</a></li>')
                            $('.dropdown-input').text($('.selected-items').text() + " " + documentText);
                            $('.dropdown-list li').click(function(e){
                                $('.dropdown-list').hide(); 
                            });
                        });
                    } else {
                        $('.modal-media.media').show();
                        $('#delete-appointment .remove-doc-title').text("{{ __('lang.remvoe-document-text') }}");
                        $('#delete-appointment h2').text("{{ __('lang.documentText') }} {{ __('lang.remove_doc_head') }}");
                        var $row = $('#document-list tbody input[type="checkbox"]:checked').closest('tr');
                        var data = table.row($row).data();
                        $('.single-file-delete-name').text(data.filename);
                        $('.single-file-delete-date').text(moment(data.created_at).format('DD.MM.YYYY'));
                    }
                    $('#delete-appointment').modal('show');
                });

                //to set the attribute to rename the document
                $('body').on('click', '.clickable-menu .rename-link', function(e) {
                    $("#edit-record").attr("file-name", $(this).attr('data-filename'));
                    $("#edit-record").attr("file-id", $(this).attr('data-id'));
                    var get_file_name = $(this).attr('data-filename').substr(0, $(this).attr('data-filename').lastIndexOf('.')) || $(this).attr('data-filename');
                    $('#new_file_name').val(get_file_name);
                    $('.form-control').each(function() {
                        if ($(this).val().length > 0) {
                            // $(this).focus();
                            $(this).addClass("onfocus");
                        }
                    });
                    $('#edit-record').modal('show');
                });
                $('.download-link').on('click', function() {
                    var file_ids = [];
                    var documentId = $(this).attr('data-id');
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        type: 'POST',
                        url: "{{ url('documents/show/') }}"+'/'+ documentId,
                        data: {
                                'is_preview': true,
                                private_key: private_key,
                                download_flag: true,
                            },
                        success: function(data) {
                            if(data && data['type'] == 'image') {
                                var url = "data:image/jpg;base64,"+data['base64_data'];
                            } else if(data && data['type'] == 'pdf') {
                                var url = "data:application/pdf;base64,"+data['base64_data'];
                            } else {
                                var url = '';
                            }

                            if(url) 
                            {
                                var a = document.createElement("a"); //Create <a>
                                a.href = url; //Image Base64 Goes here
                                a.download = data['filename']; //File name Here
                                a.click(); //Downloaded file
                            }
                        },
                        error: function(e) {
                            console.log(e);
                        }
                    });
                });

                //to set the attribute to change-category the document
                $('body').on('click', '.clickable-menu .change-category-link', function(e) {
                    $("#change-category").attr("file-name", $(this).attr('data-filename'));
                    $("#change-category").attr("file-id", $(this).attr('data-id'));
                    $("#change-category").attr("file-type", $(this).attr('data-file-type'));
                    var elm = new Dropkick("#new_category");
                    // Select by index
                    elm.select($(this).attr('data-file-type')); //selects & returns 5th item in the list
                    $('#new_category').dropkick('refresh');
                    $('.form-control').each(function() {
                        if ($(this).val().length > 0) {
                            // $(this).focus();
                            $(this).addClass("onfocus");
                        }
                    });
                    $('#change-category').modal('show');
                });

                $('#remove-btn').on('click', function() {
                    var file_ids = [];
                    var file_name = $(this).closest('#delete-appointment').attr('file-name');
                    var file_id = $(this).closest('#delete-appointment').attr('file-id');
                    var user_id = $(this).closest('#delete-appointment').attr('file-user_id');
                    var type = $(this).closest('#delete-appointment').attr('fileids');
                    file_ids.push(file_id);
                    if (type) {
                        var rows_selected = [];
                        var table = $('#document-list').DataTable();
                        $('#document-list tbody input[type="checkbox"]:checked').each(function(e) {
                            var $row = $(this).closest('tr');
                            // Get row data
                            var data = table.row($row).data();
                            // Get row ID
                            var rowId = data.id;
                            // // Determine whether row ID is in the list of selected row IDs 
                            var index = $.inArray(rowId, rows_selected);

                            // // If checkbox is checked and row ID is not in list of selected row IDs
                            if (this.checked && index === -1) {
                                rows_selected.push(rowId);

                                // Otherwise, if checkbox is not checked and row ID is in list of selected row IDs
                            } else if (!this.checked && index !== -1) {
                                rows_selected.splice(index, 1);
                            }
                        });
                        var file_ids = rows_selected;
                    }
                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        type: 'POST',
                        url: "{{ url('remove-documents') }}" + '/' + user_id,
                        data: {
                            'document_id': file_ids,
                        },
                        success: function(data) {
                            location.reload();
                        },
                        error: function(e) {}
                    });
                })

                //to get the count of the selected documents for remove
                $('body').on('change', '.dt-checkboxes', function(e) {
                    var countchecked = table.rows().nodes().to$().find('input[type="checkbox"]:checked').length;
                    if (countchecked) {
                        var documentText = "{{ __('lang.documentText') }} {{ __('lang.selected') }}"; // for single
                        if (countchecked > 1) {
                            documentText = "{{ __('lang.documentsText') }} {{ __('lang.selected') }}"; // for multiple
                        }
                        $('.all-employee-detail').hide();
                        $('.delete-record').show();
                        $('.delete-record span').html('<span class="selected-items">' + countchecked + '</span> ' + documentText);
                    } else {
                        $('.all-employee-detail').show();
                        $('.delete-record').hide();
                    }
                });

                // to get the selected ids of the document to remove 
                $('#document-list tbody').on('click', 'input[type="checkbox"]', function(e) {
                    var $row = $(this).closest('tr');
                    // Get row data
                    var table = $('#document-list').DataTable();
                    var data = table.row($row).data();
                    // Get row ID
                    var rowId = data.id;
                    // // Determine whether row ID is in the list of selected row IDs 
                    var index = $.inArray(rowId, rows_selected);

                    // // If checkbox is checked and row ID is not in list of selected row IDs
                    if (this.checked && index === -1) {
                        rows_selected.push(rowId);

                        // Otherwise, if checkbox is not checked and row ID is in list of selected row IDs
                    } else if (!this.checked && index !== -1) {
                        rows_selected.splice(index, 1);
                    }

                    if (this.checked) {
                        $row.addClass('selected');
                    } else {
                        $row.removeClass('selected');
                    }

                    if($(".document-table tbody tr.selected").length >= 1){
                        $('.table-responsive').addClass('visible-checkbox');
                        // $(".document-table .accordion-toggle").addClass("show-checkbox");
                        $(".document-table .minus-box").addClass("show-minus-btn");
                        if($(".document-table tbody tr.selected").length != $(".document-table tbody tr").length) {
                            $(".document-table .checkbox-group").removeClass(" selected");
                        } else {
                            $(".document-table .checkbox-group").addClass(" selected");
                        }
                    } else {
                        $(".document-table .minus-box").removeClass("show-minus-btn");
                        $('.table-responsive').removeClass('visible-checkbox');
                    }

                    e.stopPropagation();
                });

                var documentText = "{{ __('lang.documentText') }}"; // for single
                if (table.page.info().recordsDisplay > 1) {
                    documentText = "{{ __('lang.documentsText') }}"; // for multiple
                }
                $('.custom-block.center-align-loader').hide();
                $('body').removeClass('show-overlay');
                if (table.page.info().recordsDisplay) {
                    $('.data_table').show();                    
                    $('.all-employee-detail.search-datatable').show();
                    $('.document-list-wrapper .table-view-design .document-table thead').css('display', 'contents');
                } else {
                    $('.data_table').hide();
                    $('.select-patient-block h2').text(locale == "de" ? "Keine Dokumente vorhanden" : 'No documents available');
                    $('.select-patient-block').show();
                }
                $('#count').html(table.page.info().recordsDisplay ? ": <em>" + table.page.info().recordsDisplay + ' ' + documentText + "</em>" : '');
                added_row_count = table.page.info().recordsTotal;
                //to load the data after scoll
                var tbody_element = $(".document-template-page .document-list-wrapper .table-view-design .document-table tbody");
                tbody_element.scroll(function(e) {
                    clearTimeout($.data(this, 'scrollTimer'));
                    $.data(this, 'scrollTimer', setTimeout(function() {
                        if (tbody_element.scrollTop() >= 50) {
                            // processing = true;
                            start = parseInt(start) + parseInt(length);
                            if (added_row_count < total_count) {
                                $('.more-doc-loader').show();
                                loadData(start, table, api);
                            }
                        }
                    }, 250));
                });

                // on mouse over display common checkbox
                $(".document-table thead tr th.select-checkbox").on('mouseover', function () {
                    $(".document-table .minus-box").addClass("show-minus-btn remove-dash");
                    $(".document-table tbody tr").addClass("show-checkbox");
                    if($(".document-table tbody tr.accordion-toggle").hasClass("selected")){
                        $(".document-table .minus-box").removeClass("remove-dash");
                    }
                });

                $(".document-table thead tr th.select-checkbox").on('mouseleave', function () {
                    if(!$(".document-table .checkbox-group").hasClass("selected")){
                            $(".document-table .minus-box").removeClass("show-minus-btn remove-dash");
                            $(".document-table tbody tr").removeClass("show-checkbox");
                    }
                    if($(".document-table tbody tr.accordion-toggle").hasClass("selected")){
                        $(".document-table .minus-box").addClass("show-minus-btn");
                            $(".document-table .minus-box").removeClass("remove-dash");
                        }
                });

                $(".all-employee-sorting .sorting-dropdown .dk-selected:not(.all-doc)").addClass("active");
                    $('.all-employee-sorting .sorting-dropdown.custom_dropdown').dropkick({
                    change: function () {
                        $(".all-employee-sorting .sorting-dropdown .dk-selected:not(.all-doc)").addClass("active");
                    }
                });

                /*$('a[data-toggle="tab"]').on( 'shown.bs.tab', function (e) {
                    $($.fn.dataTable.tables( true ) ).css('width', '100%');
                    $($.fn.dataTable.tables( true ) ).DataTable().columns.adjust().draw();
                } );*/
            },
            "bProcessing": true,
            "bServerSide": false,
            "bRetrieve": true,
            "bLengthChange": false,
            "dom": 'Bfrtip',
            // "scrollY": "calc(100vh - 170px)",
            "bScrollInfinite": true,
            "bScrollCollapse": true,
            //"scrollX": true,
            'columnDefs': [{
                    'targets': 0,
                    'orderable': false,
                    'className': 'select-checkbox',
                    'render': function() {
                        return '<input type="checkbox" class="dt-checkboxes">';
                    },
                },
                {
                    "targets": [3],
                    "visible": false
                }
            ],
            'select': {
                'style': 'multi',
                'selector': 'td:first-child'
            },
            "fnDrawCallback": function (oSettings) {
                var documentText = "{{ __('lang.documentText') }}"; // for single
                if (oSettings.aiDisplay.length == 0 || oSettings.aiDisplay.length > 1) {
                    documentText = "{{ __('lang.documentsText') }}"; // for multiple
                }
                var categoryText = $("#select-category-type").val() ? ' in ' + $("#select-category-type option:selected").text() : '';
                $('#count').html(total_count ? ": <em>" + oSettings.aiDisplay.length + ' ' + documentText + categoryText + "</em>" : '');
            },
            // 'order': [
            //     [2, 'desc']
            // ],
            ajax: {
                url: update_url,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: {
                    'start': start,
                },
                type: "GET",
                error: function(jqXHR, exception) {
                    $('#employee-list_processing').hide();
                    var message = locale == "de" ? 'Ungültige Anforderung' : 'Bad request';
                    $('#employee-list tbody')
                        .empty()
                        .append('<tr><td colspan="12" class="dataTables_empty">' + message + '</td></tr>')
                },
            },
            "createdRow": function(row, data, dataIndex) {
                var data = table.rows().data();
                $(row).addClass('accordion-toggle')
            },
            columns: [{
                    "title": '<div class="checkbox-group"><em class="minus-box"></em><em class="inner-checkbox"><input type="checkbox" id="select_all" /></em></div>',
                    "data": null
                },
                {
                    "title": "<span>{{ __('lang.name') }}</span>",
                    "data": "filename",
                    "render": function(data, type, row) {
                        const acceptedImageTypes = ['GIF', 'JPEG', 'PNG', 'JPG'];
                        var type = row.url.split('.');
                        var img_url = "{{ asset('assets/images/document.svg') }}";
                        var created_at = moment(row.created_at).format('DD.MM.YYYY');
                        // if (acceptedImageTypes.includes(type[type.length - 1].toUpperCase())) {
                        //     img_url = "{{ url('/storage')  }}" + row.url;
                        // }
                        // return '<embed width="191" height="207" name="plugin" src="http://library.umac.mo/ebooks/b17771201.pdf" type="application/pdf">';
                        var show_patient_url = "'{{ url('documents/show/document_id')}}'";
                        show_patient_url = show_patient_url.replace('document_id', row.encrypted_id);
                        return '<div onclick="window.location.href=' + show_patient_url + ';" class="title-wrapper"><div class="img-wrapper"><img src=' + img_url + ' title="Logo"></div><div class="document-detail"><span>' + (row.filename).charAt(0).toUpperCase() + (row.filename).slice(1) + '</span><span class="tiny-text">' + created_at + '</span></div></div>';
                    }
                },
                {
                    "title": "<span>{{ __('lang.changed') }}</span>",
                    "data": "updated_at",
                    "render": function(data, type, row) {
                        var updated_at = locale == 'de' ? moment(row.updated_at).locale('de').format('DD.MM.YYYY HH:mm') + ' Uhr' : moment(row.updated_at).format('DD.MM.YYYY hh:mm A');
                        var show_patient_url = "'{{ url('documents/show/document_id')}}'";
                        show_patient_url = show_patient_url.replace('document_id', row.encrypted_id);
                        var uploaded_by = row.uploader ? row.uploader.firstname + ' ' + row.uploader.lastname : patient_name;
                        return '<div onclick="window.location.href=' + show_patient_url + ';" class="document-detail"><span>' + updated_at + '</span><span class="tiny-text"> {{ __("lang.by") }}: ' + uploaded_by + '</span></div>'
                    }
                },
                {
                    "data": "type",
                },
                {
                    "data": null,
                    "render": function(data, type, row) {
                        var show_patient_url = "{{ url('documents/show/document_id')}}";
                        show_patient_url = show_patient_url.replace('document_id', data.encrypted_id);
                        var html = '';
                        // html += "<a href=" + show_patient_url + " title='{{ __('lang.to-open') }}' class='open-btn btn caribbean-green-btn'>{{ __('lang.to-open') }}</a>";
                        html += "<a href='javascript:void(0);' class='dots-link'><img src='{{ asset('assets/images/menu.svg') }}' alt='three-dots'></a>";
                        html += '<div class="clickable-menu">';
                        html += '<ul class="icon-listing">';
                        @php if(!empty($permission_array) && $permission_array['can_update']) { @endphp
                            html += '<li>';
                            html += '<a href="javascript:void(0);" class="rename-link" data-toggle="modal" data-filename="' + data.filename + '" data-id="' + data.encrypted_id + '" title="{{ __("lang.rename") }}"><i><img src="{{ asset("assets/images/edit-icon.svg") }}" alt=""></i>{{ __("lang.rename") }}</a>';
                            html += '</li>';
                            html += '<li>';
                            html += '<a href="javascript:void(0);" class="change-category-link" data-toggle="modal" data-file-type="'+data.type+'" data-filename="' + data.filename + '" data-id="' + data.encrypted_id + '" title="{{ __("lang.change-category") }}"><i><img src="{{ asset("assets/images/all-doc.svg") }}" alt=""></i>{{ __("lang.change-category") }}</a>';
                            html += '</li>';
                        @php } @endphp
                        html += '<li>';
                        html += '<a href="javascript:void(0);" class="download-link" data-id="' + data.encrypted_id + '" title="{{ __("lang.download") }}"><i><img src="{{ asset("assets/images/download-light.svg") }}" alt=""></i>{{ __("lang.download") }}</a>';
                        html += '</li>';
                        @php if(!empty($permission_array) && $permission_array['can_delete']) { @endphp
                            html += '<li>';
                            html += '<a href="javascript:void(0);" title="{{ __("lang.clear") }}" class="delete-link" data-toggle="modal" data-user-id="' + data.encrypted_user_id + '" data-filename="' + data.filename + '" data-id="' + data.id + '" data-file-date="'+ data.created_at +'"><i><img src="{{ asset("assets/images/delete.svg") }}" alt=""></i>{{ __("lang.clear") }}</a>';
                            html += '</li>';
                        @php } @endphp
                        html += '</ul>';
                        html += '</div>';
                        return html;
                    }
                },
            ],
            "paging": false,
            "info": false,
            "language": {
                "search": "",
                "searchPlaceholder": locale == "de" ? "Suche..." : 'Search...',
                "processing": '<img class="center-loader" src="{{ asset("assets/images/edit-loader.gif") }}" alt="">',
                "zeroRecords": locale == "de" ? "Keine Dokumente vorhanden" : 'No documents available',
            }
        });
    }

    //to change the view (list, grid)
    function changeView(view, data) {
        $('.view-btn').removeClass('active');
        $(data).addClass('active');
        switch (view) {
            case 'grid-zoom':
                if ($('#document-list').is('.grid-layout') || $('#document-list').is('.col-3')) {
                    $('#document-list').removeClass('grid-layout col-3');
                }
                $('#document-list, .document-list_wrapper').addClass('grid-layout col-3');
                break;
            case 'grid':
                if ($('#document-list').is('.grid-layout') || $('#document-list').is('.col-3')) {
                    $('#document-list').removeClass('grid-layout col-3');
                }
                $('#document-list, .document-list_wrapper').addClass('grid-layout');
                break;
            default:
                if ($('#document-list').is('.grid-layout') || $('#document-list').is('.col-3')) {
                    $('#document-list, .document-list_wrapper').removeClass('grid-layout col-3')
                }
        }
    }

    //function to show/hide the label in patient list
    function showHidePhaseLabel() {
        var phase_title_letter = [];
        $(".draggable-list .draggable-item").each(function() {
            if (!$(this).hasClass("inactive")) {
                var string_char = $(this).find(".title").text().charAt(0);
                phase_title_letter.push(string_char.toLowerCase());
            }
        });
        $(".list-title").each(function() {
            var title = $(this).text().toLowerCase();
            if (!phase_title_letter.includes(title)) {
                $(this).hide();
            } else {
                $(this).show();
            }
        });
    }

    // to submit the final document form to save the documents details to database
    $('#save-btn-id').on('click', function(event) {
        event.preventDefault();
        $(".document-loader").show();
        $("#save-btn-id").hide();
        $('a.close').hide();
        var formData = new FormData(document.querySelector('#patient-document-upload-dropzone'))
        //ajax call to save the documents
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            url: "{{ url('save-documents') }}" + '/' + patient_id,
            async: true,
            dataType: "json",
            data: formData,
            type: 'post',
            processData: false,
            contentType: false,
            success: function(response) {
                $(".document-loader").hide();
                $("#save-btn-id").show();
                $('a.close').show();
                if (!response.success) {
                    //to set the server side errors messages
                    $.each(response.errors, function(key, data) {
                        var docName = key.split(".");
                        if(docName[0] && docName[1] && docName[2]) {
                            var docNameFind = docName[0]+"["+docName[1].replace('->','.')+"]["+docName[2]+"]";
                            $("select[name='"+docNameFind+"']").parent().find('.dk-selected').addClass("category-doc-validate");
                        }
                        $('#toast-msg').html(data);
                    });
                    $('.custom-toast').show().delay(5000).slideUp(800);
                } else {
                    warning = false;
                    location.reload();
                }
            },
            error: function(err_res) {
                $(".document-loader").hide();
                $("#save-btn-id").show();
                $('a.close').show();
                if (err_res.status == 400 && err_res.responseJSON.error != '') {
                    $('#toast-msg').html(err_res.responseJSON.error);
                    $('.custom-toast').show().delay(5000).slideUp(800);
                    $(".modal-wrap").animate({
                        scrollTop: 0
                    });
                }
            }
        });
    });

    //to rename the documents 
    $('#rename-btn').on('click', function() {
        $('.form-group').removeClass('has-error');
        $('span.error-msg').html('');
        var file_old_name = $(this).closest('#edit-record').attr('file-name');
        var file_id = $(this).closest('#edit-record').attr('file-id');
        var form = $('#renameDocumentForm');
        var file_name = $('#new_file_name').val();
        $('#old_file_name').val(file_old_name);
        $('#file_id').val(file_id);
        if (file_name == '') {
            $('input[name="updated_name"]').parent('.form-group').addClass('has-error');
            var message = locale == 'de' ? "Bitte geben Sie den Namen ein" : "Please enter name";
            $('input[name="updated_name"]').parent().find('span.error-msg').html(message);
        } else {
            var file_name_text = file_old_name.split('.').slice(0, -1).join('.');
            if (file_name_text == file_name) {
                $('input[name="updated_name"]').parent('.form-group').addClass('has-error');
                var message = locale == 'de' ? "Bitte benennen Sie das Dokument um." : "Please rename document.";
                $('input[name="updated_name"]').parent().find('span.error-msg').html(message);
            } else {
                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    url: "{{ url('/rename-document/') }}" + '/' + file_id,
                    data: {
                        'updated_name': file_name,
                        'old_file_name': file_old_name,
                        'file_id': file_id,
                        'user_id': patient_id,
                    },
                    type: 'post',
                    success: function(response) {
                        if (response.custom_error) {
                            $('input[name=updated_name]').parent('.form-group').addClass('has-error');
                            $('input[name=updated_name]').parent().find('span.error-msg').html(response.errors);
                        } else if (!response.success) {
                            //to set the server side errors messages
                            $.each(response.errors, function(key, data) {
                                $('input[name=' + key + ']').parent('.form-group').addClass('has-error');
                                $('input[name=' + key + ']').parent().find('span.error-msg').html(data);
                            });
                        } else if (response.status == '200') {
                            location.reload();
                        }
                    },
                    error: function(err_res) {
                        if (err_res.status == 400 && err_res.responseJSON.errors != '') {
                            $(".modal-wrap").animate({
                                scrollTop: 0
                            });
                        }
                    }
                });
            }
        }

    });

    //to change the category of the document
    $('#change-category-btn').on('click', function() {
        var file_id = $(this).closest('#change-category').attr('file-id');
        var form = $('#renameDocumentForm');
        var file_category = $('#new_category').val();
        $('#file_id').val(file_id);
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                url: "{{ url('/change-category/') }}" + '/' + file_id,
                data: {
                    'category': file_category,
                    'file_id': file_id,
                },
                type: 'post',
                success: function(response) {
                    location.reload();
                },
                error: function(err_res) {
                    if (err_res.status == 400 && err_res.responseJSON.errors != '') {
                        $(".modal-wrap").animate({
                            scrollTop: 0
                        });
                    }
                }
            });
    });

    //to remove the documents from the storage folder if user not saved it 
    window.onbeforeunload = function(e) {
        if (warning && count) {
            Dropzone.forElement("#patient-document-upload-dropzone").removeAllFiles(true);
            return "You have made changes on this page that you have not yet confirmed. If you navigate away from this page you will lose your unsaved changes";
        }
    };

    //function to append the new data after scroll
    function loadData(start, table, api) {
        var update_url = '{{url("get-patient-documents")}}/' + patient_id;
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            url: update_url,
            type: 'GET',
            data: {
                'start': start,
            },
            success: function(response) {
                var column = api.column(3);
                var types = [];
                table.rows.add(response.data).draw(false);
                added_row_count = table.page.info().recordsTotal;
                //to filter the documents using category
                $('#select-category-type').on('change', function() {
                    var val = $(this).val();
                    column.search(val).draw()
                });
                $(".document-table .checkbox-group").removeClass(" selected");
                $('.more-doc-loader').hide();
            },
            error: function(err_res) {
                if (err_res.status == 400 && err_res.responseJSON.error != '') {
                    $('#toast-msg').html(err_res.responseJSON.error);
                    $('.custom-toast').show().delay(5000).slideUp(800);
                    $(".modal-wrap").animate({
                        scrollTop: 0
                    });
                }
            }
        });
    }

    //to load the dropzone 
    function loadDropZoneArea(patient_id) {
        $('.custom-class').empty();
        if (patient_id != '') {
            var url = "{{ url('get-dropzone-view') }}" + '/' + patient_id;
        }
        $.ajax({
            type: "POST",
            url: url,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(data) {
                $('#save-document-id').removeClass('disabled');
                $(".custom-class").append(data);
            },
            error: function(data) {}
        });
    }
</script>