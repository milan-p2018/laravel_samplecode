<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Plan;
use App\Phases;
use App\ExerciseGroupsList;
use App\PhaseExerciseGroups;
use App\PatientsMeetings;
use App\ScheduleCategory;
use Auth;
use Carbon\Carbon;
use Storage;
use Helper;
use App\PatientData;
use App\OrganizationDistributor;
use App\PlanPatientAssignmentTemplate;
use App\WorkerData;
use App\Document;
use App\TherapyPlanTemplates;
use App\Patient\PatientPhaseCourses;
use App\Patient\PatientPhaseExercises;
use App\Patient\PatientExercises;
use App\Patient\PatientCourseExercises;
use App\Patient\PatientCourses;
use App\Patient\PatientTherapyPlanTemplates;
use App\Patient\PatientAssessmentTemplates;
use App\ExerciseStatus;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\UploadedFile;
use App\Patient\PatientPhases;
use App\Exercises;
use App\ExerciseMaterialsLists;
use App\Traits\Encryptable;
use App\User;
use App\Organization;
use App\Patient\PatientPhaseAssessments;
use App\Patient\PatientAssessmentSubCategories;
use App\Patient\PatientPlanNetwork;
use App\Patient\PatientAssessmentSchedules;

class PatientController extends Controller
{
    use Encryptable;
    //function to get all the plans of users
    public function getAllPlans(Request $request)
    {
        
        $status = 200;
        $response = [];
        $plans_data = PlanPatientAssignmentTemplate::with('TherapyPlanTemplates')->where('patient_id', Auth::guard('api')->user()->id)->get();
        if (!$plans_data->isEmpty()) {
            $res = json_encode($plans_data);  //encode the data
            //to encrypt the response
            $passphrase = substr(Auth::guard('api')->user()->api_token, 0, 20);
            $encrypted_data = Helper::cryptoJsAesEncrypt($passphrase, $res);
            $encrypted_phrase = Helper::encrypt($passphrase, trim(Auth::guard('api')->user()->PatientData()->first()->public_key));
            $response  = [
                'success' => true,
                'message' => \Lang::get('lang.exercise-group-successfully-fetched'),
                'passphrase' => base64_encode($encrypted_phrase),
                'data' => $encrypted_data,
            ];
            return response()->json($response, 200);
        } else {
            $err_response  = [
                'success' => false,
                'errors' => \Lang::get('lang.plans-not-found')
            ];
            return response()->json($err_response, 200);
        }
    }

    //function to get all the exercises groups wih exercises
    public function getExerciseGroups(Request $request)
    {
        $patient_id = Auth::guard('api')->user()->id;
        // To get the patient therapy plan details of logged-in user
        $plans_data = PatientTherapyPlanTemplates::with('PatientPlanNetwork.Organizations.SpeciesWithCode')->where('patient_id', $patient_id)->where('is_complete', 0)->where('aborted', 0)->first();
        if($plans_data) {
            $start_date = $plans_data->start_date;
            // To check patient therapy plan is released or not
            if($plans_data->is_released == 0) {
                $planNetwork = $plans_data->PatientPlanNetwork;
                $countOfPlanNetwork = $planNetwork->count();
                // if count of plan network is 1 then only owner is available for that plan
                if($countOfPlanNetwork == 1) {
                    $isOwnerOrganization = $planNetwork[0]->is_owner_organization;
                    // if current organization is owner
                    if($isOwnerOrganization == 1) {
                        // To get the type of organization
                        $isbsnr = $planNetwork[0]->Organizations->SpeciesWithCode->isbsnr;
                        //call the function to check the needed organization type
                        $bsnrValue = $this->getNeededOrganizationType($plans_data, $isbsnr);
                        if(empty($bsnrValue)) {
                            // All rights assign to owner
                            $status = 2;
                            // To Get the needed type of organization
                            $needed_type = ($isbsnr == 'true') ? 'Doctor Organization' : 'Physio Organization';
                        } else {
                            // Need another organization
                            $status = 1;
                            // To Get the needed type of organization
                            $needed_type = ($bsnrValue == 1) ? 'Doctor Organization' : 'Physio Organization';
                        }
                        $response  = [
                            'status' => $status,
                            'start_date' => $start_date,
                            'success' => true,
                            'message' => \Lang::get('lang.plan-is-not-released'),
                            'needed_type' => $needed_type,
                        ];
                        return response()->json($response, 200);
                    }
                } else {
                    // If 2 or more networks available for current plan
                    foreach($planNetwork as $key => $plans) {
                        // To check the owner of organization type
                        $isOwnerOrganization = $plans->is_owner_organization;
                        if($isOwnerOrganization) {
                            // To get the type of organization
                            $isbsnr = $plans->Organizations->SpeciesWithCode->isbsnr;
                        } else {
                            //call the function to check the organization type
                            $bsnrValue = $this->getNeededOrganizationType($plans_data, $isbsnr);
                            if(!empty($bsnrValue)) {
                                $status = 1;
                                // To Get the needed type of organization
                                $needed_type = ($bsnrValue == 1) ? 'Doctor Organization' : 'Physio Organization';
                                // To get the status type
                                $checkPlanStatus = $plans->status;
                                if($checkPlanStatus == 0){
                                    $is_org_request_status = 0;
                                } else {
                                    $is_org_request_status = 1;
                                }
                            }
                            $response  = [
                                'status' => $status,
                                'start_date' => $start_date,
                                'success' => true,
                                'message' => \Lang::get('lang.plan-is-not-released'),
                                'needed_type' => $needed_type,
                                'is_org_request_status' => $is_org_request_status,
                            ];
                            return response()->json($response, 200);
                        }
                    }
                }
            } else {
                // If plan is already released
                // If plan will start in future or not
                if (Carbon::now()->startOfDay()->lt(Carbon::parse($start_date)->startOfDay()))
                {
                    $status = 3;
                    $response  = [
                        'status' => $status,
                        'start_date' => $start_date,
                        'success' => true,
                        'message' => \Lang::get('lang.plan-starts-in-future-message')
                    ];
                    return response()->json($response, 200);
                } else {
                    $status = 4;
                    //get all the phases of specific plans
                    foreach ($plans_data->Phases as $key => $phases) {
                        //to set the start/end date of phases
                        if ($key == 0) {
                            $dates['start_date'][$key] = Carbon::parse($start_date);
                            $dates['end_date'][$key] = Carbon::parse($start_date)->addWeeks($phases->duration)->subDay(1);
                        } else {
                            $dates['start_date'][$key] = Carbon::parse($dates['end_date'][$key - 1])->addDay();
                            $dates['end_date'][$key] = Carbon::parse($dates['start_date'][$key])->addWeeks($phases->duration)->subDay(1);
                        }
                        
                        $phases->start_date = $dates['start_date'][$key];
                        $phases->end_date = $dates['end_date'][$key];
                        //to check the active phase
                        if (Carbon::now()->startOfDay()->gte(Carbon::parse($phases->start_date)->startOfDay()) && Carbon::now()->startOfDay()->lte(Carbon::parse($phases->end_date)->startOfDay())) {
                            
                            $course_of_exercises = PatientPhaseCourses::with('Course.CourseExercises.Exercises.Materials')->withCount(['Course'])->where('phase_id', $phases->id)->where('status', 1)->get();
                            $individual_exercises = PatientPhaseExercises::with('Exercises.Materials')->where('status', 1)->where('phase_id', $phases->id)->get();
                            //To fetch the phase-assessment data
                            $assessments_data  = PatientPhaseAssessments::with('PatientAssessmentTemplates.Schedules')->where('phases_id', $phases->id)->get();
                            $all_schedules_data = null;
                            if(!empty($assessments_data)) {
                                //to check the schedules are exists or not
                                if(!empty($assessments_data[0]->PatientAssessmentTemplates->Schedules)) {
                                    $all_schedules_data = $assessments_data[0]->PatientAssessmentTemplates->Schedules;
                                    foreach($all_schedules_data as $schedule_key => $schedules) {
                                        // Completed assessment count is 0
                                        $completed_count = 0;
                                        // To get the schedule id
                                        $schedule_id = $schedules->id;
                                        // To get the sub category details
                                        $sub_category_data = PatientAssessmentTemplates::with(['SubCategoriesData'=> function($query) use($schedule_id){
                                            $query->whereRaw("find_in_set('".$schedule_id."',patient_assessment_schedules_id)");
                                        }])->where('id', $schedules->patient_assessment_templates_id)->first();
                                        $sub_categoris_details = $sub_category_data->SubCategoriesData->where('status', 1);
                                        //To set the start and end time in schedules data
                                        $scheduled_data['start_time'][$schedule_key] = $schedules->time;
                                        if(isset($all_schedules_data[$schedule_key + 1])) {
                                            $scheduled_data['end_time'][$schedule_key] = $all_schedules_data[$schedule_key + 1]->time;
                                        } else {
                                            $scheduled_data['end_time'][$schedule_key] = NULL;
                                        }

                                        $schedules->start_time = $scheduled_data['start_time'][$schedule_key];
                                        $schedules->end_time = $scheduled_data['end_time'][$schedule_key];
                                        // To get the current date
                                        $current_date =  Carbon::now()->format('d:m:Y');
                                        foreach($sub_categoris_details as $sub_categoris_key => $measurementData) {
                                            $mesureMentJson = json_decode($measurementData->measurements_json);
                                            // If measurement-json is available and has record of current date
                                            if(!empty($measurementData->measurements_json) && array_key_exists(strtolower($current_date), $mesureMentJson)) {
                                                $current_measurement = $mesureMentJson->$current_date;
                                                foreach($current_measurement as $key => $value) {
                                                    if($key == $schedule_id ) {
                                                        // Add completed assessment
                                                        $completed_count = $completed_count + 1;
                                                    }
                                                }
                                            }
                                        }
                                        $schedules->total_assesment = count($sub_categoris_details);
                                        $schedules->completed_assesment = $completed_count;
                                        $schedules->sub_category = $sub_categoris_details;
                                    }
                                }
                            }
                            if($course_of_exercises || $individual_exercises) {
                                $data['plan_template_id'] =  $plans_data->id;
                                $data['phase_id'] =  $phases->id;
                                $data['exercises'] = $individual_exercises;
                                $data['courses'] = $course_of_exercises;
                                $data['assessments'] = $all_schedules_data;
                            }
                            // return response()->json($data, 200);
                            $res = json_encode($data);  //encode the data
                            //to encrypt the response
                            $passphrase = substr(Auth::guard('api')->user()->api_token, 0, 20);
                            $encrypted_data = Helper::cryptoJsAesEncrypt($passphrase, $res);
                            $encrypted_phrase = Helper::encrypt($passphrase, trim(Auth::guard('api')->user()->PatientData()->first()->public_key));
                            $response  = [
                                'status' => $status,
                                'start_date' => $start_date,
                                'success' => true,
                                'message' => \Lang::get('lang.exercises-successfully-fetched'),
                                'passphrase' => base64_encode($encrypted_phrase),
                                'data' => $encrypted_data,
                            ];
                            return response()->json($response, 200);
                        }
                    }
                }
            }    
        }
        // If plan not found
        $status = 0;
        $err_response  = [
            'status' => $status,
            'success' => false,
            'errors' => \Lang::get('lang.no-active-phase-detected')
        ];
        return response()->json($err_response, 200);
    }

