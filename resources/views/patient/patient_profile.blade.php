<div class="full-page-wrapper">
    <div class="md-container">
        @php $editDisable = '' @endphp
        @if(!isset($patientData['verified_at']) || !$patientData['verified_at'] || ( !empty($patientData['user']) && !$patientData['user']->email_verified_at))
        @php $editDisable = 'edit-disable' @endphp
        <div class="toast-relative-align"><span>{{ __('lang.not_confirm_identity') }}</span>
            <div class="btn-wrapper">
                @if(!isset($patientData['verified_at']) || !$patientData['verified_at'])
                    <a href="#" class="btn btn-green verify-patient-button">{{ __('lang.confirm_now') }}</a>
                    @if(!empty($permission_array) && $permission_array['can_delete'])
                        <a href="#" title="" data-toggle="modal" data-target="#abort-request" class="btn btn-transparent btn-lg" data-dismiss="modal" aria-label="Close">{{ __('lang.abort') }}</a>
                    @endif
                @else
                    <a href="{{ route('resend-confirmation-mail', $id)}}" class="btn btn-green resend-email-verification-btn">{{ __('lang.resend-confirmaton-mail') }}</a>
                @endif
            </div>
        </div>
        @endif
        <div class="toast_msg danger toast-relative-align patient-profile-alert" style="display:none;">
            <a href="#" class="close_toast"><img src="{{ asset('assets/images/close.svg') }}" alt=""></a>
        </div>
        <div class="detail-block {{ $editDisable }}">
            <div class="detail-wrap">
                <div class="left-block">
                    <div class="profile-div">
                        @php $profile = asset("storage".$patientData['user']->profile_pic) @endphp
                        <a href="#" class="profile-img"><img src="{{ $profile }}" alt="" onerror="this.onerror=null;this.src='{{ asset('assets/images/no_photo_available.png') }}';"></a>
                    </div>
                </div>
                <div class="right-block">
                    <div class="heading-div">
                        @php $salutation = isset($patientData['salutation']) ? ($patientData['salutation'] == 1 ? __('lang.mr') : ($patientData['salutation'] == 2 ? __('lang.mrs') : '')) : ''; @endphp
                        <h2 class="updated-fullname">{{$salutation}} {{ isset(Config::get('globalConstants.user_title')[$patientData['title']]) ? Config::get('globalConstants.user_title')[$patientData['title']] : ''}} {{$patientData['name']}} {{$patientData['lastname']}}</h2>
                    </div>
                    <div class="detail-nav">
                        <div class="content-scrolling">
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" id="doctor-profile-tab" data-toggle="tab" href="#doctor-profile" role="tab" aria-controls="profile" aria-selected="true" title="{{ __('lang.profile') }}">{{ __('lang.profile') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="doctor-contact-tab" data-toggle="tab" href="#doctor-contact" role="tab" aria-controls="doctor-contact-tab" aria-selected="false" title="{{ __('lang.contact') }}">{{ __('lang.contact') }}</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" id="doctor-settings-tab" data-toggle="tab" href="#doctor-settings" role="tab" aria-controls="settings" aria-selected="false" title="{{ __('lang.health_insurance_header') }}">{{ __('lang.health_insurance_header') }}</a>
                                </li>
                            </ul>
                        </div>
                        <form id="patientProfileForm">
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="doctor-profile" role="tabpanel" aria-labelledby="doctor-profile-tab">
                                    <a href="javascript:void(0)" class="tab-link" title="">{{ __('lang.profile') }}</a>
                                    <div class="content-wrap">
                                        <div class="profile-wrap">
                                            <div class="custom-row">
                                                <span class="title-div">{{ __('lang.salutation') }}</span>
                                                @php $salutationShort = isset($patientData['salutation']) ? $patientData['salutation'] : '' @endphp
                                                <div class="description-div">
                                                    <div class="form-group edit-label has-select can-edit">
                                                        <select class="custom_dropdown" name="salutation" id="salutation" value="{{ isset($patientData['salutation']) ? $patientData['salutation'] : ''}}">
                                                            <option value="" >{{ __('lang.not specified') }}</option>
                                                            <option value="1" {{ $salutationShort == '1' ? 'selected="selected"' : ''}}>{{ __('lang.mr') }}</option>
                                                            <option value="2" {{ $salutationShort == '2' ? 'selected="selected"' : ''}}>{{ __('lang.mrs') }}</option>
                                                        </select>
                                                            @if(!empty($permission_array) && $permission_array['can_update']) 
                                                                <i class="edit-icon"><img src="{{ asset('assets/images/edit-icon.svg') }}" alt=""></i>
                                                                <i class="edit-loader"><img src="{{ asset('assets/images/edit-loader.gif') }}" alt=""></i>
                                                            @endif
                                                        <em class="error-msg salutation-error-span"></em>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="custom-row">
                                                <span class="title-div">{{ __('lang.title') }}</span>
                                                <div class="description-div">
                                                    <div class="form-group edit-label has-select can-edit">
                                                        <select class="custom_dropdown" name="title" id="title" value="{{ isset($patientData['title']) ? $patientData['title'] : ''}}">
                                                            <option value="">{{ __('lang.no information') }}</option>
                                                            <option value="dr." {{$patientData['title'] == 'dr.' ? 'selected="selected"' : '' }}>Dr.</option>
                                                            <option value="dr. med." {{$patientData['title'] == 'dr. med.' ? 'selected="selected"' : '' }}>Dr. Med.</option>
                                                            <option value="prof." {{$patientData['title'] == 'prof.' ? 'selected="selected"' : '' }}>Prof.</option>
                                                            <option value="dr. prof." {{$patientData['title'] == 'dr. prof.' ? 'selected="selected"' : '' }}>Prof. Dr.</option>
                                                            <option value="dr. prof. med." {{$patientData['title'] == 'dr. prof. med.' ? 'selected="selected"' : '' }}>Prof. Dr. Med.</option>
                                                        </select>
                                                        @if(!empty($permission_array) && $permission_array['can_update'])
                                                            <i class="edit-icon"><img src="{{ asset('assets/images/edit-icon.svg') }}" alt=""></i>
                                                            <i class="edit-loader"><img src="{{ asset('assets/images/edit-loader.gif') }}" alt=""></i>
                                                        @endif
                                                        <em class="error-msg title-error-span"></em>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="custom-row">
                                                <span class="title-div">{{ __('lang.firstname') }}</span>
                                                <div class="description-div">
                                                    <div class="form-group edit-label can-edit">
                                                        <input type="text" for="switch6" class="form-control" name="name" id="name" value="{{$patientData['name']}}">
                                                        @if(!empty($permission_array) && $permission_array['can_update'])
                                                            <i class="edit-icon"><img src="{{ asset('assets/images/edit-icon.svg') }}" alt=""></i>
                                                            <i class="edit-loader"><img src="{{ asset('assets/images/edit-loader.gif') }}" alt=""></i>
                                                        @endif
                                                        <em class="error-msg name-error-span"></em>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="custom-row">
                                                <span class="title-div">{{ __('lang.lastname') }}</span>
                                                <div class="description-div">
                                                    <div class="form-group edit-label can-edit">
                                                        <input type="text" for="switch6" class="form-control" name="lastname" id="lastname" value="{{$patientData['lastname']}}">
                                                        @if(!empty($permission_array) && $permission_array['can_update'])
                                                            <i class="edit-icon"><img src="{{ asset('assets/images/edit-icon.svg') }}" alt=""></i>
                                                            <i class="edit-loader"><img src="{{ asset('assets/images/edit-loader.gif') }}" alt=""></i>
                                                        @endif
                                                        <em class="error-msg lastname-error-span"></em>
                                                    </div>
                                                </div>
                                            </div>
                                            @php $gender = $patientData['gender'] ? $patientData['gender'] : ""; @endphp
                                            <div class="custom-row">
                                                <span class="title-div">{{ __('lang.gender') }}</span>
                                                <div class="description-div">
                                                    <div class="form-group edit-label has-select can-edit">
                                                        <select class="custom_dropdown" name="gender" id="gender" value="{{ $gender}}">
                                                            <option value="M" {{ $gender == 'M' ? 'selected="selected"' : ''}}>{{ __('lang.male') }}</option>
                                                            <option value="F" {{ $gender == 'F' ? 'selected="selected"' : ''}}>{{ __('lang.female') }}</option>
                                                            <option value="MISC" {{ $gender == 'MISC' ? 'selected="selected"' : ''}}>{{ __('lang.miscellaneous') }}</option>

                                                        </select>
                                                        @if(!empty($permission_array) && $permission_array['can_update'])
                                                            <i class="edit-icon"><img src="{{ asset('assets/images/edit-icon.svg') }}" alt=""></i>
                                                            <i class="edit-loader"><img src="{{ asset('assets/images/edit-loader.gif') }}" alt=""></i>
                                                        @endif
                                                        <em class="error-msg gender-error-span"></em>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="custom-row">
                                                @php $assign_doctor = !empty($patientData['assign_doctor']) ? $patientData['assign_doctor'] : ""; @endphp
                                                <span class="title-div">{{ __('lang.treating_doc') }}</span>
                                                <div class="description-div">
                                                    <div class="form-group edit-label has-select can-edit">
                                                        <select class="custom_dropdown" name="assign_doctor" id="assign_doctor" value="{{ $assign_doctor }}">
                                                            <option value="">{{ __('lang.treating_doc') }}</option>
                                                            @foreach($doctorList as $doc)
                                                            <option value="{{$doc->id}}" {{ $assign_doctor == $doc->id ? 'selected="selected"' : ''}}>{{ ucfirst($doc->fullname) }}</option>
                                                            @endforeach
                                                        </select>
                                                        @if(!empty($permission_array) && $permission_array['can_update'])
                                                            <i class="edit-icon"><img src="{{ asset('assets/images/edit-icon.svg') }}" alt=""></i>
                                                            <i class="edit-loader"><img src="{{ asset('assets/images/edit-loader.gif') }}" alt=""></i>
                                                        @endif
                                                        <em class="error-msg assign_doctor-error-span"></em>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="custom-row">
                                                <span class="title-div">{{ __('lang.Date of birth') }}</span>
                                                <div class="description-div">
                                                    <div class="form-group edit-label can-edit">
                                                        <input type="text" for="switch6" class="form-control select-date" name="bday" id="bday" value="{{$patientData['bday']}}" readonly>
                                                        @if(!empty($permission_array) && $permission_array['can_update'])
                                                            <i class="edit-icon"><img src="{{ asset('assets/images/edit-icon.svg') }}" alt=""></i>
                                                            <i class="edit-loader"><img src="{{ asset('assets/images/edit-loader.gif') }}" alt=""></i>
                                                        @endif
                                                        <em class="error-msg bday-error-span"></em>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="custom-row notice-row  {{!empty($patientData['note']) ? 'has-data' : ''}}">
                                                <span class="title-div">{{ __('lang.note') }}</span>
                                                <div class="description-div">
                                                    <div class="form-group edit-label can-edit">
                                                        <textarea type="text" for="switch6" class="form-control" name="note" id="note" value="{{!empty($patientData['note']) ? $patientData['note'] : ''}}">{{!empty($patientData['note']) ? $patientData['note'] : ''}}</textarea>
                                                        @if(!empty($permission_array) && $permission_array['can_update'])
                                                            <i class="edit-icon text-link {{!empty($patientData['note']) ? '' : 'default-show'}}" style="display:contents;"><button type="button" class="edit-icon btn transparent-btn default-show">{{ __('lang.add') }}</button></i>
                                                            <i class="edit-icon image-link {{empty($patientData['note']) ? 'd-none' : ''}}"><img src="{{ asset('assets/images/edit-icon.svg') }}" alt=""></i>
                                                            <i class="edit-loader large-content"><img src="{{ asset('assets/images/edit-loader.gif') }}" alt=""></i>
                                                        @endif
                                                        <em class="error-msg note-error-span"></em>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                   <!--  <div class="notice-block">
                                        <span class="title">{{ __('lang.note') }}</span>
                                        <div class="custom-row">
                                            <div class="description-div">
                                                <div class="form-group edit-label can-edit">
                                                    <textarea type="text" for="switch6" class="form-control" name="note" id="note" value="{{!empty($patientData['note']) ? $patientData['note'] : ''}}">{{!empty($patientData['note']) ? $patientData['note'] : ''}}</textarea>
                                                    <i class="edit-icon"><img src="{{ asset('assets/images/edit-icon.svg') }}" alt=""></i>
                                                    <i class="edit-loader"><img src="{{ asset('assets/images/edit-loader.gif') }}" alt=""></i>
                                                    <em class="error-msg note-error-span"></em>
                                                </div>
                                            </div>
                                        </div>
                                    </div> -->
                                </div>
                                <div class="tab-pane fade" id="doctor-contact" role="tabpanel" aria-labelledby="doctor-contact-tab">
                                    <a href="javascript:void(0)" class="tab-link" title="">{{ __('lang.contact') }}</a>
                                    <div class="content-wrap">
                                        <div class="profile-wrap">
                                            <div class="custom-row">
                                                <span class="title-div">{{ __('lang.street') }}</span>
                                                <div class="description-div">
                                                    <div class="form-group edit-label can-edit multiple-input">
                                                        <input type="text" for="switch6" class="form-control" name="street" id="street" value="{{$patientData['street']}}">
                                                        <input type="text" for="switch6" class="form-control" name="streetnumber" id="streetnumber" value="{{$patientData['streetnumber']}}">
                                                        @if(!empty($permission_array) && $permission_array['can_update'])
                                                            <i class="edit-icon"><img src="{{ asset('assets/images/edit-icon.svg') }}" alt=""></i>
                                                            <i class="edit-loader"><img src="{{ asset('assets/images/edit-loader.gif') }}" alt=""></i>
                                                        @endif
                                                        <em class="error-msg street-error-span"></em>
                                                        <em class="error-msg streetnumber-error-span"></em>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="custom-row">
                                                <span class="title-div">{{ __('lang.postcode short') }} / {{ __('lang.place') }}</span>
                                                <div class="description-div">
                                                    <div class="form-group edit-label can-edit multiple-input value-has-space">
                                                        <input type="text" for="switch6" class="form-control" name="postcode" id="postcode" value="{{$patientData['postcode']}}">
                                                        <input type="text" for="switch6" class="form-control" name="place" id="place" value="{{$patientData['place']}}">
                                                        @if(!empty($permission_array) && $permission_array['can_update'])
                                                            <i class="edit-icon"><img src="{{ asset('assets/images/edit-icon.svg') }}" alt=""></i>
                                                            <i class="edit-loader"><img src="{{ asset('assets/images/edit-loader.gif') }}" alt=""></i>
                                                        @endif
                                                        <em class="error-msg postcode-error-span"></em>
                                                        <em class="error-msg place-error-span"></em>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="custom-row">
                                                <span class="title-div">{{ __('lang.telephone') }}</span>
                                                <div class="description-div">
                                                    <div class="form-group edit-label can-edit">
                                                        <input type="text" for="switch6" class="form-control" name="phone" id="phone" value="{{$patientData['phone']}}">
                                                        @if(!empty($permission_array) && $permission_array['can_update'])
                                                            <i class="edit-icon"><img src="{{ asset('assets/images/edit-icon.svg') }}" alt=""></i>
                                                            <i class="edit-loader"><img src="{{ asset('assets/images/edit-loader.gif') }}" alt=""></i>
                                                        @endif
                                                        <em class="error-msg phone-error-span"></em>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="custom-row">
                                                <span class="title-div">{{ __('lang.mobile') }}</span>
                                                <div class="description-div">
                                                    <div class="form-group edit-label can-edit">
                                                        <input type="text" for="switch6" class="form-control" name="mobile" id="mobile" value="{{ !empty($patientData['mobile']) ? $patientData['mobile'] : ''}}">
                                                        @if(!empty($permission_array) && $permission_array['can_update'])
                                                            <i class="edit-icon"><img src="{{ asset('assets/images/edit-icon.svg') }}" alt=""></i>
                                                            <i class="edit-loader"><img src="{{ asset('assets/images/edit-loader.gif') }}" alt=""></i>
                                                        @endif
                                                        <em class="error-msg mobile-error-span"></em>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="custom-row">
                                                <span class="title-div">{{ __('lang.fax short') }}</span>
                                                <div class="description-div">
                                                    <div class="form-group edit-label can-edit">
                                                        <input type="text" for="switch6" class="form-control" name="fax" id="fax" value="{{$patientData['fax']}}">
                                                        @if(!empty($permission_array) && $permission_array['can_update'])
                                                            <i class="edit-icon"><img src="{{ asset('assets/images/edit-icon.svg') }}" alt=""></i>
                                                            <i class="edit-loader"><img src="{{ asset('assets/images/edit-loader.gif') }}" alt=""></i>
                                                        @endif
                                                        <em class="error-msg fax-error-span"></em>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="custom-row">
                                                <span class="title-div">{{ __('lang.email') }}</span>
                                                <span class="description-div">{{$patientData['user']->email}}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="doctor-settings" role="tabpanel" aria-labelledby="doctor-settings-tab">
                                    <a href="javascript:void(0)" class="tab-link" title="">{{ __('lang.health_insurance') }}</a>
                                    <div class="content-wrap">
                                        <div class="profile-wrap">
                                            <div class="custom-row">
                                                <span class="title-div">{{ __('lang.health_insurance') }}</span>
                                                <div class="description-div">
                                                    <div class="form-group edit-label can-edit">
                                                        <input type="text" for="switch6" class="form-control" name="health_insurance" id="health_insurance" value="{{$patientData['health_insurance']}}">
                                                        @if(!empty($permission_array) && $permission_array['can_update'])
                                                            <i class="edit-icon"><img src="{{ asset('assets/images/edit-icon.svg') }}" alt=""></i>
                                                            <i class="edit-loader"><img src="{{ asset('assets/images/edit-loader.gif') }}" alt=""></i>
                                                        @endif
                                                        <em class="error-msg health_insurance-error-span"></em>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="custom-row">
                                                <span class="title-div">{{ __('lang.health_insurance_type') }}</span>
                                                <div class="description-div">
                                                    <div class="form-group edit-label has-select can-edit">
                                                        <select class="custom_dropdown" name="insurance_type" id="insurance_type" value="{{ $patientData['insurance_type'] }}">
                                                            <option value="">{{ __('lang.health_insurance_type') }}</option>
                                                            <option value="1" {{$patientData['insurance_type'] == '1' ? 'selected="selected"' : ''}}>{{ __('lang.private') }}</option>
                                                            <option value="2" {{$patientData['insurance_type'] == '2' ? 'selected="selected"' : ''}}>{{ __('lang.legally') }}</option>
                                                        </select>
                                                        @if(!empty($permission_array) && $permission_array['can_update'])
                                                            <i class="edit-icon"><img src="{{ asset('assets/images/edit-icon.svg') }}" alt=""></i>
                                                            <i class="edit-loader"><img src="{{ asset('assets/images/edit-loader.gif') }}" alt=""></i>
                                                        @endif
                                                        <em class="error-msg insurance_type-error-span"></em>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="custom-row">
                                                <span class="title-div">{{ __('lang.insurance_number') }}</span>
                                                <div class="description-div">
                                                    <div class="form-group edit-label can-edit">
                                                        <input type="text" for="switch6" class="form-control" name="health_insurance_no" id="health_insurance_no" value="{{$patientData['health_insurance_no']}}">
                                                        @if(!empty($permission_array) && $permission_array['can_update'])
                                                            <i class="edit-icon"><img src="{{ asset('assets/images/edit-icon.svg') }}" alt=""></i>
                                                            <i class="edit-loader"><img src="{{ asset('assets/images/edit-loader.gif') }}" alt=""></i>
                                                        @endif
                                                        <em class="error-msg health_insurance_no-error-span"></em>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="custom-row">
                                                <span class="title-div">{{ __('lang.health_insurance_number') }}</span>
                                                <div class="description-div">
                                                    <div class="form-group edit-label can-edit">
                                                        <input type="text" for="switch6" class="form-control" name="insurance_number" id="insurance_number" value="{{$patientData['insurance_number']}}">
                                                        @if(!empty($permission_array) && $permission_array['can_update'])
                                                            <i class="edit-icon"><img src="{{ asset('assets/images/edit-icon.svg') }}" alt=""></i>
                                                            <i class="edit-loader"><img src="{{ asset('assets/images/edit-loader.gif') }}" alt=""></i>
                                                        @endif
                                                        <em class="error-msg insurance_number-error-span"></em>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- confirm+add doctor modal -->
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
                                @php $name = $salutation. " ".$patientData['name']." ".$patientData['lastname'];@endphp
                                <input type="text" id="patient_name" class="form-control onfocus" value="{{$name}}" readonly>
                                <label>{{ __('lang.name') }}</label>
                            </div>
                            <div class="form-group">
                                <select class="dropdown_select2" name="patient_doctor_id_select" id="patient_doctor_id_select">
                                    <option value="">{{ __('lang.treating_doc') }}</option>
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
                            <a href="#" title="" class="btn  btn-green verifyPatient" title="{{ __('lang.confirm') }}">{{ __('lang.confirm') }}</a>
                            <a href="#" title="" class="btn btn-transparent btn-lg close" title="{{ __('lang.close') }}" data-dismiss="modal" aria-label="Close">{{ __('lang.close') }}</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="modal phase-list fade custom-modal tiny-modal" id="abort-request" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h2>{{ __('lang.abort-request') }}</h2>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i><img src="{{ asset('assets/images/cross-icon.svg') }}"></i></span>
                </button>
            </div>
            <div class="modal-wrap">
                <div class="modal-container has-text-msg">
                    <p>{{ __('lang.abort-request-text') }}</p>
                </div>
            </div>
            <div class="modal-footer">
                <div class="btn-wrap">
                    <a href="javascript:void(0);" title="" class="btn red-btn" id="abort-btn" title="{{ __('lang.clear') }}">{{ __('lang.clear') }}</a>
                    <a href="javascript:void(0);" title="" class="btn btn-transparent btn-lg close" title="{{ __('lang.close') }}" data-dismiss="modal" aria-label="Close">{{ __('lang.close') }}</a>
                    <i class="edit-loader submit-button-loader" style="display:none"><img src="{{ asset('assets/images/edit-loader.gif') }}" alt=""></i>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('assets/js/custom.js') }}"></script>
