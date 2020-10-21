@extends('layouts.main_with_tabs')
@section('content')
@component('layouts.patient_tabs')
@section('slot')
<div class="tabbing-block">
    <div class="fixed-content-block">
        <div class="inner-content-block">
            <div class="tab-content-block">
                <div data-id="praxis" class="tab-inner-content">
                </div>
                <div data-id="arzte" class="tab-inner-content"></div>
                <div data-id="mitarbeiter" class="tab-inner-content active">
                    <div class="document-wrap">
                        <div class="content-block">
                            <div class="content-header">
                                <div class="header-preview-block">
                                    <a href="{{ url($common_data['back_link_history']) }}" title="" class="back-btn"><i><img src="{{ asset('assets/images/left-arrow.svg') }}"></i></a>
                                    <h2>{{ __('lang.preview') }}: {{ $document->filename }}</h2>
                                </div>
                                <div class="document-title-wrapper">
                                    <span class="document-title">{{ ucfirst( __('lang.'. $document->type) ) }} </span>
                                    <a href="javascript:void(0);" title="" class="dots-link clickable-menu-link"><i><img src="{{ asset('assets/images/menu.svg') }}" alt="three-dots"></i></a>
                                    <div class="clickable-menu-block">
                                        <!-- <h5><i><img src="{{ asset('assets/images').'/'.Config::get('globalConstants.documents-type-icons')[$document->type] }}"></i>{{ $document->filename }}</h5> -->
                                        <!-- <h5><i><img src="{{ asset('assets/images/document-gray.svg') }}"></i>{{ strtoupper( __('lang.'. $document->type) ) }} - {{ ucfirst(__('lang.document')) }}</h5> -->
                                        <ul class="icon-listing">
                                            @if(!empty($permission_array) && $permission_array['can_update'])
                                                <li>
                                                    <a href="javascript:void(0);" class="rename-link" title="{{ __('lang.rename') }}" data-toggle="modal" data-filename="{{$document->filename}}" data-id="{{ encrypt($document->id) }}"><i><img src="{{ asset('assets/images/edit-icon.svg') }}" alt=""></i>{{ __('lang.rename') }}</a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0);" class="change-category-link" title="{{ __('lang.change-category') }}" data-toggle="modal" data-file-type="{{$document->type}}" data-filename="{{$document->filename}}" data-id="{{ encrypt($document->id) }}"><i><img src="{{ asset('assets/images/all-doc.svg') }}" alt=""></i>{{ __('lang.change-category') }}</a>
                                                </li>
                                            @endif
                                            <!-- <li>
                                                <a href="#" title="{{ __('lang.open-with') }}"><i><img src="{{ asset('assets/images/share-icon-gray.svg') }}" alt=""></i>{{ __('lang.open-with') }}</a>
                                            </li> -->
                                            <li>
                                                <a class="download-link" href="javascript:void(0);" title="{{ __('lang.download') }}"><i><img src="{{ asset('assets/images/download-light.svg') }}" alt=""></i>{{ __('lang.download') }}</a>
                                            </li>
                                            @if(!empty($permission_array) && $permission_array['can_delete'])
                                                <li>
                                                    <a href="javascript:void(0);" title="{{ __('lang.clear') }}" class="delete-link" data-toggle="modal" data-target="#delete-appointment"><i><img src="{{ asset('assets/images/delete.svg') }}" alt=""></i>{{ __('lang.clear') }}</a>
                                                </li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>

                            </div>
                            <div class="view-doc">
                            </div>
                        </div>
                    </div>
                </div>
                <div data-id="therapieplanvorlagen" class="tab-inner-content"></div>
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
                    <p>{{ __('lang.remvoe-document-text') }}</p>
                    <div class="modal-media media">
                        <div class="media-left">
                            <img src="{{ asset('assets/images/document.svg') }}" class="media-img">
                        </div>
                        <div class="media-body">
                            <h4 class="single-file-delete-name">{{ $document->filename}}</h4>
                        </div>
                    </div>
                    <!-- <div class="custom-list-dropdown">
                        <span class="dropdown-input"></span>
                        list of all doc name for delete
                        <ul class='dropdown-list'>
                        </ul>
                    </div> -->
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
<script>
    $(document).ready(function() {

        $('.dropdown-input').click(function(){
            $('.dropdown-list').toggle();        
        });

        var organization_id = '{{ \Session::has('organization_id') ? decrypt(\Session::get('organization_id')) : '' }}';
        var redirect_url = "{{ url('scan-key') }}";
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

        $.ajax({
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            type: 'POST',
            url: "{{ url('documents/show/').'/'. encrypt($document->id) }}",
            data: {
                    'is_preview': true,
                    private_key: private_key,
                },
            success: function(data) {
                if(data && data['type'] == 'image') {
                    $('.view-doc').append('<img src="data:image/jpg;base64,'+data['base64_data']+'" alt="document-image" />');
                } else if(data && data['type'] == 'pdf') {
                    $('.view-doc').append('<object data="data:application/pdf;base64,'+data['base64_data']+'" type="application/pdf"></object>');
                    
                } else {
                    $('.view-doc').append('<h2>{{ __("lang.no-preview-text") }}</h2>');
                }
            },
            error: function(e) {
                console.log(e);
            }
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

        // to download the documents
        $('.download-link').on('click', function() {
            var file_ids = [];
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                type: 'POST',
                url: "{{ url('documents/show/').'/'. encrypt($document->id) }}",
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

        // to remove the documents from the storage and folder
        $('#remove-btn').on('click', function() {
            var file_ids = [];
            var document_id = "{{ $document->id}}";
            file_ids.push(document_id);
            $.ajax({
                headers: {
                    'X-CSRF-TOKEN': "{{ csrf_token() }}"
                },
                type: 'POST',
                url: "{{ url('remove-documents', encrypt($user_id)) }}",
                data: {
                    'document_id': file_ids,
                },
                success: function(data) {
                    window.location.href = "{{ url('patients/documents/'). '/'.encrypt($user_id) }}";
                },
                error: function(e) {
                    console.log(e);
                }
            });
        });

        //for rename the document
        $(".rename-link").click(function() {
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

        //to set the attribute to change-category the document
        $('.change-category-link').click(function(e) {
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


        //to rename the document
        $('#rename-btn').on('click', function() {
            $('.form-group').removeClass('has-error');
            $('span.error-msg').html('');
            var file_old_name = $(this).closest('#edit-record').attr('file-name');
            var file_id = $(this).closest('#edit-record').attr('file-id');
            var form = $('#renameDocumentForm');
            var file_name = $('#new_file_name').val();
            $('#old_file_name').val(file_old_name);
            $('#file_id').val(file_id);
            if(file_name == ''){
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
                            'updated_name' : file_name,
                            'old_file_name' : file_old_name,
                            'file_id': file_id,
                            'user_id': "{{ encrypt($user_id) }}",
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
    })
</script>
@endpush