    //function to get the doctors details of organization 
    public function getDoctorsList(Request $request)
    {
        $status = 200;
        $doctors_data = OrganizationDistributor::with(['OrganizationData.WorkerData', 'Group'])->whereHas('Group', function ($query) use ($request) {
            $query->where('org_unique_id', $request->organization_id);
        })->whereHas('OrganizationData', function ($query) use ($request) {
            $query->whereNotNull('lanr')->where('worker_data_species_id', 'lang_doc')->orderBy('id', 'desc');
        })->get();
        if ($doctors_data) {
            $res = json_encode($doctors_data);  //encode the data
            //to encrypt the response
            $passphrase = substr(Auth::guard('api')->user()->api_token, 0, 20);
            $encrypted_data = Helper::cryptoJsAesEncrypt($passphrase, $res);
            $encrypted_phrase = Helper::encrypt($passphrase, trim(Auth::guard('api')->user()->PatientData()->first()->public_key));
            $response  = [
                'success' => true,
                'message' => \Lang::get('lang.organization-doctor-fetched'),
                'passphrase' => base64_encode($encrypted_phrase),
                'data' => $encrypted_data,
            ];
            return response()->json($response, $status);
        } else {
            //if doctors are not found
            $err_response  = [
                'success' => false,
                'errors' => \Lang::get('lang.doctors-not-found')
            ];
            return response()->json($err_response, 200);
        }
    }

    //function to get the document listing
    public function getDocumentList(Request $request)
    {
        $status = 200;
        $response = [];
        $user_id = Auth::guard('api')->user()->id;
        $documents = Document::where('user_id', $user_id);
        if (!empty($request->type)) {
            $documents = $documents->where('type', $request->type);
        }
        $documents = $documents->get();
        $documents->makeHidden(['document_details_for_org']); // prevent to share encryption key
        $data = [];
        if (!empty($documents)) {
            //to distribute the documents as per the specialist, physiotherapist
            foreach ($documents as $files) {
                $organization = NULL;
                $org_ids = explode(',',$files->organization_id);
                if(!empty($org_ids)) {
                    $org_data = Organization::select('id','name')->find($org_ids);
                    foreach ($org_data as $org_key => $value) {
                        $organization[$org_key]['id'] = $value->id;
                        $organization[$org_key]['name'] = $value->name;
                    }
                }
                $files->organizations = $organization;
                $data['all'][] = $files;
            }
            $res = json_encode($data);  //encode the data
            //to encrypt the response
            $passphrase = substr(Auth::guard('api')->user()->api_token, 0, 20);
            $encrypted_data = Helper::cryptoJsAesEncrypt($passphrase, $res);
            $encrypted_phrase = Helper::encrypt($passphrase, trim(Auth::guard('api')->user()->PatientData()->first()->public_key));
            $response  = [
                'success' => true,
                'message' => \Lang::get('lang.documents-successfully-fetched'),
                'passphrase' => base64_encode($encrypted_phrase),
                'data' => $encrypted_data,
            ];
            return response()->json($response, $status);
        } else {
            //if documents are not found
            $err_response  = [
                'success' => false,
                'errors' => \Lang::get('lang.documents-not-found')
            ];
            return response()->json($err_response, 200);
        }
    }

    //public function to upload the documents
    public function uploadDocuments(Request $request)
    {
        $status = 200;
        $response = [];
        $user_id = Auth::guard('api')->user()->id;
        //validation rules
        $rules = [
            'document_name' => ['required'],
            // 'organization_ids' => ['required'],
            'file' => 'required',
            'type' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $err_response  = [
                'success' => false,
                'errors' => $validator->messages()
            ];
            return response()->json($err_response, 200);
        }

        if ($request->has('file') && !empty($request->file)) {
            $file = $request->file;
            $filename = $request->document_name;
            $organization_ids = $request->has('organization_ids') ? $request->organization_ids : NULL;
            $all_documents = Document::where('user_id', $user_id)->get();

            $user_data = User::find($user_id);
            //save the documents into folder
            
            //to get the file content
            $file_content = file_get_contents($file);
            //generate the key 
            $key = $user_id . '_' . substr(base64_encode(Auth::guard('api')->user()->email), 0, (31 - strlen($user_id)));
            $encrypted_text = Helper::cryptoJsAesEncrypt($key, base64_encode($file_content));
            $folder = '/users/' . $user_id . '/common-documents/';
            $file_name_without_extension = substr($filename, 0, strripos($filename, '.', 0));
            $extensionwithDot = substr($filename, strripos($filename, '.', 0));
            $checkFileName = $file_name_without_extension;
            $filePath = $folder . $filename;
            $counter = 0;
            while (Storage::disk('public')->exists($folder . $checkFileName . '.txt')) {
                $checkFileName = $file_name_without_extension . '_' . ($counter + 1);
                $counter++;
            }
            if ($counter) {
                $filename = $checkFileName. $extensionwithDot;
                $filePath = $folder . $filename;
            }
            $key_user = $user_id . '_' . substr(base64_encode($user_data->email), 0, (31 - strlen($user_id)));      // set the key for encrypt/decrypt documents
            $data = [];
            // to set the organization json data
            if(!empty($organization_ids)) {
                $organization_ids_array = Organization::find($organization_ids);
                foreach($organization_ids_array as $organization)  
                {
                    $public_key = base64_decode($organization->publicKey);
                    $encrypted_key = (base64_encode($this->encrypt($key_user, $public_key)));
                    $data[$organization->id]['key'] = $encrypted_key;
                    $data[$organization->id]['filename'] = $filename;
                }
            }
            //save the documents into folder
            Storage::disk('public')->put($folder . $checkFileName . '.txt', $encrypted_text);
            $document = new Document;
            $document->user_id = $user_id;
            $document->filename = $filename;
            $document->organization_id = !empty($organization_ids) ? implode(',', $organization_ids): NULL;
            $document->size = $file->getSize();
            $document->type = $request->type;
            $document->data = '';
            $document->seen = '';
            $document->url = $filePath;
            $document->document_details_for_org = !empty($data) ? json_encode($data) : NULL;
            $document->save();
            $response  = [
                'success' => true,
                'message' => \Lang::get('lang.document-successfully-uploaded'),
            ];
            return response()->json($response, $status);
        } else {
            $err_response  = [
                'success' => false,
                'errors' => \Lang::get('lang.general-error-message')
            ];
            return response()->json($err_response, 200);
        }
    }

    //function to rename the file 
    public function renameDocument(Request $request)
    {
        $status = 200;
        $response = [];
        $document_id = $request->document_id;
        $document_name = $request->document_name;
        $user_id = Auth::guard('api')->user()->id;
        //get the document details
        $document = Document::find($document_id);
        //get all the documents of patients
        $all_documents = Document::where('user_id', $user_id)->get();

        if (!empty($document)) {
            // to check the file name is already exist or not
            foreach ($all_documents as $file) {
                if (strtolower($file->filename) == strtolower($request->document_name)) {
                    $err_response  = [
                        'success' => false,
                        'errors' => \Lang::get('lang.file-name-already-exist-msg')
                    ];
                    return response()->json($err_response, 200);
                }
            }
            //old file name(txt exntension)
            $oldname = substr($document->filename, 0, strripos($document->filename, '.', 0)) . '.txt';
            //set the new encrypted file name with txt exntesion
            $file_name = substr($document_name, 0, strripos($document_name, '.', 0)) . '.txt';
            $organization_id = explode('/', $document->url)[4];
            //To set the folder path for documents
            if(!empty($document->uploaded_by)) {
                $folder = '/users/' . $user_id . '/organizations/' . $organization_id . '/';
            } else {
                $folder = '/users/' . $user_id . '/common-documents/';
            }
            //rename the file in storage folder
            Storage::disk('public')->move($folder . $oldname, $folder . $file_name);
            //save the renamed file name and url in the database
            $document->filename = $document_name;
            $document->url = $folder . $document_name;
            $document->save();
            $response  = [
                'success' => true,
                'message' => \Lang::get('lang.document-renamed-successfully'),
            ];
            return response()->json($response, $status);
        } else {
            //if documents are not found
            $err_response  = [
                'success' => false,
                'errors' => \Lang::get('lang.document-not-exist')
            ];
            return response()->json($err_response, 200);
        }
    }

    //function to delete the document
    public function removeDocuments(Request $request)
    {
        $status = 200;
        $response = [];
        $documents = $request->documents_ids;
        $flag = false;
        $user_id = Auth::guard('api')->user()->id;
        if (!empty($documents)) {
            foreach ($documents as $key => $id) {
                $file = Document::where('user_id', $user_id)->find($id);
                if (!empty($file)) {
                    if (!empty($file->uploaded_by)) {
                        $flag = true;
                    } else {
                        $file_name = substr($file->filename, 0, strripos($file->filename, '.', 0));
                        //if document added by organization 
                        $organization_id = explode('/', $file->url)[4];
                        $folder = '/users/' . $user_id . '/common-documents/';
                        //remove the file from the folder
                        Storage::disk('public')->delete($folder  . $file_name . '.txt');
                        //remove the file from the database
                        Document::destroy($id);
                    }
                }
            }
            if ($flag) {
                $response  = [
                    'success' => false,
                    'errors' => \Lang::get('lang.document-upload-error'),
                ];
            } else {
                $response  = [
                    'success' => true,
                    'message' =>  count($documents) > 1 ? \Lang::get('lang.documents-removed-successfully') : \Lang::get('lang.document-removed-successfully'),
                ];
            }
            return response()->json($response, $status);
        } else {
            //if documents are not found
            $err_response  = [
                'success' => false,
                'errors' => \Lang::get('lang.document-not-exist')
            ];
            return response()->json($err_response, 200);
        }
    }

    //function to change the category of the documents
    public function changeCategoryOfDocument(Request $request) {
        $status = 200;
        $response = [];
        $user_id = Auth::guard('api')->user()->id;
        //validation rules
        $rules = [
            'id' => 'required',
            'type' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $err_response  = [
                'success' => false,
                'errors' => $validator->messages()
            ];
            return response()->json($err_response, 200);
        }
        $document = Document::where('user_id', $user_id)->find($request->id);
        if(!empty($document)) {
            if(empty($document->uploaded_by)) {
                $document->type = $request->type;
                $document->save();
                $response  = [
                    'success' => true,
                    'message' =>  \Lang::get('lang.document-category-change-msg'),
                ];
                return response()->json($response, $status);
            } else {
                $response  = [
                    'success' => false,
                    'errors' => \Lang::get('lang.document-category-error'),
                ];
                return response()->json($response, $status);
            }
        } else {
            $err_response  = [
                'success' => false,
                'errors' => \Lang::get('lang.document-not-exist')
            ];
            return response()->json($err_response, 200);
        }
    }