<script type="text/javascript">
    // $(".description-div .select-date").datepicker({language:locale, format: 'dd.mm.yyyy',autoclose: true});
    $('#bday').datepicker({
        format: 'dd.mm.yyyy',
        language:locale,
        // startDate: moment().subtract(99, 'years').toDate(),
        endDate: moment(new Date()).toDate(),
        autoclose: true,
    });
    $(document).ready(function() {
        $(".dropdown_select2").select2({
            tags: true
        });
        var organization_id = '{{ \Session::has('organization_id') ? decrypt(\Session::get('organization_id')) : '' }}';
        var data = JSON.parse(localStorage.getItem('keys'));
        var patientOldData = <?php echo json_encode($patientData); ?>;
        var isEdit = false;
        var inputNotChange = false;
        var id = "{{ $patientData['user']->id }}";
        var token = $('input[name="_token"]').attr('value');
        var url = "{{ url('edit-patient') }}/" + id;
        var validateFlag = true;
        var changeFieldName = [];

        $.each(data, function(key, value) {
            if (organization_id == value.organization_id) {
                private_key = value.private_key;
                return 0;
            };
        });

        //function to verify open modal to select doctor 
        $(".verify-patient-button").on('click', function(e) {
            $('#edit-record').modal('show');
        });

        //function to verify the user identity
        $(".verifyPatient").on('click', function(e) {
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
                        $("div.toast-relative-align").slideUp(100);
                        $('#edit-record').modal('hide');
                        $("div.detail-block").removeClass('edit-disable');
                        $('.toast-relative-align.patient-profile-alert').text(data.success);
                        $('.toast-relative-align.patient-profile-alert').addClass('success').show();
                        setTimeout(() => {
                            $('.toast-relative-align.patient-profile-alert').addClass('success').hide();
                        }, 5000);
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

        $(".description-div .edit-label input,.description-div .edit-label textarea, .description-div .edit-label .custom_dropdown,datepicker").click(function(e) {
            e.stopPropagation();
        })

        $(".description-div .edit-label .edit-icon").click(function() {
            $(".description-div .edit-label").addClass("can-edit");
            setTimeout(() => {
                if (validateFlag) { // single val update condition
                    $(this).parent().removeClass("can-edit");
                }
            }, 10);
            if($(this).closest('.custom-row').hasClass('notice-row')) {
                $('.image-link').addClass('d-none');
            }
            inputNotChange = true;
        });

        $(".description-div input").bind("keypress", function (e) {
            if (e.keyCode == 13) {
                $("body").trigger('click');
                return false;
            }
        });

        $('.description-div input').on('keyup', function() {
            isEdit = true;
            changeFieldName.push($(this).attr('name'));
        });
        $('.description-div textarea').on('keyup', function() {
            isEdit = true;
            changeFieldName.push($(this).attr('name'));
        });
        $('.description-div select').on('change', function() {
            isEdit = true;
            changeFieldName.push($(this).attr('name'));
            $("body").trigger('click');

        });
        $('.select-date').change(function() {
            isEdit = true;
            changeFieldName.push($(this).attr('name'));

        });
        $(".description-div .edit-label").each(function() {
            $this_input = $(this).children("input,textarea");
            $this_input.each(function(index) {
                var value = $(this).attr("value");
                $(this).parent().append("<span class='new-data copied-value" + index + "'>" + value + "</span>");
                $(this).on('keyup', function() {
                    var current = $(this).val();
                    $(this).siblings(".copied-value" + index).text(current);
                });
            })
        });

        $(".detail-block .select-date").on("change", function() {
            var selected = $(this).val();
            $(this).siblings(".copied-value0").text(selected);
        });

        $("body").click(function(e) {
            if (!$(".datepicker").length) {
                if (isEdit) {
                    var profileData = {};
                    var patientChangedData = {};

                    // for multiple data save at a time
                    // get profile new data
                    // $(".description-div input").each(function() {
                    //     profileData[$(this).attr("name")] = $(this).val();
                    // });
                    // $(".description-div select").each(function() {
                    //     profileData[$(this).attr("name")] = $(this).val();
                    // });
                    // $(".description-div textarea").each(function() {
                    //     profileData[$(this).attr("name")] = $(this).val();
                    // });
                    // for single data save at time
                    $(".description-div input").each(function() {
                        if (changeFieldName.includes($(this).attr("name")))
                            profileData[$(this).attr("name")] = $(this).val();
                    });
                    $(".description-div select").each(function() {
                        if (changeFieldName.includes($(this).attr("name")))
                            profileData[$(this).attr("name")] = $(this).val();
                    });
                    $(".description-div textarea").each(function() {
                        if (changeFieldName.includes($(this).attr("name")))
                            profileData[$(this).attr("name")] = $(this).val();
                    });
                    $(".detail-block .detail-wrap .right-block .detail-nav .nav-tabs .nav-item").removeClass('error-tab');

                    // validate data with old profile data
                    if (Object.keys(profileData).length && Object.keys(patientOldData).length) {
                        var validator = $("#patientProfileForm").validate();
                        validateFlag = true;
                        for (key in profileData) {
                            if (patientOldData[key] === null) patientOldData[key] = "";
                            if (profileData[key] != patientOldData[key]) {
                                $("input[name=" + key + "],select[name=" + key + "],textarea[name=" + key + "]").siblings(".edit-loader").addClass("loader-show");
                                if(key == 'note') {
                                    if(profileData[key] != '') {
                                        $('.image-link').removeClass('d-none');
                                        $('.text-link').removeClass('default-show');
                                        $('.notice-row').addClass('has-data');
                                    } else {
                                        $('.image-link').addClass('d-none');
                                        $('.text-link').addClass('default-show');
                                        $('.notice-row').removeClass('has-data');
                                    }
                                    $("textarea[name=" + key + "]").parent(".description-div .edit-label").addClass("can-edit");
                                }
                                // validate only changed field
                                if (!validator.element("#" + key)) {
                                    validateFlag = false;
                                    $("input[name=" + key + "],select[name=" + key + "],textarea[name=" + key + "]").parent(".description-div .edit-label").removeClass("can-edit");
                                }
                                if (validateFlag) {
                                    patientChangedData[key] = profileData[key];
                                    patientOldData[key] = profileData[key];
                                }
                            } else {

                                // if value is same as old value and no validation than remove input box css
                                $("." + key + "-error-span").text("");
                                var flag = true;
                                $("input[name=" + key + "]").siblings("em").each(function() {
                                    if ($(this).find("label").text()) {
                                        flag = false;
                                    }
                                });
                                if (flag) {
                                    $("input[name=" + key + "],select[name=" + key + "],textarea[name=" + key + "]").parents('div .form-group').removeClass('has-error');
                                    $("input[name=" + key + "],select[name=" + key + "],textarea[name=" + key + "]").parent(".description-div .edit-label").addClass("can-edit");
                                }
                            }
                        }
                    }

                    if (Object.keys(patientChangedData).length && validateFlag) {
                        isEdit = false;
                        changeFieldName.forEach(element => $("input[name=" + element + "],select[name=" + element + "]").parent(".description-div .edit-label").addClass("can-edit"));
                        $('.detail-block').addClass('edit-disable');
                        if(patientChangedData['health_insurance_no'] || patientChangedData['insurance_type']) {
                            patientChangedData['health_insurance_no'] = patientOldData['health_insurance_no'];
                            patientChangedData['insurance_type'] = patientOldData['insurance_type'];
                        }
                        // update profile data
                        $.ajax({
                            type: "post",
                            url: url,
                            // async: false,
                            data: {
                                'profileData': patientChangedData,
                                private_key: private_key
                            },
                            headers: {
                                'X-CSRF-Token': token
                            },
                            beforeSend: function() {
                                $(".edit-loader.loader-show").show();
                            },
                            success: function(data) {
                                if (!data.success) {

                                    // error from server
                                    patientOldData = <?php echo json_encode($patientData); ?>;
                                    $(".edit-loader.loader-show").hide();
                                    $(".edit-loader").removeClass("loader-show");
                                    $('.detail-block').removeClass('edit-disable');
                                    isEdit = true;
                                    for (let [key, value] of Object.entries(data.errors)) {
                                        var errorMsg = value[0];
                                        $("input[name=" + key + "]").parent().removeClass("can-edit");
                                        $("input[name=" + key + "]").parents('div .form-group').addClass('has-error')
                                        $("." + key + "-error-span").text(errorMsg);
                                    }
                                } else if (data.status == 200 && data.success != '') {
                                    $('.toast-relative-align.patient-profile-alert').text(data.success);
                                    $("input").parents('div .form-group').removeClass('has-error')
                                    var salutation = patientOldData['salutation'] ? (patientOldData['salutation'] == 1 ? "{{ __('lang.mr') }}" : (patientOldData['salutation'] == 2 ? "{{ __('lang.mrs') }}" : '')) : '';
                                    var titlePatient = '';
                                    if($('#title').val()) {
                                        titlePatient = $('#title option:selected').text();
                                    }
                                    $('.updated-fullname').text(salutation + ' ' + titlePatient + ' ' + patientOldData['name'] + ' ' + patientOldData['lastname']);
                                    $('.change-new-name').text(titlePatient + ' ' + patientOldData['name'] + ' ' + patientOldData['lastname']);
                                    setTimeout(() => {
                                        $(".edit-loader.loader-show").hide();
                                        $(".edit-loader").removeClass("loader-show");
                                        $('.detail-block').removeClass('edit-disable');
                                        $('.toast-relative-align.patient-profile-alert').addClass('success').show();
                                        setTimeout(() => {
                                            $('.toast-relative-align.patient-profile-alert').addClass('success').hide();
                                        }, 5000);
                                    }, 750);
                                }
                            },
                            error: function(data) {
                                if (data.status == 400 && data.error != '') {
                                    $('.toast-relative-align.patient-profile-alert').text(data.error);
                                    $('.toast-relative-align.patient-profile-alert').removeClass('success').addClass('error').show();
                                    setTimeout(() => {
                                        $('.toast-relative-align.patient-profile-alert').removeClass('success').addClass('error').hide();
                                    }, 5000)
                                }
                            }
                        });
                    }
                } else if (inputNotChange && !($(e.target).parent().prop('className').includes('edit-icon'))) {
                    $(".description-div .edit-label").addClass("can-edit");
                    setTimeout(() => {
                        if($('.notice-row').find('.edit-label').hasClass('can-edit')) {
                            $('.image-link').removeClass('d-none');
                            if(!$('.notice-row').hasClass('has-data')) {
                                $('.image-link').addClass('d-none');
                            }
                        }   
                    }, 10);
                    
                }
            }
        });

        //to abort the request 
        $("#abort-btn").click(function(e) {
            $(".submit-button-loader").show();
            $('#abort-btn').addClass('disabled');
            $('a.close').hide();
            var abort_request_url = "{{ url('patients/abort-request') }}/" + "{{ $patientData['user']->encrypted_user_id }}";
            $.ajax({
                type: "post",
                url: abort_request_url,
                data: {
                    private_key: private_key,
                },
                headers: {
                    'X-CSRF-Token': token
                },
                success: function(data) {
                    $(".submit-button-loader").hide();
                    $('#abort-btn').removeClass('disabled');
                    $('a.close').show();
                    window.location.href = "{{ url('patients') }}";
                },
                error: function(data) {
                    $(".submit-button-loader").hide();
                    $('#abort-btn').removeClass('disabled');
                    $('a.close').show();
                    if (data.status == 400 && data.responseJSON.errors != '') {

                    }
                }
            });
        });
    });
</script>