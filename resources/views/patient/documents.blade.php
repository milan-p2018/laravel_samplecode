@extends('layouts.main_with_tabs')

@section('content')
@component('layouts.patient_tabs')
@section('slot')
<div class="tabbing-block" onbeforeunload="myFunction()">
    <div class="tab-content-block">
        <div data-id="praxis" class="tab-inner-content">
        </div>
        <div data-id="arzte" class="tab-inner-content"></div>
        <div data-id="mitarbeiter" class="tab-inner-content active">
            <div class="employee-sorting document-page-sorting">
                <div class="add-employee-btn">
                    @if(!empty($permission_array) && $permission_array['can_create'])
                        <button type="button" class="btn btn-green dark" data-toggle="modal" data-target="#upload-document">{{ __('lang.upload-document') }}</button>
                    @endif
                </div>
                <div class="all-employee-sorting multi-row-sorting">
                    <div class="all-employee-detail pdf-icon">
                        <!-- <button class="btn btn-all-employee"><i><img src="{{ asset('assets/images/all-icon.svg') }}"></i>{{ __('lang.all-documents') }} <span id="count"></span></button> -->
                    </div>
                    <div class="all-employee-detail search-datatable" style="display:none">
                        {{--<button class="btn icon-btn search-btn"><i><img src="{{ asset('assets/images/search-icon.svg') }}"></i></button>--}}
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
                            <button data-fileids="true" class="btn transparent-btn delete-btn"><span></span> <i><img src="{{ asset('assets/images/delete.svg') }}"></i></button>
                        </div>
                    @endif
                </div>
            </div>
            <div class="table-responsive secondary_document_table">
                <table class="collapsible-table document-table" id="document-list">
                    <thead style="display:none">
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
        </div>
        <div data-id="therapieplanvorlagen" class="tab-inner-content">
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
                <div class="modal-container">
                    <div class="btn-wrap">
                        <a href="#" title="{{ __('lang.file-upload') }}" id="select-doc" class="btn caribbean-green-btn">{{ __('lang.file-upload') }} </a>
                        <div class="right-heading">
                            <div class="form-group">
                                
                            </div>
                            <button class="btn border-btn img-border-btn"><i><img src="{{ asset('assets/images/user-fill.svg') }}"></i>{{ucFirst($user->firstname) }} {{ ucFirst($user->lastname) }}</button>
                        </div>
                    </div>
                    <div class="drop-document">
                        <form method="post" class="dropzone document-upload-dropzone dropzone-counter-outer" files=true id="document-upload-dropzone" action="{{ url('save-documents', $id) }}" enctype="multipart/form-data">
                        <div class="document-upload-counter" style="display:none">
                            <span><em class="uploadDocCount">0</em> {{ __('lang.form_doc') }} <em class="totalDoc">0</em> {{ __('lang.documents_uploaded') }} (<em class="totalFileSize">0</em> MB) </span>
                        </div>
                            <div class="dz-message" data-dz-message>
                                <div class="edit-profile">
                                    <a class="btn upload-btn document-border" title="Upload">
                                        <i><img src="{{ asset('assets/images/upload - green.svg') }}"></i>
                                    </a>
                                    <p>{{ __('lang.drag-picture-here-text') }}</p>
                                    <span>{{ __('lang.or') }}</span>
                                    <a href="#" title="{{ __('lang.choose') }}">{{ __('lang.choose') }}</a>
                                    <p class="document-note">JPG, BMP, PNG, PDF (max. 10 MB)</p>
                                </div>
                            </div>
                            <div class="dz-message old-msg" data-dz-message>
                                <div class="hover-message">
                                    {{ __('lang.put-files-here-text') }}
                                </div>
                            </div>
                            @csrf
                            <div class="fallback">
                                <input name="file" type="files" multiple />
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="btn-wrap">
                    <button class="btn btn-green " id="save-btn-id" title="{{ __('lang.save') }}" >{{ __('lang.save') }}</button>
                    <a href="javascript:void(0);" class="btn btn-transparent btn-lg close" title="{{ __('lang.close') }}" aria-label="Close">{{ __('lang.close') }}</a>
                    <i class="edit-loader submit-button-loader document-loader" style="display:none"><img src="{{ asset('assets/images/edit-loader.gif') }}" alt=""></i>
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
                    <input type="hidden" name="old_file_name" id="old_file_name">
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
@endsection
@endcomponent
@endsection
@push('custom-scripts')
<script type="text/javascript" src="//gyrocode.github.io/jquery-datatables-checkboxes/1.2.11/js/dataTables.checkboxes.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.17.1/moment-with-locales.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-3-typeahead/4.0.2/bootstrap3-typeahead.min.js"></script>
<script>
    var warning = true;
    var count = 0;
    var dataSrc = [];
    var sucess_count = 0;
    var processing;
    var start = 0;
    var length = "{{ $length }}";
    var added_row_count = 0;
    var total_count = 0;
    var total_file_size = 0;
    var private_key;
    $('#select-doc').on('click', function() {
        $(".close-link").trigger('click');
        $('#document-upload-dropzone').trigger('click');
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

    var rows_selected = [];
    $(document).ready(function() {
        var organization_id = '{{ \Session::has('organization_id') ? decrypt(\Session::get('organization_id')) : '' }}';
        var data = JSON.parse(localStorage.getItem('keys'));
        var token = $('input[name="_token"]').attr('value');

        $.each(data, function(key, value) {
            if (organization_id == value.organization_id) {
                private_key = value.private_key;
                return 0;
            };
        });
        documentsSearchFilter();

        setTimeout(function(){
            $("#document-list_filter").addClass("document-datafilter");
            var document_list = $("#document-list_filter");
            $(document_list).prependTo(".all-employee-detail.search-datatable");
        },500);

        $('.dropdown-input').click(function(){
            $('.dropdown-list').toggle();        
        });

        //to prevent the Rename-form submit on enter
        var isEnterClicked;
        $("#edit-record").bind("keypress", function (e) {
            if (e.keyCode == 13) {
                // if (isEnterClicked) {
                // 	return false;
                // }
                // isEnterClicked = true;
                $('#rename-btn').trigger('click');
            }
        });

        var id = "{{ $id }}";
        var update_url = '{{url("get-patient-documents")}}/' + id;
        var table = $('#document-list').DataTable({
            "initComplete": function(settings, json) {
                $('body').removeClass('show-datatable-overlay');
                $('body').remove('datatable-overlay');
                if(json.count == 1 || json.count == 2) {
                    $('.table-responsive').addClass('single-document');
                }
                $(".secondary_document_table .document-table tbody").mCustomScrollbar({
                    axis: "y"
                });
                total_count = json.count;
                total_exist_count = json.existDocCount;
                var api = this.api();

                // add fixed scroll
                $('#document-list_wrapper').addClass('fixed-header-lg');

                //to check the checkbox is selected or not
                $('body').on('click', '.select-checkbox input[type="checkbox"]', function() {
                    if ($(this).prop("checked") == true) {
                        $(this).closest('tr').addClass('selected');
                    } else if ($(this).prop("checked") == false) {
                        $(this).closest('tr').removeClass('selected');
                    }
                });
                var column = api.column(3);
                var types = [];
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

                //to open the tooltip for rename, download and remove document options
                $('body').on('click', '.document-table .accordion-toggle .dots-link', function(e) {
                    e.stopPropagation();
                    $('.custom-list-dropdown, .dropdown-list').hide();
                    $('.modal-media.media').hide();
                    $(".clickable-menu").not($(this).siblings(".clickable-menu")).fadeOut();

                    $(this).siblings(".clickable-menu").fadeToggle();
                    var space_bottom = $(this).siblings(".clickable-menu").height();
                    $(this).closest(".document-table").attr('style', 'margin-bottom:' + space_bottom + 'px !important');

                });

                $(".document-table .minus-box").click(function(){
                    $('.all-employee-detail').hide();
                    $('.delete-record').show();
                    if($(".document-table .accordion-toggle").hasClass("show-checkbox")){
                        $(".document-table .accordion-toggle").addClass("show-checkbox  selected");
                        $(".document-table .checkbox-group").addClass(" selected");
                        $('.delete-record span').html('<span class="selected-items">'+$(".document-table .accordion-toggle").length+'</span> '+"{{ __('lang.documentsText') }} {{ __('lang.selected') }}");
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

                //to set the attribute to remove the single document
                $('body').on('click', '.clickable-menu .delete-link', function(e) {
                    $("#delete-appointment").removeAttr('file-name file-id fileids');
                    $("#delete-appointment").attr("file-name", $(this).attr('data-filename'));
                    $("#delete-appointment").attr("file-id", $(this).attr('data-id'));
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
                        $('#delete-appointment .remove-doc-title').text("{{ __('lang.remvoe-documents-text') }}");
                        $('#delete-appointment h2').text("{{ __('lang.documentsText') }} {{ __('lang.remove_doc_head') }}");
                        var documentText = "{{ __('lang.documentsText') }} {{ __('lang.selected') }}"; // for multiple
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
                        url: "{{ url('remove-documents', $id) }}",
                        data: {
                            'document_id': file_ids,
                        },
                        success: function(data) {
                            location.reload();
                        },
                        error: function(e) {}
                    });
                })

                //to get the count of selected checkbox
                $('body').on('change', '.dt-checkboxes', function(e) {
                    var countchecked = table.rows().nodes().to$().find('input[type="checkbox"]:checked').length;
                    if (countchecked) {
                        var documentText = "{{ __('lang.documentText') }} {{ __('lang.selected') }}"; // for single
                        if (countchecked > 1) {
                            documentText = "{{ __('lang.documentsText') }} {{ __('lang.selected') }}"; // for multiple
                        }
                        $('.all-employee-detail').hide();
                        $('.delete-record').show();
                        $('.delete-record span').html('<span class="selected-items">'+countchecked+'</span> '+documentText);
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
                if(total_exist_count) {
                    $('.all-employee-detail.search-datatable').show();
                    $('.document-table thead').css('display', 'contents');
                } else {
                    var minus_height = $("header").height() + $(".header-tabbing").height() + $(".employee-sorting").height() + 150;
                    $('.dataTables_empty').css( { height: `calc(100vh - ${minus_height}px)`});
                }

                added_row_count = table.page.info().recordsTotal;
                //to load the data after scoll
                var tbody_element = $(".fixed-header-lg .document-table tbody");
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
            },
            "bProcessing": true,
            "bServerSide": false,
            "bRetrieve": true,
            "bLengthChange": false,
            "dom": 'Bfrtip',
            // "scrollY": "calc(100vh - 170px)",
            "bScrollInfinite": true,
            "bScrollCollapse": true,
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
                beforeSend: function (request) {
                    $('body').addClass('show-datatable-overlay');
                    $('body').append('<div class="datatable-overlay"></div>');
                },
                error: function(jqXHR, exception) {
                    $('body').removeClass('show-datatable-overlay');
                    $('body').remove('datatable-overlay');
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
                $('#count').text(' (' + data.length + ')');
            },
            columns: [{
                    "title": '<div class="checkbox-group"><em class="minus-box"></em><em class="inner-checkbox"><input type="checkbox" id="select_all" /></em></div>',
                    "data": null
                },
                {
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
                        var show_patient_url = "'{{ url('patients/documents/show/document_id')}}'";
                        show_patient_url = show_patient_url.replace('document_id', row.encrypted_id);
                        return '<div onclick="window.location.href=' + show_patient_url + ';" class="title-wrapper"><div class="img-wrapper"><img src=' + img_url + ' title="Logo"></div><div class="document-detail"><span>' + (row.filename).charAt(0).toUpperCase() + (row.filename).slice(1) + '</span><span class="tiny-text">' + created_at + '</span></div></div>';
                    }
                },
                {
                    "data": "updated_at",
                    "render": function(data, type, row) {
                        var updated_at = locale == 'de' ? moment(row.updated_at).locale('de').format('DD.MM.YYYY HH:mm') + ' Uhr' : moment(row.updated_at).format('DD.MM.YYYY hh:mm A');
                        var show_patient_url = "'{{ url('patients/documents/show/document_id')}}'";
                        show_patient_url = show_patient_url.replace('document_id', row.encrypted_id);
                        var uploaded_by = row.uploader ? row.uploader.firstname + ' ' + row.uploader.lastname : "{{ $common_data['user_name'] }}";
                        return '<div onclick="window.location.href=' + show_patient_url + ';" class="document-detail"><span>' + updated_at + '</span><span class="tiny-text"> {{ __("lang.by") }}: ' + uploaded_by + '</span></div>'
                    }
                },
                {
                    "data": "type",
                },
                {
                    "data": null,
                    "render": function(data, type, row) {
                        var show_patient_url = "{{ url('patients/documents/show/document_id')}}";
                        show_patient_url = show_patient_url.replace('document_id', data.encrypted_id);
                        var html = '';
                        //html += "<a href=" + show_patient_url + " title='{{ __('lang.to-open') }}' class='open-btn btn caribbean-green-btn'>{{ __('lang.to-open') }}</a>";
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
                            html += '<a href="javascript:void(0);" title="{{ __("lang.clear") }}" class="delete-link" data-toggle="modal" data-filename="' + data.filename + '" data-id="' + data.id + '"><i><img src="{{ asset("assets/images/delete.svg") }}" alt=""></i>{{ __("lang.clear") }}</a>';
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
                if(file_name_text == file_name) {
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
                            'user_id': "{{ $id }}"
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
    });

    function changeView(view, data) {
        $('.view-btn').removeClass('active');
        $(data).addClass('active');
        switch (view) {
            case 'grid-zoom':
                if ($('#document-list').is('.grid-layout') || $('#document-list').is('.col-3')) {
                    $('#document-list').removeClass('grid-layout col-3');
                }
                $('#document-list').addClass('grid-layout col-3');
                break;
            case 'grid':
                if ($('#document-list').is('.grid-layout') || $('#document-list').is('.col-3')) {
                    $('#document-list').removeClass('grid-layout col-3');
                }
                $('#document-list').addClass('grid-layout');
                break;
            default:
                if ($('#document-list').is('.grid-layout') || $('#document-list').is('.col-3')) {
                    $('#document-list').removeClass('grid-layout col-3')
                }
        }
    }
    Dropzone.autoDiscover = false;
    var uploadedDocumentMap = {};


    //initialise the dropzone
    var dropzone = new Dropzone('#document-upload-dropzone', {
        url: "{{ url('uploads-documents', $id) }}",
        autoProcessQueue: true,
        // parallelUploads: 10,
        timeout: 150000,
        filesizeBase: 1024,
        maxFilesize: 10,
        addRemoveLinks: true,
        uploadMultiple: false,
        acceptedFiles: "image/*,application/pdf",
        headers: {
            'X-CSRF-TOKEN': "{{ csrf_token() }}"
        },
        init: function() {
            var _this;
            //after files added to dropbox
            this.on('addedfile', function(file) {
                total_file_size += file.size;
                $('.totalFileSize').text((parseFloat(total_file_size)/1024/1024).toFixed(2));
                $('.totalDoc').text(dropzone.files.length);
                _this = file.previewElement;
                $(_this).find(".dz-remove").css({
                    "background-image": "url({{ asset('assets/images/cross-icon.svg') }})"
                    });
                $(_this).append("<span class='file-progress'></span>");
                var loader_html = '<div class="file-upload-progress"><i class="edit-loader submit-button-loader"><img src="{{ asset('assets/images/edit-loader.gif') }}" alt=""></i></div>';
                $(loader_html).insertAfter(_this);
            })
            //show the uploading progress
            this.on('uploadprogress', function(file, progress, bytes) {
                if (file.previewElement) {
                    _this = file.previewElement;
                    $(file.previewElement).find("span.file-progress").text(parseInt(progress) + " %");
                }
            });
            //show the remove icon after uploading complete
            this.on("complete", function(file) {
                _this = file.previewElement;
                $(".custom_dropdown").dropkick({
                    mobile: true
                });
                $(_this).find(".dz-progress").addClass("display-msg");
            });
            //run after successfully imaplemnted
            this.on("success", function(file) {
            });
            //shows the error message and remove the precreated form element
            this.on("error", function(file, response) {
                if("maxfilesizeexceeded") {
                    this.removeFile(file);
                }
                count = dropzone.files.length;
                if(count != 0) {
                    $('.edit-profile').hide();
                }
                _this = file.previewElement;
                $(_this).next('.file-upload-progress').remove();
                $(_this).find(".file-progress").remove();
                $(_this).remove();
                $('.toast_msg.danger.custom-toast').text("{{ __('lang.document-validation-message') }}").show();
                setTimeout(() => {
                    $('.toast_msg.danger.custom-toast').hide();                    
                }, 5000);
                if(!($(".dropzone .dz-complete").length)) {
                    $(".dropzone").removeClass("dz-started");
                }
                $("input[name='documents[" + file.upload.filename + "][url]']").remove();
                $("input[name='documents[" + file.upload.filename + "][size]']").remove();
                $("input[name='documents[" + file.upload.filename + "][type]']").remove();
            })

        },
        canceled: function(file) {
            total_file_size -= file.size;
            $('.totalFileSize').text((parseFloat(total_file_size)/1024/1024).toFixed(2));
            count = dropzone.files.length;
            _this = file.previewElement;
            $(_this).next(".dropdown-list").remove();
        },
        removedfile: function(file) {
            total_file_size -= file.size;
            $('.totalFileSize').text((parseFloat(total_file_size)/1024/1024).toFixed(2));
            count = dropzone.files.length;
            after_removed_count = dropzone.files.length;
            if (after_removed_count == 0 ){
                $('.edit-profile').show();
                $('.document-upload-counter').hide();
                $('.common-category-select-label').remove();
                $('.select-document-type').remove();
            }
            _this = file.previewElement;
            $('.totalDoc').text(dropzone.files.length);
            var removed_file_name = $(_this).children('.delete-btn').attr('file_name');
            $(_this).next(".dropdown-list").remove();
            $(_this).next('.file-upload-progress').remove();
            $(_this).remove();
            $('.uploadDocCount').text($('.document-select-category-each').length);
            var name = ''
            if (typeof file.upload.filename !== 'undefined' || removed_file_name != '') {
                name = removed_file_name
            } else {
                name = uploadedDocumentMap[file.name]
            }
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                type: 'POST',
                url: "{{ url('remove-documents', $id) }}",
                data: {
                    filename: name
                },
                success: function(data) {
                    $("input[name='documents[" + name + "][url]']").remove();
                    $("input[name='documents[" + name + "][size]']").remove();
                    $("input[name='documents[" + name + "][type]']").remove();
                    if(!($(".dropzone .dz-preview").length)) {
                        $(".dropzone").removeClass("dz-started");
                        // $('#save-btn-id').prop('disabled', 'disabled'); 
                    }
                },
                error: function(e) {
                    console.log(e);
                }
            });
            // var fileRef;
            // return (fileRef = file.previewElement) != null ?
            //     fileRef.parentNode.removeChild(file.previewElement) : void 0;
        },
        success: function(file, response) {
            // if(sucess_count) {
            //     $('#save-btn-id').removeClass('disabled');
            // }
            $('#save-btn-id').prop('disabled', false); 
            $(".dz-preview").find(".close-link").trigger('click');
            $.each(response, function(index, res) {
                if (res.success) {
                    count = dropzone.files.length;
                    sucess_count = dropzone.files.length;
                    _this = file.previewElement;
                    $(_this).find(".dz-remove").css({
                        "background-image": "url({{ asset('assets/images/delete.svg') }})"
                    });
                    $(_this).find(".dz-remove").addClass('delete-btn');
                    $(_this).find(".dz-remove").attr('file_name',res.file_name);
                    $(_this).find(".file-progress").remove();
                    var success_file_upload = "{{ __('lang.success_file_upload') }}";
                    $(_this).find(".dz-progress").append("<span class='success-msg'>"+success_file_upload+" ["+(parseFloat(res.size) / 1024 / 1024).toFixed(2)+" MB].</span>");
                    var html = "";
                    var commoncategorySelect = "";
                    html += '<div class="dropdown-list document-select-category-each">';
                    html += '<select class="custom_dropdown sorting-dropdown select-document-type has-white-bg" name="documents[' + res.file_name + '][type]">';
                    html += '<option value="" class="all-doc">{{ __("lang.choose-category") }}</option>';
                    commoncategorySelect += '<label for="" class="common-category-select-label">{{ __("lang.common_category_select") }}:</label>';
                    commoncategorySelect += '<select class="custom_dropdown common-category-select sorting-dropdown select-document-type has-white-bg">';
                    commoncategorySelect += '<option value="" class="all-doc">{{ __("lang.choose-category") }}</option>';
                    @php
                    foreach(Config::get('globalConstants.documents-types') as $data) {
                        @endphp
                        html += '<option value="{{ $data }}" class="{{ $data }}">{{ __("lang.".$data) }}</option>';
                        commoncategorySelect += '<option value="{{ $data }}" class="{{ $data }}">{{ __("lang.".$data) }}</option>';
                        @php
                    }
                    @endphp
                    html += '</select>';
                    commoncategorySelect += '</select>';
                    html += '</div>';
                    $(_this).next('.file-upload-progress').hide();
                    $(html).insertAfter(_this);
                    $(_this).append(
                            '<a href="#" class="edit-link" title="edit" data-filename="' + res.file_name + '" style="background-image:url({{ asset('assets/images/edit-icon.svg') }})"></a>'
                        )
                    
                    if(!($('.right-heading .form-group .common-category-select').length)) {
                        $('.right-heading .form-group').append(commoncategorySelect);
                    }
                    if(sucess_count != 0) {
                        $('.document-upload-counter').show();
                    }
                    // uploaded file count
                    $('.uploadDocCount').text($('.document-select-category-each').length);
                    edit_name();

                    function edit_name() {
                        $(_this).find(".edit-link").click(function() {
                            $(this).closest(".dz-preview").siblings().find(".save-link").trigger('click');
                            $(this).parent(".dz-preview").addClass('edit-mode');
                            var file_name = $(this).siblings(".dz-details").find(
                                ".dz-filename span").text();
                            var file_name_text = file_name.split('.').slice(0, -1).join('.');
                            var uploaded_file_name = $(this).attr('data-filename').substr(0, $(this).attr('data-filename').lastIndexOf('.')) || $(this).attr('data-filename');
                            $(this).siblings(".dz-details").find(".dz-filename span")
                                .remove();
                            $(this).siblings(".dz-details").find(".dz-filename").append(
                                '<input type="text" class="form-control doc-rename-field" value="' +
                                file_name_text + '">');
                                $(this).after(
                                '<a href="#" class="close-link" title="edit" title="edit" style="background-image:url({{ asset('assets/images/cross-icon.svg') }})"></a>'
                                );
                                $(this).after(
                                    '<a href="#" class="save-link" title="edit" data-filename="' + uploaded_file_name + '" title="edit" style="background-image:url({{ asset('assets/images/tick.svg') }})"></a>'
                                );
                            my_fun(file_name);
                            $(this).hide();
                            $('.doc-rename-field').keypress(function(event) {
                                if (event.keyCode === 10 || event.keyCode === 13) {
                                    event.preventDefault();
                                }
                            });
                        });

                        var commonCategoryVal = $('select.common-category-select').val();
                        $('.select-document-type option[value="'+commonCategoryVal+'"]').attr("selected", true);
                        $('.document-select-category-each select').dropkick('refresh');

                        $('select.common-category-select').on('change', function() {
                            $('.select-document-type option[value="'+$(this).val()+'"]').attr("selected", true);
                            $('.document-select-category-each select').dropkick('refresh');
                        })
                    }

                    function my_fun(file_name) {
                        $(".close-link").click(function() {
                            $(".dz-preview").removeClass('edit-mode');
                            $(this).siblings(".dz-details").find(".dz-filename").append(
                                '<span data-dz-name>' +
                                file_name + '<span>');
                            $(this).siblings(".dz-details").find(".dz-filename input")
                                .remove();
                            $(this).siblings(".edit-link").show();
                            $(this).siblings(".save-link").remove();
                            $(this).parent(".dz-preview").find(".file-error-msg").remove();
                            $(this).remove();

                        });

                        $(".save-link").click(function() {
                            $(".dz-preview").removeClass('edit-mode');
                            var ext = file_name.substr(file_name.lastIndexOf('.') + 1);
                            var old_file_name = $(this).attr('data-filename').substr(0, $(this).attr('data-filename').lastIndexOf('.')) || $(this).attr('data-filename');
                            var file_name_new = $(this).siblings(".dz-details").find(
                                ".dz-filename input").val();
                            var file_new_append = file_name_new + '.' + ext;
                            var file_old_append = old_file_name + '.' + ext;
                            if (!file_name_new) {
                                $(this).parent(".dz-preview").find(".success-msg").remove();
                                $(this).parent(".dz-preview").find(".dz-progress").append("<span class='file-error-msg'>Please enter file name.</span>");
                            } else {
                                if (file_new_append == file_name) {
                                    $(this).siblings(".close-link").trigger('click');
                                } else {
                                    var _this_prev = $(this);
                                    $.ajax({
                                        headers: {
                                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                                        },
                                        url: "{{ url('/rename-document/') }}",
                                        data: {
                                            'updated_name': file_name_new,
                                            'old_file_name': old_file_name,
                                            'user_id': "{{ $id }}",
                                        },
                                        type: 'post',
                                        success: function(response) {
                                            if (response.custom_error) {
                                                // error
                                                _this_prev.parent(".dz-preview").find(".success-msg").remove();
                                                _this_prev.parent(".dz-preview").find(".dz-progress").append("<span class='file-error-msg'>" + response.errors + "</span>");
                                            } else if (!response.success) {
                                                //to set the server side errors messages
                                                _this_prev.parent(".dz-preview").find(".success-msg").remove();
                                                $.each(response.errors, function(key, data) {
                                                    _this_prev.parent(".dz-preview").find(".dz-progress").append("<span class='file-error-msg'>" + data + "</span>");
                                                });
                                            } else if (response.status == '200') {
                                                // change new file name at input field for database
                                                var old_url = $("input[name='documents[" + file_old_append + "][url]']").val();
                                                $("input[name='documents[" + file_old_append + "][size]']").attr('name', "documents[" + file_new_append + "][size]");
                                                $("input[name='documents[" + file_old_append + "][url]']").attr('name', "documents[" + file_new_append + "][url]");
                                                $("select[name='documents[" + file_old_append + "][type]']").attr('name', "documents[" + file_new_append + "][type]");
                                                var new_url = old_url.replace(file_old_append, file_new_append);
                                                $("input[name='documents[" + file_new_append + "][url]']").val(new_url);

                                                // change file name at dropzone display file name
                                                _this_prev.siblings(".edit-link").attr('data-filename', file_new_append);
                                                _this_prev.siblings(".dz-details").find(".dz-filename").append(
                                                    '<span data-dz-name>' +
                                                    file_new_append + '<span>');

                                                // add rename success msg
                                                _this_prev.parent(".dz-preview").find(".success-msg").remove();
                                                _this_prev.parent(".dz-preview").find(".file-error-msg").remove();
                                                var success_file_rename = "{{ __('lang.success_file_rename') }}";
                                                _this_prev.parent(".dz-preview").find(".dz-progress").append("<span class='success-msg'>"+success_file_rename+"</span>");

                                                // remove edit/close icon
                                                _this_prev.siblings(".dz-details").find(".dz-filename input")
                                                    .remove();
                                                _this_prev.siblings(".dz-remove").attr('file_name',file_new_append);
                                                _this_prev.siblings(".edit-link").show();
                                                _this_prev.siblings(".close-link").remove();
                                                _this_prev.remove();
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

                        $(".doc-rename-field").bind("keypress", function (e) {
                            if (e.keyCode == 13) {
                                event.preventDefault();
                                $(this).closest(".dz-preview ").find(".save-link").trigger("click");

                            }
                        });
                    }
                    $('#document-upload-dropzone').append('<input type="hidden" name="documents[' + res.file_name + '][size]" value="' + res.size + '">')
                    $('#document-upload-dropzone').append('<input type="hidden" name="documents[' + res.file_name + '][url]" value="' + res.file_url + '">')
                    uploadedDocumentMap[file.name] = res.file_name
                }
            });
        },
        error: function(file, response) {}
    });

    // to submit the final document form to save the documents details to database
    $('#save-btn-id').on('click', function(event) {
        $(".document-loader").show();
        $("#save-btn-id").hide();
        $('a.close').hide();
        event.preventDefault();
        var formData = new FormData(document.querySelector('#document-upload-dropzone'))
        //ajax call to save the documents
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            url: "{{ url('save-documents', $id) }}",
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

    // override the removal callback behavior
    Dropzone.confirm = function(question, fnAccepted, fnRejected) {
        fnAccepted()
    };

    //to remove the documents from the storage folder if user not saved it 
    window.onbeforeunload = function(e) {
        if (warning && count) {
            Dropzone.forElement("#document-upload-dropzone").removeAllFiles(true);
            return "You have made changes on this page that you have not yet confirmed. If you navigate away from this page you will lose your unsaved changes";
        }
    };

    //function to append the new data after scroll
    function loadData(start, table, api) {
        var id = "{{ $id }}";
        var update_url = '{{url("get-patient-documents")}}/' + id;
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

                $('.more-doc-loader').hide();

                // //for autosuggestion in the search column
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
    //To detect the changes in dropzone
    $('body').on('change', dropzone.getQueuedFiles(), function() {
        $('.modal').each(function() {
            if($(this).hasClass('show')) {
                if($(this).attr('id') == 'upload-document') {
                    changed = 1;
                }
            }
        })
        
    })
    //if user click on cancel button
    $('body').on('click', '.modal .close', function() {
        if(changed){
            $('#closing-warning-modal').modal('show');
        } else {
            $(this).closest('.modal').modal('hide');
            changed = 0;
        }
    });
</script>
@endpush