    //function to get the event listing
    public function getPatientEvents(Request $request)
    {
        $status = 200;
        $response = [];
        $user_id = Auth::guard('api')->user()->id;
        $language_id = $request->lang == 'en' ? 1 : 2;
        // get patient events with category filter
        $patientMeetings = PatientsMeetings::with('Organization')->with([
            'meetingCategoryWithCode' => function ($type) use ($language_id) {
                return $type->where('language_id', $language_id);
            }
        ])->where('user_id', $user_id)->with('workerData')->get();
        $data = [];
        if (!empty($patientMeetings)) {
            //to distribute the events as per the specialist, physiotherapist
            foreach ($patientMeetings as $events) {
                $events->event_assign_doctor_name = $events['workerData'] ? $events['workerData']->full_name : null;
                $events->patient_doctor_name = $events['workerData'] ? $events['workerData']->full_name : null;
                $data['all'][] = $events;
                if (!empty($events->Organization->bsnr)) {
                    $data['specialist'][] = $events;
                } else {
                    $data['physiotherapist'][] = $events;
                }
            }
            $res = json_encode($data);  //encode the data
            //to encrypt the response
            $passphrase = substr(Auth::guard('api')->user()->api_token, 0, 20);
            $encrypted_data = Helper::cryptoJsAesEncrypt($passphrase, $res);
            $encrypted_phrase = Helper::encrypt($passphrase, trim(Auth::guard('api')->user()->PatientData()->first()->public_key));
            $response  = [
                'success' => true,
                'message' => \Lang::get('lang.events-successfully-fetched'),
                'passphrase' => base64_encode($encrypted_phrase),
                'data' => $encrypted_data,
            ];
            return response()->json($response, $status);
        } else {
            //if events are not found
            $err_response  = [
                'success' => false,
                'errors' => \Lang::get('lang.events-not-found')
            ];
            return response()->json($err_response, 200);
        }
    }

    /**
     * function to fetch static categories list of the documents
     * @return type
     */
    public function getDocCategoriesList()
    {
        $status = 200;
        $passphrase = substr(Auth::guard('api')->user()->api_token, 0, 20);
        $encrypted_data = Helper::cryptoJsAesEncrypt($passphrase, json_encode(\Config::get('globalConstants.documents-types')));
        $encrypted_phrase = Helper::encrypt($passphrase, trim(Auth::guard('api')->user()->PatientData()->first()->public_key));
        $response  = [
            'success' => true,
            'passphrase' => base64_encode($encrypted_phrase),
            'data' => $encrypted_data
        ];

        return response()->json($response, $status);
    }

    // function create or update patient events
    public function updateOrCreatemeetings(Request $request)
    {
        $response = [];
        $user_id = Auth::guard('api')->user()->id;
        $rules = [
            'title' => 'required',
            'startDate' => 'required',
            'endDate' => 'required',
            'startTiming' => 'required',
            'endTiming' => 'required',
            'category' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $err_response  = [
                'success' => false,
                'errors' => $validator->messages()
            ];
            return response()->json($err_response, 200);
        }

        // date validation
        $DateFlag = new \DateTime($request->endDate) >= new \DateTime($request->startDate);
        if ($DateFlag) {
            $strStartTime = $request->startTiming;
            $strEndTime = $request->endTiming;
            if (((new \DateTime($request->endDate) == new \DateTime($request->startDate)) && strtotime($strStartTime) >= strtotime($strEndTime))) {
                $response  = [
                    'success' => false,
                    'errors' => ["endTiming" => [\Lang::get('lang.end-date-start-date-error')]],
                ];
                return response()->json($response, 200);
            }
        } else {
            $response  = [
                'success' => false,
                'errors' => ["endTiming" => [\Lang::get('lang.date-error')]],
            ];
            return response()->json($response, 200);
        }

        // add/update events
        if (!empty($request->id)) {
            $PatientsMeetings = PatientsMeetings::find($request->id);
            if (!$PatientsMeetings) {
                $response  = [
                    'success' => false,
                    'message' => \Lang::get('lang.patient-not-found')
                ];
                return response()->json($response, 200);
            }

            $message = \Lang::get('lang.events-successfully-updated');
        } else {
            $PatientsMeetings = new PatientsMeetings;
            $message = \Lang::get('lang.events-successfully-created');
        }

        $organization_id = $request->organization_id;

        if ($organization_id) {
            // validation: at a time one patient for related doctor
            $startDate = date('Y-m-d H:i:s', strtotime("$request->startDate $request->startTiming"));
            $endDate = date('Y-m-d H:i:s', strtotime("$request->endDate $request->endTiming"));

            $checkDateExistOrNot = null;
            if (!empty($request->patient_doctor_id)) {
                $checkDateExistOrNot = PatientsMeetings::where('organization_id', $organization_id)->where('patient_doctor_id', $request->patient_doctor_id)->where('start_date', '<=', $startDate)->where('end_date', '>', $startDate)->first();
            }

            if ($checkDateExistOrNot && $checkDateExistOrNot->id !== $PatientsMeetings->id) {
                $response  = [
                    'success' => false,
                    'message' => \Lang::get('lang.event-scheduled-given-time-msg')
                ];
                return response()->json($response, 200);
            } else {
                $PatientsMeetings->title = $request->title;
                $PatientsMeetings->user_id = $user_id;
                $PatientsMeetings->start_date = $startDate;
                $PatientsMeetings->end_date = $endDate;
                $PatientsMeetings->reminder = $request->reminder;
                $PatientsMeetings->is_reminder_sent = 0;
                $PatientsMeetings->description = $request->description;
                $PatientsMeetings->schedule_category_code = $request->category;
                $PatientsMeetings->patient_doctor_id = $request->patient_doctor_id;
                $PatientsMeetings->organization_id = $organization_id;
                $PatientsMeetings->place = $request->place;
                $PatientsMeetings->materials = $request->has("materials") && count($request->materials) ? json_encode($request->materials) : null;
                $PatientsMeetings->is_verified = 1; // temp default value
                $PatientsMeetings->save();
                $response  = [
                    'success' => true,
                    'message' => $message
                ];
                return response()->json($response, 200);
            }
        } else {
            $response  = [
                'success' => false,
                'errors' => ["organization_id" => [\Lang::get('lang.organization-required')]],

            ];
            return response()->json($response, 200);
        }
    }

    // delete meeting
    public function deleteMeetings(Request $request)
    {
        if (!empty($request->appointment_id)) {
            PatientsMeetings::where('id', $request->appointment_id)->delete();
            $response  = [
                'success' => true,
                'message' => \Lang::get('lang.appointment-successfully-deleted'),
            ];
        } else {
            $response  = [
                'success' => true,
                'message' => \Lang::get('lang.appointment-required'),
            ];
        }
        return response()->json($response, 200);
    }

    public function createExercise(Request $request)
    {
        $rules = [
            'name' => 'required',
            'type' => 'required',
            'value_of_type' => 'required',
            'description' => 'required',
            'phase_id' => 'required',
            'tools' => 'required',
            'plan_template_id' => 'required',
            'frequency' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $err_response  = [
                'success' => false,
                'errors' => $validator->messages()
            ];
            return response()->json($err_response, 200);
        }
        
        $exercises = new Exercises;

        if($request->has('image') || $request->has('video')) {
            if ($request->has('image') && $request->image != '' && $request->image != NULL) {
                $image = $request->file('image');            
                $name = preg_replace('/\s+/', '', $request->name) . '_' . time();
                // Define folder path
                $folder = '/exercises/';
                // Make a file path where image will be stored [ folder path + file name + file extension]
                $filePath = $folder . $name . '.' . $image->clientExtension();
                // Upload image
                $this->uploadOne($image, $folder, 'public', $name);
                // Set user profile image path in database to filePath
                $exercises->image = $filePath;
                $exercises->video = NULL;
            }
            if ($request->has('video') && $request->video != '' && $request->video != NULL) {
                $video = $request->file('video');            
                $name = preg_replace('/\s+/', '', $request->name) . '_' . time();
                // Define folder path
                $folder = '/exercises/videos/';
                // Make a file path where image will be stored [ folder path + file name + file extension]
                $filePath = $folder . $name . '.' . $video->clientExtension();
                // Upload image
                $this->uploadOne($video, $folder, 'public', $name);
                // Set user profile image path in database to filePath
                $exercises->video = $filePath;
                if(empty($request->image)) {
                    $exercises->image = Helper::getThumbnailForExerciseVideo($name, $filePath);    
                }
                
            }
        }
        $phaseData = PatientPhases::with(['TherapyPlanTemplates'])->find($request->phase_id);
        $exercises->name = $request->name;
        $exercises->description = $request->description;
        // $exercises->tools = $request->tools;
        $exercises->organization_id = $phaseData->TherapyPlanTemplates->organization_id;
        $exercises->user_id = Auth::guard('api')->user()->id;
        $exercises->patient_id = Auth::guard('api')->user()->id;
        $exercises->created_by = Auth::guard('api')->user()->id;
        $exercises->updated_by = Auth::guard('api')->user()->id;
        $exercises->save();
        //to save the materials in the exercise_materials table(pivot)
        $exe_materials = ExerciseMaterialsLists::find(explode(',',$request->tools));
        $exercises->Materials()->attach($exe_materials);


        $phaseExercises = new PatientPhaseExercises;
        $phaseExercises->phase_id = $request->phase_id;
        $phaseExercises->exercise_id = $exercises->id;
        $phaseExercises->type = $request->type;
        $phaseExercises->value_of_type = $request->value_of_type;
        $phaseExercises->frequency = json_decode($request->frequency);
        $phaseExercises->created_at = Carbon::now();
        $phaseExercises->save();

        $response  = [
            'success' => true,
            'message' => \Lang::get('lang.exercises-successfully-added-msg')
        ];
        return response()->json($response, 200);
    }

    // function to upload the file at specific location
    public function uploadOne(UploadedFile $uploadedFile, $folder = null, $disk = 'public', $filename = null)
    {
        $name = !is_null($filename) ? $filename : str_random(25);

        $file = $uploadedFile->storeAs($folder, $name . '.' . $uploadedFile->clientExtension(), $disk);

        return $file;
    }

     public function exerciseProgress(Request $request)
    {
        $rules = [
            'phase_id' => 'required',
            'plan_template_id' => 'required',
            'time_taken' => 'required',
            'has_completed' => 'required',
            'has_aborted' => 'required',
        ];
        if($request->course_id) {
            $rules['round_no'] = 'required';
        }
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $err_response  = [
                'success' => false,
                'errors' => $validator->messages()
            ];
            return response()->json($err_response, 200);
        }

        if($request->course_id) {
            $patientCourses = PatientCourses::find($request->course_id);
        } else {
            if($request->excercise_id && !is_array($request->excercise_id)) {
                $getId = $request->excercise_id;
                $patientCourses = PatientPhaseExercises::find($request->excercise_id);
            } else if($request->abort_exercise_id) {
                $getId = $request->abort_exercise_id;
                $patientCourses = PatientPhaseExercises::find($request->abort_exercise_id);
            } else {
                $getId = '';
                $err_response  = [
                    'success' => false,
                    'errors' => \Lang::get('lang.exercise-not-exist')
                ];
                return response()->json($err_response, 200);
            }
        }
        $current_date = Carbon::now()->format('d:m:Y');
        $current_day = strtolower(Carbon::now()->format('l'));
        if($patientCourses) {
            //to set the data
            if(array_key_exists($current_day, $patientCourses->frequency_data)) {
                $data['excercise_id'] = $request->excercise_id;
                $data['frequency_count'] = $request->current_frequency_count;
                $data['time_taken'] = $request->time_taken;
                $data['has_completed'] = $request->has_completed;
                $data['has_aborted'] = $request->has_aborted;
                $data['skip_exercise_ids'] = $request->skip_exercise_ids;
                $data['abort_exercise_id'] = $request->abort_exercise_id;
                $data['abort_reason'] = $request->abort_reason;
                $data['performed_date'] = Carbon::now();
                if($request->has('course_id')) {
                    $data['round_no'] = $request->round_no;
                }
                //if round info exists
                if($patientCourses->round_info) {
                    $roundInfo = $patientCourses->round_info;
                    if(array_key_exists($current_date, $roundInfo)) {
                        //for course progress
                        if($request->has('course_id')) {
                            //to check the frequency_count exist ot not
                            if(false === array_search($request->current_frequency_count, array_column($roundInfo[$current_date]['frequency_count'], 'frequency_count'))) {
                                $roundInfo[$current_date]['completed_frequency_count'] = $request->completed_frequency_count;
                                $roundInfo[$current_date]['frequency'] = $patientCourses->frequency;
                                $frequency_count['has_completed'] = $request->has_completed;
                                $frequency_count['has_aborted'] = $request->has_aborted;
                                $frequency_count['frequency_count'] = $request->current_frequency_count;
                                $frequency_count['last_perform_date'] = Carbon::now();
                                $frequency_count['total_time'] = $request->time_taken;  
                                $frequency_count['data'][] = $data;
                                $roundInfo[$current_date]['frequency_count'][] = $frequency_count;
                            } else {
                                $roundInfo[$current_date]['completed_frequency_count'] = $request->completed_frequency_count;
                                $roundInfo[$current_date]['frequency'] = $patientCourses->frequency;
                                foreach($roundInfo[$current_date]['frequency_count'] as  $key => $fre_data) {
                                    if($request->current_frequency_count == $fre_data['frequency_count']) {
                                        $fre_data['data'][] = $data;
                                        $fre_data['total_time'] = array_sum(array_column($fre_data['data'],'time_taken'));
                                        $fre_data['has_completed'] = $request->has_completed;
                                        $fre_data['has_aborted'] = $request->has_aborted;
                                        $fre_data['last_perform_date'] = Carbon::now();
                                        $roundInfo[$current_date]['frequency_count'][$key] = $fre_data;
                                    }
                                }
                            }
                        } else {
                            //for exercise progress
                            $roundInfo[$current_date]['completed_frequency_count'] = $request->completed_frequency_count;
                            $roundInfo[$current_date]['frequency'] = $patientCourses->frequency;
                            $roundInfo[$current_date]['last_perform_date'] = Carbon::now();
                            $roundInfo[$current_date]['data'][] = $data;
                            $roundInfo[$current_date]['total_time'] = array_sum(array_column($roundInfo[$current_date]['data'],'time_taken'));
                        }
                    } else {
                        //if current date does not exist
                        if($request->has('course_id')) {
                            $roundInfo[$current_date]['completed_frequency_count'] = $request->completed_frequency_count;
                            $roundInfo[$current_date]['frequency'] = $patientCourses->frequency;
                            $frequency_count['has_completed'] = $request->has_completed;
                            $frequency_count['has_aborted'] = $request->has_aborted;
                            $frequency_count['frequency_count'] = $request->current_frequency_count;
                            $frequency_count['last_perform_date'] = Carbon::now();
                            $frequency_count['total_time'] = $request->time_taken; 
                            $frequency_count['data'][] = $data;
                            $roundInfo[$current_date]['frequency_count'][] = $frequency_count;
                        } else {
                            $roundInfo[$current_date]['completed_frequency_count'] = $request->completed_frequency_count;
                            $roundInfo[$current_date]['frequency'] = $patientCourses->frequency;
                            $roundInfo[$current_date]['last_perform_date'] = Carbon::now(); 
                            $roundInfo[$current_date]['data'][] = $data;
                            $roundInfo[$current_date]['total_time'] = $request->time_taken;
                        }
                    }
                } else {
                    //if round info does not exist
                    $roundInfo[$current_date]['completed_frequency_count'] = $request->completed_frequency_count;
                    $roundInfo[$current_date]['frequency'] = $patientCourses->frequency;
                    if($request->has('course_id')) {
                        $frequency_count['has_completed'] = $request->has_completed;
                        $frequency_count['has_aborted'] = $request->has_aborted;
                        $frequency_count['frequency_count'] = $request->current_frequency_count;
                        $frequency_count['last_perform_date'] = Carbon::now();
                        $frequency_count['total_time'] = $request->time_taken;
                        $frequency_count['data'][] = $data;
                        $roundInfo[$current_date]['frequency_count'][] = $frequency_count;
                    } else {
                        $roundInfo[$current_date]['completed_frequency_count'] = $request->completed_frequency_count;
                        $roundInfo[$current_date]['last_perform_date'] = Carbon::now(); 
                        $roundInfo[$current_date]['data'][] = $data;
                        $roundInfo[$current_date]['total_time'] = $request->time_taken; 
                    }
                }
                $roundInfo = json_encode($roundInfo);
                $patientCourses->round_info = $roundInfo;
                $patientCourses->save();
                if($request->course_id) {
                    $responseCourse = PatientCourses::with('CourseExercises.Exercises')->find($request->course_id);
                } else {
                    $responseCourse = PatientPhaseExercises::with('Exercises')->find($getId);
                }
                $res = json_encode($responseCourse);  //encode the data
                //to encrypt the response
                $passphrase = substr(Auth::guard('api')->user()->api_token, 0, 20);
                $encrypted_data = Helper::cryptoJsAesEncrypt($passphrase, $res);
                $encrypted_phrase = Helper::encrypt($passphrase, trim(Auth::guard('api')->user()->PatientData()->first()->public_key));
                $response  = [
                    'success' => true,
                    'message' => \Lang::get('lang.progress-status-added-successfully'),
                    'passphrase' => base64_encode($encrypted_phrase),
                    'data' => $encrypted_data,
                ];
                return response()->json($response, 200);
            } else {
                $err_response  = [
                    'success' => false,
                    'errors' => \Lang::get('lang.can-not-perform-exercise-error')
                ];
                return response()->json($err_response, 200);
            }
        }
        $err_response  = [
            'success' => false,
            'errors' => \Lang::get('lang.course-exe-not-exist')
        ];
        return response()->json($err_response, 200);
    }

    public function courseFeedback(Request $request)
    {
        $rules = [
            'exercise_id' => 'required_without_all:course_id',
            'course_id' => 'required_without_all:exercise_id',
            'frequency_count' => 'required',
            'pain_level' => 'required',
            'taken_painkiller' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $err_response  = [
                'success' => false,
                'errors' => $validator->messages()
            ];
            return response()->json($err_response, 200);
        }
        $current_date = Carbon::now()->format('d:m:Y');
        //for course feedback
        if($request->course_id) {
            $patientCourses = PatientCourses::find($request->course_id);
            if($patientCourses) {
                $data['frequency_count'] = $request->frequency_count;
                $data['pain_level'] = $request->pain_level;
                $data['taken_painkiller'] = $request->taken_painkiller;
                $data['course_completed_datetime'] = Carbon::now();
                if($patientCourses->course_feedback) {
                    $roundInfo = $patientCourses->course_feedback;
                    $roundInfo[$current_date]['data'][] = $data;
                    $roundInfo = json_encode($roundInfo);
                } else {
                    $roundInfo[$current_date]['data'][] = $data;
                    $roundInfo = json_encode($roundInfo);
                }
                $patientCourses->course_feedback = $roundInfo;
                $patientCourses->save();
                $response  = [
                    'success' => true,
                    'message' => \Lang::get('lang.course-feedback-saved-successfully')
                ];
                return response()->json($response, 200);
            }
        } else {
            //for exercise feedback
           $patientPhaseExe = PatientPhaseExercises::find($request->exercise_id);
            if($patientPhaseExe) {
                $data['frequency_count'] = $request->frequency_count;
                $data['pain_level'] = $request->pain_level;
                $data['taken_painkiller'] = $request->taken_painkiller;
                $data['course_completed_datetime'] = Carbon::now();
                if($patientPhaseExe->course_feedback) {
                    $roundInfo = $patientPhaseExe->course_feedback;
                    $roundInfo[$current_date]['data'][] = $data;
                    $roundInfo = json_encode($roundInfo);
                } else {
                    $roundInfo[$current_date]['data'][] = $data;
                    $roundInfo = json_encode($roundInfo);
                }
                $patientPhaseExe->course_feedback = $roundInfo;
                $patientPhaseExe->save();
                $response  = [
                    'success' => true,
                    'messsage' => \Lang::get('lang.exercise-feedback-saved-successfully')
                ];
                return response()->json($response, 200);
            }
        }
        $response  = [
            'success' => false,
            'errors' => \Lang::get('lang.course-exe-not-exist')
        ];
        return response()->json($response, 200);
    }

    //function to get the exercise analytics data
    public function getAnalyticsData(Request $request) {
        $plans_data = PatientTherapyPlanTemplates::where('patient_id', Auth::guard('api')->user()->id)->where('is_complete', 0)->where('aborted', 0)->first();
        $patient_id = Auth::guard('api')->user()->id;
        if($plans_data) {
            $start_date = $plans_data->start_date;
            //get all the phases of specific plans
            foreach ($plans_data->Phases as $key => $phases) {
                //to set the start/end date of phases
                if ($key == 0) {
                    $dates['start_date'][$key] = Carbon::parse($start_date);
                    $dates['end_date'][$key] = Carbon::parse($start_date)->addWeeks($phases->duration)->subDay(1);
                } else {
                    $dates['start_date'][$key] = Carbon::parse($dates['end_date'][$key - 1])->addDay();
                    $dates['end_date'][$key] = Carbon::parse($dates['start_date'][$key])->addWeeks($phases->duration)->subDay(1);
                }
                
                $phases->start_date = $dates['start_date'][$key];
                $phases->end_date = $dates['end_date'][$key];
                //to check the active phase
                if (Carbon::now()->startOfDay()->gte(Carbon::parse($phases->start_date)->startOfDay()) && Carbon::now()->startOfDay()->lte(Carbon::parse($phases->end_date)->startOfDay())) {
                    $phase_start_date = $phases->start_date;
                    $phase_duration = $phases->duration;
                    $phase_dates = array();
                    for($i = 1; $i <= $phase_duration; $i++) {
                        if($i == 1) {
                            $phase_dates[$i]['phase_weekly_start_date'] = $phases->start_date;
                            $phase_dates[$i]['phase_weekly_end_date'] = Carbon::parse($phases->start_date)->addWeeks(1)->subDay(1);
                        } else {
                            $phase_dates[$i]['phase_weekly_start_date'] = Carbon::parse($phase_dates[$i-1]['phase_weekly_end_date'])->addDay();
                            if(Carbon::parse($phase_dates[$i]['phase_weekly_start_date'])->addWeeks(1)->gt(Carbon::parse($phases->end_date))) {
                                $phase_dates[$i]['phase_weekly_end_date'] = $phases->end_date;
                            } else {
                                $phase_dates[$i]['phase_weekly_end_date'] = Carbon::parse($phase_dates[$i]['phase_weekly_start_date'])->addWeeks(1)->subDay(1);
                            }
                        }
                        if (Carbon::now()->startOfDay()->gte(Carbon::parse($phase_dates[$i]['phase_weekly_start_date'])->startOfDay()) && Carbon::now()->startOfDay()->lte(Carbon::parse($phase_dates[$i]['phase_weekly_end_date'])->startOfDay())) {
                        // if(Carbon::now()->between($phase_dates[$i]['phase_weekly_start_date'],$phase_dates[$i]['phase_weekly_end_date'])) {
                            $individual_exercises = PatientPhaseExercises::with('Exercises.Materials')->where('phase_id', $phases->id)->get();
                            $course_of_exercises = PatientPhaseCourses::with('Course.CourseExercises.Exercises.Materials')->withCount(['Course'])->where('phase_id', $phases->id)->get();
                            $individual_assessments = PatientPhaseAssessments::with('PatientAssessmentTemplates.SubCategoriesData')->where('phases_id', $phases->id)->first();
                            $exercise = [];
                            $course = [];
                            $assessment_details = [];
                            $total_exercises_count = 0;
                            $total_completed_exercises = 0;
                            $total_aborted_exercises = 0;
                            $total_courses_count = 0;
                            $total_completed_courses = 0;
                            $total_aborted_courses = 0;
                            
                            // To check assessment-details
                            if(!empty($individual_assessments)) {
                                // To get the details of sub-categories
                                $all_assessment_details = $individual_assessments->PatientAssessmentTemplates->SubCategoriesData->where('status', 1)->where('is_patient_assessment', 1);
                                $assessment_type = [];
                                foreach($all_assessment_details as $key => $assessments) {
                                    $date = '';
                                    $assessment_data = [];
                                    // To set the assessment type of sub category
                                    $assessments->assessment_type = \Lang::get('lang.'.$assessments->type);
                                    $assessments->measurement_json_data = NULL;
                                    // To get type vice sub-category of assessment
                                    if(!in_array($assessments->type, $assessment_type, true)){
                                        array_push($assessment_type, $assessments->type);
                                        // To check the measurement-json
                                        if(!empty($assessments->measurements_json)){
                                            $measurementData = json_decode($assessments->measurements_json, true);
                                            foreach($measurementData as $key => $value) {
                                                foreach($value as $k => $v){
                                                    $date = Carbon::parse($v['created_date']);
                                                    $assessment_data['first_measurement'] = $v['first_measurement'];
                                                    $assessment_data['second_measurement'] = $v['second_measurement'];
                                                    $assessment_data['difference'] = $v['difference'];
                                                    $assessments->measurement_json_data = $assessment_data;
                                                }
                                            }
                                        }
                                        $assessment_details[$assessments->type] = $assessments;
                                    } else {
                                        // if assessment type is already exists
                                        if(!empty($assessments->measurements_json)){
                                            $measurementData = json_decode($assessments->measurements_json, true);
                                            foreach($measurementData as $key => $value) {
                                                foreach($value as $k => $v){
                                                    if((empty($date)) || $date->lt(Carbon::parse($v['created_date']))) {
                                                        $assessment_data['first_measurement'] = $v['first_measurement'];
                                                        $assessment_data['second_measurement'] = $v['second_measurement'];
                                                        $assessment_data['difference'] = $v['difference'];
                                                        $assessments->measurement_json_data = $assessment_data;
                                                        $assessment_details[$assessments->type] = $assessments;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            } 

                            // to get the exercise count
                            if(!$individual_exercises->isEmpty()) {
                                foreach($individual_exercises as $key => $exercises) {
                                    // To check status of exercise is zero or not and if exercise has round info
                                    if($exercises->status == 0 && empty($exercises->round_info)){
                                        continue;
                                    }
                                    $count = 0;
                                    foreach($exercises->frequency_data as $fre) {
                                        $count = $fre;  
                                    }
                                    $freq_count = 0;
                                    // To get the start date of week
                                    $start_date = Carbon::parse($phase_dates[$i]['phase_weekly_start_date'])->startOfDay();
                                    // To get the exercise count as per recorded
                                    for($j=0; $j<=6; $j++) {
                                        // To get current date in exercise
                                        $current_date =  Carbon::parse($start_date)->addDay($j)->format('d:m:Y');
                                        $day = strtolower(Carbon::parse($start_date)->addDay($j)->format('l'));
                                        // If current date has performed data
                                        if(!empty($exercises->round_info) && array_key_exists(strtolower($current_date), $exercises->round_info)) {
                                            // To get the frequency from round info of exercise
                                            $frequencyExercises = json_decode($exercises->round_info[$current_date]['frequency']);
                                            // If current day has frequecy data of exercise in round info
                                            if(array_key_exists($day, $frequencyExercises)) {
                                                // To add round info frequecy count
                                                $freq_count += $frequencyExercises->$day;
                                            }
                                        } else {
                                            // if exercise is not perform on current day
                                            if(array_key_exists(strtolower($day), $exercises->frequency_data)) {
                                                // To add exercise frequecy count
                                                $freq_count += $count;
                                            }
                                        }
                                    }
                                    // To get the total exercise count
                                    $total_exercises_count += $freq_count;
                                    if(!empty($exercises->round_info)) {
                                        foreach($exercises->round_info as $key => $value) {
                                            $res = explode(":",  $key);
                                            $changedDate = $res[0] . "-" . $res[1] . "-" . $res[2];
                                            $data['start_date'] = Carbon::parse($changedDate)->format('Y-m-d H:i:s');
                                            if(Carbon::parse($changedDate)->between($phase_dates[$i]['phase_weekly_start_date'],$phase_dates[$i]['phase_weekly_end_date'])) {
                                                $total_completed_exercises += $value['completed_frequency_count'];
                                                // To get frequency of exercises
                                                $frequencyExe = json_decode($value['frequency']);
                                                foreach($frequencyExe as $exe_freq_data) {
                                                    $day_exercise_count = $exe_freq_data;  
                                                }
                                                // If completed exercises is less than day exercises
                                                if($value['completed_frequency_count'] < $day_exercise_count) {
                                                    foreach($value['data'] as $exe_data) {
                                                        $aborted_count = 0;
                                                        if($exe_data['has_aborted'] == "true") {
                                                            $aborted_count = 1;
                                                            break;
                                                        }
                                                    }
                                                    // To get total aborted exercise count
                                                    $total_aborted_exercises +=  (int)$aborted_count;
                                                }
                                            }
                                        }
                                    }
                                }

                                //set the exercise related data
                                $exercise['total'] = $total_exercises_count;
                                $exercise['completed'] = $total_completed_exercises;
                                $exercise['aborted'] = $total_aborted_exercises;
                                $exercise['undone'] = $total_exercises_count - $total_completed_exercises - $total_aborted_exercises;
                                $exercise['completed_in_percentage'] = round(($total_completed_exercises * 100) / $total_exercises_count, 2);
                                $exercise['aborted_in_percentage'] = round(($total_aborted_exercises * 100) / $total_exercises_count, 2);
                                $exercise['undone_in_percentage'] = round(($exercise['undone'] * 100) / $total_exercises_count, 2);
                            }

                            // to get the course count
                            if(!$course_of_exercises->isEmpty()) {
                                foreach($course_of_exercises as $key => $phase_courses) {
                                    // To check status of course is zero or not and if course has round info
                                    if($phase_courses->status == 0 && empty($phase_courses->Course->round_info)){
                                        continue;
                                    }
                                    $fre_count = 0;
                                    foreach($phase_courses->Course->frequency_data as $fre) {
                                        $fre_count = $fre;  
                                    }
                                    $freq_course_count = 0;
                                    //To get the start date of week
                                    $courseStartDate = Carbon::parse($phase_dates[$i]['phase_weekly_start_date'])->startOfDay();
                                    // To get the course count as per recorded
                                    for($j=0; $j<=6; $j++) {
                                        // To get current date of courses
                                        $course_current_date = Carbon::parse($courseStartDate)->addDays($j)->format('d:m:Y');
                                        $course_day = strtolower(Carbon::parse($courseStartDate)->addDays($j)->format('l'));
                                        //to check the course need to perform today 
                                        if(!empty($phase_courses->Course->round_info) && array_key_exists(strtolower($course_current_date), $phase_courses->Course->round_info)) {
                                            // To get the course frequency from round info
                                            $courseFrequency = json_decode($phase_courses->Course->round_info[$course_current_date]['frequency']);
                                            // If current day has frequecy data of course in round info
                                            if(array_key_exists($course_day, $courseFrequency)) {
                                                // To add round info frequecy count of course
                                                $freq_course_count += $courseFrequency->$course_day;
                                            }
                                        } else {
                                            // if course is not perform on current day
                                            if(array_key_exists($course_day, $phase_courses->Course->frequency_data)) {
                                                // To add course frequecy count
                                                $freq_course_count += $fre_count;
                                            }
                                        }
                                    }
                                    // To get the total course count
                                    $total_courses_count += $freq_course_count;
                                    if(!empty($phase_courses->Course->round_info)) {
                                        foreach($phase_courses->Course->round_info as $key => $value) {
                                            $res = explode(":",  $key);
                                            $course_attempted_date = $res[0] . "-" . $res[1] . "-" . $res[2];
                                            //To check the course performed data in the current week or not
                                            if(Carbon::parse($course_attempted_date)->between($phase_dates[$i]['phase_weekly_start_date'],$phase_dates[$i]['phase_weekly_end_date'])) {
                                                $total_completed_courses += $value['completed_frequency_count'];
                                                // To get frequency of courses
                                                $course_frequency = json_decode($value['frequency']);
                                                foreach($course_frequency as $fre) {
                                                    $day_course_count = $fre;  
                                                }
                                                // If completed courses is less than day courses
                                                if($value['completed_frequency_count'] < $day_course_count) {
                                                    foreach($value['frequency_count'] as $courses_data) {
                                                        $aborted_courses_count = 0;
                                                        if($courses_data['has_aborted'] == "true") {
                                                            $aborted_courses_count = 1;
                                                            break;
                                                        }
                                                    }
                                                    // To get the total aborted courses count
                                                    $total_aborted_courses +=  (int)$aborted_courses_count;
                                                }
                                            }
                                        }
                                    }
                                }
                                //set the course related data
                                $course['total'] = $total_courses_count;
                                $course['completed'] = $total_completed_courses;
                                $course['aborted'] = $total_aborted_courses;
                                $course['undone'] = $total_courses_count - $total_completed_courses - $total_aborted_courses;
                                $course['completed_in_percentage'] = round(($total_completed_courses * 100) / $total_courses_count, 2);
                                $course['aborted_in_percentage'] = round(($total_aborted_courses * 100) / $total_courses_count, 2);
                                $course['undone_in_percentage'] = round(($course['undone'] * 100) / $total_courses_count, 2);
                            }
                        }
                    }
                    $data = [
                        'plan_template_id' => $plans_data->id,
                        'phase_id' => $phases->id,
                        'phase_name' => $phases->name,
                        'exercises' => !empty($exercise) ? $exercise : NULL,
                        'courses' => !empty($course) ? $course : NULL,
                        'assessment_details' => !empty($assessment_details) ? $assessment_details : NULL,
                    ];
                    // return response()->json($data, 200);
                    $res = json_encode($data);  //encode the data
                    //to encrypt the response
                    $passphrase = substr(Auth::guard('api')->user()->api_token, 0, 20);
                    $encrypted_data = Helper::cryptoJsAesEncrypt($passphrase, $res);
                    $encrypted_phrase = Helper::encrypt($passphrase, trim(Auth::guard('api')->user()->PatientData()->first()->public_key));
                    $response  = [
                        'success' => true,
                        'message' => \Lang::get('lang.analytics-data-successfully-fetched'),
                        'passphrase' => base64_encode($encrypted_phrase),
                        'data' => $encrypted_data,
                    ];
                    return response()->json($response, 200);
                }
            }
        }
        $err_response  = [
            'success' => false,
            'errors' => \Lang::get('lang.no-active-phase-detected')
        ];
        return response()->json($err_response, 200);
    }

    //function to get the analytical data week-vise
    public function getWeeklyAnalyticsData(Request $request) {
        $plans_data = PatientTherapyPlanTemplates::where('patient_id', Auth::guard('api')->user()->id)->where('is_complete', 0)->where('aborted', 0)->first();
        $patient_id = Auth::guard('api')->user()->id;
        if($plans_data) {
            $start_date = $plans_data->start_date;
            //get all the phases of specific plans
            foreach ($plans_data->Phases as $key => $phases) {
                //to set the start/end date of phases
                if ($key == 0) {
                    $dates['start_date'][$key] = Carbon::parse($start_date);
                    $dates['end_date'][$key] = Carbon::parse($start_date)->addWeeks($phases->duration)->subDay(1);
                } else {
                    $dates['start_date'][$key] = Carbon::parse($dates['end_date'][$key - 1])->addDay();
                    $dates['end_date'][$key] = Carbon::parse($dates['start_date'][$key])->addWeeks($phases->duration)->subDay(1);
                }
                
                $phases->start_date = $dates['start_date'][$key];
                $phases->end_date = $dates['end_date'][$key];
                //to check the active phase
                if (Carbon::now()->startOfDay()->gte(Carbon::parse($phases->start_date)->startOfDay()) && Carbon::now()->startOfDay()->lte(Carbon::parse($phases->end_date)->startOfDay())) {
                    $phase_start_date = $phases->start_date;
                    if(Carbon::parse($phase_start_date)->format('l') == 'Monday') {
                        $phase_duration = $phases->duration;
                    } else {
                        $phase_duration = $phases->duration + 1;
                    }
                    $phase_dates = array();
                    $all_details = [];
                    // To set the monday-sunday interval for phase
                    for($i = 1; $i <= $phase_duration; $i++) {
                        if($i == 1) {
                            $phase_dates[$i]['phase_weekly_start_date'] = Carbon::parse($phases->start_date)->startOfWeek();
                            $phase_dates[$i]['phase_weekly_end_date'] = Carbon::parse($phases->start_date)->startOfWeek()->addWeeks(1);
                        } else {
                            $phase_dates[$i]['phase_weekly_start_date'] = Carbon::parse($phase_dates[$i-1]['phase_weekly_end_date']);
                            $phase_dates[$i]['phase_weekly_end_date'] = Carbon::parse($phase_dates[$i]['phase_weekly_start_date'])->addWeeks(1);
                        }
                    }
                    //to set the weekly data
                    foreach($phase_dates as $key => $weekly_phase) {
                        $exercise_days = [];
                        $course_days = [];
                        $individual_exercises = PatientPhaseExercises::with('Exercises.Materials')->where('phase_id', $phases->id)->get();
                        $courses = PatientPhaseCourses::with('Course.CourseExercises.Exercises.Materials')->withCount(['Course'])->where('phase_id', $phases->id)->get();
                        //to set the data weekdays wise
                        for($i = 0; $i <= 6; $i++) {
                            //get current date
                            $current_date = Carbon::parse($weekly_phase['phase_weekly_start_date'])->addDays($i)->format('d:m:Y');
                            $unformatted_date = Carbon::parse($weekly_phase['phase_weekly_start_date'])->addDays($i);
                            //get the week dat of current date
                            $day = Carbon::parse($weekly_phase['phase_weekly_start_date'])->addDays($i)->format('l');
                            $total_exercises_count = 0;
                            $total_completed_exercises = 0;
                            $total_aborted_exercises = 0;
                            $total_undone_exercises = 0;
                            $total_courses_count = 0;
                            $total_completed_courses = 0;
                            $total_aborted_courses = 0;
                            $total_undone_courses = 0;
                            $data = [];
                            //to check the current date is in phase periods or not
                            if (Carbon::parse($unformatted_date)->gte(Carbon::parse($phases->start_date)->startOfDay()) && Carbon::parse($unformatted_date)->lte(Carbon::parse($phases->end_date)->startOfDay())) 
                            {
                                //to set the exercise data
                                if(!$individual_exercises->isEmpty()) {
                                    foreach($individual_exercises as $exe_key => $exercises) {
                                        // To check status of course is zero or not and if course has round info
                                        if($exercises->status == 0 && empty($exercises->round_info)){
                                            continue;
                                        }
                                        $count = 0;
                                        $freq_count = 0;
                                        $exercise_data = [];
                                        $currentDay = strtolower($day);
                                        foreach($exercises->frequency_data as $fre) {
                                            $count = $fre;
                                        }
                                        //if exercise need to perform on current day
                                        if(!empty($exercises->round_info) && array_key_exists(strtolower($current_date), $exercises->round_info)) {
                                            // To get the frequency from round info of exercise
                                            $frequencyExercises = json_decode($exercises->round_info[$current_date]['frequency']);
                                            // If current day has frequecy data of exercise in round info
                                            if(array_key_exists($currentDay, $frequencyExercises)) {
                                                // To add round info frequecy count
                                                $freq_count += $frequencyExercises->$currentDay;
                                            }   
                                        } else {
                                            // if course is not perform on current day
                                            if(array_key_exists(strtolower($currentDay), $exercises->frequency_data)) {
                                                // To add exercise frequecy count
                                                $freq_count += $count;
                                            }
                                        }
                                        // To get the total exercise count
                                        $total_exercises_count += $freq_count;
                                        if(!empty($exercises->round_info)) {
                                            if(array_key_exists($current_date, $exercises->round_info)) {
                                                $current_round_info = $exercises->round_info[$current_date];
                                                $temp_exe_round_id = 0;
                                                //to get the round info data
                                                foreach($current_round_info['data'] as $exe_fre_key => $exe_data) {
                                                    $exer_data =  [];
                                                    if($exe_data['has_completed'] == 'true') {
                                                        //if completed then add the data
                                                        $temp_exe_round_id = $temp_exe_round_id + 1;
                                                        $exer_data['phase_exercise_id'] = $exercises->id;
                                                        $exer_data['exercise_id'] = $exercises->Exercises->id;
                                                        $exer_data['name'] = $exercises->Exercises->name;
                                                        $exer_data['total_time'] = $exe_data['time_taken'];
                                                        $exer_data['pain_level'] = 0;
                                                        $exer_data['taken_painkiller'] = "false";
                                                        $exer_data['performed_datetime'] = $exe_data['performed_date'];
                                                        //get the pain level of exercise
                                                        if(!empty($exercises->course_feedback)) {
                                                            if(array_key_exists($current_date, $exercises->course_feedback)) {
                                                                $current_feedback = $exercises->course_feedback[$current_date];
                                                                foreach($current_feedback['data'] as $fedd_exe_key => $feedback_data)
                                                                {
                                                                    if($fedd_exe_key == ($temp_exe_round_id - 1)) {
                                                                        $exer_data['pain_level'] = $feedback_data['pain_level'];
                                                                        $exer_data['taken_painkiller'] = $feedback_data['taken_painkiller'];
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                    //if exer_data is not empty the add
                                                    if(!empty($exer_data)) {
                                                        if(!empty($exercise_days[$current_date]['data'][0])) {
                                                            array_push($exercise_days[$current_date]['data'][0], $exer_data);
                                                        } else {
                                                            $exercise_data[] = $exer_data;
                                                        }
                                                    }
                                                }
                                                $current_round_info = $exercises->round_info[$current_date];
                                                $total_completed_exercises += (int)$current_round_info['completed_frequency_count'];
                                                // To get frequency of exercise
                                                $frequencyExe = json_decode($current_round_info['frequency']);
                                                foreach($frequencyExe as $exe_freq_data) {
                                                    $day_exercise_count = $exe_freq_data;  
                                                }
                                                // If completed exercises is less than day exercise
                                                if($current_round_info['completed_frequency_count'] < $day_exercise_count) {
                                                    foreach($current_round_info['data'] as $exe_data) {
                                                        $aborted_count = 0;
                                                        if($exe_data['has_aborted'] == "true") {
                                                            $aborted_count = 1;
                                                            break;
                                                        }
                                                    }
                                                    // To get the total aborted exercise count
                                                    $total_aborted_exercises +=  (int)$aborted_count;
                                                }
                                            }
                                        }
                                        if(!empty($exercise_data)) {
                                            $exercise_days[$current_date]['data'][] = $exercise_data;
                                        }
                                    }
                                }
                                //to set allover exercise data
                                $total_undone_exercises = $total_exercises_count - $total_completed_exercises - $total_aborted_exercises;
                                $exercise_days[$current_date]['total_exercises_count'] = $total_exercises_count;
                                $exercise_days[$current_date]['total_completed_exercises'] = $total_completed_exercises;
                                $exercise_days[$current_date]['total_aborted_exercises'] = $total_aborted_exercises;
                                $exercise_days[$current_date]['total_undone_exercises'] = $total_undone_exercises;

                                //to check the course data
                                if(!$courses->isEmpty()) {
                                    foreach($courses as $course_key => $phase_courses) {
                                        // To check status of course is zero or not and if course has round info
                                        if($phase_courses->status == 0 && empty($phase_courses->Course->round_info)){
                                            continue;
                                        }
                                        $fre_count = 0;
                                        $freq_course_count = 0;
                                        $course_data = [];
                                        $course_day = strtolower($day);
                                        foreach($phase_courses->Course->frequency_data as $fre) {
                                            $fre_count = $fre;  
                                        }
                                        //to check the course need to perform today 
                                        if(!empty($phase_courses->Course->round_info) && array_key_exists(strtolower($current_date), $phase_courses->Course->round_info)) {
                                            // To get the course frequency from round info
                                            $courseFrequency = json_decode($phase_courses->Course->round_info[$current_date]['frequency']);
                                            // If current day has frequecy data of course in round info
                                            if(array_key_exists($course_day, $courseFrequency)) {
                                                // To add round info frequecy count of course
                                                $freq_course_count += $courseFrequency->$course_day;
                                            }
                                        } else {
                                            // if course is not perform on current day
                                            if(array_key_exists($course_day, $phase_courses->Course->frequency_data)) {
                                                // To add course frequecy count
                                                $freq_course_count += $fre_count;
                                            }
                                        }
                                        // To get the total courses count
                                        $total_courses_count += $freq_course_count;
                                        if(!empty($phase_courses->Course->round_info)) {
                                            if(array_key_exists($current_date, $phase_courses->Course->round_info)) {
                                                $current_course_round_info = $phase_courses->Course->round_info[$current_date];
                                                $temp_course_round_id = 0;
                                                foreach($current_course_round_info['frequency_count'] as $fre_key => $course_progress) {
                                                    $cour_data =  [];
                                                    if($course_progress['has_completed'] == 'true') {
                                                        //if completed then add the data
                                                        $temp_course_round_id = $temp_course_round_id + 1;
                                                        $cour_data['phase_course_id'] = $phase_courses->id;
                                                        $cour_data['course_id'] = $phase_courses->Course->id;
                                                        $cour_data['name'] = $phase_courses->Course->name;
                                                        $cour_data['total_time'] = $course_progress['total_time'];
                                                        $cour_data['pain_level'] = 0;
                                                        $cour_data['taken_painkiller'] = "false";
                                                        $cour_data['performed_datetime'] = $course_progress['last_perform_date'];
                                                        //to get the pain level of courses
                                                        if(!empty($phase_courses->Course->course_feedback)) {
                                                            if(array_key_exists($current_date, $phase_courses->Course->course_feedback)) {
                                                                $current_feedback = $phase_courses->Course->course_feedback[$current_date];
                                                                foreach($current_feedback['data'] as $fedd_key => $feedback_data)
                                                                {
                                                                    if($fedd_key == ($temp_course_round_id - 1)) {
                                                                        $cour_data['pain_level'] = $feedback_data['pain_level'];
                                                                        $cour_data['taken_painkiller'] = $feedback_data['taken_painkiller'];
                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                    //if cour_data is not empty the add
                                                    if(!empty($cour_data)) {
                                                        if(!empty($course_days[$current_date]['data'][0])) {
                                                            array_push($course_days[$current_date]['data'][0], $cour_data);
                                                        } else {
                                                            $course_data[] = $cour_data;    
                                                        }
                                                    }
                                                }
                                                $total_completed_courses += (int)$current_course_round_info['completed_frequency_count'];
                                                // To get frequency of courses
                                                $frequencyCour = json_decode($current_course_round_info['frequency']);
                                                foreach($frequencyCour as $exe_freq_data) {
                                                    $day_exercise_count = $exe_freq_data;  
                                                }
                                                // If completed courses is less than day courses
                                                if($current_course_round_info['completed_frequency_count'] < $day_exercise_count) {
                                                    foreach($current_course_round_info['frequency_count'] as $course_progress) {
                                                        $aborted_course_count = 0;
                                                        if($course_progress['has_aborted'] == "true") {
                                                            $aborted_course_count = 1;
                                                            break;
                                                        }
                                                    }
                                                    // To get the total aborted courses count
                                                    $total_aborted_courses +=  (int)$aborted_course_count;
                                                }
                                            }
                                        }
                                        
                                        if(!empty($course_data)) {
                                            $course_days[$current_date]['data'][] = $course_data;
                                        }
                                    }
                                }
                                //to set allover data of courses
                                $total_undone_courses = $total_courses_count - $total_completed_courses - $total_aborted_courses;
                                $course_days[$current_date]['total_courses_count'] = $total_courses_count;
                                $course_days[$current_date]['total_completed_courses'] = $total_completed_courses;
                                $course_days[$current_date]['total_aborted_courses'] = $total_aborted_courses;
                                $course_days[$current_date]['total_undone_courses'] = $total_undone_courses;
                            } else {
                                //to set allover exercise data
                                $total_undone_exercises = $total_exercises_count - $total_completed_exercises - $total_aborted_exercises;
                                $exercise_days[$current_date]['total_exercises_count'] = $total_exercises_count;
                                $exercise_days[$current_date]['total_completed_exercises'] = $total_completed_exercises;
                                $exercise_days[$current_date]['total_aborted_exercises'] = $total_aborted_exercises;
                                $exercise_days[$current_date]['total_undone_exercises'] = $total_undone_exercises;
                                //to set allover data of courses
                                $total_undone_courses = $total_courses_count - $total_completed_courses - $total_aborted_courses;
                                $course_days[$current_date]['total_courses_count'] = $total_courses_count;
                                $course_days[$current_date]['total_completed_courses'] = $total_completed_courses;
                                $course_days[$current_date]['total_aborted_courses'] = $total_aborted_courses;
                                $course_days[$current_date]['total_undone_courses'] = $total_undone_courses;
                            } 
                        }
                        $phases_details = [];
                        $phases_details['start_date'] = $weekly_phase['phase_weekly_start_date'];
                        $phases_details['end_date'] = $weekly_phase['phase_weekly_end_date']->subDays(1);
                        $phases_details['phase_name'] = $phases->name;
                        $phases_details['exercises'] = $exercise_days;
                        $phases_details['courses'] = $course_days;
                        $all_details[] = $phases_details;
                    }
                    // return response()->json($all_details, 200);
                    $res = json_encode($all_details);  //encode the data
                    //to encrypt the response
                    $passphrase = substr(Auth::guard('api')->user()->api_token, 0, 20);
                    $encrypted_data = Helper::cryptoJsAesEncrypt($passphrase, $res);
                    $encrypted_phrase = Helper::encrypt($passphrase, trim(Auth::guard('api')->user()->PatientData()->first()->public_key));
                    $response  = [
                        'success' => true,
                        'message' => \Lang::get('lang.analytics-data-successfully-fetched'),
                        'passphrase' => base64_encode($encrypted_phrase),
                        'data' => $encrypted_data,
                    ];
                    return response()->json($response, 200);
                }
            }
        }
        $err_response  = [
            'success' => false,
            'errors' => \Lang::get('lang.no-active-phase-detected')
        ];
        return response()->json($err_response, 200);
    }

    //function to save the assessment measurements
    public function addMeasurementsOfAssessments(Request $request) {
        $rules = [
            'assessment_id' => 'required',
            'first_measurement' => 'required',
            'schedule_id' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $err_response  = [
                'success' => false,
                'errors' => $validator->messages()
            ];
            return response()->json($err_response, 200);
        }
        //get the assessment details using assessment_id
        $assessment_sub_categories = PatientAssessmentSubCategories::find($request->assessment_id);
        if(!empty($assessment_sub_categories)) {
            //To set the data for json 
            $current_date = Carbon::now()->format('d:m:Y');
            $scheduleId = $request->schedule_id;
            $data['first_measurement'] = $request->first_measurement;
            $data['second_measurement'] = !empty($request->second_measurement) ? $request->second_measurement : null;
            $data['difference'] = !empty($request->difference) ? $request->difference : null;
            $data['created_date'] = Carbon::now();
            //if json exists then append
            if($assessment_sub_categories->measurements_json) {
                $json_data = json_decode($assessment_sub_categories->measurements_json, true);
                $json_data[$current_date][$scheduleId] = $data;
            } else {
                //create new json
                $json_data[$current_date][$scheduleId] = $data;
            }
            $assessment_sub_categories->measurements_json = json_encode($json_data);
            $assessment_sub_categories->save();
            $response = [
                'success' => true,
                'messsage' => \Lang::get('lang.assessment-measurement-saved')
            ];
                return response()->json($response, 200);
        } else {
            $err_response  = [
            'success' => false,
            'errors' => \Lang::get('lang.no-assessment-found')
        ];
        return response()->json($err_response, 200);
        }
    }
    
    // function to get organization connected with patient
    public function getOrganizationList(Request $request) {
        $user = Auth::guard('api')->user();
        // To get the patient therapy plan details of logged-in user
        $planData = PatientTherapyPlanTemplates::with('PatientPlanNetwork.Organizations.SpeciesWithCode')
                        ->with(['PatientPlanNetwork' => function($query) {
                            $query->where('is_owner_organization', 1);
                        }])
                        ->where('is_released', 0)
                        ->where('aborted', 0)
                        ->where('is_draft', 0)
                        ->where('is_complete', 0)
                        ->where('patient_id', $user->id)
                        ->first();
        // To Check the type of organization
        $isBsnr = $planData->PatientPlanNetwork[0]->Organizations->SpeciesWithCode->isbsnr;
        // 1 = Doctor Organization, 2 = physio Organization
        $organization_type  = ($isBsnr == 'true') ? '1' : '2';
        // To create the permission array of phases, assessment and exercise/courses
        $permissions_array = array($planData->phases_editable_by, $planData->assessments_editable_by, $planData->exercises_courses_editable_by);
        // To check the which organization type is needed ex. doctor/physio
        $requeired_permission = array_filter($permissions_array, function ($var) use    ($organization_type) {
            return ($var != $organization_type);
        });
        $bsnrValue = array_shift($requeired_permission);
        if(!empty($bsnrValue)) {
            // 1 = Doctor Organization, 2 = physio Organization
            $isbsnr = $bsnrValue == '1' ? 'true' : 'false';
            // To get the listing of organizations of specific type (doctor/physio)
            $organizationData = Organization::whereNotNull('verified_at')
                                    ->whereHas('OrganizationPatient', function ($q) use ($user) {
                                        $q->where('user_id', $user->id);
                                    })
                                    ->whereHas('SpeciesWithCode', function($query) use($isbsnr){ 
                                        $query->where('isbsnr', $isbsnr);
                                    })
                                    ->get();
            $res = json_encode($organizationData);
            // To encrypt the response
            $passphrase = substr($user->api_token, 0, 20);
            $encrypted_data = Helper::cryptoJsAesEncrypt($passphrase, $res);
            $encrypted_phrase = Helper::encrypt($passphrase, trim(Auth::guard('api')->user()->PatientData()->first()->public_key));
            $response = [
                'success' => true,
                'message' => \Lang::get('lang.organization-successfully-fetched'),
                'passphrase' => base64_encode($encrypted_phrase),
                'data' => $encrypted_data
            ];
            return response()->json($response, 200);
        } else {
            $err_response = [
                'success' => false,
                'errors' => \Lang::get('lang.organization not found')
            ];
            return response()->json($err_response, 200);
        }
    }

    // To connect to treatment organization
    public function connectToTreatmentOrganization(Request $request){
        $status = 200;
        $validator = Validator::make($request->all(), [
            'organization_id' => ['required'],
        ]);
        if ($validator->fails()) {
            $err_response  = [
                'success' => false,
                'errors' => $validator->messages()
            ];
            return response()->json($err_response, 200);
        }
        $organization_id = $request->organization_id;
        // To get the organiation data 
        $organizations = Organization::where('org_unique_id', $organization_id)->first();
        if (!empty($organizations)) {
            $user = Auth::guard('api')->user();
            // To get the patient therapy plan details of logged-in user
            $patient_data = PatientTherapyPlanTemplates::where('is_released', 0)
                            ->where('aborted', 0)
                            ->where('is_draft', 0)
                            ->where('is_complete', 0)
                            ->where('patient_id', $user->id)
                            ->with(['PatientPlanNetwork' => function($q) {
                                $q->where('is_owner_organization', 1);
                            }])
                            ->first();
            if($patient_data) {
                $check_progress_status = $patient_data->PatientPlanNetwork[0];
                $patientRequestData = new PatientPlanNetwork();
                $patientRequestData->patient_id = $user->id;
                $patientRequestData->plan_id = $patient_data->id;
                $patientRequestData->organization_id = $organizations->id;
                $patientRequestData->user_id = null;
                $patientRequestData->is_owner_organization = 0;
                $patientRequestData->draft_id = null;
                $patientRequestData->status = 0;
                $patientRequestData->is_edit_flag = 0;
                if($check_progress_status->process_status == 0 || $check_progress_status->process_status == 1) {
                    $patientRequestData->process_status  = 1;
                } else {
                    $patientRequestData->process_status  = 2;
                }
                $patientRequestData->save();
                $response = [
                    'success' => true,
                    'message' => \Lang::get('lang.treatmet-request-successfully-sent'),
                ];
                return response()->json($response, $status);
            } else {
                $err_response = [
                    'success' => false,
                    'errors' => \Lang::get('lang.no-active-phase-detected')
                ];
                return response()->json($err_response, 200);
            }
        } else {
            $err_response = [
                'success' => false,
                'errors' => \Lang::get('lang.organization not found')
            ];
            return response()->json($err_response, 200);
        }
        
    }

    // To get the needed type of organization
    public function getNeededOrganizationType($plans_data, $isbsnr)
    {
        // To check organization type : 1 = Doctore and 2 = Physio
        $organization_type  = ($isbsnr == 'true') ? '1' : '2';
        // To create the permission array of phases, assessment and exercise/courses
        $permissions_array = array($plans_data->phases_editable_by, $plans_data->assessments_editable_by, $plans_data->exercises_courses_editable_by);
        // To check the which organization type is needed ex. doctor/physio
        $requeired_permission = array_filter($permissions_array, function ($var) use    ($organization_type) {
            return ($var != $organization_type);
        });
        $bsnrValue = array_shift($requeired_permission);
        return $bsnrValue;
    }

    // To get the event details
    public function getEventDetails(Request $request, $id) {
        // To get the locale language 
        $locale = $request->headers->has('Content-Language') ? $request->header('Content-Language') : 'en';
        $language_id = $locale == 'en' ? 1 : 2;
        // To get the event details
        $eventDetails = PatientsMeetings::with([
            'meetingCategoryWithCode' => function ($type) use ($language_id) {
                return $type->where('language_id', $language_id);
            }
        ])->with(['workerData', 'userData'])->where('id', $id)->first();
        // To check evenet details 
        if (!empty($eventDetails)) {
            $res = json_encode($eventDetails);
            $passphrase = substr(Auth::guard('api')->user()->api_token, 0, 20);
            $encrypted_data = Helper::cryptoJsAesEncrypt($passphrase, $res);
            $encrypted_phrase = Helper::encrypt($passphrase, trim(Auth::guard('api')->user()->PatientData()->first()->public_key));
            $response = [
                'success' => true,
                'message' => \Lang::get('lang.appointment-details-successfully-fetched'),
                'passphrase' => base64_encode($encrypted_phrase),
                'data' => $encrypted_data
            ];
            return response()->json($response, 200);
        } else {
            $err_response = [
                'success' => false,
                'errors' => \Lang::get('lang.meeting-not-found')
            ];
            return response()->json($err_response, 200);
        }
    }
    
    public function getAssessmentDetails(Request $request) {
        $rules = [
            'plan_template_id' => 'required',
            'assessment_type' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $err_response  = [
                'success' => false,
                'errors' => $validator->messages()
            ];
            return response()->json($err_response, 200);
        }
        // To get the assign plan for logged in user
        $plans_data = PatientTherapyPlanTemplates::find($request->plan_template_id);
        // To check plan exist
        if($plans_data) {
            $start_date = $plans_data->start_date;
            //get all the phases of specific plans
            foreach ($plans_data->Phases as $key => $phases) {
                //to set the start/end date of phases
                if ($key == 0) {
                    $dates['start_date'][$key] = Carbon::parse($start_date);
                    $dates['end_date'][$key] = Carbon::parse($start_date)->addWeeks($phases->duration)->subDay(1);
                } else {
                    $dates['start_date'][$key] = Carbon::parse($dates['end_date'][$key - 1])->addDay();
                    $dates['end_date'][$key] = Carbon::parse($dates['start_date'][$key])->addWeeks($phases->duration)->subDay(1);
                }
                
                $phases->start_date = $dates['start_date'][$key];
                $phases->end_date = $dates['end_date'][$key];
                //to check the active phase
                if (Carbon::now()->startOfDay()->gte(Carbon::parse($phases->start_date)->startOfDay()) && Carbon::now()->startOfDay()->lte(Carbon::parse($phases->end_date)->startOfDay())) {
                    $phase_start_date = $phases->start_date;
                    if(Carbon::parse($phase_start_date)->format('l') == 'Monday') {
                        $phase_duration = $phases->duration;
                    } else {
                        $phase_duration = $phases->duration + 1;
                    }
                    $assessment_type = $request->assessment_type;
                    $phase_dates = array();
                    $all_details = [];
                    // To set the monday-sunday interval for phase
                    for($i = 1; $i <= $phase_duration; $i++) {
                        if($i == 1) {
                            $phase_dates[$i]['phase_weekly_start_date'] = Carbon::parse($phases->start_date)->startOfWeek();
                            $phase_dates[$i]['phase_weekly_end_date'] = Carbon::parse($phases->start_date)->startOfWeek()->addWeeks(1);
                        } else {
                            $phase_dates[$i]['phase_weekly_start_date'] = Carbon::parse($phase_dates[$i-1]['phase_weekly_end_date']);
                            $phase_dates[$i]['phase_weekly_end_date'] = Carbon::parse($phase_dates[$i]['phase_weekly_start_date'])->addWeeks(1);
                        }
                    }
                    foreach($phase_dates as $key => $weekly_phase) {
                        $assessment_days = [];
                        // To get the assessment details
                        $individual_assessments = PatientPhaseAssessments::with(['PatientAssessmentTemplates.SubCategoriesData' => function($query) use($assessment_type) {
                            $query->where('type', $assessment_type)->where('is_patient_assessment', 1);
                        }])->where('phases_id', $phases->id)->first();
                        
                        for($i = 0; $i <= 6; $i++) {
                            //get current date
                            $current_date = Carbon::parse($weekly_phase['phase_weekly_start_date'])->addDays($i)->format('d:m:Y');
                            $unformatted_date = Carbon::parse($weekly_phase['phase_weekly_start_date'])->addDays($i);
                            //get the week dat of current date
                            if (Carbon::parse($unformatted_date)->gte(Carbon::parse($phases->start_date)->startOfDay()) && Carbon::parse($unformatted_date)->lte(Carbon::parse($phases->end_date)->startOfDay())) 
                            {
                                // to check the assessment exist for assigned plan
                                if(!empty($individual_assessments)) {
                                    // To get the assessment-sub-categories
                                    $all_assessment_details = $individual_assessments->PatientAssessmentTemplates->SubCategoriesData;
                                    $data = [];
                                    foreach($all_assessment_details as $assess_key => $value) {
                                        if($value->is_patient_assessment == 1) {
                                            $measurementData = json_decode($value->measurements_json, true);
                                            // To check current date is exists in assessment measurement json
                                            if(!empty($measurementData) && array_key_exists(strtolower($current_date), $measurementData)) {
                                                foreach ($measurementData[$current_date] as $schedule_id => $jsonValue) {
                                                    // To find the schedule-data
                                                    $scheduleData = PatientAssessmentSchedules::find($schedule_id);
                                                    $data['first_measurement'] = $jsonValue['first_measurement'];
                                                    $data['second_measurement'] = $jsonValue['second_measurement'];
                                                    $data['difference'] = $jsonValue['difference'];
                                                    $data['created_date'] = $jsonValue['created_date'];
                                                    $data['schedule_id'] = $schedule_id;
                                                    $data['schedule_name'] = $scheduleData->name;
                                                    $data['assessment_id'] = $value->id;
                                                    $data['assessment_name'] = $value->name;
                                                    $assessment_days[$current_date][$value->id][] = $data;
                                                }
                                            } else {
                                                $assessment_days[$current_date][$value->id] = NULL;
                                            }
                                        }
                                    }
                                }
                            } else {
                                $assessment_days[$current_date] = NULL;
                            }
                        }
                        $phases_details = [];
                        $phases_details['start_date'] = $weekly_phase['phase_weekly_start_date'];
                        $phases_details['end_date'] = $weekly_phase['phase_weekly_end_date']->subDays(1);
                        $phases_details['phase_name'] = $phases->name;
                        $phases_details['assessment'] = $assessment_days;
                        $all_details[] = $phases_details;
                    }
                    $res = json_encode($all_details);  //encode the data
                    //to encrypt the response
                    $passphrase = substr(Auth::guard('api')->user()->api_token, 0, 20);
                    $encrypted_data = Helper::cryptoJsAesEncrypt($passphrase, $res);
                    $encrypted_phrase = Helper::encrypt($passphrase, trim(Auth::guard('api')->user()->PatientData()->first()->public_key));
                    $response  = [
                        'success' => true,
                        'message' => \Lang::get('lang.assessment-analytics-data-successfully-fetched'),
                        'passphrase' => base64_encode($encrypted_phrase),
                        'data' => $encrypted_data,
                    ];
                    return response()->json($response, 200);
                }
            }
        } else {  
            $err_response  = [
                'success' => false,
                'errors' => \Lang::get('lang.no-active-phase-detected')
            ];
            return response()->json($err_response, 200);
        }
    }
}
