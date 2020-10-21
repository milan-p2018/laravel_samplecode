<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use App\OrganizationPatient;
use App\PatientData;
use App\OrganizationPatientJsonData;
use App\Organization;
use App\User;
use App\Indication;
use App\TherapyPlanTemplates;
use App\Patient\PatientTherapyPlanTemplates;
use App\Patient\PatientPlanNetwork;
use App\Patient\PatientPlanRequests;
use App\Patient\PlanProcessLog;
use App\Patient\PatientPhases;
use App\Patient\PatientLimitations;
use App\Patient\PatientBodyRegions;
use App\Patient\PatientExercises;
use App\Patient\PatientCourses;
use App\Patient\PatientPhaseAssessments;
use App\Patient\PatientAssessmentTemplates;
use App\Patient\PatientAssessmentCategories;
use App\Patient\PatientAssessmentSubCategories;
use App\Patient\PatientAssessmentMeasurements;
use App\Patient\PatientPhaseExercises;
use App\Patient\PatientPhaseCourses;
use App\Patient\PatientCourseExercises;
use App\Phases;
use App\BodyRegions;
use App\PhaseExercises;
use App\PatientsMeetings;
use App\WorkerData;
use App\ScheduleCategory;
use App\Material;
use DB;
use View;
use phpseclib\Crypt\RSA;
use Carbon\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use App\Repositories\Interfaces\OrganizationInterface;
use App\Traits\Encryptable;
use App\Rules\HealthInsuranceNumber;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Mail\SetPassword;
use App\Document;
use File;
use Helper;
use App\Limitations;
use App\PlanPatientAssignmentTemplate;
use Illuminate\Encryption\Encrypter;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Password;
use Config;
use App\AssessmentTemplates;
use App\Exercises;
use App\Courses;
use App\ExerciseMaterialsLists;
use App\Patient\PatientAssessmentSchedules;


class PatientController extends Controller
{
    use Encryptable;
    public function __construct(OrganizationInterface $orgRepository)
    {
        $this->orgRepository = $orgRepository;

        //Module_id = 5 (Global Events Module)
        $this->middleware('check-permission:5__1__view', ['only' => ['getOrganizationMeeting']]);
        //Module_id = 3 (Global Documents Module)
        $this->middleware('check-permission:3__1__view', ['only' => ['allDocuments']]);
        $this->middleware('check-permission:3__1__multi-purpose-func', ['only' => ['GetAllPatients']]);

        //Module_id = 2 (Patients Module)
        //Sub_module_id = 1 (Base Data Module)
        $this->middleware('check-permission:2__1__view', ['only' => ['getPatientProfileData']]);
        $this->middleware('check-permission:2__1__multi-purpose-func', ['only' => ['index']]);

        //Module_id = 2 (Patients Module)
        //Sub_module_id = 2 (Therapy Plan Module)
        $this->middleware('check-permission:2__2__view', ['only' => ['patientTherapyPlans']]);

        //Module_id = 2 (Patients Module)
        //Sub_module_id = 3 (Document Module)
        $this->middleware('check-permission:2__3__view', ['only' => ['patientDocuments']]);

        //Module_id = 2 (Patients Module)
        //Sub_module_id = 5 (Events Module)
        $this->middleware('check-permission:2__5__view', ['only' => ['getPatientsMeeting']]);

    }

    //function to load the patient view
    public function index()
    {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        //to check the organization is verified or not
        $organization_verified_status = Organization::select('verified_at', 'owner')->find($organization_id);
        //to open the modal to create the another patient 
        $keep_model_open = false;
        if (Session::has('keep_model_open') && Session::get('keep_model_open')) {
            Session::forget('keep_model_open');
            $keep_model_open = true;
        }

        $OrganizationDataFilter = function ($q) use ($organization_id) {
            $q->where('organization_id', '=', $organization_id)->where('verified_at', '<>', null);
        };
        $doctorList = WorkerData::with(['OrganizationData' => $OrganizationDataFilter])->whereHas('OrganizationData', $OrganizationDataFilter)->whereIn('worker_data_species_id', ['lang_doc', 'lang_physio'])->orderBy('id', 'desc')->get();
        return view('patient.index', compact('organization_verified_status', 'keep_model_open', 'doctorList'));
    }

    //function to get the decrypted organization patient data 
    public function getOrganizationPatientListing(Request $request)
    {
        //get the current organization_id
        $private_key = '';
        $private_key = urldecode($request->get('private_key'));
        $organizaton_patient_final_data = $this->orgRepository->getOrganizationPatientDataListing($private_key);
        if (empty($private_key)) {
            return response()->json('failure', 400);
        }
        return response()->json($organizaton_patient_final_data);
    }

    //function to get the details of the specific patient data
    public function getPatientTherapyPlans(Request $request, $id)
    {
        //redirect user to patient page 
        return redirect()->route('patients.index');
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        //to check this user is verified by organization or not
        $organization_patient_data = OrganizationPatient::with('User')->where('user_id', decrypt($id))->where('organization_id', $organization_id)->whereNotNull('verified_at')->first();
        $emailCheckUser = User::find(decrypt($id));
        $verifiedFlag = true;
        if (!$organization_patient_data) {
            //if user is not verified then prevent to access the meeting page
            $verifiedFlag = false;
            $message = \Lang::get('lang.not-verified-user-mesg');
        }
        if (!$emailCheckUser->email_verified_at) {
            //if user is not verified then prevent to access the meeting page
            $verifiedFlag = false;
            $message = \Lang::get('lang.not-email-verified-user-mesg');
        }
        if (!$verifiedFlag) {
            $request->session()->flash('alert-danger', $message);
            return redirect()->back();
        }
        //Exercises master data
        $user = PatientData::where('user_id', decrypt($id))->first();
        $therapy_plans = Phases::with(['plan', 'limitations'])->whereHas('plan', function ($query) use ($id, $organization_id) {
            $query->where('user_id', decrypt($id));
            $query->where('organization_id', $organization_id);
        })->get();
        $indication = Indication::all();
        $phases_data = array();
        $therapy_plans_phases = array();
        $indication_id = '';
        $indication_name = '';
        $start_date = '';
        $dates = array();
        if (!empty($therapy_plans)) {
            foreach ($therapy_plans as $main_key => $data) {
                $indication_id = $data->plan->indication_id;
                $indication_name = Indication::find($indication_id)->name;
                $start_date = $data->plan->start_date;
                //get the exercises groups as per the phase
                $exercises_groups = PhaseExerciseGroups::where('phase_id', $data->id)->with(['ExerciseGroupList', 'PhaseExercises.PhaseExerciseList'])->get();
                //Set the start/end date for every phases
                if ($main_key == 0) {
                    $dates['start_date'][$main_key] = Carbon::parse($data->plan->start_date);
                    $dates['end_date'][$main_key] = Carbon::parse($data->plan->start_date)->addWeeks($data->duration)->subDay(1);
                } else {
                    $dates['start_date'][$main_key] = Carbon::parse($dates['end_date'][$main_key - 1])->addDay();
                    $dates['end_date'][$main_key] = Carbon::parse($dates['start_date'][$main_key])->addWeeks($data->duration)->subDay(1);
                }
                $data->start_date = $dates['start_date'][$main_key];
                $data->end_date = $dates['end_date'][$main_key];
                $data->exercises_groups = $exercises_groups;
                $therapy_plans_phases[] = $data;
            }
        }
        //Pass this data from template to tab-layout
        $common_data = array(
            'user_name' => ucfirst($user->firstname) . ' ' . ucfirst($user->lastname),
            'therapy_plan_link' => route('patients.therapy-plans', $id),
            'base_data_link' => route('patients.base-data', $id),
            'event_data_link' => route('patients.meetings', $id),
            'documents_link' => route('patients.documents', $id)
        );
        View::share('common_data', $common_data);
        return view('patient.therapy_plans', compact('therapy_plans_phases', 'user', 'indication', 'id', 'start_date', 'indication_id', 'indication_name'));
    }

    //function to save the therapy plan
    public function saveTherapyPlan(Request $request)
    {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        if (Plan::where('user_id', decrypt($request->id))->where('organization_id', $organization_id)->exists()) {
            $plans = Plan::where('user_id', decrypt($request->id))->where('organization_id', $organization_id)->first();
        } else {
            $plans = new Plan;
        }
        parse_str($request->data, $new_phases); //get data from serialized form 
        $validator = Validator::make($new_phases, [
            'indication' => 'required',
            'start_date' => 'required',
            '*.*.name' => 'required',
            // '*.*.description' => 'required',
            '*.*.duration'    => 'required',
        ]);
        if ($validator->fails()) {
            $err_response  = [
                'success' => false,
                'errors' => $validator->messages()
            ];
            return response()->json($err_response, 400);
        }
        $phases_ids_array = array();
        $existing_phases_id = array();
        //to check atleast one phase is addded or not
        if (array_key_exists('phase', $new_phases)) {
            //to format the date
            $res = explode("/", $new_phases['start_date']);
            $changedDate = $res[1] . "-" . $res[0] . "-" . $res[2];
            $plans->start_date = Carbon::createFromFormat('d-m-Y', $changedDate);
            $plans->week = 0;
            $plans->indication_id = $new_phases['indication'];
            $plans->user_id = decrypt($request->id);
            $plans->organization_id = $organization_id;
            $plans->save();
            foreach ($new_phases['phase'] as $data) {
                //for update the existing phases
                if (array_key_exists('exists_id', $data)) {
                    $update_phase = Phases::find($data["exists_id"]);
                } else {
                    //for insert the new phases
                    $update_phase = new Phases;
                }
                $update_phase->name = $data["name"];
                // $update_phase->description = $data["description"];
                $update_phase->duration = $data["duration"];
                $update_phase->plan_id = $plans->id;
                $update_phase->save();
                $existing_phases_id[] = $update_phase->id;
            }
        }
        //get existing phase details
        $existing_phases = Phases::where('plan_id', $plans->id)->select('id')->get();
        if (!empty($existing_phases)) {
            //created the array of existing exercise-groups-id
            foreach ($existing_phases as $phase_data) {
                $phases_ids_array[] = $phase_data->id;
            }
        }
        //get the deleted phases
        $deleted_phases = array_diff($phases_ids_array, $existing_phases_id);
        foreach ($deleted_phases as $id) {
            //to remove the phases
            $delete_phases = Phases::destroy($id);
        }
        $request->session()->flash('alert-success', 'Plans are successfully saved');
        return response()->json(200);
    }

    //function to save the phase details
    public function savePhaseDetails(Request $request, $id)
    {
        $phase = Phases::find($id);
        $rules = [
            'phase_name' => ['required'],
            '*.*.name' => 'required',
            '*.*.start_week' => 'required',
            '*.*.start_day' => 'required',
            '*.*.end_week' => 'required',
            '*.*.end_day' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $err_response  = [
                'success' => false,
                'errors' => \Lang::get('lang.fill-all-the-details-msg')
            ];
            return response()->json($err_response, 400);
        }
        if (!empty($phase)) {
            $phase->name = $request->phase_name;
            $phase->phase_objectives = isset($request->phase_objectives) && !empty($request->phase_objectives) ? $request->phase_objectives : '';
            $phase->practical_exercises = isset($request->practical_exercises) && !empty($request->practical_exercises) ? $request->practical_exercises : '';
            $phase->duration = $request->week_duration;
            $phase->save();
        }
        $limitations_ids_array = array();
        $existing_limitations_id = array();
        //to validate the start and end data subsquent
        foreach ($request->limitation as $value) {
            $start_value = (int) ($value["start_week"] . $value["start_day"]);
            $end_value = (int) ($value["end_week"] . $value["end_day"]);
            if ($start_value > $end_value) {
                $err_response  = [
                    'success' => false,
                    'errors' => \Lang::get('lang.start-end-week-error-message')
                ];
                return response()->json($err_response, 400);
            }
        }
        if (!empty($request->limitation)) {
            foreach ($request->limitation as $key => $limitation) {
                if (array_key_exists('existing_id', $limitation)) {
                    // $existing_limitations_id[] = $limitation['existing_id'];
                    $limitation_data = Limitations::find($limitation["existing_id"]);
                } else {
                    $limitation_data = new Limitations;
                }
                $limitation_data->name = $limitation["name"];
                $limitation_data->phase_id = $id;
                $limitation_data->start_week = $limitation["start_week"];
                $limitation_data->start_day = $limitation["start_day"];
                $limitation_data->end_week = $limitation["end_week"];
                $limitation_data->end_day = $limitation["end_day"];
                $limitation_data->save();
                $existing_limitations_id[] = $limitation_data->id;
            }
        }
        $existing_limitations = Limitations::where('phase_id', $id)->select('id')->get();
        if (!empty($existing_limitations)) {
            //created the array of existing exercise-groups-id
            foreach ($existing_limitations as $limitations) {
                $limitations_ids_array[] = $limitations->id;
            }
        }
        //get the deleted exercises
        $deleted_limitations = array_diff($limitations_ids_array, $existing_limitations_id);
        foreach ($deleted_limitations as $id) {
            //to remove the exercise groups from the database
            $delete_limitations = Limitations::destroy($id);
        }
        $message = \Lang::get('lang.phase-limitation-successfully-updates');
        $request->session()->flash('alert-success', $message);
        return response()->json(['success' => \Lang::get('lang.phase-limitation-successfully-updates'), 'status' => 200], 200);
    }

    // function to view patient profile
    public function getPatientProfileData(Request $request, $id)
    {
        $getShowData = $request->get('getShowData');
        $private_key = urldecode($request->get('private_key'));
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        $patientData = [];
        if ($getShowData) {
            $organizaton_patient_final_data = $this->orgRepository->getOrganizationPatientDataListing($private_key);
            if ($organizaton_patient_final_data['data'] && count($organizaton_patient_final_data['data'])) {
                // get patient profile by user 
                $patientData = array_filter($organizaton_patient_final_data['data'], function ($var) use ($id) {
                    return ($var['user']->id == decrypt($id));
                });
                if (count($patientData)) {
                    $patientData =  call_user_func_array('array_merge', $patientData);
                    $OrganizationDataFilter = function ($q) use ($organization_id) {
                        $q->where('organization_id', '=', $organization_id)->where('verified_at', '<>', null);
                    };
                    $doctorList = WorkerData::with(['OrganizationData' => $OrganizationDataFilter])->whereHas('OrganizationData', $OrganizationDataFilter)->whereIn('worker_data_species_id', ['lang_doc', 'lang_physio'])->orderBy('id', 'desc')->get();
                    $doc = json_decode(json_encode($doctorList), true);
                    $assign_doc_name = '';
                    if (!empty($patientData['assign_doctor'])) {
                        $key = array_search($patientData['assign_doctor'], array_column($doc, 'id'));
                        if ($key !== -1) {
                            $assign_doc_name = isset(Config::get('globalConstants.user_title')[$doctorList[$key]->title]) ? Config::get('globalConstants.user_title')[$doctorList[$key]->title] . " " . $doctorList[$key]->firstname . " " . $doctorList[$key]->lastname : '' . " " . $doctorList[$key]->firstname . " " . $doctorList[$key]->lastname;
                        }
                    }
                    $returnHTML =  view('patient.patient_profile', compact('patientData', 'doctorList', 'assign_doc_name', 'id'))->render();
                    return response()->json(array('new_patient_name' => (isset(Config::get('globalConstants.user_title')[$patientData['title']]) ? Config::get('globalConstants.user_title')[$patientData['title']] : '') . " " . $patientData['name'] . " " . $patientData['lastname'], 'html' => $returnHTML));
                } else {
                    response()->json('error', 200);
                }
            }
        } else {
            $user = PatientData::where('user_id', decrypt($id))->first();
            $common_data = array(
                'user_name' => ucfirst($user->firstname) . ' ' . ucfirst($user->lastname),
                'therapy_plan_link' => route('patients.therapy-plans', $id),
                'base_data_link' => route('patients.base-data', $id),
                'event_data_link' => route('patients.meetings', $id),
                'documents_link' => route('patients.documents', $id)
            );
            View::share('common_data', $common_data);

            return view('patient.patient_details', ['patientData' => $patientData, 'getShowData' => $getShowData, 'common_data' => $common_data, 'id' => $id, 'user' => $user]);
        }
    }

    // verify patient profile
    public function editPatientProfile(Request $request, $id)
    {

        try {
            $profileData = $request->get('profileData');
            $verifyPatient = $request->get('verifyPatient');

            if (!$verifyPatient) {
                // $after = Carbon::now()->subYears(99)->format('Y-m-d');
                $before = Carbon::now()->format('Y-m-d');
                $status = 200;
                $rules = [
                    'name' => 'required|min:2|max:100|regex:/^[A-Za-z0-9-_.öäüÖÄÜß\s]{2,100}$/',
                    'street' => ['required', 'regex:/^[A-Za-z_.öäüÖÄÜß\s]{2,200}$/'],
                    'phone' => 'required|min:4|regex:/^[0-9-()\/+\s]{4,20}$/',
                    'streetnumber' => 'required|max:10|regex:/^[A-Za-z0-9-\/\s]{1,10}$/',
                    'postcode' => 'required|max:5|regex:/^[0-9]*$/',
                    'place' => 'required|min:2|max:100|regex:/^[A-Za-z0-9_.öäüÖÄÜß\s]{2,100}$/',
                    'lastname' => 'required|min:2|max:100|regex:/^[A-Za-z0-9-_.öäüÖÄÜß\s]{2,100}$/',
                    'bday' => 'required|date|before_or_equal:' . $before,
                    'gender' => 'required',
                    // 'title' => 'required',
                    'fax' => 'nullable|regex:/^[0-9-()\/+\s]{4,20}$/',
                    'mobile' => 'nullable|regex:/^[0-9-()\/+\s]{4,20}$/',
                    // 'salutation' => 'required',
                    'insurance_number' => 'required',
                    'insurance_type' => 'required',
                    'health_insurance_no' => ['required'],
                    'health_insurance' => 'required',
                ];

                if (isset($profileData['insurance_type']) && $profileData['insurance_type'] == 2) {
                    $rules['health_insurance_no'] = ['required', new HealthInsuranceNumber];
                }

                // apply only changed fields rules
                $selection = array_keys($profileData);
                $filtered = array_intersect_key($rules, array_flip($selection));

                $validator = Validator::make($profileData, $filtered, [
                    'insurance_number.required' => \Lang::get('lang.insurance_number_required'),
                    'health_insurance_no.required' => \Lang::get('lang.health_insurance_no_required'),
                    'place.required' => \Lang::get('lang.place-required'),
                    'phone.required' => \Lang::get('lang.phone-required'),
                    'health_insurance.required' => \Lang::get('lang.health_insurance_name_required')
                ]);
                if ($validator->fails()) {
                    $err_response  = [
                        'success' => false,
                        'errors' => $validator->messages()
                    ];
                    return response()->json($err_response, 200);
                }
            }

            $private_key = urldecode($request->get('private_key'));
            $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
            if ($organization_id) {
                $organization_data = Organization::where('id', $organization_id)->first();

                if ($verifyPatient) {
                    // verify patient
                    $profileDataEncrypt = ['verified_at' => Carbon::now(), 'assign_doctor' => $request->selectedDocId];
                    $profileData = $profileDataEncrypt;
                } else {
                    // encrypt patient updated data
                    $profileDataEncrypt = [];

                    $public_key = base64_decode($organization_data->publicKey);
                    foreach ($profileData as $key => $profile) {
                        $data = ($profile == null) ? '' : $profile;
                        if ($key != 'assign_doctor') {
                            $dataVal = $this->encrypt($data, $public_key);
                        } else {
                            $dataVal = $data;
                        }
                        $profileDataEncrypt[$key] = $dataVal;
                    }
                }


                if (Auth::user()->WorkerSettings()->exists() && !empty($private_key)) {

                    // update encrypted patient data in OrganizationPatient
                    OrganizationPatient::where('user_id', $id)->where('organization_id', $organization_id)->update($profileDataEncrypt);

                    // update perticular field data in json
                    if (Auth::user()->WorkerSettings()->exists() && !empty($private_key)) {
                        $organization_id = Auth::user()->WorkerSettings()->first()->organization_id;
                        $organization_patient_raw_data = OrganizationPatientJsonData::with(['Organization'])
                            ->where('organization_id', $organization_id)->first();
                        if (!empty($organization_patient_raw_data)) {
                            $organization_patient_data = array();
                            $organizations_patients = new OrganizationPatient;
                            $organization_patient_data = unserialize($organizations_patients->decrypt($organization_patient_raw_data->json, $organization_patient_raw_data->organization->pdf_password, $private_key));

                            // update selected patient data only
                            array_walk($organization_patient_data, function (&$data, $key) use ($id, $profileData) {
                                if ($key == $id) {
                                    $data = array_replace($data, $profileData);
                                }
                            });

                            // update OrganizationPatientJsonData with new patient data
                            $organization_json_data = $this->encryptObject($organization_data, serialize($organization_patient_data));
                            OrganizationPatientJsonData::where('organization_id', $organization_id)->update(['json' => $organization_json_data]);
                        }

                        if ($verifyPatient) {
                            return response()->json(['success' => \Lang::get('lang.patient-verified-successfully'), 'status' => 200], 200);
                        } else {
                            return response()->json(['success' => \Lang::get('lang.profile successfully updated text'), 'status' => 200], 200);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            return response()->json(['error' => \Lang::get('lang.general-error-message'), 'status' => 400], 400);
        }
    }

    //function to get the exercise groups according to selected phase
    public function getExerciseGroups(Request $request)
    {
        $phase_id = $request->phase_id;
        if (!empty($phase_id)) {
            $raw_exercises = ExerciseGroupsList::orderBy('group_name')->get();
            //grouped the exercises as per the alphabets
            $grouped_exercises = $raw_exercises->groupBy(function ($item, $key) {
                return substr($item->group_name, 0, 1);
            });
            //get the exercises groups as per the selected phase
            $exercises_groups = PhaseExerciseGroups::where('phase_id', $phase_id)->with('ExerciseGroupList')->get();
            $selected_exercises = array();
            $selected_exe_ids_array = array();
            foreach ($exercises_groups as $exercises) {
                $selected_exercises[] = $exercises;
                $selected_exe_ids_array[] = $exercises->ExerciseGroupList->id; //set the selected exercises-id
            }
            $exercises_data['selected'] = $selected_exercises;
            $exercises_data['all'] = $grouped_exercises;
            $exercises_data['selected_exe_array'] = $selected_exe_ids_array;
            return response()->json($exercises_data, 200);
        } else {
            $message = \Lang::get('lang.no-active-phase-found');
            $request->session()->flash('alert-danger', $message);
            return response()->json(['error' => 'Some error occure. Please try again.', 'status' => 400], 400);
        }
    }

    //function to save the exercise groups for specific phase
    public function saveExerciseGroups(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'active_phase' => 'required',
        ]);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        $exercise_groups_ids_arr = array();
        $existing_phase_exercise_groups = PhaseExerciseGroups::where('phase_id', $request->active_phase)->get();
        $existing_groups_id = array();
        if (!empty($existing_phase_exercise_groups)) {
            //created the array of existing exercise-groups-id
            foreach ($existing_phase_exercise_groups as $exercise_groups) {
                $exercise_groups_ids_arr[] = $exercise_groups->exercise_group_list_id;
            }
        }
        //check the exercises are empty or not
        if (!empty($request->exercise)) {
            //to get all the exercise groups details
            foreach ($request->exercise as $key => $value) {
                //if id exist in database then update the exercise group
                $existing_groups_id[] = $value['id'];
                if (in_array($value['id'], $exercise_groups_ids_arr)) {
                    $phase_exercise_groups = PhaseExerciseGroups::where('phase_id', $request->active_phase)->where('exercise_group_list_id', $value['id'])->get();
                    $phase_exercise_groups[0]->estimated_course_time = $value['estimated_course_time'];
                    $phase_exercise_groups[0]->per_week = $value['per_week'];
                    $phase_exercise_groups[0]->updated_at = date('Y-m-d H:i:s');
                    $phase_exercise_groups[0]->save();
                } else {
                    //create the new exercise group
                    $phase_exercise_groups = new PhaseExerciseGroups;
                    $phase_exercise_groups->phase_id = $request->active_phase;
                    $phase_exercise_groups->exercise_group_list_id = $value['id'];
                    $phase_exercise_groups->estimated_course_time = $value['estimated_course_time'];
                    $phase_exercise_groups->per_week = $value['per_week'];
                    $phase_exercise_groups->created_at = date('Y-m-d H:i:s');
                    $phase_exercise_groups->updated_at = date('Y-m-d H:i:s');
                    $phase_exercise_groups->save();
                }
            }
        } else {
            //if exercises are empty then remove all the exercises from database
            PhaseExerciseGroups::where('phase_id', $request->active_phase)->delete();
        }
        //get the deleted exercises
        $deleted_exercises = array_diff($exercise_groups_ids_arr, $existing_groups_id);
        foreach ($deleted_exercises as $exe) {
            //to remove the exercise groups from the database
            $delete_exercise = PhaseExerciseGroups::where('phase_id', $request->active_phase)->where('exercise_group_list_id', $exe)->delete();
        }
        $message = \Lang::get('lang.exercise-groups-created-successfully-msg');
        $request->session()->flash('alert-success', $message);
        return redirect()->back();
    }

    //function to get the exercises for the specific exercise groups
    public function getExercises(Request $request)
    {
        // dd($request);
        $exercise_groups_id = $request->exercise_group_id;

        $exercises = PhaseExerciseGroups::with(['PhaseExerciseList' => function ($query) {
            $query->orderBy('name');
        }])->find($exercise_groups_id);
        $grouped_exercises = $exercises->PhaseExerciseList->groupBy(function ($item, $key) {
            return substr($item->name, 0, 1);
        });
        $selected_exercises = PhaseExercises::with(['PhaseExerciseList', 'PhaseExerciseGroup'])->where('phase_exercise_group_id', $exercise_groups_id)->get();
        $exercises_data['selected'] = $selected_exercises;
        $exercises_data['all_data'] = $exercises;
        $exercises_data['all_exercises'] = $grouped_exercises;
        // $exercises_data['selected_exe_array'] = $selected_exe_ids_array;
        return response()->json($exercises_data, 200);
    }

    //function to save the exercise to phase exercise list table
    public function savePhaseExercise(Request $request)
    {
        //check validation
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'description' => 'required',
            'type' => 'required',
            'duration' => 'required',
            'material'    => 'required',
            'exerciseImage' => 'required|image'
        ]);
        if ($validator->fails()) {
            $err_response  = [
                'success' => false,
                'errors' => $validator->messages()
            ];
            return response()->json($err_response, 400);
        }
        //save the exercise to phase-exercise-list table
        $phase_exercises_list = new  PhaseExerciseList;
        $phase_exercises_list->phase_exercise_group_id = $request->exercise_group_id;
        $phase_exercises_list->name = $request->name;
        $phase_exercises_list->description = $request->description;
        $phase_exercises_list->material = $request->material;
        $phase_exercises_list->type = $request->type;
        $phase_exercises_list->duration = $request->duration;
        if ($request->has('exerciseImage') && $request->exerciseImage != '' && $request->exerciseImage != NULL) {
            // Get image file
            $image = $request->file('exerciseImage');
            // Make a image name based on user name and current timestamp
            $name = $request->name . '_' . time();
            // Define folder path
            $folder = '//phase-exercises/' . $request->exercise_group_id . '/';
            // Make a file path where image will be stored [ folder path + file name + file extension]
            $filePath = $folder . $name . '.' . $image->getClientOriginalExtension();
            // Upload image
            $this->uploadOne($image, $folder, 'public', $name);
            // Set user profile image path in database to filePath
            $phase_exercises_list->image = $filePath;
        }
        $phase_exercises_list->save();
        $request->session()->flash('alert-success', \Lang::get('lang.exercises-successfully-added-msg'));
        return response()->json(200);
    }

    //function to save the exercises in the phase-exercises table
    public function saveExercises(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exercise_group_id' => 'required',
        ]);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        $updated_exercises_id = array();
        //get existing exercises id
        $existing_phase_exercises = PhaseExercises::where('phase_exercise_group_id', $request->exercise_group_id)->get();
        $existing_exercises_id = array();
        if (!empty($existing_phase_exercises)) {
            //created the array of existing exercises-id
            foreach ($existing_phase_exercises as $exercises) {
                $existing_exercises_id[] = $exercises->id;
            }
        }
        if (!empty($request->exercise)) {
            //save the exercises and pauses in the phase exercises table
            foreach ($request->exercise as $exercise) {
                if (array_key_exists("exist_id", $exercise)) {
                    //exist_id key exist then update the value
                    $updated_exercises_id[] = $exercise['exist_id'];
                    $phase_exercises = PhaseExercises::find($exercise['exist_id']);
                } else {
                    //add the value
                    $phase_exercises = new PhaseExercises;
                }
                $phase_exercises->phase_exercise_group_id = $request->exercise_group_id;
                if (array_key_exists('id', $exercise)) {
                    //save the exercises 
                    $phase_exercises->phase_exercise_list_id = $exercise['id'];
                    $phase_exercises->type = $exercise['type'];
                    $phase_exercises->duration = $exercise['duration'];
                } else {
                    //save the pause 
                    $phase_exercises->duration = $exercise['pause_time'];
                }
                $phase_exercises->save();
            }
            // //get the deleted exercises
            if (!empty($updated_exercises_id)) {
                $deleted_exercises = array_diff($existing_exercises_id, $updated_exercises_id);
                foreach ($deleted_exercises as $exercise_to_delete) {
                    //to remove the exercise groups from the database
                    $delete_exercise = PhaseExercises::where('id', $exercise_to_delete)->delete();
                }
            }
        } else {
            //remove all the exercise og phases groups
            PhaseExercises::where('phase_exercise_group_id', $request->exercise_group_id)->delete();
        }
        //update the estimated-course-time, per-week and round for phase exercise groups
        $phase_exercise_groups = PhaseExerciseGroups::find($request->exercise_group_id);
        $phase_exercise_groups->estimated_course_time = $request->estimated_course_time;
        $phase_exercise_groups->round = $request->round;
        $phase_exercise_groups->per_week = $request->per_week;
        $phase_exercise_groups->save();
        $message = \Lang::get('lang.exercises-successfully-added-msg');
        $request->session()->flash('alert-success', $message);
        return redirect()->back();
    }

    //function to create the new patient
    public function createPatient(Request $request)
    {
        //current organization id 
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        if (empty($organization_id)) {
            $message = \Lang::get('lang.organization-not-selected-msg');
            $request->session()->flash('alert-danger', $message);
            return response()->json(['success' => $message, 'status' => 200], 200);
        }

        //birthdate validation for age between 18-99
        $after = Carbon::now()->subYears(99)->format('Y-m-d');
        $before = Carbon::now()->format('Y-m-d');

        //validation rules
        $rules = [
            'firstname' => 'required|min:2|max:100|regex:/^[A-Za-z0-9-_.öäüÖÄÜß\s]{2,100}$/',
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users', 'regex:/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/'],
            'street' => ['required', 'regex:/^[A-Za-z_.öäüÖÄÜß\s]{2,200}$/'],
            'phone' => 'required|min:4|regex:/^[0-9-()\/+\s]{4,20}$/',
            'streetnumber' => 'required|max:10|regex:/^[A-Za-z0-9-\/\s]{1,10}$/',
            'postcode' => 'required|max:5|regex:/^[0-9]*$/',
            'place' => 'required|min:2|max:100|regex:/^[A-Za-z0-9_.öäüÖÄÜß\s]{2,100}$/',
            'lastname' => 'required|min:2|max:100|regex:/^[A-Za-z0-9-_.öäüÖÄÜß\s]{2,100}$/',
            'birthdate' => 'required|date|before_or_equal:' . $before,
            'gender' => 'required',
            'title' => 'nullable',
            'mobile' => 'nullable|regex:/^[0-9-()\/+\s]{4,20}$/',
            'fax' => 'nullable|regex:/^[0-9-()\/+\s]{4,20}$/',
            'salutation' => 'nullable',
            'insurance_type' => 'required',
            'health_insurance' => 'required',
        ];

        if ($request->has('insurance_type') && ($request->insurance_type == 2)) {
            $rules['health_insurance_no'] = ['required', new HealthInsuranceNumber];
            $rules['insurance_number'] = ['required'];
        }
        
        $validator = Validator::make($request->all(), $rules, [
            'insurance_number.required' => \Lang::get('lang.insurance_number_required'),
            'health_insurance_no.required' => \Lang::get('lang.health_insurance_no_required'),
            'place.required' => \Lang::get('lang.place-required'),
            'phone.required' => \Lang::get('lang.phone-required'),
            'health_insurance.required' => \Lang::get('lang.health_insurance_name_required')
        ]);
        if ($validator->fails()) {
            $err_response  = [
                'success' => false,
                'errors' => $validator->messages()
            ];
            return response()->json($err_response, 200);
        }
        //create new patient in the user table
        $user = new User;
        $user->type = 1;
        $user->api_token = hash('sha256', Str::random(60));
        $user->expired_at = Carbon::now()->addDays(14)->toDateTimeString();
        $user->email = $request->email;
        $user->password = '';
        $user->secret = Hash::make(Str::random(60));
        $user->save();

        //to send the mail for set the password
        event(new \Illuminate\Auth\Events\Registered($user));  //for e-mail verification mail


        //save the patient data
        $patient_data = new PatientData();
        $patient_data->user_id = $user->id;
        $patient_data->firstname = $request->firstname;
        $patient_data->lastname = $request->lastname;
        $patient_data->street = $request->street;
        $patient_data->streetnumber = $request->streetnumber;
        $patient_data->bday = $request->birthdate;
        $patient_data->gender = $request->gender;
        $patient_data->postcode = $request->postcode;
        $patient_data->place = $request->place;
        $patient_data->phone = $request->phone;
        $patient_data->mobile = $request->mobile;
        $patient_data->fax = $request->has('fax') ? $request->fax : NULL;
        $patient_data->title = $request->has('title') ? $request->title : NULL;
        $patient_data->salutation = $request->has('salutation') ? $request->salutation : NULL;
        $patient_data->public_key = '';
        $patient_data->insurance_number = $request->has('insurance_number') ? $request->insurance_number : NULL;
        $patient_data->insurance_type = $request->insurance_type;
        $patient_data->health_insurance_no = $request->has('health_insurance_no') ? $request->health_insurance_no : NULL;
        $patient_data->health_insurance = $request->health_insurance;
        $patient_data->note = $request->note;
        $patient_data->save();
        $organization_data = Organization::find($organization_id);

        //to join the patient to current orgnaization
        if (!empty($organization_data)) {
            $assign_doc = !empty($request->patient_doctor) ? $request->patient_doctor : null;
            $this->orgRepository->joinOrganization($organization_data->org_unique_id, null, $patient_data->user_id, $assign_doc, true);
        }

        if ($request->addNewPatient) {
            Session(['keep_model_open' => true]);
        }
        $message = \Lang::get('lang.patient-created-successfully-msg');
        $request->session()->flash('alert-success', $message);
        return response()->json(['success' => $message, 'status' => 200], 200);
    }

    //function to show the set password form to user
    public function showSetPassword(Request $request, $id)
    {
        return view('auth.passwords.set-password-form', ['token' => $id]);
    }

    //function to save the password of newly created patient
    public function savePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => ['required', 'string', 'min:8', 'confirmed', 'regex:/^(?=.*?[A-Z])(?=(.*[a-z]){1,})(?=(.*[\d]){1,})(?=(.*[\W]){1,})(?!.*\s).{8,}$/'],
        ], [
            'password.regex' => 'Password must contain uppercase, lowercase, digit and special characters',
        ]);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        $user = User::where('email', decrypt($request->token))->first();
        $user->password = Hash::make($request->password);
        $user->save();
        $message = \Lang::get('lang.password-successfully-saved-msg');
        $request->session()->flash('class', 'success');
        return back()->with('status', $message);
    }

    //function to get the selected patient documents
    public function patientDocuments(Request $request, $id)
    {
        $length = env("RECORDS_PER_PAGE") ? env("RECORDS_PER_PAGE") : 25;
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        //to check this user is verified by organization or not
        $organization_patient_data = OrganizationPatient::with('User')->where('user_id', decrypt($id))->where('organization_id', $organization_id)->whereNotNull('verified_at')->first();
        $emailCheckUser = User::find(decrypt($id));
        $verifiedFlag = true;
        if (!$organization_patient_data) {
            //if user is not verified then prevent to access the meeting page
            $verifiedFlag = false;
            $message = \Lang::get('lang.not-verified-user-mesg');
        }
        if (!$emailCheckUser->email_verified_at) {
            //if user is not verified then prevent to access the meeting page
            $verifiedFlag = false;
            $message = \Lang::get('lang.not-email-verified-user-mesg');
        }
        if (!$verifiedFlag) {
            $request->session()->flash('alert-danger', $message);
            return redirect()->back();
        }
        $user = PatientData::where('user_id', decrypt($id))->first();
        //Pass this data from template to tab-layout
        $common_data = array(
            'user_name' => ucfirst($user->firstname) . ' ' . ucfirst($user->lastname),
            'therapy_plan_link' => route('patients.therapy-plans', $id),
            'base_data_link' => route('patients.base-data', $id),
            'event_data_link' => route('patients.meetings', $id),
            'documents_link' => route('patients.documents', $id),
        );
        View::share('common_data', $common_data);
        return view('patient.documents', compact('id', 'user', 'length'));
    }

    //function to get the documents of specific patient
    public function getPatientDocuments(Request $request, $id)
    {
        $start = $request->has('start') ?  $request->start : 0;
        $length = env("RECORDS_PER_PAGE") ? env("RECORDS_PER_PAGE") : 25;
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        $documents = Document::with(['uploader'])->where('user_id', decrypt($id))->whereRaw("find_in_set('".$organization_id."',organization_id)")->orderBy('updated_at', 'DESC')->skip($start)->take($length)->get();
        $document_count = Document::where('user_id', decrypt($id))->where('organization_id', $organization_id)->orderBy('updated_at', 'DESC')->count();
        // to get all the documents
        //check file exist in storage or not
        foreach ($documents as $key => $object) {
            if ($object->fileUrl && $object->document_details_for_org) {    
                $orgDetils = json_decode($object->document_details_for_org, true);  
                if(isset($orgDetils[$organization_id])) {   
                    $object->filename = $orgDetils[$organization_id]['filename'];   
                } else {    
                    unset($documents[$key]);    
                }   
            } else {    
                unset($documents[$key]);    
            }
        }
        // $documents = Document::where('user_id', decrypt($id))->where('organization_id', $organization_id)->get();
        if (app('router')->getRoutes()->match(app('request')->create(url()->previous()))->getName() == 'documents') {
            $patient_data = PatientData::where('user_id', decrypt($id))->first();
            $request->session()->put('patient_id', $id);
            $request->session()->put('patient_decr_id', decrypt($id));
            $request->session()->put('patient_name', ucfirst($patient_data->firstname) . ' ' . ucfirst($patient_data->lastname));
        }
        return response()->json(['data' => $documents, 'count' => $document_count, 'existDocCount' => count($documents)]);
    }

    //function to upload the patient documents into storage folder
    public function uploadDocuments(Request $request, $id)
    {

        if ($request->has('file')) {
            // Get image file
            $file = $request->file('file');
            $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
            $organization = Organization::find($organization_id);
            $user_id = decrypt($id);
            $user_data = User::find($user_id);
            // foreach ($files as $key => $file) {
            $name = $file->getClientOriginalName();
            $checkFileName = pathinfo($name)['filename'];
            $extension = $file->getClientOriginalExtension();
            //to get the file content
            $file_content = file_get_contents($file);
            //generate the key 
            $key = decrypt($id) . '_' . substr(base64_encode($user_data->email), 0, (31 - strlen(decrypt($id))));
            $encrypted_text = Helper::cryptoJsAesEncrypt($key, base64_encode($file_content));
            $folder = '/users/' . $user_id . '/organizations/' . $organization_id . '/';
            $filePath = $folder . $name;
            //save the documents into folder
            $counter = 0;
            while (Storage::disk('public')->exists($folder . $checkFileName . '.txt')) {
                $checkFileName = pathinfo($name)['filename'] . '_' . ($counter + 1);
                $counter++;
            }
            if ($counter) {
                $name = $checkFileName . '.' . $extension;
                $filePath = $folder . $name;
            }
            Storage::disk('public')->put($folder . pathinfo($name)['filename'] . '.txt', $encrypted_text);
            $data = [
                'success' => true,
                'file_name' => $name,
                'size' => $file->getSize(),
                'file_url' => $filePath,
            ];
            $response[] = $data;
            // }
            return response()->json($response, 200);
        }
        return response()->json(['error' => \Lang::get('lang.upload-documents-error-message'), 'status' => 400], 400);
    }

    //function to remove the documents from the folder
    public function removeDocuments(Request $request, $id)
    {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        if ($request->has('filename')) {
            $file = $request->filename;
            $file_name = substr($file, 0, strripos($file, '.', 0));
            $user_id = decrypt($id);
            //remove the documents from storage
            $folder = 'public/users/' . $user_id . '/organizations/' . $organization_id . '/' . $file_name . '.txt';
            Storage::delete($folder);
            return response()->json(['success' => 'Document successfully removed', 'status' => 200], 200);
        } else {
            $documents_ids = $request->document_id;
            if (!empty($documents_ids)) {
                foreach ($documents_ids as $document_id) {
                    $document = Document::find($document_id);
                    $file_name = substr($document->filename, 0, strripos($document->filename, '.', 0));
                    //set the folder path for file
                    $folder = '/users/' . $document->user_id . '/organizations/' . $organization_id . '/' . $file_name . '.txt';
                    if (!empty($document->uploaded_by)) {
                        Storage::disk('public')->delete($folder);
                        Document::destroy($document_id);
                    } else {
                        $orgDetils = json_decode($document->document_details_for_org, true);
                        if(isset($orgDetils[$organization_id])) {
                            unset($orgDetils[$organization_id]);
                        }
                        $orgs = explode(",",$document->organization_id);
                        $abc = array_diff( $orgs, [$organization_id] );
                        $document->document_details_for_org = json_encode($orgDetils);
                        $document->organization_id = count($abc) ? implode(',',$abc) : NULL;
                        $document->save();
                    }
                }
                return response()->json(['success' => 'Document successfully removed', 'status' => 200], 200);
            }
        }
        return response()->json(['error' => \Lang::get('lang.upload-documents-error-message'), 'status' => 400], 400);
    }

    //function to save the documents to database
    public function saveDocuments(Request $request, $id)
    {
        try {
            $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
            $organization = Organization::find($organization_id);
            $user_id = decrypt($id);
            $user_data = User::find($user_id);
            $rules = [
                'documents' => 'required',
            ];
            if ($request->has('documents') && !empty($request->documents)) {
                $rules['*.*.type'] = 'required';
            }
            $validator = Validator::make($request->all(), $rules, [
                '*.*.type.required' => \Lang::get('lang.select-category-error-message'),
                'documents.required' => \Lang::get('lang.upload-documents-error-message')
            ]);
            if ($validator->fails()) {
                $err_response  = [
                    'success' => false,
                    'errors' => $validator->messages()
                ];
                return response()->json($err_response, 200);
            }
            $key_user = decrypt($id) . '_' . substr(base64_encode($user_data->email), 0, (31 - strlen(decrypt($id))));
            $public_key = base64_decode($organization->publicKey);
            $encrypted_key = (base64_encode($this->encrypt($key_user, $public_key)));

            //to save the documents in database
            foreach ($request->documents as $key => $file) {
                $data[$organization_id]['key'] = $encrypted_key;
                $data[$organization_id]['filename'] = $key;
                $document = new Document;
                $document->user_id = $user_id;
                $document->filename = $key;
                $document->organization_id = $organization_id;
                $document->uploaded_by = Auth::user()->id;
                $document->size = $file['size'];
                $document->type = $file['type'];
                $document->data = '';
                $document->seen = '';
                $document->url = $file['url'];
                $document->document_details_for_org = json_encode($data);
                $document->save();
            }
            $message = \Lang::get('lang.document-saved-message');
            $request->session()->flash('alert-success', $message);
            return response()->json(['success' => \Lang::get('lang.document-saved-message'), 'status' => 200], 200);
        } catch (Exception $e) {
            return response()->json(['error' => \Lang::get('lang.general-error-message'), 'status' => 400], 400);
        }
    }

    //function to open the documents
    public function openDocuments(Request $request, $document_id, $download_flag = null, $is_preview = null)
    {
        $showtabs = true;
        //to hide the patient tabs
        $document = Document::find(decrypt($document_id));  //get the documents details
        $backUrl = url('/patients/documents/' . encrypt($document->user_id));
        if (\Request::is('documents/show/*')) {
            $showtabs = false;
            $backUrl = url('/documents');
        }
        if ($document) {
            $user_id = $document->user_id;
            $user_data = User::find($user_id);
            //get the file exntension
            $file_extn = substr($document->filename, strrpos($document->filename, '.') + 1);
            //get the file name 
            $file_name = substr($document->filename, 0, strripos($document->filename, '.', 0));
            //get organization details 
            $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
            $organization = Organization::find($organization_id);
            
            $img_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            //check the file is image or pdf or another format
            if (in_array(strtolower($file_extn), $img_extensions)) {
                $type = 'image';
            } else if (strtolower($file_extn) == 'pdf') {
                $type = 'pdf';
            } else {
                $type = 'general';
            }
            if(!empty($request->is_preview)) {
                $base64_data = '';
                $orgDetils = json_decode($document->document_details_for_org, true);
                $key = '';
                if(isset($orgDetils[$organization_id])) {
                    $key_new = $orgDetils[$organization_id]['key'];
                    $key = $this->decrypt(base64_decode($key_new), $organization->pdf_password, urldecode($request->private_key));
                }
                //key for decryption
                if($key) {
                    if(!empty($document->uploaded_by)) {
                        $folder = '/users/' . $user_id . '/organizations/' . $organization_id . '/';
                    } else {
                        $folder = '/users/' . $user_id . '/common-documents/';
                    }
                    //get content of the  encrypted file
                    $file_content = file_get_contents(Storage::disk('public')->path($folder . '/' . $file_name . '.txt'));
                    //decrypt the content of the file
                    $plain_text = Helper::cryptoJsAesDecrypt($key, $file_content);
                    // $plain_text = $encrypterFrom->decrypt( $file_content ); // get the decrypted text from the enxrypted file
                    $base64_data = $plain_text;
                }
                return response()->json(['base64_data' => $base64_data, 'type' => $type, 'filename' => $document->filename], 200);
            }

            $user = PatientData::where('user_id', $user_id)->first();
            //Pass this data from template to tab-layout
            $common_data = array(
                'user_name' => ucfirst($user->firstname) . ' ' . ucfirst($user->lastname),
                'therapy_plan_link' => route('patients.therapy-plans', encrypt($user_id)),
                'base_data_link' => route('patients.base-data', encrypt($user_id)),
                'event_data_link' => route('patients.meetings', encrypt($user_id)),
                'documents_link' => route('patients.documents', encrypt($user_id)),
                'showtabs' => $showtabs,
                'back_link_history' => $backUrl,
            );
            $orgDetils = json_decode($document->document_details_for_org, true);
            // organization wise file name
            if(isset($orgDetils[$organization_id])) {
                $document->filename = $orgDetils[$organization_id]['filename'];
            }
            View::share('common_data', $common_data);
            return view('patient.view-document', compact('user_id', 'document', 'type', 'showtabs'));
        } else {
            $message = \Lang::get('lang.general-error-message');
            $request->session()->flash('alert-danger', $message);
            return redirect()->route('patients.index');
        }
    }

    // function to upload the file at specific location
    public function uploadOne(UploadedFile $uploadedFile, $folder = null, $disk = 'public', $filename = null)
    {
        $name = !is_null($filename) ? $filename : str_random(25);

        $file = $uploadedFile->storeAs($folder, $name . '.' . $uploadedFile->getClientOriginalExtension(), $disk);

        return $file;
    }

    // get patients meetings data
    public function getPatientsMeetingById(Request $request, $id)
    {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        $language_id = \Lang::getLocale() == 'de' ? 2 : 1;

        // get patient events with category filter
        $patientMeetings = PatientsMeetings::with('Organization')->with([
            'meetingCategoryWithCode' => function ($type) use ($language_id) {
                return $type->where('language_id', $language_id);
            }
        ])->with('workerData')->where('user_id', $id)->where('organization_id', $organization_id)->where('is_verified', 1)->get();

        // patient event data
        $PatientDataEvents = [];
        foreach ($patientMeetings as $key => $data) {
            if ($data['Organization'] && $data['Organization'] && $data['Organization']->pdf_password && $request->private_key) {
                $start_date = \Carbon\Carbon::parse($data->start_date);
                $end_date = \Carbon\Carbon::parse($data->end_date);
                $meeting['id'] = $data->id;
                $meeting['title'] = $data->title ?? $data['meetingCategoryWithCode']->name;
                if ($data->is_full_day) {
                    $meeting['start'] = $start_date->format('Y-m-d');
                    $meeting['end'] = $end_date->format('Y-m-d');
                } else {
                    $meeting['start'] = $data->start_date;
                    $meeting['end'] = $data->end_date;
                }
                if ($data->schedule_category_code == 'phase_start') {
                    $meeting['editable'] = false;
                } else {
                    $meeting['editable'] = true;
                }
                $meeting['startDate'] = $start_date->format('d.m.Y');
                $meeting['endDate'] = $end_date->format('d.m.Y');
                $meeting['startTiming'] = $start_date->format('H:i');
                $meeting['endTiming'] = $end_date->format('H:i');
                $meeting['reminder'] = $data->reminder;
                $meeting['description'] = $data->description;
                $meeting['schedule_category_code'] = $data->schedule_category_code;
                $meeting['orgName'] = $data['meetingCategoryWithCode']->name;
                $meeting['patient_doctor_id'] = $data['patient_doctor_id'];
                $meeting['place'] = $data->place;
                $meeting['is_full_day'] = $data->is_full_day ? true : false;
                $meeting['allDay'] = $data->is_full_day ? true : false;
                $meeting['materials'] = json_decode($data->materials);
                $meeting['className'] = \Lang::get('lang.class_' . $data->schedule_category_code) . '-event';
                $meeting['reminderText'] = $data->reminder_text;
                $meeting['doctor_name'] = !empty($data['workerData']) ? isset(Config::get('globalConstants.user_title')[$data['workerData']->title]) ? Config::get('globalConstants.user_title')[$data['workerData']->title] . " " . $data['workerData']->firstname . " " . $data['workerData']->lastname : '' . " " . $data['workerData']->firstname . " " . $data['workerData']->lastname : '';
                $PatientDataEvents[] = $meeting;
            }
        }
        return response()->json($PatientDataEvents);
    }

    // get patients meeting view
    public function getPatientsMeeting(Request $request, $id)
    {
        $user = PatientData::where('user_id', decrypt($id))->first();
        $language_id = \Lang::getLocale() == 'de' ? 2 : 1;
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        $organization_patient_data = OrganizationPatient::with('User')->where('user_id', decrypt($id))->where('organization_id', $organization_id)->whereNotNull('verified_at')->first();
        $emailCheckUser = User::find(decrypt($id));
        $verifiedFlag = true;
        if (!$organization_patient_data) {
            //if user is not verified then prevent to access the meeting page
            $verifiedFlag = false;
            $message = \Lang::get('lang.not-verified-user-mesg');
        }
        if (!$emailCheckUser->email_verified_at) {
            //if user is not verified then prevent to access the meeting page
            $verifiedFlag = false;
            $message = \Lang::get('lang.not-email-verified-user-mesg');
        }
        if (!$verifiedFlag) {
            $request->session()->flash('alert-danger', $message);
            return redirect()->back();
        }
        $species = ScheduleCategory::where('language_id', $language_id)->get();
        $assign_doc = $organization_patient_data->assign_doctor;
        $organizationData = Organization::find($organization_id);
        $organizationAdd = $organizationData->street . " " . $organizationData->street_number . " , " . $organizationData->postcode . " " . $organizationData->place;
        $materials = Material::where('language_id', $language_id)->get();

        $common_data = array(
            'user_name' => ucfirst($user->firstname) . ' ' . ucfirst($user->lastname),
            'therapy_plan_link' => route('patients.therapy-plans', $id),
            'base_data_link' => route('patients.base-data', $id),
            'event_data_link' => route('patients.meetings', $id),
            'documents_link' => route('patients.documents', $id)
        );
        $id = decrypt($id);
        View::share('common_data', $common_data);
        return view('patient.meeting', compact('id', 'assign_doc', 'species'));
    }

    // create or update patient meetings
    public function updateOrCreatemeetings(Request $request)
    {
        if (!empty($request->meeting_id)) {
            $PatientsMeetings = PatientsMeetings::find($request->meeting_id);
            $message = \Lang::get('lang.event_update');
        } else {
            $PatientsMeetings = new PatientsMeetings;
            $message = \Lang::get('lang.event_create');
        }

        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';

        // validation: at a time one patient for related doctor
        $startDate = date('Y-m-d H:i:s', strtotime("$request->startDate $request->startTiming"));
        $endDate = date('Y-m-d H:i:s', strtotime("$request->endDate $request->endTiming"));
        $checkDateExistOrNot = PatientsMeetings::where('organization_id', $organization_id)->where('patient_doctor_id', $request->patient_doctor_id)->where('start_date', '<=', $startDate)->where('end_date', '>', $startDate)->first();

        $PatientsMeetings->title = $request->title;
        $PatientsMeetings->user_id = !empty($request->patient_name) ? $request->patient_name : $request->user_id;
        $PatientsMeetings->start_date = $startDate;
        $PatientsMeetings->end_date = $endDate;
        $PatientsMeetings->is_reminder_sent = 0;
        $PatientsMeetings->description = $request->description;
        $PatientsMeetings->schedule_category_code = $request->schedule_category_code;
        $PatientsMeetings->patient_doctor_id = $request->patient_doctor_id;
        $PatientsMeetings->organization_id = $organization_id;
        $PatientsMeetings->place = $request->place;
        $PatientsMeetings->timezone = $request->timezone;
        $PatientsMeetings->materials = $request->has("materials") && count($request->materials) ? json_encode($request->materials) : null;
        $PatientsMeetings->is_verified = 1; //temp default value
        $PatientsMeetings->is_full_day = $request->has('is_full_day') ? 1 : null;
        $PatientsMeetings->save();
        $request->session()->flash('alert-success', $message);
        if (isset($request->return_type) && $request->return_type) {
            return response()->json(['success' => $message, 'status' => 200], 200);
        }
        if (!empty($request->patient_name)) {
            return  redirect()->route('organization.meetings');
        } else {
            return  redirect()->back();
        }
    }

    // delete meeting
    public function deleteMeetings(Request $request)
    {
        if (!empty($request->meeting_id)) {
            PatientsMeetings::where('id', $request->meeting_id)->delete();
        }
        $message = \Lang::get('lang.event_delete');
        $request->session()->flash('alert-success', $message);
        return redirect()->back();
    }

    // get organization meeting view
    public function getOrganizationMeeting(Request $request)
    {
        $language_id = \Lang::getLocale() == 'de' ? 2 : 1;
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';

        $OrganizationDataFilter = function ($q) use ($organization_id) {
            $q->where('organization_id', '=', $organization_id)->where('verified_at', '<>', null);
        };
        $doctorList = WorkerData::with(['OrganizationData' => $OrganizationDataFilter])->whereHas('OrganizationData', $OrganizationDataFilter)->whereIn('worker_data_species_id', ['lang_doc', 'lang_physio'])->orderBy('id', 'desc')->get();
        $species = ScheduleCategory::where('language_id', $language_id)->get();
        $organizationData = Organization::find($organization_id);
        $materials = Material::where('language_id', $language_id)->get();
        $organizationAdd = '';
        if (!empty($organizationData)) {
            $organizationAdd = $organizationData->street . " " . $organizationData->street_number . " , " . $organizationData->postcode . " " . $organizationData->place;
        }
        if (!empty($request->isPopup)) {
            if (empty($request->private_key)) {
                return response()->json();
            } else {
                // get all patient name connected with organization
                $organizaton_patient_final_data = $this->orgRepository->getOrganizationPatientDataListing(urldecode($request->private_key));
                $pdf_pass = $organizationData->pdf_password;
                $organizationPatientNameArray = [];
                $organizationDoctorNameArray = [];
                foreach ($organizaton_patient_final_data['data'] as $key => $organizationPatient) {
                    if ($organizationPatient['verified_flag'] && $organizationPatient['user'] && $organizationPatient['user']->email_verified_at) {
                        $name = $organizationPatient['name'];
                        $lastname = $organizationPatient['lastname'];
                        $bday = $organizationPatient['bday'];
                        $bday = Carbon::parse($bday)->format('d.m.Y');
                        $fullname = $name . " " . $lastname . ' ( ' . $bday . ' )';
                        $organizationPatientNameArray[$organizationPatient['user_id']] = ['fullname' => $fullname, 'assign_doc' => !empty($organizationPatient['assign_doctor']) ? $organizationPatient['assign_doctor'] : null];
                    }
                }
                foreach ($doctorList as $doctor) {
                    $organizationDoctorNameArray[] = array("id" => $doctor->id, 'text' => $doctor->fullname);
                }
                $returnHTML =  view('common-views.organization_meetings_popup', compact('doctorList', 'species', 'organizationAdd', 'materials', 'organizationPatientNameArray'))->render();
                return response()->json(array('organizationDoctorName' => $organizationDoctorNameArray, 'html' => $returnHTML));
            }
        }
        return view('events.organization_meeting', compact('species'));
    }

    // get organization meetings data
    public function getOrganizationMeetingData(Request $request)
    {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        $language_id = \Lang::getLocale() == 'de' ? 2 : 1;

        // get patient events with category filter
        $OrganizationDataFilter = function ($q) use ($organization_id) {
            $q->where('organization_id', '=', $organization_id)->where('verified_at', '<>', null);
        };
        $patientMeetings = PatientsMeetings::with([
            'meetingCategoryWithCode' => function ($type) use ($language_id) {
                return $type->where('language_id', $language_id);
            }
        ])->with(['OrganizationPatientData' => $OrganizationDataFilter])->whereHas('OrganizationPatientData', $OrganizationDataFilter)->with('workerData')->where('organization_id', $organization_id)->where('is_verified', 1);

        $patientMeetings = $patientMeetings->get();

        // get organization data
        $organizationData = Organization::find($organization_id);
        $pdf_pass = $organizationData->pdf_password;

        // get all patient name connected with organization
        $organizationPatients = OrganizationPatient::where('organization_id', $organization_id)->where('verified_at', '<>', null)->get();
        $organizationPatientNameArray = [];
        foreach ($organizationPatients as $organizationPatient) {
            $name = $this->decrypt($organizationPatient->name, $pdf_pass, urldecode($request->private_key));
            $lastname = $this->decrypt($organizationPatient->lastname, $pdf_pass, urldecode($request->private_key));
            $bday = $this->decrypt($organizationPatient->bday, $pdf_pass, urldecode($request->private_key));
            $bday = Carbon::parse($bday)->format('d.m.Y');
            $fullname = $name . " " . $lastname . ' ( ' . $bday . ' )';
            $organizationPatientNameArray[] = array("id" => $organizationPatient->user_id, 'text' => $fullname);
        }

        // patient event data
        $PatientDataEvents = [];
        foreach ($patientMeetings as $key => $data) {
            if ($pdf_pass) {
                $name = $this->decrypt($data['OrganizationPatientData']->name, $pdf_pass, urldecode($request->private_key));
                $lastname = $this->decrypt($data['OrganizationPatientData']->lastname, $pdf_pass, urldecode($request->private_key));
                $patientfullname = ucfirst($name) . ' ' .ucfirst($lastname);
                $start_date = \Carbon\Carbon::parse($data->start_date);
                $end_date = \Carbon\Carbon::parse($data->end_date);
                $meeting['id'] = $data->id;
                $meeting['title'] = $data->title ?? $data['meetingCategoryWithCode']->name;
                if ($data->is_full_day) {
                    $meeting['start'] = $start_date->format('Y-m-d');
                    $meeting['end'] = $end_date->format('Y-m-d');
                } else {
                    $meeting['start'] = $data->start_date;
                    $meeting['end'] = $data->end_date;
                }
                if ($data->schedule_category_code == 'phase_start') {
                    $meeting['editable'] = false;
                } else {
                    $meeting['editable'] = true;

                }
                $meeting['startDate'] = $start_date->format('d.m.Y');
                $meeting['endDate'] = $end_date->format('d.m.Y');
                $meeting['startTiming'] = $start_date->format('H:i');
                $meeting['endTiming'] = $end_date->format('H:i');
                $meeting['reminder'] = $data->reminder;
                $meeting['description'] = $data->description;
                $meeting['schedule_category_code'] = $data->schedule_category_code;
                $meeting['orgName'] = $data['meetingCategoryWithCode']->name;
                $meeting['patient_doctor_id'] = $data['patient_doctor_id'];
                $meeting['className'] = \Lang::get('lang.class_' . $data->schedule_category_code) . '-event ' . $data->user_id . '-patient-event';
                $meeting['reminderText'] = $data->reminder_text;
                $meeting['name'] = $name;
                $meeting['lastname'] = $lastname;
                $meeting['patient_name'] = $data->user_id;
                $meeting['patientfullname'] = $patientfullname;
                $meeting['is_full_day'] = $data->is_full_day ? true : false;
                $meeting['allDay'] = $data->is_full_day ? true : false;
                $meeting['place'] = $data->place;
                $meeting['materials'] = json_decode($data->materials);
                $meeting['doctor_name'] = !empty($data['workerData']) ? isset(Config::get('globalConstants.user_title')[$data['workerData']->title]) ? Config::get('globalConstants.user_title')[$data['workerData']->title] . " " . $data['workerData']->firstname . " " . $data['workerData']->lastname : '' . " " . $data['workerData']->firstname . " " . $data['workerData']->lastname : '';
                $PatientDataEvents[] = $meeting;
            }
        }
        return response()->json(['PatientDataEvents' => $PatientDataEvents, 'organizationPatientNames' => $organizationPatientNameArray]);
    }

    //to open the all-document page
    public function allDocuments(Request $request)
    {
        return view('patient.all-documents');
    }

    //get all the patients
    public function GetAllPatients(Request $request)
    {
        $private_key = '';
        $private_key = urldecode($request->get('private_key'));
        $organizaton_patient_final_data = $this->orgRepository->getOrganizationPatientDataListing($private_key);
        $patients = array();
        foreach ($organizaton_patient_final_data['data'] as $key => $value) {
            //to get the verifid patient only
            if ($value['verified_flag']) {
                $patients[strtoupper($value['lastname'][0])][] = $value;
            }
        }
        ksort($patients);
        if (empty($private_key)) {
            return response()->json('failure', 400);
        }
        if ($request->has('getShowData') && $request->get('getShowData')) {
            return view('common-views.documents-view', ['patientData' => $patients]);
        }
        return view('patient.documents-template', ['patientData' => $patients]);
    }

    //function to rename the document
    public function renameDocument(Request $request, $id = null)
    {
        //validation rules
        $rules = [
            'updated_name' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $err_response  = [
                'success' => false,
                'errors' => $validator->messages()
            ];
            return response()->json($err_response, 200);
        }

        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        $all_documents = Document::where('user_id', decrypt($request->user_id))->whereRaw("find_in_set('".$organization_id."',organization_id)")->get();
        // to check the file name is already exist or not
        foreach ($all_documents as $file) {
            $file_name = '';
            $orgDetils = json_decode($file->document_details_for_org, true);
            if(isset($orgDetils[$organization_id])) {
                $file_name = $orgDetils[$organization_id]['filename'];
            }
            if (strtolower($file_name) == strtolower($request->updated_name)) {
                $err_response  = [
                    'success' => false,
                    'custom_error' => true,
                    'errors' => \Lang::get('lang.file-name-already-exist-msg')
                ];
                return response()->json($err_response, 200);
            }
        }
        if ($id) {
            $document = Document::find(decrypt($id));
            if (!empty($document)) {
                //old file name(txt exntension)
                $oldname = substr($document->filename, 0, strripos($document->filename, '.', 0)) . '.txt';
                //set the new encrypted file name with txt exntesion
                $new_file_name = $request->updated_name . '.txt';
                //if document is added by patient
                // $folder = '/users/' . $document->user_id . '/organizations/' . $document->organization_id . '/';
                // //rename the file in storage folder
                // Storage::disk('public')->move($folder . $oldname, $folder . $new_file_name);
                //save the renamed file name and url in the database
                // $document->filename = $request->updated_name . '.' . substr($request->old_file_name, strrpos($request->old_file_name, '.') + 1);
                // $document->url = $folder . $request->updated_name . '.' . substr($request->old_file_name, strrpos($request->old_file_name, '.') + 1);
                if(!empty($document->uploaded_by)) {
                    //if document is added by patient
                    $folder = '/users/' . $document->user_id . '/organizations/' . $document->organization_id . '/';
                    //rename the file in storage folder
                    if (Storage::disk('public')->exists($folder . $new_file_name)) {
                        $err_response  = [
                            'success' => false,
                            'custom_error' => true,
                            'errors' => \Lang::get('lang.file-name-already-exist-msg')
                        ];
                        return response()->json($err_response, 200);
                    }
                    Storage::disk('public')->move($folder . $oldname, $folder . $new_file_name);
                    $document->filename = $request->updated_name . '.' . substr($request->old_file_name, strrpos($request->old_file_name, '.') + 1);
                    $document->url = $folder . $request->updated_name . '.' . substr($request->old_file_name, strrpos($request->old_file_name, '.') + 1);
                }
                $orgDetils = json_decode($document->document_details_for_org, true);
                if(isset($orgDetils[$organization_id])) {
                    $orgDetils[$organization_id]['filename'] = $request->updated_name . '.' . substr($request->old_file_name, strrpos($request->old_file_name, '.') + 1);
                }
                $document->document_details_for_org = json_encode($orgDetils);
                $document->save();
                return response()->json(['success' => 'Document successfully renamed', 'status' => 200], 200);
            }
        } else {
            //old file name(txt exntension)
            $oldname = $request->old_file_name . '.txt';
            //set the new encrypted file name with txt exntesion
            $new_file_name = $request->updated_name . '.txt';
            //if document is added by patient
            $folder = '/users/' . decrypt($request->user_id) . '/organizations/' . $organization_id . '/';
            //rename the file in storage folder
            if (Storage::disk('public')->exists($folder . $new_file_name)) {
                $err_response  = [
                    'success' => false,
                    'custom_error' => true,
                    'errors' => \Lang::get('lang.file-name-already-exist-msg')
                ];
                return response()->json($err_response, 200);
            }
            Storage::disk('public')->move($folder . $oldname, $folder . $new_file_name);
            return response()->json(['success' => 'Document successfully renamed', 'status' => 200], 200);
        }
    }

    //function to change the category of document
    public function changeCategoryOfDocument(Request $request, $id = null) {
        $document = Document::find(decrypt($id));
        if (!empty($document)) {
            $document->type = $request->category;
            $document->save();
            $message = \Lang::get('lang.document-category-change-msg');
            $request->session()->flash('alert-success', $message);
            return response()->json(['success' => \Lang::get('lang.document-category-change-msg'), 'status' => 200], 200);
        }
        $err_response  = [
            'success' => false,
            'custom_error' => true,
            'errors' => \Lang::get('lang.document-not-exist')
        ];
        return response()->json($err_response, 400);
    }

    //function to set the session for patient
    public function setPatientSession(Request $request, $id)
    {
        $patient_data = PatientData::where('user_id', decrypt($id))->first();
        $data = [];
        if (!empty($patient_data)) {
            $request->session()->put('patient_id', $id);
            $request->session()->put('patient_decr_id', decrypt($id));
            $request->session()->put('patient_name', ucfirst($patient_data->firstname) . ' ' . ucfirst($patient_data->lastname));
            $data = array(
                'success' => true,
                'patient_id' => $id,
                'patient_decr_id' => decrypt($id),
                'patient_name' => ucfirst($patient_data->firstname) . ' ' . ucfirst($patient_data->lastname)
            );
            return response()->json($data, 200);
        } else {
            return response()->json('failure', 400);
        }
    }

    //function to load the dropzone
    public function viewDropZone(Request $request, $id)
    {
        $patient_data = PatientData::where('user_id', decrypt($id))->first();
        $data = array(
            'patient_id' => $id,
            'patient_decr_id' => decrypt($id),
            'patient_name' => ucfirst($patient_data->firstname) . ' ' . ucfirst($patient_data->lastname)
        );
        return view('common-views.document-dropzone', ['data' => $data]);
    }

    //function to abort the patient request to connect the organization
    public function abortPatientRequest(Request $request, $id)
    {
        $user_id = decrypt($id);
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        //get the organization patient json data
        $organization_patient_raw_data = OrganizationPatientJsonData::with(['Organization'])->where('organization_id', $organization_id)->first();
        $organization_data = Organization::where('id', $organization_id)->first();
        //to remove the records from the organization_patient table
        OrganizationPatient::where('user_id', $user_id)->where('organization_id', $organization_id)->delete();
        $private_key = urldecode($request->get('private_key'));
        if (!empty($organization_patient_raw_data)) {
            $organization_patient_data = array();
            $organizations_patients = new OrganizationPatient;
            //to get the decrypted the json data
            $organization_patient_data = unserialize($organizations_patients->decrypt($organization_patient_raw_data->json, $organization_patient_raw_data->organization->pdf_password, $private_key));
            // remove the patient from the organization_patient_json
            foreach ($organization_patient_data as $key => $data) {
                if ($key == $user_id) {
                    unset($organization_patient_data[$key]);
                }
            }
            // update OrganizationPatientJsonData with new patient data
            $organization_json_data = $this->encryptObject($organization_data, serialize($organization_patient_data));
            OrganizationPatientJsonData::where('organization_id', $organization_id)->update(['json' => $organization_json_data, 'count' => count($organization_patient_data)]);
        }
        $message = \Lang::get('lang.abort-patient-success-message');
        $request->session()->flash('alert-success', $message);
        return response()->json(['success' => 'Patient removed successfully', 'status' => 200], 200);
    }

    public function sendResetPasswordEmail(Request $request, $id)
    {
        $user = User::where('email', decrypt($id))->first();
        View::share('ConfirmationStepShow', false);
        if (Session::get('verified')) {
            return view('auth.verify', ['linkStatus' => 'This user email is already approved.']);
        } else {
            if ($user) {
                if($user->user_created_by_front) {
                    return view('auth.verify', ['linkStatus' => 'Your email address has been verified.']);
                } else {
                    \Mail::to($user->email)->send(new SetPassword($user));
                    return view('auth.verify', ['linkStatus' => 'A temporary password and password reset link has been sent to your registetered email address.']);
                }
            } else {
                return view('auth.verify', ['linkStatus' => 'The link might be invalid or expire.']);
            }
        }
    }

    //function to get the phase details
    public function getPhaseDetails(Request $request, $id)
    {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        $therapy_plans = Phases::with(['plan', 'limitations'])->whereHas('plan', function ($query) use ($id, $request, $organization_id) {
            $query->where('user_id', decrypt($request->user_id));
            $query->where('organization_id', $organization_id);
        })->get();
        $dates = array();
        $main_data = array();
        $ids = array();
        if (!empty($therapy_plans)) {
            foreach ($therapy_plans as $main_key => $data) {
                //Set the start/end date for every phases
                if ($main_key == 0) {
                    $dates['start_date'][$main_key] = Carbon::parse($data->plan->start_date);
                    $dates['end_date'][$main_key] = Carbon::parse($data->plan->start_date)->addWeeks($data->duration)->subDay(1);
                } else {
                    $dates['start_date'][$main_key] = Carbon::parse($dates['end_date'][$main_key - 1])->addDay();
                    $dates['end_date'][$main_key] = Carbon::parse($dates['start_date'][$main_key])->addWeeks($data->duration)->subDay(1);
                }
                $data->start_date = $dates['start_date'][$main_key];
                $data->end_date = $dates['end_date'][$main_key];
                $ids[] = $data->id;
                // to set the data of specific phases
                if ($data->id == $id) {
                    $data->key = $main_key;
                    $main_data = $data;
                }
            }
            $main_data['ids'] = $ids;
            return response()->json(['success' => $main_data, 'status' => 200], 200);
        } else {
            $err_response  = [
                'success' => false,
                'errors' => 'Something went wrong'
            ];
            return response()->json($err_response, 200);
        }
    }

    //function to load the old plan template 
    public function getPatientTherapyPlansOld(Request $request, $id)
    {
        //redirect user to patient page 
        return redirect()->route('patients.index');
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        //to check this user is verified by organization or not
        $organization_patient_data = OrganizationPatient::with('User')->where('user_id', decrypt($id))->where('organization_id', $organization_id)->whereNotNull('verified_at')->first();
        $emailCheckUser = User::find(decrypt($id));
        $verifiedFlag = true;
        if (!$organization_patient_data) {
            //if user is not verified then prevent to access the meeting page
            $verifiedFlag = false;
            $message = \Lang::get('lang.not-verified-user-mesg');
        }
        if (!$emailCheckUser->email_verified_at) {
            //if user is not verified then prevent to access the meeting page
            $verifiedFlag = false;
            $message = \Lang::get('lang.not-email-verified-user-mesg');
        }
        if (!$verifiedFlag) {
            $request->session()->flash('alert-danger', $message);
            return redirect()->back();
        }
        //Exercises master data
        $user = PatientData::where('user_id', decrypt($id))->first();
        $therapy_plans = Phases::with(['plan', 'limitations'])->whereHas('plan', function ($query) use ($id) {
            $query->where('user_id', decrypt($id));
        })->get();
        $indication = Indication::all();
        $phases_data = array();
        $therapy_plans_phases = array();
        $indication_id = '';
        $indication_name = '';
        $start_date = '';
        $dates = array();
        if (!empty($therapy_plans)) {
            foreach ($therapy_plans as $main_key => $data) {
                $indication_id = $data->plan->indication_id;
                $indication_name = Indication::find($indication_id)->name;
                $start_date = $data->plan->start_date;
                //get the exercises groups as per the phase
                $exercises_groups = PhaseExerciseGroups::where('phase_id', $data->id)->with(['ExerciseGroupList', 'PhaseExercises.PhaseExerciseList'])->get();
                //Set the start/end date for every phases
                if ($main_key == 0) {
                    $dates['start_date'][$main_key] = Carbon::parse($data->plan->start_date);
                    $dates['end_date'][$main_key] = Carbon::parse($data->plan->start_date)->addWeeks($data->duration)->subDay(1);
                } else {
                    $dates['start_date'][$main_key] = Carbon::parse($dates['end_date'][$main_key - 1])->addDay();
                    $dates['end_date'][$main_key] = Carbon::parse($dates['start_date'][$main_key])->addWeeks($data->duration)->subDay(1);
                }
                $data->start_date = $dates['start_date'][$main_key];
                $data->end_date = $dates['end_date'][$main_key];
                $data->exercises_groups = $exercises_groups;
                $therapy_plans_phases[] = $data;
            }
        }
        //Pass this data from template to tab-layout
        $common_data = array(
            'user_name' => ucfirst($user->firstname) . ucfirst($user->lastname),
            'therapy_plan_link' => route('patients.therapy-plans', $id),
            'base_data_link' => route('patients.base-data', $id),
            'event_data_link' => route('patients.meetings', $id),
            'documents_link' => route('patients.documents', $id)
        );
        View::share('common_data', $common_data);
        return view('patient.therapy_plans_old', compact('therapy_plans_phases', 'user', 'indication', 'start_date', 'indication_id', 'indication_name'));
    }

    public function DeleteUnverifiedUser()
    {
        $user = User::where('created_at','<', Carbon::now()->subHours(24))->whereNull('email_verified_at')->delete();
        return response()->json("success");
    }

    public function patientTherapyPlans(Request $request, $id)
    {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        //to check this user is verified by organization or not
        $organization_patient_data = OrganizationPatient::with('User')->where('user_id', decrypt($id))->where('organization_id', $organization_id)->whereNotNull('verified_at')->first();
        $emailCheckUser = User::find(decrypt($id));
        $verifiedFlag = true;
        if (!$organization_patient_data) {
            //if user is not verified then prevent to access the meeting page
            $verifiedFlag = false;
            $message = \Lang::get('lang.not-verified-user-mesg');
        }
        if (!$emailCheckUser->email_verified_at) {
            //if user is not verified then prevent to access the meeting page
            $verifiedFlag = false;
            $message = \Lang::get('lang.not-email-verified-user-mesg');
        }
        if (!$verifiedFlag) {
            $request->session()->flash('alert-danger', $message);
            return redirect()->back();
        }
        $plan_history_data = '';
        $planData = TherapyPlanTemplates::where(function($query) use ($organization_id){
            $query->whereNull('user_id');
            $query->whereNull('organization_id');
            $query->orWhere(function($query) use ( $organization_id ) {
                $query->where('organization_id', $organization_id);
            });
        })->get();
        $assessments = AssessmentTemplates::where(function($query) use ($organization_id){
            $query->whereNull('user_id');
            $query->whereNull('organization_id');
            $query->orWhere(function($query) use ( $organization_id ) {
                $query->where('organization_id', $organization_id);
            });
        })->get();

        // defining varibales 
        $patient_plan_networks_data = null;
        $patient_plan_request_data = null;
        $plan_network_data = null;
        //get assigned plan details        
        $assign_plans_data = PatientTherapyPlanTemplates::with(['Phases.Limitations', 'Phases.PhaseCourses','Phases.PhaseAssessments.PatientAssessmentTemplates.Categories.SubCategories','Indications', 'BodyRegions', 'Phases.PhaseExercises.Exercises', 'Phases.PhaseCourses.PatientCourse.CourseExercises.Exercises', 'Phases.PhaseCourses.PatientCourse.BodyRegions', 'AllExercises', 'AllCourses', 'AllAssessments', 'PatientPlanNetwork'])
        ->with(['Phases.PhaseAssessments.PatientAssessmentTemplates.Categories' => function ($query) {
            // To get Categories if status is 1
            $query->where('status', 1)->with(['SubCategories' => function ($query) {
                    // To get sub-categories if status is 1
                    $query->where('status', 1);
                }]);
        }])
        ->with(['Phases.PhaseExercises' => function ($query) 
        {
            // To get the undeleted exercises
            $query->where('status', 1);
        }])->with(['Phases.PhaseCourses' => function ($query) 
        {
            // To get the undeleted courses
            $query->where('status', 1);
        }])->with(['AllExercises' => function ($query) 
        {
            // To get un-removed  exercises
            $query->where('status', 1);
        }])->with(['AllCourses' => function ($query) 
        {
            // To get un-removed  courses
            $query->where('status', 1);
        }])->where('patient_id', decrypt($id))->where('is_draft', 0)->where('is_complete', 0)->where('aborted', 0)->orderBy('id', 'desc')->first();
    
        // get connected orgnizations name
        $owner_organization = null;
        $connected_organization = null;
        if ($assign_plans_data) {
            $patient_plan_networks_data = $assign_plans_data->PatientPlanNetwork;

            $plan_network_data = $assign_plans_data->PatientPlanNetwork->where('organization_id', $organization_id)->first();
            $owner_organization_network = $patient_plan_networks_data->where('is_owner_organization', 1)->first();
            $connected_organization_network = $patient_plan_networks_data->where('is_owner_organization', 0)->first();

            if ($owner_organization_network) {
                $owner_organization = Organization::find($owner_organization_network->organization_id);
            }
            if($connected_organization_network) {
                    $connected_organization = Organization::find($connected_organization_network->organization_id);
                    $connected_organization->status = $connected_organization_network->status;
            }
            if ($plan_network_data) {
                if ($plan_network_data->draft_id) {
                    $assign_plans_data = PatientTherapyPlanTemplates::with(['Phases.Limitations', 'Phases.PhaseCourses','Phases.PhaseAssessments.PatientAssessmentTemplates.Categories.SubCategories','Indications', 'BodyRegions', 'Phases.PhaseExercises.Exercises', 'Phases.PhaseCourses.PatientCourse.CourseExercises.Exercises', 'Phases.PhaseCourses.PatientCourse.BodyRegions', 'AllExercises', 'AllCourses', 'AllAssessments', 'PatientPlanNetwork'])
                    ->with(['Phases.PhaseAssessments.PatientAssessmentTemplates.Categories' => function ($query) {
                        // To get Categories if status is 1
                        $query->where('status', 1)->with(['SubCategories' => function ($query) {
                                // To get sub-categories if status is 1
                                $query->where('status', 1);
                            }]);
                    }])
                    ->with(['Phases.PhaseExercises' => function ($query) 
                    {
                        // To get the undeleted exercises
                        $query->where('status', 1);
                    }])->with(['Phases.PhaseCourses' => function ($query) 
                    {
                        // To get the undeleted courses
                        $query->where('status', 1);
                    }])
                    ->where('patient_id', decrypt($id))->where('is_draft', 1)->where('is_complete', 0)->where('aborted', 0)->orderBy('id', 'desc')->first();
                }
            } else {
                $message = \Lang::get('lang.access_denied');
                $request->session()->flash('alert-danger', $message);
                return redirect()->route('patients.base-data', $id);
            }
            
        }

        if(empty($assign_plans_data)) {
            $plan_history_data = PatientTherapyPlanTemplates::where(function($query) use ($id){
                $query->orWhere(function($query) {
                    $query->orWhere('is_complete', 0);
                    $query->orWhere('aborted', 0);
                });
            })->where('patient_id', decrypt($id))->get();
        }
        $dates = array();
        $total_duration = 0;
        $set_active = 0;
        $isbsnr = null;
        if (!empty($assign_plans_data)) {
            // role wise status flow
            $flow_type = 1; // 1 = default, 2 = only owner, 3 = all rights to other org
            $org_type = Organization::with(['SpeciesWithCode'])->find($organization_id);
            $isbsnr = $org_type->SpeciesWithCode->isbsnr;
            
            if (($assign_plans_data->phases_editable_by == $assign_plans_data->assessments_editable_by) && ($assign_plans_data->phases_editable_by == $assign_plans_data->exercises_courses_editable_by)) {
                if (($assign_plans_data->phases_editable_by == 1 && $isbsnr == 'true' && $plan_network_data->is_owner_organization == 1) || ($assign_plans_data->phases_editable_by == 2 && $isbsnr == 'false' && $plan_network_data->is_owner_organization == 1)) {
                    $flow_type = 2;
                } else {
                    $flow_type = 3;
                }
            }
            $plan_network_data->flow_type = $flow_type;

            foreach ($assign_plans_data->Phases as $main_key => $data) {
                $start_date = $assign_plans_data->start_date;
                $total_duration = $total_duration + $data->duration;
                //Set the start/end date for every phases
                if ($main_key == 0) {
                    $dates['start_date'][$main_key] = Carbon::parse($start_date);
                    $dates['end_date'][$main_key] = Carbon::parse($start_date)->addWeeks($data->duration)->subDay(1);
                } else {
                    $dates['start_date'][$main_key] = Carbon::parse($dates['end_date'][$main_key - 1])->addDay();
                    $dates['end_date'][$main_key] = Carbon::parse($dates['start_date'][$main_key])->addWeeks($data->duration)->subDay(1);
                }
                $data->start_date = $dates['start_date'][$main_key];
                $data->end_date = $dates['end_date'][$main_key];
                if (Carbon::now()->startOfDay()->gte(Carbon::parse($data->start_date)->startOfDay()) && Carbon::now()->startOfDay()->lte(Carbon::parse($data->end_date)->startOfDay())) {
                    $set_active = 1;
                }

                //To set the measurements value of assessment sub categories
                if(!$data->PhaseAssessments->isEmpty()) {
                    foreach($data->PhaseAssessments[0]->PatientAssessmentTemplates->Categories as $category) {
                        foreach($category->SubCategories as $key => $subcat) {
                            $measurements_values = [];
                            $new_date = $data->start_date;
                            $measurements = PatientAssessmentMeasurements::where('patient_assessment_sub_categories_id', $subcat->id)->orderBy('date', 'ASC')->get();
                            $key = 0;
                            while(Carbon::parse($new_date)->startOfDay()->lte(Carbon::parse($data->end_date)->startOfDay()))
                            {
                                $formatted_new_date = Carbon::parse($new_date)->format('d:m:Y');
                                $measurements_values[$key] = 0;
                                if(!empty($subcat->start_value) && $subcat->start_value < 0) {
                                    $measurements_values[$key] = $subcat->start_value;    
                                }
                                if($subcat->is_patient_assessment) {
                                    if(!empty($subcat->measurements_json)) {
                                        $added_measurements = json_decode($subcat->measurements_json, true);
                                        if(array_key_exists($formatted_new_date, $added_measurements)) {
                                            $measurementsValues =  $added_measurements[$formatted_new_date];
                                            foreach ($measurementsValues as $measurement_key => $measurements){
                                                $measurements_values[$key] = $measurements['first_measurement'];
                                            }
                                        }
                                    }
                                } else if(!empty($measurements)) {
                                    if(count($measurements)) {
                                        foreach($measurements as $measurement) {
                                            // To check the measurement date must be in phase interval
                                            if(Carbon::parse($new_date)->startOfDay() == Carbon::parse($measurement->date)->startOfDay()) {
                                                $measurements_values[$key] = $measurement->value;
                                            }
                                        }
                                    }
                                }
                                $new_date = Carbon::parse($new_date)->addDay();
                                $key = $key + 1; 
                            }
                            $subcat->measurements_data = $measurements_values;
                        }
                    }
                }
            }
            $patient_plan_request_data = PatientPlanRequests::where('plan_id', $assign_plans_data->id)->where('to', $organization_id)->orderBy('id', 'desc')->first();
            $plan_access = 0;
            if (!empty($patient_plan_networks_data)) {
                foreach ($patient_plan_networks_data as $key => $plan_network) {
                    $plan_network->organization_id;
                    if ($plan_network->organization_id == $organization_id) {
                        $plan_access = 1;
                        $plan_network_data = $plan_network;
                        if ($plan_network_data->status == 0) {
                            $plan_network_data->is_edit_flag = 0;
                        }
                    }
                }
                if ($plan_access == 0) {
                    $message = \Lang::get('lang.access_denied');
                    $request->session()->flash('alert-danger', $message);
                    return redirect()->route('patients.base-data', $id);
                }
            }
        }

        $user = PatientData::where('user_id', decrypt($id))->first();
        $common_data = array(
            'user_name' => ucfirst($user->firstname) ." ". ucfirst($user->lastname),
            'therapy_plan_link' => route('patients.therapy-plans', $id),
            'base_data_link' => route('patients.base-data', $id),
            'event_data_link' => route('patients.meetings', $id),
            'documents_link' => route('patients.documents', $id),
            'add_class' => empty($assign_plans_data),
        );
        $body_regions = BodyRegions::all();
        $indications = Indication::all();
        $phasesData = $assign_plans_data ? $assign_plans_data->Phases : NULL;
        View::share('common_data', $common_data);
        return view('assign-patient-views.assign-therapy-plan-templates', compact('indications', 'body_regions', 'planData', 'id', 'assign_plans_data', 'total_duration', 'phasesData' , 'assessments', 'set_active', 'plan_history_data', 'plan_network_data', 'patient_plan_request_data', 'owner_organization', 'connected_organization', 'isbsnr'));
    }

    public function assignPatientTherapyPlans(Request $request, $id)
    {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        if($request->therapy_plan_template_id) {
            $templateData = TherapyPlanTemplates::with(['Phases.Limitations', 'Phases.PhaseCourses','Phases.PhaseAssessments.TherapyPlanAssessments.Categories.SubCategories.Schedules','Phases.PhaseAssessments.TherapyPlanAssessments.BodyRegions','Phases.PhaseAssessments.TherapyPlanAssessments.Schedules','Phases.PhaseAssessments.TherapyPlanAssessments.Indications','Indications', 'BodyRegions', 'Phases.PhaseExercises.Exercises.BodyRegions', 'Phases.PhaseCourses.TherapyPlanCourses.TherapyPlanCourseExercises.Exercises.BodyRegions', 'Phases.PhaseCourses.TherapyPlanCourses.BodyRegions','Phases.PhaseCourses.TherapyPlanCourses.Indications', 'AllExercises', 'AllCourses', 'AllAssessments']);
            $templateData = $templateData->find(decrypt($request->therapy_plan_template_id));

            // copy therapy plan data
            if($templateData) {
                $data = $templateData->attributesToArray();
                $data = Arr::except($data, ['id', 'encrypted_plan_template_id']);
                $data['user_id'] = Auth::user()->id;
                $data['organization_id'] = $organization_id;
                $res = explode(".", $request->start_date);
                $changedDate = $res[0] . "-" . $res[1] . "-" . $res[2];
                $data['start_date'] = Carbon::parse($changedDate)->format('Y-m-d H:i:s');
                $data['phases_editable_by'] = $request->phase_rights;
                $data['assessments_editable_by'] = $request->assessment_rights;
                $data['exercises_courses_editable_by'] = $request->exe_rights;
                $data['patient_id'] = decrypt($id);
                $data['is_released'] = 0;
                $data['plan_owner'] = Auth::user()->id;
                $data['is_sent_to_patient'] = 1;
                $data['is_draft'] = 0;
                // create new Order based on Post's data
                $newPlan = PatientTherapyPlanTemplates::create($data);
                /// add log //create_plan
                $this->addLogOfPlanProcessStatus('create_plan', $newPlan->id, null, decrypt($id));

                $networkData = array();
                $networkData['patient_id'] = decrypt($id);
                $networkData['plan_id'] = $newPlan->id;
                $networkData['organization_id'] = $organization_id;
                $networkData['user_id'] = Auth::user()->id;
                $networkData['draft_id'] = null;
                $networkData['status'] = 1;
                $networkData['is_owner_organization'] = 1;
                $networkData['is_edit_flag'] = 0;
                // create new plan network for another organization.
                $newPlanNetwork = PatientPlanNetwork::create($networkData);
        
                //copy indication data
                $indications = Indication::find($request->indications);
                $newPlan->Indications()->sync($indications);
                //copy body region data
                $body_regions = BodyRegions::find($request->body_regions);
                $newPlan->BodyRegions()->sync($body_regions);
                $total_duration = 0;
                $dates = array();
                $start_date = $data['start_date'];
                // copy plan phase data
                if($templateData['phases'] && count($templateData['phases'])) {
                    foreach($templateData['phases'] as $key => $phase) {
                        $phaseData = $phase->attributesToArray();
                        $phaseData['therapy_plan_templates_id'] =  $newPlan->id;
                        $getUpdatePhase = array_values($templateData['phases']->toArray());
                        $phaseIndex = array_search($phaseData['id'], array_column($getUpdatePhase, 'id'));
                        if($phaseIndex !== false) {
                            $phaseData['name'] =  $getUpdatePhase[$phaseIndex]['name'];
                            $phaseData['duration'] =  $getUpdatePhase[$phaseIndex]['duration'];
                            $phaseData = Arr::except($phaseData, ['id', 'encrypted_phase_id']);
                            $newPhase = PatientPhases::create($phaseData);

                            // copy phase limitation data
                            if($phase['limitations'] && count($phase['limitations'])) {
                                foreach($phase['limitations'] as $limitation) {
                                    $limitationData = $limitation->attributesToArray();
                                    $limitationData = Arr::except($limitationData, ['id']);
                                    $limitationData['phase_id'] =  $newPhase->id;
                                    $newlimitation = PatientLimitations::create($limitationData);
                                }
                            }
                
                            // copy assesment data
                            if($phase['PhaseAssessments'] && count($phase['PhaseAssessments'])) {
                                foreach($phase['PhaseAssessments'] as $assessment) {
                                    $assessmentData = $assessment['TherapyPlanAssessments']->attributesToArray();
                                    $assessmentData = Arr::except($assessmentData, ['id', 'encrypted_assessment_id']);
                                    $assessmentData['user_id'] = Auth::user()->id;
                                    $newassessment = PatientAssessmentTemplates::create($assessmentData);
                    
                                    // copy phase and assessment relation
                                    $assessmentPhaseData['phases_id'] =  $newPhase->id;
                                    $assessmentPhaseData['assessment_templates_id'] =  $newassessment->id;
                                    $newassessmentPhase = PatientPhaseAssessments::create($assessmentPhaseData);

                                    //copy assessment schedules data
                                    $general_schedules_data = [];
                                    if($assessment['TherapyPlanAssessments']['Schedules'] && count($assessment['TherapyPlanAssessments']['Schedules'])) {
                                        foreach($assessment['TherapyPlanAssessments']['Schedules'] as $schedules) {
                                            $scheduleData = $schedules->attributesToArray();
                                            $scheduleData = Arr::except($scheduleData, ['id', 'therapyplan_assessment_templates_id']);
                                            $scheduleData['patient_assessment_templates_id'] =  $newassessment->id; 
                                            $newschedule = PatientAssessmentSchedules::create($scheduleData);
                                            $general_schedules_data[$newschedule->id] = $newschedule->name;
                                        }
                                    }

                                    // copy assessment categories data
                                    if($assessment['TherapyPlanAssessments']['Categories'] && count($assessment['TherapyPlanAssessments']['Categories'])) {
                                        foreach($assessment['TherapyPlanAssessments']['Categories'] as $category) {
                                            if($category['is_active']) {
                                                $categoryData = $category->attributesToArray();
                                                $categoryData = Arr::except($categoryData, ['id', 'encrypted_cat_id', 'therapyplan_assessment_templates_id']);
                                                $categoryData['assessment_template_id'] =  $newassessment->id; 
                                                $newcategory = PatientAssessmentCategories::create($categoryData);
                            
                                                // copy assessment sub categories data
                                                if($category['SubCategories'] && count($category['SubCategories'])) {
                                                    foreach($category['SubCategories'] as $subCategories) {
                                                        if($subCategories['is_active']) {
                                                            $subCategoriesData = $subCategories->attributesToArray();
                                                            $subCategoriesData = Arr::except($subCategoriesData, ['id', 'chart_data', 'encrypted_sub_cat_id', 'translated_unit', 'therapyplan_assessment_category_id', 'therapyplan_assessment_schedules_id', 'schedules_data']);
                                                            $subCategoriesData['assessment_category_id'] =  $newcategory->id;
                                                            //To check the assigned schedules exist or not
                                                            if(!empty($subCategories['therapyplan_assessment_schedules_id']) && $subCategories['schedules_data']) {
                                                                $sche_ids = [];
                                                                foreach($subCategories['schedules_data'] as $sche_key => $sche_data) {
                                                                    $sche_name = $sche_data->name;
                                                                    $filtered_arr = array_filter($general_schedules_data, function ($var) use ($sche_name) {
                                                                        return ($var == $sche_name);
                                                                    });
                                                                    if(!empty($filtered_arr))
                                                                        $sche_ids[] = key($filtered_arr);
                                                                }
                                                                $subCategoriesData['patient_assessment_schedules_id'] =  implode(',', $sche_ids);
                                                            }
                                                            $newsubCategories = PatientAssessmentSubCategories::create($subCategoriesData); 

                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                    
                                    // copy assessment indication data
                                    if($assessment['TherapyPlanAssessments']['Indications'] && count($assessment['TherapyPlanAssessments']['Indications'])) {
                                        foreach($assessment['TherapyPlanAssessments']['Indications'] as $indication) {
                                            $indications = Indication::find($indication->id);
                                            $newassessment->Indications()->attach($indications);
                                        }
                                    }
                    
                                    //copy assessment body region data
                                    if($assessment['TherapyPlanAssessments']['BodyRegions'] && count($assessment['TherapyPlanAssessments']['BodyRegions'])) {
                                        foreach($assessment['TherapyPlanAssessments']['BodyRegions'] as $bodyRegion) {
                                            $bodyRegions = BodyRegions::find($bodyRegion->id);
                                            $newassessment->BodyRegions()->attach($bodyRegions);
                                        }
                                    }
                                }
                            }
                
                            // copy phase exercise data with exercises data
                            if($phase['PhaseExercises'] && count($phase['PhaseExercises'])) {
                                foreach($phase['PhaseExercises'] as $phaseExercise) {
                                    $newexercise = NULL;

                                    $phaseExerciseData = $phaseExercise->attributesToArray();
                                    $phaseExerciseData = Arr::except($phaseExerciseData, ['id', 'frequency_data']);
                                    $phaseExerciseData['exercise_id'] =  $phaseExerciseData['exercise_id'];
                                    $phaseExerciseData['phase_id'] =  $newPhase->id;
                                    $newPhaseExercise = PatientPhaseExercises::create($phaseExerciseData);
                                }
                            }
                
                            // copy course, course exercises, exercises, phase course data
                            if($phase['PhaseCourses'] && count($phase['PhaseCourses'])) {
                                foreach($phase['PhaseCourses'] as $PhaseCourses) {
                                    $newcourses = NULL;
                                    if($PhaseCourses['TherapyPlanCourses']) {
                                        $courses = $PhaseCourses['TherapyPlanCourses'];
                                        $coursesData = $courses->attributesToArray();
                                        $coursesData = Arr::except($coursesData, ['id', 'encrypted_course_id', 'exercise_counts' ,'frequency_data']);
                                        $newcourses = PatientCourses::create($coursesData);
                                    }
                    
                                    $PhaseCoursesData = $PhaseCourses->attributesToArray();
                                    $PhaseCoursesData = Arr::except($PhaseCoursesData, ['id']);
                                    $PhaseCoursesData['course_id'] =  $newcourses ? $newcourses->id : NULL;
                                    $PhaseCoursesData['phase_id'] =  $newPhase->id;
                                    $newPhaseCourses = PatientPhaseCourses::create($PhaseCoursesData);
                    
                                    // copy course indication data
                                    if($courses['Indications'] && count($courses['Indications'])) {
                                        foreach($courses['Indications'] as $indication) {
                                            $indications = Indication::find($indication->id);
                                            $newcourses->Indications()->attach($indications);
                                        }
                                    }
                    
                                    //copy course body region data
                                    if($courses['BodyRegions'] && count($courses['BodyRegions'])) {
                                        foreach($courses['BodyRegions'] as $bodyRegion) {
                                            $bodyRegions = BodyRegions::find($bodyRegion->id);
                                            $newcourses->BodyRegions()->attach($bodyRegions);
                                        }
                                    }
                                    
                                    if($courses['TherapyPlanCourseExercises'] && count($courses['TherapyPlanCourseExercises'])) {
                                        foreach($courses['TherapyPlanCourseExercises'] as $courseExercise) {
                                            $newexerciseForCourse = NULL;
                                            
                                            $courseExerciseData = $courseExercise->attributesToArray();
                                            $courseExerciseData = Arr::except($courseExerciseData, ['id']);
                                            $courseExerciseData['course_id'] =  $newcourses->id;
                                            // $courseExerciseData['exercise_id'] = $newexerciseForCourse ? $newexerciseForCourse->id : NULL;
                                            $newcourseExercise = PatientCourseExercises::create($courseExerciseData);
                                        }
                                    }
                                }
                            }
                        }
            

                    }
                }

                if(!empty($request->phase)) {
                    foreach($request->phase as $phases) {
                        if (!(array_key_exists('exists_id', $phases))) {
                            //for insert the new phases
                            $update_phase = new PatientPhases;
                            $update_phase->therapy_plan_templates_id = $newPlan->id;
                            $update_phase->duration = $phases['duration'];
                            $update_phase->name = $phases['name'];
                            $update_phase->save();
                        }
                    }
                }

                return response()->json(['success' => 'assign patient successfully.' , 'status' => 200], 200);

            }
        }

    }

    // function to add patient's phases in appointment page
    private function addPhasesToMeetings($startDate, $title, $user_id, $phase_id) {
        $PatientsMeetings = new PatientsMeetings();

        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        $organizationPatient = OrganizationPatient::where('user_id', '=', $user_id)->where('organization_id', '=', $organization_id)->get()->first();
        // validation: at a time one patient for related doctor
        $PatientsMeetings->title = $title;
        $PatientsMeetings->user_id = $user_id;
        $PatientsMeetings->start_date = $startDate;
        $PatientsMeetings->end_date = $startDate;
        $PatientsMeetings->is_reminder_sent = 0;
        $PatientsMeetings->description = null;
        $PatientsMeetings->schedule_category_code = 'phase_start';
        $PatientsMeetings->patient_doctor_id = $organizationPatient->assign_doctor;
        $PatientsMeetings->organization_id = $organization_id;
        $PatientsMeetings->place = null;
        $PatientsMeetings->timezone = null;
        $PatientsMeetings->materials =  null;
        $PatientsMeetings->is_verified = 1; //temp default value
        $PatientsMeetings->is_full_day = 1;
        $PatientsMeetings->phase_id = $phase_id;
        $PatientsMeetings->save();
    }

    //function to get the therapy plan popup data
    public function getTherapyPlanAssignPopupData(Request $request, $id = null) {
        $therapyPlanPhasesData = [];
        $model_type = '';
        $body_regions = BodyRegions::all();
        $indications = Indication::all();
        if (!empty($id)) {
            //to get the specific therapy plan template data
            $therapyPlanPhasesData = TherapyPlanTemplates::with(['Phases', 'Indications', 'BodyRegions'])->where('id', decrypt($id))->first();
            $total_duration = 0;
            foreach ($therapyPlanPhasesData->Phases as $main_key => $data) {
                $total_duration = $total_duration + $data->duration;
            }
        }
        return view('common-views.therapy-plan-assign-with-phases-popup', compact('therapyPlanPhasesData', 'id', 'total_duration', 'body_regions', 'model_type', 'indications'));
    }

    //function to save the phase limitations
    public function saveAssignedPhaseDetails(Request $request, $id)
    {
        $phase = PatientPhases::find($id);
        $rules = [
            'phase_name' => ['required'],
            '*.*.name' => 'required',
            '*.*.start_week' => 'required',
            '*.*.start_day' => 'required',
            '*.*.end_week' => 'required',
            '*.*.end_day' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $err_response  = [
                'success' => false,
                'errors' => \Lang::get('lang.fill-all-the-details-msg')
            ];
            return response()->json($err_response, 400);
        }
        //if phase details change then update it
        if (!empty($phase)) {
            $phase->name = $request->phase_name;
            $phase->phase_objectives = isset($request->phase_objectives) && !empty($request->phase_objectives) ? $request->phase_objectives : '';
            $phase->practical_exercises = isset($request->practical_exercises) && !empty($request->practical_exercises) ? $request->practical_exercises : '';
            $phase->duration = $request->week_duration;
            $phase->save();
        }
        $limitations_ids_array = array();
        $existing_limitations_id = array();
        //to validate the start and end data subsquent
        if (!empty($request->limitation)) {
            foreach ($request->limitation as $value) {
                $start_value = (int) ($value["start_week"] . $value["start_day"]);
                $end_value = (int) ($value["end_week"] . $value["end_day"]);
                if ($start_value > $end_value) {
                    $err_response  = [
                        'success' => false,
                        'errors' => \Lang::get('lang.start-end-week-error-message')
                    ];
                    return response()->json($err_response, 400);
                }
            }
        }
        //to save the limitations
        if (!empty($request->limitation)) {
            foreach ($request->limitation as $key => $limitation) {
                if (array_key_exists('existing_id', $limitation)) {
                    // $existing_limitations_id[] = $limitation['existing_id'];
                    $limitation_data = PatientLimitations::find($limitation["existing_id"]);
                } else {
                    $limitation_data = new PatientLimitations;
                }
                $limitation_data->name = $limitation["name"];
                $limitation_data->phase_id = $id;
                $limitation_data->start_week = $limitation["start_week"];
                $limitation_data->start_day = $limitation["start_day"];
                $limitation_data->end_week = $limitation["end_week"];
                $limitation_data->end_day = $limitation["end_day"];
                $limitation_data->save();
                $existing_limitations_id[] = $limitation_data->id;
            }
        }
        $existing_limitations = PatientLimitations::where('phase_id', $id)->select('id')->get();
        if (!empty($existing_limitations)) {
            //created the array of existing exercise-groups-id
            foreach ($existing_limitations as $limitations) {
                $limitations_ids_array[] = $limitations->id;
            }
        }
        //get the deleted limitations
        $deleted_limitations = array_diff($limitations_ids_array, $existing_limitations_id);
        foreach ($deleted_limitations as $id) {
            //to remove the exercise groups from the database
            $delete_limitations = PatientLimitations::destroy($id);
        }
        $message = \Lang::get('lang.phase-limitation-successfully-updates');
        $request->session()->flash('phase-id', $phase->id);
        $request->session()->flash('alert-success', $message);
        return response()->json(['success' => \Lang::get('lang.phase-limitation-successfully-updates'), 'status' => 200], 200);
    }

    //function to show the exercise popup for therapy plan
    public function getAssignedTherapyPlanExercisesPopupData(Request $request, $id = null) {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        $courseData = [];
        $body_regions = BodyRegions::all();
        $indications = Indication::all();
        $materials = ExerciseMaterialsLists::all();
        $type = $request->form_type;
        $patient_id = $request->patient_id;
        $exercisesData = Exercises::with(['BodyRegions'])->where(function($query) use ($organization_id){
                            $query->whereNull('user_id');
                            $query->whereNull('organization_id');
                            $query->orWhere(function($query) use ( $organization_id ) {
                                $query->where('organization_id', $organization_id);
                            });
                        })->get();
        if (!empty($id)) {
            //to get the specific courses data
            $phaseData = PatientPhases::with(['PhaseExercises.Exercises'])->with(['PhaseExercises' => function ($query) {
               // To show the undeleted exercises
                $query->where('status', 1);
            }])->where('id', decrypt($id))->first();
        }
        return view('assign-patient-views.assigned-therapy-plan-exercises', compact('exercisesData','phaseData','id', 'body_regions', 'type', 'indications', 'materials', 'patient_id'));
    }

    //fuunction to save the phase exercises
    public function saveAssignedTherapyPlanExercises(Request $request) {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        $rules = [
            '*.*.frequency' => 'nullable',
        ];
        //to check the duration validation
        if(!empty($request->exercise)) {
            $rules['*.*.frequency'] = 'required';
            foreach($request->exercise as $exercise) {
                if($exercise['type'] == 2) {
                    $duration_res = $exercise['dur_min'] .$exercise['dur_sec'];
                    if($duration_res == "00") {
                        $rules['dur_min'] ='required_without_all:dur_sec';
                        $rules['dur_sec'] = 'required_without_all:dur_min';
                    }
                }
            }
        }
        $validator = Validator::make($request->all(), $rules, [
            'required' => \Lang::get('lang.add-exercise-frequency-error-text'),
            'required_without_all' => \Lang::get('lang.duration-required-error'),
        ]);
        if ($validator->fails()) {
            $err_response  = [
                'success' => false,
                'error' => $validator->messages()
            ];
            return response()->json($err_response, 400);
        }
        if($request->save_type == 'add-model') {
            if(!empty($request->exercise)) {
                foreach($request->exercise as $exe) {
                    $frequency = [];
                    $phase_exercises = New PatientPhaseExercises();
                    $phase_exercises->exercise_id = $exe['exercise_id'];
                    $phase_exercises->phase_id = decrypt($request->phase_id);
                    $phase_exercises->type = $exe['type'];
                    foreach ($exe['frequency'] as $key => $value) {
                        $frequency[$value] = $exe['count']; 
                    }
                    $phase_exercises->frequency = json_encode($frequency);
                    if($exe['type'] == 1) {
                        $phase_exercises->value_of_type = $exe['value_of_type'];
                    } else {
                        $val_of_type = ((int)$exe['dur_min'] * 60) + (int)$exe['dur_sec'];
                        $phase_exercises->value_of_type = $val_of_type;
                    }
                    $phase_exercises->save();
                }
            }
            if(!empty($request->exercise_val) && $request->add_to_catalog == "true") {
                $exercises = Exercises::find(decrypt($request->exercise_val));
                $exercises->patient_id = NULL;
                $exercises->save();
            }
            $message = \Lang::get('lang.exercises-successfully-added-msg');
            $request->session()->flash('phase-id',decrypt($request->phase_id));
            $request->session()->flash('alert-success', $message);
            return response()->json(['success' => \Lang::get('lang.exercises-successfully-added-msg'), 'status' => 200], 200);
        } else if($request->save_type == 'edit-model') {
            $updated_exe_ids = array();
            if(!empty($request->exercise)) {
                foreach($request->exercise as $exe) {
                    $frequency = [];
                    $phase_exercises = New PatientPhaseExercises();
                    if(array_key_exists('exist_phase_exe_id', $exe)) {
                        $phase_exercises = PatientPhaseExercises::find($exe['exist_phase_exe_id']);
                    }
                    $phase_exercises->exercise_id = $exe['exercise_id'];
                    $phase_exercises->phase_id = decrypt($request->phase_id);
                    $phase_exercises->type = $exe['type'];
                    foreach ($exe['frequency'] as $key => $value) {
                        $frequency[$value] = $exe['count']; 
                    }
                    $phase_exercises->frequency = json_encode($frequency);
                    if($exe['type'] == 1) {
                        $phase_exercises->value_of_type = $exe['value_of_type'];
                    } else {
                        $val_of_type = ((int)$exe['dur_min'] * 60) + (int)$exe['dur_sec'];
                        $phase_exercises->value_of_type = $val_of_type;
                    }
                    $phase_exercises->save();
                    $updated_exe_ids[] = $phase_exercises->id;
                }
            }
            $existing_exercises = PatientPhaseExercises::where('phase_id', decrypt($request->phase_id))->select('id')->get();
            $existing_exercises_ids = array();
            if (!empty($existing_exercises)) {
                //created the array of existing categories_id
                foreach ($existing_exercises as $exe) {
                    $existing_exercises_ids[] = $exe->id;
                }
            }
            //get the deleted categories
            $deleted_exercises = array_diff($existing_exercises_ids, $updated_exe_ids);
            foreach ($deleted_exercises as $id) {
                // status change to 0 for deleted exercise
                $delete_categories_data = PatientPhaseExercises::where('id', $id)->update(['status' => 0]);
            }
            if(!empty($request->exercise_val) && $request->add_to_catalog == "true") {
                $exercises = Exercises::find(decrypt($request->exercise_val));
                $exercises->patient_id = NULL;
                $exercises->save();
            }
            $message = \Lang::get('lang.exercises-successfully-added-msg');
            $request->session()->flash('phase-id',decrypt($request->phase_id));
            $request->session()->flash('alert-success', $message);
            return response()->json(['success' => \Lang::get('lang.exercises-successfully-added-msg'), 'status' => 200], 200);
        }
    }

    //function to save the exercise data
    public function saveAssignedExercise(Request $request, $id) {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        $rules = [
            'name' => 'required',
            'description' => 'required',
            'position' => 'required',
            'target' => 'required',
            'body_regions' => 'required',
            'difficulty' => 'required',
            'indications' => 'required',
        ];
        $custom_messages = [
            'required' => \Lang::get('lang.exercise-fields-error'),
            'video.mimes' => \Lang::get('lang.exercise-file-error'),
            'video.mimetypes' => \Lang::get('lang.exercise-file-error'),
            'video.max' => \Lang::get('lang.exercise-file-error'),
            'image.mimes' => \Lang::get('lang.exercise-file-error'),
            'required_without_all' => \Lang::get('lang.image-or-video-required'),
        ];
        if($request->image_exist == 0 && $request->video_exist == 0) {
            $rules['video'] = 'required_without_all:image|mimes:mp4,x-flv,x-mpegURL,MP2T,3gpp,quicktime,x-msvideo,x-ms-wmv,mov,avi|mimetypes:video/*|max:100000';
            $rules['image'] = 'required_without_all:video|mimes:jpeg,png,jpg,gif,svg|mimetypes:image/*';
        } else {
            $rules['video'] = 'nullable|mimes:mp4,x-flv,x-mpegURL,MP2T,3gpp,quicktime,x-msvideo,x-ms-wmv,mov,avi|mimetypes:video/*|max:100000';
            $rules['image'] = 'nullable|mimes:jpeg,png,jpg,gif,svg|mimetypes:image/*';
        }
        //to check the validation
        $validator = Validator::make($request->all(), $rules, $custom_messages);
        if ($validator->fails()) {
            $err_response  = [
                'success' => false,
                'error' => $validator->messages()
            ];
            return response()->json($err_response, 400);
        }
        //to copy or new create exercise
        if($request->type == 'create-exercise') {
            $exercise = New Exercises;
            $exercise->name = $request->name;
            $exercise->description = $request->description;
            $exercise->position = $request->position;
            $exercise->target = $request->target;
            $exercise->difficulty = $request->difficulty;
            $exercise->user_id = decrypt($id);
            $exercise->patient_id = decrypt($id);
            $exercise->organization_id = $organization_id;
            $exercise->created_by = Auth::user()->id;
            $exercise->updated_by = Auth::user()->id;
            //if file upload
            if($request->has('image') || $request->has('video')) {
                if ($request->has('image') && $request->image != '' && $request->image != NULL) {
                    $image = $request->file('image');            
                    $name = preg_replace('/\s+/', '', $request->name) . '_' . time();
                    // Define folder path
                    $folder = '/exercises/';
                    // Make a file path where image will be stored [ folder path + file name + file extension]
                    $filePath = $folder . $name . '.' . $image->getClientOriginalExtension();
                    // Upload image
                    $this->uploadOne($image, $folder, 'public', $name);
                    // Set user profile image path in database to filePath
                    $exercise->image = $filePath;
                    $exercise->video = NULL;
                }
                if ($request->has('video') && $request->video != '' && $request->video != NULL) {
                    $video = $request->file('video');            
                    $name = preg_replace('/\s+/', '', $request->name) . '_' . time();
                    // Define folder path
                    $folder = '/exercises/videos/';
                    // Make a file path where image will be stored [ folder path + file name + file extension]
                    $filePath = $folder . $name . '.' . $video->getClientOriginalExtension();
                    // Upload image
                    $this->uploadOne($video, $folder, 'public', $name);
                    // Set user profile image path in database to filePath
                    $exercise->video = $filePath;
                    if(empty($request->image)) {
                        $exercise->image = Helper::getThumbnailForExerciseVideo($name, $filePath);    
                    }
                    
                }
            }
            $exercise->save();
            //to save the body_regions in the exercise_body_regions table(pivot)
            $body_regions = BodyRegions::find(explode(',',$request->body_regions));
            $exercise->BodyRegions()->attach($body_regions);

            //to save the indications in the exercise_indications table(pivot)
            $indications = Indication::find(explode(',',$request->indications));
            $exercise->Indications()->attach($indications);

            //to save the materials in the exercise_materials table(pivot)
            $exe_materials = ExerciseMaterialsLists::find(explode(',',$request->tools));
            $exercise->Materials()->attach($exe_materials);

            $message = \Lang::get('lang.exercise-successfully-created-msg');
            $request->session()->flash('alert-success', $message);
            return response()->json(['success' => \Lang::get('lang.exercise-successfully-created-msg'), 'status' => 200, 'id' => encrypt($exercise->id) ], 200);
        }
    }

    //function to show the assessment modal popup 
    public function getAssessmentPopupData(Request $request, $id = null ) {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        $type = $request->type;
        $assessmentData = [];
        $indications = Indication::all();
        $body_regions = BodyRegions::all();
        $assessment_type = 'assessments';
        $assessments = AssessmentTemplates::where(function($query) use ( $organization_id )  {
                            $query->whereNull('user_id');
                            $query->whereNull('organization_id');
                            $query->orWhere(function($query) use ( $organization_id ) {
                                $query->where('organization_id', $organization_id);
                            });
                        })->get();
        if (!empty($id)) {
            //to get the specific assessment data
            $assessmentData = AssessmentTemplates::with(['Categories.SubCategories.Schedules', 'Indications', 'BodyRegions', 'Schedules'])->where('id', decrypt($id))->first();
            if($request->type == 'edit-therapy-plan-assessment') {
                $assessmentData = PatientAssessmentTemplates::with(['Categories.SubCategories.Schedules', 'Indications', 'BodyRegions', 'Schedules'])
                ->with(['Categories' => function ($query) {
                    // To get category if status is 1
                    $query->where('status', 1)
                            ->with(['SubCategories' => function ($query) {
                                // To get sub-category if status is 1
                                $query->where('status', 1);
                            }]);
                }])->where('id', decrypt($id))->first();
            }
        }
        if($request->usage == 'for-assigned') {
            $for = 'assign-patient';
            return view('common-views.assessment-modal-template', compact('assessmentData', 'id', 'indications', 'body_regions', 'type', 'assessment_type', 'assessments', 'for'));
        }
        return view('assign-patient-views.assigned-assessment-popup', compact('assessmentData', 'id', 'indications', 'body_regions', 'type'));
    }
    //function to save the assessment data
    public function saveAssessment(Request $request, $id = null) {
        $procedure_types_array = ['pain-level', 'rating', 'sensors', 'scoring/questionnaire'];
        $only_single_point_units_array = ['bpm', 'mmhg', 'mikrovolts', 'step-sensor', 'koos', 'lsi', 'rtaa'];
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        //to check atleast one category and subcategory exist
        if(empty($request->category)) {
            return response()->json(['error' => \Lang::get('lang.add-category/assessment'), 'status' => 400], 400);
        } else {
            foreach($request->category as $main_category) {
                if(!array_key_exists('subcategory', $main_category)) {
                    return response()->json(['error' => \Lang::get('lang.add-category/assessment'), 'status' => 400], 400);    
                }
            }
        }
        $rules = [
            'schedules' => 'required',
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $err_response  = [
                'success' => false,
                'errors' => $validator->messages()
            ];
            return response()->json(['error' => \Lang::get('lang.add-schedules-message'), 'status' => 400], 400);
        }
        if($request->type == 'create-therapy-plan-assessment') {
            $save_for = 'therapy-plan';
            $treatementPlan = new PatientAssessmentTemplates();
            $treatementPlan->template_name = $request->template_name;
            $treatementPlan->description = $request->description;
            $treatementPlan->user_id = Auth::user()->id;
            $treatementPlan->organization_id = $organization_id;
            $treatementPlan->updated_by = Auth::user()->id;
            $treatementPlan->save();
            //to save the indications in the assessment_indications table(pivot)
            $indications = Indication::find($request->indications);
            $treatementPlan->Indications()->attach($indications);
            //to save the body_regions in the assessment_body_regions table(pivot)
            $body_regions = BodyRegions::find($request->body_regions);
            $treatementPlan->BodyRegions()->attach($body_regions);
            //To save the schedules data
            $schedules_array = [];
            if(!empty($request->schedules)) {
                foreach($request->schedules as $key => $schedules) {
                    $schedules_data = new PatientAssessmentSchedules;
                    $schedules_data->patient_assessment_templates_id = $treatementPlan->id;
                    $schedules_data->name = $schedules['name'];
                    $schedules_data->time = $schedules['time'];
                    $schedules_data->save();
                    $schedules_array[$key] = $schedules_data->id;
                }
            }
            if(!empty($request->category)) {
                foreach($request->category as $main_category) {
                    if(array_key_exists('is_active', $main_category)) {
                        $category = New PatientAssessmentCategories;
                        $category->name = $main_category['name'];
                        $category->is_active = array_key_exists('is_active', $main_category) ? 1 : 0;
                        $category->assessment_template_id = $treatementPlan->id;
                        $category->save();
                        if(array_key_exists('subcategory', $main_category)) {
                            foreach($main_category['subcategory']  as $sub_category) {
                                if(array_key_exists('is_active', $sub_category)) {
                                    $subcategory = New PatientAssessmentSubCategories;
                                    $subcategory->name = $sub_category['name'];
                                    $subcategory->type = $sub_category['type'];
                                    $subcategory->is_active = array_key_exists('is_active', $sub_category) ? 1 : 0;
                                    $subcategory->title = $sub_category['title'];
                                    $subcategory->unit = $sub_category['unit'];
                                    if(!in_array($subcategory->unit, $procedure_types_array)) {
                                        $subcategory->measurement_type = $sub_category['measurement_type'];
                                        $subcategory->start_value = $sub_category['start_value'];
                                        $subcategory->end_value = $sub_category['end_value'];
                                        $subcategory->target = $sub_category['target'];
                                        $subcategory->target_area = $sub_category['target_area'];
                                    }
                                    if(!in_array($subcategory->unit, $only_single_point_units_array))
                                    {
                                        $subcategory->comparison_range = $sub_category['comparison_range'];
                                    }
                                    $subcategory->description = $sub_category['description'];
                                    if(array_key_exists('is_patient_assessment', $sub_category)) {
                                        $subcategory->is_patient_assessment = 1;
                                        $subcategory->routine = $sub_category['routine'];
                                        $subcategory->frequency = implode(",",$sub_category['frequency']);
                                        $sche_ids = [];
                                        foreach($sub_category['schedule_id'] as $sche_key => $sche_value) {
                                            $filtered_arr = array_filter($schedules_array, function ($var, $key_q) use ($sche_value) {
                                                return ((int)$key_q == (int)$sche_value);
                                            }, ARRAY_FILTER_USE_BOTH);
                                            if(!empty($filtered_arr))
                                                $sche_ids[] = (int)array_shift($filtered_arr);
                                        }
                                        $subcategory->patient_assessment_schedules_id = implode(',', $sche_ids);
                                    }
                                    $subcategory->assessment_category_id = $category->id;
                                    if(array_key_exists('save_max_target', $sub_category) && !empty($sub_category['max_target'])) {
                                        $subcategory->max_target = $sub_category['max_target'];
                                    } else {
                                        $subcategory->max_target = NULL;
                                    }
                                    $subcategory->save();
                                }
                            }
                        }
                    }
                }
            }
            if($request->has('phase_id') && !empty($request->phase_id)) {
                $phase_assessments = New PatientPhaseAssessments;
                $phase_assessments->phases_id = decrypt($request->phase_id);
                $phase_assessments->assessment_templates_id = $treatementPlan->id;
                $phase_assessments->save();
            }
            $message = \Lang::get('lang.assessment-successfully-created-msg');
            $request->session()->flash('phase-id',decrypt($request->phase_id));
            $request->session()->flash('alert-success', $message);
        } else if($request->type == 'edit-therapy-plan-assessment') {
            $save_for = 'therapy-plan';
            //to edit the template 
            $treatementPlan = PatientAssessmentTemplates::find(decrypt($id));
            $updated_cat_ids = array();
            $updates_schedules_ids = array();
            $updated_sub_cat_ids = array();
            $treatementPlan->template_name = $request->template_name;
            $treatementPlan->description = $request->description;
            $treatementPlan->updated_by = Auth::user()->id;
            $treatementPlan->save();
            //to save the indications in the assessment_indications table(pivot)
            $indications = Indication::find($request->indications);
            $treatementPlan->Indications()->sync($indications);
            //to save the body_regions in the assessment_body_regions table(pivot)
            $body_regions = BodyRegions::find($request->body_regions);
            $treatementPlan->BodyRegions()->sync($body_regions);

            // To save the schedules 
            $schedules_array = [];
            if(!empty($request->schedules)) {
                foreach($request->schedules as $key => $schedules) {
                    $schedules_data = new PatientAssessmentSchedules;
                    //if existing id then update the data
                    if(array_key_exists('exist_id', $schedules)) {
                        $schedules_data = PatientAssessmentSchedules::find($schedules['exist_id']);
                    }
                    $schedules_data->patient_assessment_templates_id = $treatementPlan->id;
                    $schedules_data->name = $schedules['name'];
                    $schedules_data->time = $schedules['time'];
                    $schedules_data->save();
                    $updates_schedules_ids[] = $schedules_data->id;
                    $schedules_array[$key] = $schedules_data->id;
                }
            }
            if(!empty($request->category)) {
                foreach($request->category as $main_category) {
                    $category = New PatientAssessmentCategories;
                    if(array_key_exists('is_exist', $main_category)) {
                        $category = PatientAssessmentCategories::find($main_category['is_exist']);
                    }
                    $category->name = $main_category['name'];
                    $category->is_active = array_key_exists('is_active', $main_category) ? 1 : 0;
                    $category->assessment_template_id = $treatementPlan->id;
                    $category->save();
                    $updated_cat_ids[] = $category->id;
                    if(array_key_exists('subcategory', $main_category)) {
                        foreach($main_category['subcategory']  as $sub_category) {
                            $subcategory = New PatientAssessmentSubCategories;
                            if(array_key_exists('is_exist', $sub_category)) {
                                $subcategory = PatientAssessmentSubCategories::find($sub_category['is_exist']);
                            }
                            $subcategory->name = $sub_category['name'];
                            $subcategory->type = $sub_category['type'];
                            $subcategory->is_active = array_key_exists('is_active', $sub_category) ? 1 : 0;
                            $subcategory->title = $sub_category['title'];
                            $subcategory->unit = $sub_category['unit'];
                            $subcategory->measurement_type = NULL;
                            $subcategory->start_value =NULL;
                            $subcategory->end_value = NULL;
                            $subcategory->target = NULL;
                            $subcategory->target_area = NULL;
                            $subcategory->comparison_range = NULL;
                            $subcategory->is_patient_assessment = 0;
                            $subcategory->routine = NULL;
                            $subcategory->frequency = NULL;
                            $subcategory->patient_assessment_schedules_id = NULL;
                            if(!in_array($subcategory->unit, $procedure_types_array)) {
                                $subcategory->measurement_type = $sub_category['measurement_type'];
                                $subcategory->start_value = $sub_category['start_value'];
                                $subcategory->end_value = $sub_category['end_value'];
                                $subcategory->target = $sub_category['target'];
                                $subcategory->target_area = $sub_category['target_area'];
                            }
                            if(!in_array($subcategory->unit, $only_single_point_units_array)) {
                                $subcategory->comparison_range = $sub_category['comparison_range'];
                            }
                            if(array_key_exists('is_patient_assessment', $sub_category)) {
                                $subcategory->is_patient_assessment = 1;
                                $subcategory->routine = $sub_category['routine'];
                                $subcategory->frequency = implode(",",$sub_category['frequency']);
                                $sche_ids = [];
                                foreach($sub_category['schedule_id'] as $sche_key => $sche_value) {
                                    $filtered_arr = array_filter($schedules_array, function ($var, $key_q) use ($sche_value) {
                                        return ((int)$key_q == (int)$sche_value);
                                    }, ARRAY_FILTER_USE_BOTH);
                                    if(!empty($filtered_arr))
                                        $sche_ids[] = (int)array_shift($filtered_arr);
                                }
                                $subcategory->patient_assessment_schedules_id = implode(',', $sche_ids);
                            }
                            $subcategory->description = $sub_category['description'];
                            $subcategory->assessment_category_id = $category->id;
                            if(array_key_exists('save_max_target', $sub_category) && !empty($sub_category['max_target'])) {
                            $subcategory->max_target = $sub_category['max_target'];
                            } else {
                                $subcategory->max_target = NULL;
                            }
                            $subcategory->save();
                            $updated_sub_cat_ids[] = $subcategory->id;
                        }
                    }
                }
            }
            //to get all the shcedules id
            $existing_schedules = PatientAssessmentSchedules::where('patient_assessment_templates_id', decrypt($id))->select('id')->get();
            $existing_schedules_ids = array();
            if (!empty($existing_schedules)) {
                //created the array of existing schedules_id
                foreach ($existing_schedules as $sch_data) {
                    $existing_schedules_ids[] = $sch_data->id;
                }
            }
            $deleted_schedules = array_diff($existing_schedules_ids, $updates_schedules_ids);
            foreach ($deleted_schedules as $deleted_sche_id) {
                //to remove the schedules
                $delete_schedules_data = PatientAssessmentSchedules::destroy($deleted_sche_id);
            }

            //get all the categories id 
            $existing_categories = PatientAssessmentCategories::where('assessment_template_id', decrypt($id))->select('id')->get();
            $existing_cate_ids = array();
            $existing_sub_cat_ids = array();
            if (!empty($existing_categories)) {
                //created the array of existing categories_id
                foreach ($existing_categories as $category_data) {
                    $existing_cate_ids[] = $category_data->id;
                }
            }
            //get all the sub categories id 
            $existing_sub_categories = PatientAssessmentSubCategories::with(['AssessmentCategories.AssessmentTemplates'])->select('id')
                                ->whereHas('AssessmentCategories.AssessmentTemplates', function ($query) use ($id) {
                                    $query->where('id', decrypt($id));
                                })->get();
            if (!empty($existing_sub_categories)) {
                //created the array of existing sub categories_id
                foreach ($existing_sub_categories as $sub_cat_data) {
                    $existing_sub_cat_ids[] = $sub_cat_data->id;
                }
            }
            //get the deleted categories
            $deleted_categories = array_diff($existing_cate_ids, $updated_cat_ids);
            foreach ($deleted_categories as $id) {
                //to remove the categories
                $delete_categories_data = PatientAssessmentCategories::where('id', $id)->update(['status' => 0]);
            }
            //get deleted sub_categories
            $deleted_sub_categories = array_diff($existing_sub_cat_ids, $updated_sub_cat_ids);
            foreach ($deleted_sub_categories as $id) {
                //to remove the sub categories
                $deleted_sub_categories_data = PatientAssessmentSubCategories::where('id', $id)->update(['status' => 0]);
            }
            $message = \Lang::get('lang.assessment-successfully-updated-msg');
            $request->session()->flash('phase-id',decrypt($request->phase_id));
            $request->session()->flash('alert-success', $message);
        }
        return response()->json(['success' => \Lang::get('lang.assessment-successfully-created-msg'), 'status' => 200, 'id' => encrypt($treatementPlan->id), 'save_for' => $save_for], 200);
    }

     //function to show the courses modal popup 
    public function getCoursePopupData(Request $request, $id = null ) {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        $courseData = [];
        $body_regions = BodyRegions::all();
        $indications = Indication::all();
        $materials = ExerciseMaterialsLists::all();
        $courses = [];
        $model = $request->model;
        $patient_id = $request->patient_id;
        $exercisesData = Exercises::with(['BodyRegions'])->where(function($query) use ($organization_id){
                            $query->whereNull('user_id');
                            $query->whereNull('organization_id');
                            $query->orWhere(function($query) use ( $organization_id ) {
                                $query->where('organization_id', $organization_id);
                            });
                        })->get();
        $course_type = 'courses';      
        if($request->has('for') && $request->for == 'assign-therapy-plan') {
            $course_type = 'assign_therapy_plan';
            $courses = Courses::with(['Indications', 'BodyRegions', 'CourseExercises.Exercises'])->where(function($query) use ($organization_id){
                                $query->whereNull('user_id');
                                $query->whereNull('organization_id');
                                $query->orWhere(function($query) use ( $organization_id ) {
                                    $query->where('organization_id', $organization_id);
                                });
                                $query->whereNull('phase_id');
                            })->get();
        }
        if (!empty($id)) {
            if($request->has('for') && $request->for == 'assign-therapy-plan') {
                $courseData = PatientCourses::with(['CourseExercises.Exercises', 'BodyRegions', 'Indications'])->where('id', decrypt($id))->first();
                // dd($courseData);
            }
        }
        return view('assign-patient-views.course-add-edit-popup', compact('exercisesData', 'courseData', 'id', 'body_regions', 'indications', 'courses', 'course_type', 'materials', 'model', 'patient_id'));
    }

     //function to save the course data
    public function saveCourse(Request $request, $id = null) {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        $rules = [
            'name' => 'required',
            'description' => 'required',
            'indication' => 'required',
            'body_regions' => 'required',
            'exercise' => 'required',
            'frequency'=> 'required',
        ];
        if($request->is_image_exist == 0){
            $rules['image'] = 'required|mimes:jpeg,png,jpg,gif,svg|mimetypes:image/*';
        } else {
            $rules['image'] = 'nullable|mimes:jpeg,png,jpg,gif,svg|mimetypes:image/*';
        }
        //to check the duration validation
        if(!empty($request->exercise)) {
            foreach($request->exercise as $exercise) {
                if($exercise['type'] == 2 && array_key_exists('exercise_id', $exercise)) {
                    $duration_res = $exercise['dur_min'] .$exercise['dur_sec'];
                    if($duration_res == "00") {
                        $rules['dur_min'] ='required_without_all:dur_sec';
                        $rules['dur_sec'] = 'required_without_all:dur_min';
                    }
                }
            }
        }
        //to check the validation
        $validator = Validator::make($request->all(), $rules, [
            'required_without_all' => \Lang::get('lang.duration-required-error'),
        ]);
        if ($validator->fails()) {
            $err_response  = [
                'success' => false,
                'error' => $validator->messages()
            ];
            return response()->json($err_response, 400);
        }
         //to copy or new create exercise
        if ($request->form_type == 'add-therapy-plan-course') {
            //to add the corse for therapy plan
            $course = New PatientCourses;
            $course->name = $request->name;
            $course->description = $request->description;
            $course->duration = $request->course_time;
            $course->round = $request->round;
            foreach ($request->frequency as $key => $value) {
                $frequency[$value] = $request->count; 
            }
            $course->frequency = json_encode($frequency);
            $course->user_id = Auth::user()->id;
            $course->organization_id = $organization_id;
            $course->updated_by = Auth::user()->id;

            //if file upload
            if($request->has('image') && $request->image != '' && $request->image != NULL) {
                    $image = $request->file('image');            
                    $name = preg_replace('/\s+/', '', $course->name) . '_' . time();
                    // Define folder path
                    $folder = '/courses/';
                    // Make a file path where image will be stored [ folder path + file name + file extension]
                    $filePath = $folder . $name . '.' . $image->getClientOriginalExtension();
                    // Upload image
                    $this->uploadOne($image, $folder, 'public', $name);
                    // Set user profile image path in database to filePath
                    $course->image = $filePath;

            } else if(!empty($request->copied_from)) {
                //if file not uploaded then get it from the exercise that copied
                $old_courses = Courses::find(decrypt($request->copied_from));
                if(!empty($old_courses->image)) {
                    //copy the image from the main exercise for new exercise
                    $file = storage_path($old_courses->image);
                    $file_extenstion = pathinfo($file, PATHINFO_EXTENSION);
                    $destination = '/courses/'.preg_replace('/\s+/', '', $course->name) . '_' . time() . '.'.$file_extenstion;
                    Storage::disk('public')->copy($old_courses->image, $destination);
                    $course->image = $destination;
                }
            }
            $course->save();
            if($request->has('phase_id') && !empty($request->phase_id)) {
                $phase_course = New PatientPhaseCourses;
                $phase_course->phase_id = decrypt($request->phase_id);
                $phase_course->course_id = $course->id;
                $phase_course->save();
            }
            
            //to save the body_regions in the therapyplan_courses_body_regions table(pivot)
            $body_regions = BodyRegions::find($request->body_regions);
            $course->BodyRegions()->attach($body_regions);
            $indications = Indication::find($request->indication);
            $course->Indications()->attach($indications);
            //to save the exercises
            if(!empty($request->exercise)) {
                foreach($request->exercise as $exercise) {
                    $course_exercises = new PatientCourseExercises();
                    $course_exercises->course_id = $course->id;
                    $course_exercises->type = $exercise['type'];
                    if($exercise['type'] == 1  || !array_key_exists('exercise_id', $exercise)) {
                        $course_exercises->value_of_type = $exercise['value_of_type'];
                    } else {
                        $val_of_type = ((int)$exercise['dur_min'] * 60) + (int)$exercise['dur_sec'];
                        $course_exercises->value_of_type = $val_of_type;
                    }
                    if(array_key_exists('exercise_id', $exercise)) {
                        $course_exercises->exercise_id = $exercise['exercise_id'];   
                    }
                    $course_exercises->save();
                }    
            }
            if(!empty($request->exercise_id) && $request->save_to_catalog_exe == 'true') {
                $exercises = Exercises::find(decrypt($request->exercise_id));
                $exercises->patient_id = NULL;
                $exercises->save();
            }
            $message = \Lang::get('lang.course-successfully-created-msg');
            $request->session()->flash('phase-id', decrypt($request->phase_id));
            $request->session()->flash('alert-success', $message);
            return response()->json(['success' => \Lang::get('lang.course-successfully-updated-msg'), 'status' => 200], 200);
        } else if ($request->form_type == 'edit-therapy-plan-course') {
            // to edit the phase courses
            $course = PatientCourses::find(decrypt($id));
            $course->name = $request->name;
            $course->description = $request->description;
            $course->duration = $request->course_time;
            $course->round = $request->round;
            foreach ($request->frequency as $key => $value) {
                $frequency[$value] = $request->count; 
            }
            $course->frequency = json_encode($frequency);
            $course->updated_by = Auth::user()->id;
            //if file upload
            if($request->has('image') && $request->image != '' && $request->image != NULL) {
                    //if new file uploaded then remove existing image file         
                    if ($course->image != '' && $course->image != NULL) {
                        $file_name = explode('/', $course->image)[2];
                        Storage::delete('public/courses/' . $file_name);
                    }
                    $image = $request->file('image');            
                    $name = preg_replace('/\s+/', '', $request->name) . '_' . time();
                    // Define folder path
                    $folder = '/courses/';
                    // Make a file path where image will be stored [ folder path + file name + file extension]
                    $filePath = $folder . $name . '.' . $image->getClientOriginalExtension();
                    // Upload image
                    $this->uploadOne($image, $folder, 'public', $name);
                    // Set user profile image path in database to filePath
                    $course->image = $filePath;
            }
            $course->save();
            $updated_exe_ids = array();
            //to save the body_regions in the therapyplan_courses_body_regions table(pivot)
            $body_regions = BodyRegions::find($request->body_regions);
            $course->BodyRegions()->sync($body_regions);
            $indications = Indication::find($request->indication);
            $course->Indications()->sync($indications);
            if(!empty($request->exercise)) {
                foreach($request->exercise as $exercise) {
                    $course_exercises = new PatientCourseExercises;
                    $course_exercises->course_id = $course->id;
                    $course_exercises->type = $exercise['type'];
                    if($exercise['type'] == 1 || !array_key_exists('exercise_id', $exercise)) {
                        $course_exercises->value_of_type = $exercise['value_of_type'];
                    } else {
                        $val_of_type = ((int)$exercise['dur_min'] * 60) + (int)$exercise['dur_sec'];
                        $course_exercises->value_of_type = $val_of_type;
                    }
                    if(array_key_exists('exercise_id', $exercise)) {
                        $course_exercises->exercise_id = $exercise['exercise_id'];   
                    }
                    $course_exercises->save();
                    $updated_exe_ids[] = $course_exercises->id;
                }    
            }
            if(!empty($request->exercise_id) && $request->save_to_catalog_exe == 'true') {
                $exercises = Exercises::find(decrypt($request->exercise_id));
                $exercises->patient_id = NULL;
                $exercises->save();
            }
            $existing_exercises = PatientCourseExercises::where('course_id', decrypt($id))->select('id')->get();
            $existing_exercises_ids = array();
            if (!empty($existing_exercises)) {
                //created the array of existing categories_id
                foreach ($existing_exercises as $exe) {
                    $existing_exercises_ids[] = $exe->id;
                }
            }
            //get the deleted categories
            $deleted_exercises = array_diff($existing_exercises_ids, $updated_exe_ids);
            foreach ($deleted_exercises as $id) {
                //to remove the exercises
                $delete_categories_data = PatientCourseExercises::destroy($id);
            }
            $message = \Lang::get('lang.course-successfully-updated-msg');
            $request->session()->flash('phase-id', decrypt($request->phase_id));
            $request->session()->flash('alert-success', $message);
            return response()->json(['success' => \Lang::get('lang.course-successfully-updated-msg'), 'status' => 200], 200);
        }
    }

    //function to abort plan or end phase
    public function removeAssignedPlanTemplates(Request $request) {
        $plan_template = PatientTherapyPlanTemplates::where('patient_id', $request->patient_id)->where('is_complete', 0)->update(['aborted' => 1, 'is_released' => 0]);
        return response()->json(['success' => 'Removed successfully', 'status' => 200, 'for' => 'abort-assigned-plan'], 200);
    }

    // function to delete appoitnmetn while aborting the phases plan
    private function removePhasesFromAppointment($template_id) {
        // get phases from template ID
        $patientPhases = PatientPhases::where('therapy_plan_templates_id', '=', $template_id)->get();
        if ($patientPhases) {
            foreach($patientPhases as $phase) {
                $phase_id = $phase->id;
                if ($phase_id) {
                    $delete_events = PatientsMeetings::where('phase_id', '=', $phase_id)->delete();
                }
            }
        }
    }

    //function to open the analytics page
    public function getpatientAnalytics(Request $request, $id) {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        //get assigned plan details        
        // defining varibales 
        $patient_plan_networks_data = null;
        $patient_plan_request_data = null;
        $plan_network_data = null;
        $assign_plans_data = PatientTherapyPlanTemplates::with(['Phases.Limitations', 'Phases.PhaseCourses','Phases.PhaseAssessments.PatientAssessmentTemplates.Categories.SubCategories.Measurement','Indications', 'BodyRegions', 'Phases.PhaseExercises.Exercises', 'Phases.PhaseCourses.PatientCourse.CourseExercises.Exercises', 'Phases.PhaseCourses.PatientCourse.BodyRegions', 'AllExercises', 'AllCourses', 'AllAssessments','Phases.PhaseAssessments.AssessmentCategories.SubCategories.Measurement', 'PatientPlanNetwork'])
        ->where('patient_id', decrypt($id))->where('is_draft', 0)->where('is_complete', 0)->where('aborted', 0)->first();
        if ($assign_plans_data) {
            $patient_plan_networks_data = $assign_plans_data->PatientPlanNetwork;
            $plan_network_data = $assign_plans_data->PatientPlanNetwork->where('organization_id', $organization_id)->first();
            
            if ($plan_network_data) {
                if ($plan_network_data->draft_id) {
                    $assign_plans_data = PatientTherapyPlanTemplates::with(['Phases.Limitations', 'Phases.PhaseCourses','Phases.PhaseAssessments.PatientAssessmentTemplates.Categories.SubCategories.Measurement','Indications', 'BodyRegions', 'Phases.PhaseExercises.Exercises', 'Phases.PhaseCourses.PatientCourse.CourseExercises.Exercises', 'Phases.PhaseCourses.PatientCourse.BodyRegions', 'AllExercises', 'AllCourses', 'AllAssessments','Phases.PhaseAssessments.AssessmentCategories.SubCategories.Measurement', 'PatientPlanNetwork'])
                    ->where('patient_id', decrypt($id))->where('is_draft', 1)->where('is_complete', 0)->where('aborted', 0)->first();
                }
            } else {
                $message = \Lang::get('lang.access_denied');
                $request->session()->flash('alert-danger', $message);
                return redirect()->route('patients.base-data', $id);
            }
        }
        // dd($assign_plans_data);
        $dates = array();
        $total_duration = 0;
        $set_active = 0;
        if (!empty($assign_plans_data)) {
            foreach ($assign_plans_data->Phases as $main_key => $data) {
                $start_date = $assign_plans_data->start_date;
                $total_duration = $total_duration + $data->duration;
                //Set the start/end date for every phases
                if ($main_key == 0) {
                    $dates['start_date'][$main_key] = Carbon::parse($start_date);
                    $dates['end_date'][$main_key] = Carbon::parse($start_date)->addWeeks($data->duration)->subDay(1);
                } else {
                    $dates['start_date'][$main_key] = Carbon::parse($dates['end_date'][$main_key - 1])->addDay();
                    $dates['end_date'][$main_key] = Carbon::parse($dates['start_date'][$main_key])->addWeeks($data->duration)->subDay(1);
                }
                $data->start_date = $dates['start_date'][$main_key];
                $data->end_date = $dates['end_date'][$main_key];
                if (Carbon::now()->startOfDay()->gte(Carbon::parse($data->start_date)->startOfDay()) && Carbon::now()->startOfDay()->lte(Carbon::parse($data->end_date)->startOfDay())) {
                    $set_active = 1;
                }
                //To set the measurements value of assessment sub categories
                if(!$data->PhaseAssessments->isEmpty()) {
                    foreach($data->PhaseAssessments[0]->AssessmentCategories as $category) {
                        foreach($category->SubCategories as $subcat) {
                            $subcat->last_measured_value = '-';
                            $subcat->last_measured_date = '';
                            $subcat->total_measurements_values = 0;
                            $subcat->measurements_counts = 0;
                            $measurements = PatientAssessmentMeasurements::where('patient_assessment_sub_categories_id', $subcat->id)->orderBy('date', 'ASC')->get();
                            if(count($measurements)) {
                                foreach($measurements as $measurement) {
                                    // To check the measurement date must be in phase interval
                                    if (Carbon::parse($measurement->date)->startOfDay()->gte(Carbon::parse($data->start_date)->startOfDay()) && Carbon::parse($measurement->date)->startOfDay()->lte(Carbon::parse($data->end_date)->startOfDay())) {
                                            $subcat->measurements_counts = $subcat->measurements_counts + 1;
                                            $subcat->total_measurements_values  = $subcat->total_measurements_values + (int)$measurement->value;
                                            $subcat->last_measured_value = (int)$measurement->value;
                                            $subcat->last_measured_date = $measurement->date;
                                    }   
                                }
                            }
                        }
                    }
                }
                //To set the weekly data
                $phase_dates = array();
                for($i = 1; $i <= $data->duration; $i++) {
                    // Set the weekly start and end date of each phase
                    if($i == 1) {
                        $phase_dates[$i]['phase_weekly_start_date'] = $data->start_date;
                        $phase_dates[$i]['phase_weekly_end_date'] = Carbon::parse($data->start_date)->addWeeks(1)->subDay(1);
                    } else {
                        $phase_dates[$i]['phase_weekly_start_date'] = Carbon::parse($phase_dates[$i-1]['phase_weekly_end_date'])->addDay();
                        if(Carbon::parse($phase_dates[$i]['phase_weekly_start_date'])->addWeeks(1)->gt(Carbon::parse($data->end_date))) {
                            $phase_dates[$i]['phase_weekly_end_date'] = $data->end_date;
                        } else {
                            $phase_dates[$i]['phase_weekly_end_date'] = Carbon::parse($phase_dates[$i]['phase_weekly_start_date'])->addWeeks(1)->subDay(1);
                        }
                    }
                    
                    //To check the current week 
                    if (Carbon::now()->startOfDay()->gte(Carbon::parse($phase_dates[$i]['phase_weekly_start_date'])->startOfDay()) && Carbon::now()->startOfDay()->lte(Carbon::parse($phase_dates[$i]['phase_weekly_end_date'])->startOfDay())) {
                        $exercise = [];
                        $course = [];
                        //get all exercises of this phase
                        $individual_exercises = PatientPhaseExercises::with('Exercises.Materials')->where('phase_id', $data->id)->get();
                        //get all courses of this phase
                        $course_of_exercises = PatientPhaseCourses::with('Course.CourseExercises.Exercises.Materials')->withCount(['Course'])->where('phase_id', $data->id)->get();
                        $total_exercises_count = 0;
                        $total_completed_exercises = 0;
                        $total_aborted_exercises = 0;
                        $total_courses_count = 0;
                        $total_completed_courses = 0;
                        $total_aborted_courses = 0;
                        // To get the exercise count 
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
                                $startOfWeek = Carbon::parse($phase_dates[$i]['phase_weekly_start_date'])->startOfDay();
                                // To get the exercise count as per recorded
                                for($k=0; $k<=6; $k++) {
                                    // To get current date in exercise
                                    $current_date = Carbon::parse($startOfWeek)->addDays($k)->format('d:m:Y');
                                    $day = strtolower(Carbon::parse($startOfWeek)->addDays($k)->format('l'));
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
                                // To get total exercise count
                                $total_exercises_count += $freq_count;
                                //To check exercise performed data
                                if(!empty($exercises->round_info)) {
                                    foreach($exercises->round_info as $key => $value) {
                                        $res = explode(":",  $key);
                                        $changedDate = $res[0] . "-" . $res[1] . "-" . $res[2];
                                        //To check the exercise performed data in the current week or not
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
                                // To check status of course is 0 or not and if course has round info
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
                                for($k=0; $k<=6; $k++) {
                                    // To get current date of courses
                                    $course_current_date = Carbon::parse($courseStartDate)->addDays($k)->format('d:m:Y');
                                    $course_day = strtolower(Carbon::parse($courseStartDate)->addDays($k)->format('l'));
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
                                //To checke courses performed data
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
                                                // To get the total aborted course count
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
                $data->exercises = !empty($exercise) ? $exercise : NULL;
                $data->courses = !empty($course) ? $course : NULL;
            }
        }
        
        $user_main_data = User::find(decrypt($id));
        $user = PatientData::where('user_id', decrypt($id))->first();
        $common_data = array(
            'user_name' => ucfirst($user->firstname) ." ". ucfirst($user->lastname),
            'therapy_plan_link' => route('patients.therapy-plans', $id),
            'base_data_link' => route('patients.base-data', $id),
            'event_data_link' => route('patients.meetings', $id),
            'documents_link' => route('patients.documents', $id),
            'add_class' => empty($assign_plans_data),
        );
        $body_regions = BodyRegions::all();
        $indications = Indication::all();
        $phasesData = $assign_plans_data ? $assign_plans_data->Phases : NULL;
        View::share('common_data', $common_data);
        return view('assign-patient-views.patient-analytics', compact('indications', 'body_regions', 'id', 'assign_plans_data', 'total_duration', 'phasesData' , 'id', 'set_active', 'user_main_data'));
    }

    //function to get the exercise course graph data
    public function getExerciseCoursePopUpData(Request $request, $id) {
        $plans_data = PatientTherapyPlanTemplates::with(['Phases.Limitations', 'Phases.PhaseCourses','Phases.PhaseAssessments.PatientAssessmentTemplates.Categories.SubCategories.Measurement','Indications', 'BodyRegions', 'Phases.PhaseExercises.Exercises', 'Phases.PhaseCourses.PatientCourse.CourseExercises.Exercises', 'Phases.PhaseCourses.PatientCourse.BodyRegions', 'AllExercises', 'AllCourses', 'AllAssessments','Phases.PhaseAssessments.AssessmentCategories.SubCategories.Measurement', 'PatientPlanNetwork'])
        ->where('patient_id', decrypt($id))->where('is_draft', 0)->where('is_complete', 0)->where('aborted', 0)->first();
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
                    return view('assign-patient-views.exercise-course-modal-graph', compact('all_details'));
                }
            }
        }
    }

    //function to get the assessment measurement popup data
    public function getAssessmentMeasurementPopupData(Request $request, $id) {
        $phase_assessments = PatientPhaseAssessments::with(['AssessmentCategories' => function($q)  {
            $q->where('is_active', 1);
            $q->with(['SubCategories' => function($q) {
                $q->where('is_active', 1);
            }]);
        }])->with('PatientAssessmentTemplates')->where('phases_id', decrypt($id))->first();
        if(!empty($phase_assessments)) {
            return view('assign-patient-views.assessment-measurement-popup', compact('phase_assessments'));    
        }
        return response()->json(['error' => 'Some error occure. Please try again.', 'status' => 400], 400);
        
    }

    //function to save the measurements
    public function saveAssessmentMeasurements(Request $request) {
        $rules = [
            'date' => ['required'],
            'time' => ['required'],
        ];
        if(empty(array_filter($request->subcat))) {
            $rules['subcat.*'] = ['required'];
        }
        $validator = Validator::make($request->all(), $rules, [
            'subcat.*.required' => \Lang::get('lang.measureemt-required-error')
        ]);
        if ($validator->fails()) {
            $err_response  = [
                'success' => false,
                'errors' => $validator->messages()
            ];
            return response()->json($err_response, 400);
        }
        $data = array();
        //to set the date format
        $res = explode(".", $request->date);
        $changedDate = $res[1] . "-" . $res[0] . "-" . $res[2];
        $date = Carbon::parse($changedDate .' '.$request->time )->format('Y-m-d H:i:s');
        $measurements_array = array_filter($request->subcat);
        foreach($measurements_array as $key => $asmt) {
            $data[] = array(
                'patient_assessment_sub_categories_id' => $key,
                'date' => $date,
                'time' => $request->time,
                'value' => $asmt,
                'note' => $request->has('notes') ? $request->notes : NULL,
                'additional_patient_status' => !empty($request->additional_patient_status) ? $request->additional_patient_status : NULL,
                'pain_level' => $request->pain_level,
                'used_painkillers' => $request->has('used_painkiller') ? 1 : 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            );
        }
        //to insert the assessment measurements in the asseessment_measurements table
        if(!empty($data)) {
            PatientAssessmentMeasurements::insert($data);    
        }
        $message = \Lang::get('lang.measurement-saved-msg');
        $request->session()->flash('alert-success', $message);
        return response()->json(['success' => \Lang::get('lang.measurement-saved-msg'), 'status' => 200], 200);
    }   

    //function to send the email confirmation mail
    public function resendConfirmationMail(Request $request, $id) {
        $user = User::find(decrypt($id));
        //to send the mail for set the password
        event(new \Illuminate\Auth\Events\Registered($user));
        $message = \Lang::get('lang.email-successfully-sent');
        $request->session()->flash('alert-success', $message);
        return redirect()->back();
    }

    //function to send the push notification
    public function sendNotificationForAppointment(Request $request) {
        //Get all the events which are started in future
        $patientMeetings = PatientsMeetings::with('patientData')->where('is_reminder_sent', 0)->get();
        $patientMeetings = $patientMeetings->where('utc_date_time', '>=', Carbon::now());
        foreach($patientMeetings as $key => $meeting) {
            $meetingId = $meeting->id;
            $start_date = $meeting->utc_date_time;
            $interval = $start_date->diffInSeconds(Carbon::now()); //To get the difference between start date and now
            $reminder_time = (int)$meeting->reminder;
            //If reminder not set then user the general reminder time 
            if(empty($reminder_time)) {
                $reminder_time = (int)$meeting->patientData->general_reminder_time;
            }
            //To check  the patient have device token or not
            if(!empty($meeting->patientData->device_token)) {
                if($interval <= $reminder_time*60*60) {                 //Check if reminder time has arrived
                    //Send Notification
                    $response = Helper::sendNotification($meeting->title, $meeting->start_date, $meeting->patientData->device_token, $meetingId);
                    $decoded_res = json_decode($response);
                    if(isset($decoded_res->success) && $decoded_res->success) {
                        $meeting->is_reminder_sent = 1;
                        $meeting->save();
                        \Log::info('Appointment notification successfully sent for', ['meeting_id' => $meeting->id]);
                    }
                }
            }
        }
    }

    // New Patient Plan Functionality
    // return the modal of rework request
    function loadReworkRequestPopup() {
        $returnHTML =  view('patient.request_rework_popup')->render();
        return response()->json(array('html' => $returnHTML));
    }
    function loadPlanActionConfirmationPopup(Request $request) {
        $action_data = (object)$request->data;
        return view('common-views.patient-plan-action-confirmation-modal', compact('action_data'));
    }

    // function for accepting / rejecting the treatment request
    function modifyTreamentRequest(Request $request) {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        if ($request->request_type == 'accept-treatment') {
            // set edit flag = 1 and status = 1 after practice accept the treatment request
            $plan_network = PatientPlanNetwork::where('id', $request->id)
                ->update([
                    'status' => 1,
                    'is_edit_flag' => 0,
                    'user_id' => Auth::user()->id
                ]);
            if ($plan_network) {
                $message = \Lang::get('lang.accept_request_message');
                $request->session()->flash('alert-success', $message);
                return response()->json(['success' => $message, 'status' => 200], 200);
            } else {
                $request->session()->flash('alert-danger', $message);
                return response()->json(['error' => $message, 'status' => 400], 400);
            }
        }
        if ($request->request_type == 'reject-treatment') {
            // delete the record from network if practice reject the request
            if ($request->id) {
                $delete = PatientPlanNetwork::destroy($request->id);
                // is owner has send rights to connected organization before accepting the request 
                //by connected organization then 
                //reset the status of owner organization to 0 = open state
                $plan_network_data = PatientPlanNetwork::where('plan_id', $request->plan_id)
                    ->where('patient_id', $request->patient_id)
                    ->where('is_owner_organization', 1)
                    ->update([
                        'process_status' => 0,
                    ]);
                    $message = \Lang::get('lang.reject_request_message');
                    $request->session()->flash('alert-success', $message);
                    return response()->json(['success' => $message, 'status' => 200], 200);
                } else {
                    $message = "There might be some error, Can't reject the request now";
                $request->session()->flash('alert-danger', $message);
                return response()->json(['error' => $message, 'status' => 400], 400);
            }
        }
    }


    function submitReworkRequest(Request $request) {
        if ($request->request_type == 'update_status') {
            $update = PatientPlanRequests::find($request->id);
            $update->status = 1;
            $update->save();
            return response()->json(['success' => 'request viewed successfully', 'for' => 'rework-request-status', 'status' => 200], 200);
        } else {
            $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
            $patientPlanNetwork = PatientPlanNetwork::where('plan_id', $request->network_plan_id)
                    ->where('patient_id', $request->patient_id)
                    ->where('organization_id', '!=', $organization_id)->first();
            if ($patientPlanNetwork) {
                $patientPlanRequest = new PatientPlanRequests;
                $patientPlanRequest->plan_id = $request->plan_id;
                $patientPlanRequest->from = $organization_id;
                $patientPlanRequest->to = $patientPlanNetwork->organization_id;
                $patientPlanRequest->request_type = $request->request_type;
                $patientPlanRequest->subject = $request->subject;
                $patientPlanRequest->description = $request->description;
                $patientPlanRequest->status = 0;
    
                // add log
                $this->addLogOfPlanProcessStatus('rework_request', $patientPlanNetwork->plan_id, $patientPlanNetwork->draft_id, $request->patient_id);

                $patientPlanRequest->save();
                if ($patientPlanRequest->id) {
                    // update the is_edit_falg
                    $message = \Lang::get('lang.request_change_message');
                    $request->session()->flash('alert-success',$message);
                    return redirect()->back();
                } else {
                    $request->session()->flash('alert-danger', 'there might be some error');
                    return redirect()->back();
                }
            } else {
                return redirect()->back();
            }
        }
    }

    // send edit rights to another practice
    function changeTheProcessStatusOfOrganization(Request $request) {
        $data = array();

        // check for status and changes made by other org.
        $check_status_of_network = PatientPlanNetwork::find($request->network_id);
        if ($check_status_of_network->process_status != $request->current_process_status) {
            $message = \Lang::get('lang.changes_made_already');
            $request->session()->flash('alert-success', $message);
            return response()->json(['success' => $message, 'status' => 200], 200);
        }

        $this->addLogOfPlanProcessStatus($request->request_type, $request->plan_id, $request->draft_id, $request->patient_id);

        if ($request->flow_type == 1) {
            switch ($request->request_type) {
                case 'forward_to_other_practice':
                    $plan_network = PatientPlanNetwork::where('plan_id', $request->plan_id)->where('is_owner_organization', 0)->first();
                    if (!$plan_network || $plan_network->status == 0) {
                        $data = Helper::getOrganizationsProcessStatus(2, $request->organization_type, $request->flow_type);
                    } else {
                        $data = Helper::getOrganizationsProcessStatus(3, $request->organization_type, $request->flow_type);
                    }
                    $owner_edit_flag = 0;
                    $connected_edit_flag = 0;
                    $message = \Lang::get('lang.forward_to_other_practice_message');
                    $this->updateTheStatusOfPatientPlanAction($data, $owner_edit_flag, $connected_edit_flag, $request, $message);
                    break;

                case 'revoke_approval':
                    if ($request->organization_type == 'owner') {
                        $data = Helper::getOrganizationsProcessStatus(0, $request->organization_type, $request->flow_type);
                        $owner_edit_flag = 1;
                        $connected_edit_flag = 0;
                    } else {
                        $data = Helper::getOrganizationsProcessStatus(4, $request->organization_type, $request->flow_type);
                        $owner_edit_flag = 0;
                        $connected_edit_flag = 1;
                    }
                    $owner_edit_flag = 0;
                    $connected_edit_flag = 0;
                    $message = \Lang::get('lang.revoke_approval_message');
                    $this->updateTheStatusOfPatientPlanAction($data, $owner_edit_flag, $connected_edit_flag, $request, $message);
                    break;

                case 'edit_mode':
                    if ($request->organization_type == 'owner') {
                        $data = Helper::getOrganizationsProcessStatus(1, $request->organization_type, $request->flow_type);
                        $owner_edit_flag = 1;
                        $connected_edit_flag = 0;
                    } else {
                        $data = Helper::getOrganizationsProcessStatus(3, $request->organization_type, $request->flow_type);
                        $owner_edit_flag = 0;
                        $connected_edit_flag = 1;
                    }
                    $message = \Lang::get('lang.edit_mode_message');
                    // copy plan data to draft data // create a new draft of patient plan
                    $this->copyPatientTherapyPlanData($request->plan_id);
                    $this->updateTheStatusOfPatientPlanAction($data, $owner_edit_flag, $connected_edit_flag, $request, $message);
                    break;

                case 'close_and_discard_changes':
                    if ($request->organization_type == 'owner') {
                        $data = Helper::getOrganizationsProcessStatus(0, $request->organization_type, $request->flow_type);
                        $owner_edit_flag = 0;
                        $connected_edit_flag = 0;
                    } else {
                        $data = Helper::getOrganizationsProcessStatus(4, $request->organization_type, $request->flow_type);
                        $owner_edit_flag = 0;
                        $connected_edit_flag = 0;
                    }
                    $message = \Lang::get('lang.discard_changes_message');
                    // delete the created draft to discard changes
                    $this->discardChanges($request->plan_id, $request->draft_id);
                    $this->updateTheStatusOfPatientPlanAction($data, $owner_edit_flag, $connected_edit_flag, $request, $message);
                    break;

                case 'close_and_save_changes':
                    if ($request->organization_type == 'owner') {
                        $data = Helper::getOrganizationsProcessStatus(0, $request->organization_type, $request->flow_type);
                        $owner_edit_flag = 0;
                        $connected_edit_flag = 0;
                    } else {
                        $data = Helper::getOrganizationsProcessStatus(4, $request->organization_type, $request->flow_type);
                        $owner_edit_flag = 0;
                        $connected_edit_flag = 0;
                    }
                    $message = \Lang::get('lang.save_changes_message');
                    $this->updateTheStatusOfPatientPlanAction($data, $owner_edit_flag, $connected_edit_flag, $request, $message);
                    break;

                case 'accept_treatment':
                    if ($request->current_process_status == 1) {
                        $data['owner_organization_process_status'] = 0;
                    } else {
                        $data['owner_organization_process_status'] = 3;
                    }
                    $data['connected_organization_process_status'] = $request->current_process_status;
                    $plan_network = PatientPlanNetwork::where('id', $request->network_id)
                        ->update([
                            'status' => 1,
                            'user_id' => Auth::user()->id
                        ]);
                    $owner_edit_flag = 0;
                    $connected_edit_flag = 0;
                    $message = \Lang::get('lang.accept_request_message');
                    $this->updateTheStatusOfPatientPlanAction($data, $owner_edit_flag, $connected_edit_flag, $request, $message);
                    break;

                case 'reject_treatment':
                    $delete = PatientPlanNetwork::destroy($request->network_id);
                    $data = Helper::getOrganizationsProcessStatus(1, $request->organization_type, $request->flow_type);
                    $owner_edit_flag = 0;
                    $connected_edit_flag = 0;
                    $message = \Lang::get('lang.reject_request_message');
                    $this->updateTheStatusOfPatientPlanAction($data, $owner_edit_flag, $connected_edit_flag, $request, $message);
                    break;

                case 'approve_plan':
                    $data = Helper::getOrganizationsProcessStatus(5, $request->organization_type, $request->flow_type);
                    $owner_edit_flag = 0;
                    $connected_edit_flag = 0;
                    $message = \Lang::get('lang.approve_plan_message');
                    $this->updateTheStatusOfPatientPlanAction($data, $owner_edit_flag, $connected_edit_flag, $request, $message);
                    break;

                case 'discard_plan':
                    $data = Helper::getOrganizationsProcessStatus(1, $request->organization_type, $request->flow_type);
                    $owner_edit_flag = 0;
                    $connected_edit_flag = 0;
                    $message = \Lang::get('lang.discard_plan_message');
                    $this->updateTheStatusOfPatientPlanAction($data, $owner_edit_flag, $connected_edit_flag, $request, $message);
                    break;

                case 'edit_mode_after_approval':
                    $data = Helper::getOrganizationsProcessStatus(6, $request->organization_type, $request->flow_type);
                    $owner_edit_flag = 1;
                    $connected_edit_flag = 0;
                    $message = \Lang::get('lang.edit_mode_message');
                    // copy plan data to draft data // create a new draft of patient plan
                    $this->copyPatientTherapyPlanData($request->plan_id);
                    $this->updateTheStatusOfPatientPlanAction($data, $owner_edit_flag, $connected_edit_flag, $request, $message);
                    break;

                case 'forward_to_other_practice_after_approval':
                    $data = Helper::getOrganizationsProcessStatus(9, $request->organization_type, $request->flow_type);
                    $owner_edit_flag = 0;
                    $connected_edit_flag = 0;
                    $message = \Lang::get('lang.forward_to_other_practice_message');
                    $this->updateTheStatusOfPatientPlanAction($data, $owner_edit_flag, $connected_edit_flag, $request, $message);
                    break;

                case 'revoke_approval_after_approval':
                    $data = Helper::getOrganizationsProcessStatus(5, $request->organization_type, $request->flow_type);
                    $owner_edit_flag = 0;
                    $connected_edit_flag = 0;
                    $message = \Lang::get('lang.edit_mode_message');
                    $this->updateTheStatusOfPatientPlanAction($data, $owner_edit_flag, $connected_edit_flag, $request, $message);
                    break;

                case 'close_and_discard_changes_after_approval':
                    $data['owner_organization_process_status'] = 5; // ready to publish
                    $data['connected_organization_process_status'] = 6;// in processing
                    $owner_edit_flag = 0;
                    $connected_edit_flag = 0;
                    $message = \Lang::get('lang.discard_changes_message');
                    // delete the created draft to discard changes
                    $this->discardChanges($request->plan_id, $request->draft_id);
                    $this->updateTheStatusOfPatientPlanAction($data, $owner_edit_flag, $connected_edit_flag, $request, $message);
                    break;

                case 'close_and_save_changes_after_approval':
                    $data = Helper::getOrganizationsProcessStatus(0, $request->organization_type, $request->flow_type);
                    $owner_edit_flag = 0;
                    $connected_edit_flag = 0;
                    $message = \Lang::get('lang.save_changes_message');
                    $this->updateTheStatusOfPatientPlanAction($data, $owner_edit_flag, $connected_edit_flag, $request, $message);
                    break;

                case 'publish_to_patient':
                    $data = Helper::getOrganizationsProcessStatus(7, $request->organization_type, $request->flow_type);
                    $owner_edit_flag = 0;
                    $connected_edit_flag = 0;
                    $message = \Lang::get('lang.publish_now_message');
                    $this->updateTheStatusOfPatientPlanAction($data, $owner_edit_flag, $connected_edit_flag, $request, $message);
                    break;

                case 'edit_mode_after_publish':
                    if ($request->organization_type == 'owner') {
                        $data = Helper::getOrganizationsProcessStatus(8, $request->organization_type, $request->flow_type);
                        $owner_edit_flag = 1;
                        $connected_edit_flag = 0;
                    } else {
                        $data = Helper::getOrganizationsProcessStatus(8, $request->organization_type, $request->flow_type);
                        $owner_edit_flag = 0;
                        $connected_edit_flag = 1;
                    }
                    $message = \Lang::get('lang.edit_mode_message');
                    // copy plan data to draft data // create a new draft of patient plan
                    $this->copyPatientTherapyPlanData($request->plan_id);
                    $this->updateTheStatusOfPatientPlanAction($data, $owner_edit_flag, $connected_edit_flag, $request, $message);
                    break;

                case 'close_and_discard_changes_after_publish':
                    if ($request->organization_type == 'owner') {
                        $data = Helper::getOrganizationsProcessStatus(7, $request->organization_type, $request->flow_type);
                        $owner_edit_flag = 0;
                        $connected_edit_flag = 0;
                    } else {
                        $data = Helper::getOrganizationsProcessStatus(7, $request->organization_type, $request->flow_type);
                        $owner_edit_flag = 0;
                        $connected_edit_flag = 0;
                    }
                    $message = \Lang::get('lang.discard_changes_message');
                    $this->discardChanges($request->plan_id, $request->draft_id);
                    $this->updateTheStatusOfPatientPlanAction($data, $owner_edit_flag, $connected_edit_flag, $request, $message);
                    break;

                case 'close_and_save_changes_after_publish':
                    if ($request->organization_type == 'owner') {
                        $data = Helper::getOrganizationsProcessStatus(7, $request->organization_type, $request->flow_type);
                        $owner_edit_flag = 0;
                        $connected_edit_flag = 0;
                    } else {
                        $data = Helper::getOrganizationsProcessStatus(7, $request->organization_type, $request->flow_type);
                        $owner_edit_flag = 0;
                        $connected_edit_flag = 0;
                    }
                    $message = \Lang::get('lang.changes_message');
                    $this->updateTheStatusOfPatientPlanAction($data, $owner_edit_flag, $connected_edit_flag, $request, $message);
                    break;

                default:
                    return;
                    break;
            }
        }

        if ($request->flow_type == 2) {
            switch ($request->request_type) {
                case 'edit_mode':
                    $data = Helper::getOrganizationsProcessStatus(1, $request->organization_type, $request->flow_type);
                    $message = \Lang::get('lang.edit_mode_message');
                    // copy plan data to draft data // create a new draft of patient plan
                    $this->copyPatientTherapyPlanData($request->plan_id);
                    $this->updateTheStatusOfPatientPlanAction($data, 1, 0, $request, $message);
                    break;

                case 'close_and_save_changes':
                    $data = Helper::getOrganizationsProcessStatus(0, $request->organization_type, $request->flow_type);
                    $message = \Lang::get('lang.save_changes_message');
                    $this->updateTheStatusOfPatientPlanAction($data, 0, 0, $request, $message);
                    break;

                case 'close_and_discard_changes':
                    $data = Helper::getOrganizationsProcessStatus(0, $request->organization_type, $request->flow_type);
                    $message = \Lang::get('lang.discard_changes_message');
                    // delete the created draft to discard changes
                    $this->discardChanges($request->plan_id, $request->draft_id);
                    $this->updateTheStatusOfPatientPlanAction($data, 0, 0, $request, $message);
                    break;

                case 'publish_to_patient':
                    $data = Helper::getOrganizationsProcessStatus(2, $request->organization_type, $request->flow_type);
                    $message = \Lang::get('lang.publish_now_message');
                    $this->updateTheStatusOfPatientPlanAction($data, 0, 0, $request, $message);
                    break;

                case 'edit_mode_after_publish':
                    $data = Helper::getOrganizationsProcessStatus(3, $request->organization_type, $request->flow_type);
                    $message = \Lang::get('lang.edit_mode_message');
                    // copy plan data to draft data // create a new draft of patient plan
                    $this->copyPatientTherapyPlanData($request->plan_id);
                    $this->updateTheStatusOfPatientPlanAction($data, 1, 0, $request, $message);
                    break;

                case 'close_and_save_changes_after_publish':
                    $data = Helper::getOrganizationsProcessStatus(2, $request->organization_type, $request->flow_type);
                    $message = \Lang::get('lang.submit_changes_message');
                    $this->updateTheStatusOfPatientPlanAction($data, 0, 0, $request, $message);
                    break;

                case 'close_and_discard_changes_after_publish':
                    $data = Helper::getOrganizationsProcessStatus(2, $request->organization_type, $request->flow_type);
                    $message = \Lang::get('lang.discard_changes_message');
                    // delete the created draft to discard changes
                    $this->discardChanges($request->plan_id, $request->draft_id);
                    $this->updateTheStatusOfPatientPlanAction($data, 0, 0, $request, $message);
                    break;

                default:
                    return;
                    break;
            }
        }

        if ($request->flow_type == 3) {
            switch ($request->request_type) {
                case 'accept_treatment':
                    $data = Helper::getOrganizationsProcessStatus(1, $request->organization_type, $request->flow_type);
                    $plan_network = PatientPlanNetwork::where('id', $request->network_id)
                        ->update([
                            'status' => 1,
                            'user_id' => Auth::user()->id
                        ]);
                    $message = \Lang::get('lang.accept_request_message');
                    $this->updateTheStatusOfPatientPlanAction($data, 0, 0, $request, $message);
                    break;

                case 'reject_treatment':
                    $delete = PatientPlanNetwork::destroy($request->network_id);
                    $data['owner_organization_process_status'] = 0;
                    $data['connected_organization_process_status'] = 1;
                    $message = \Lang::get('lang.reject_request_message');
                    $this->updateTheStatusOfPatientPlanAction($data, 0, 0, $request, $message);
                    break;

                case 'edit_mode':
                    $data = Helper::getOrganizationsProcessStatus(2, $request->organization_type, $request->flow_type);
                    $message = \Lang::get('lang.edit_mode_message');
                    // copy plan data to draft data // create a new draft of patient plan
                    $this->copyPatientTherapyPlanData($request->plan_id);
                    $this->updateTheStatusOfPatientPlanAction($data, 0, 1, $request, $message);
                    break;

                case 'close_and_save_changes':
                    $data = Helper::getOrganizationsProcessStatus(1, $request->organization_type, $request->flow_type);
                    $message = \Lang::get('lang.save_changes_message');
                    $this->updateTheStatusOfPatientPlanAction($data, 0, 0, $request, $message);
                    break;

                case 'close_and_discard_changes':
                    $data = Helper::getOrganizationsProcessStatus(1, $request->organization_type, $request->flow_type);
                    $message = \Lang::get('lang.discard_changes_message');
                    // delete the created draft to discard changes
                    $this->discardChanges($request->plan_id, $request->draft_id);
                    $this->updateTheStatusOfPatientPlanAction($data, 0, 0, $request, $message);
                    break;

                case 'publish_to_patient':
                    $data = Helper::getOrganizationsProcessStatus(3, $request->organization_type, $request->flow_type);
                    $message = \Lang::get('lang.publish_now_message');
                    $this->updateTheStatusOfPatientPlanAction($data, 0, 0, $request, $message);
                    break;

                case 'edit_mode_after_publish':
                    $data = Helper::getOrganizationsProcessStatus(4, $request->organization_type, $request->flow_type);
                    $message = \Lang::get('lang.edit_mode_message');
                    // copy plan data to draft data // create a new draft of patient plan
                    $this->copyPatientTherapyPlanData($request->plan_id);
                    $this->updateTheStatusOfPatientPlanAction($data, 0, 1, $request, $message);
                    break;

                case 'close_and_save_changes_after_publish':
                    $data = Helper::getOrganizationsProcessStatus(3, $request->organization_type, $request->flow_type);
                    $message = \Lang::get('lang.submit_changes_message');
                    $this->updateTheStatusOfPatientPlanAction($data, 0, 0, $request, $message);
                    break;

                case 'close_and_discard_changes_after_publish':
                    $data = Helper::getOrganizationsProcessStatus(3, $request->organization_type, $request->flow_type);
                    $message = \Lang::get('lang.discard_changes_message');
                    // delete the created draft to discard changes
                    $this->discardChanges($request->plan_id, $request->draft_id);
                    $this->updateTheStatusOfPatientPlanAction($data, 0, 0, $request, $message);
                    break;

                default:
                    return;
                    break;
            }
        }
    }

    private function updateTheStatusOfPatientPlanAction($data, $owner_edit_flag, $connected_edit_flag, $request, $message) {
        if (!empty($data)) {
            $plan_owner_network_data = PatientPlanNetwork::where('plan_id', $request->plan_id)
                    ->where('patient_id', $request->patient_id)
                    ->where('is_owner_organization', 1)
                    ->update([
                        'process_status' => $data['owner_organization_process_status'],
                        'is_edit_flag' => $owner_edit_flag,
                    ]);
            $plan_connected_network_data = PatientPlanNetwork::where('plan_id', $request->plan_id)
                ->where('patient_id', $request->patient_id)
                ->where('is_owner_organization', 0)
                ->update([
                    'process_status' => $data['connected_organization_process_status'],
                    'is_edit_flag' => $connected_edit_flag,
                ]);

            // make draft to plan after saving the changes
            if ($request->request_type == 'close_and_save_changes' || $request->request_type == 'close_and_save_changes_after_approval') {
                // copy draft data to original array to save the changes
                $this->updatePatientTherapyPlanData($request->plan_id, $request->draft_id, 'save');
            }
            if ($request->request_type == 'close_and_save_changes_after_publish') {
                // copy draft data to original array for publish changes to patient
                $this->updatePatientTherapyPlanData($request->plan_id, $request->draft_id, 'publish');
            }

            if ($request->request_type == 'publish_to_patient') {
                $therapy_plan_data = PatientTherapyPlanTemplates::find($request->plan_id);
                if ($therapy_plan_data->is_released == 0) {
                    //create new release
                    $therapy_plan_data->is_released = 1;
                    $therapy_plan_data->save();
                    $dates = array();
                    $start_date = $therapy_plan_data->start_date;
                    if($therapy_plan_data->Phases && count($therapy_plan_data->Phases)) {
                        foreach($therapy_plan_data->Phases as $key => $phase) {
                            if ($key == 0) {
                                $dates['start_date'][$key] = Carbon::parse($start_date);
                                $dates['end_date'][$key] = Carbon::parse($start_date)->addWeeks($phase->duration)->subDay(1);
                            } else {
                                $dates['start_date'][$key] = Carbon::parse($dates['end_date'][$key - 1])->addDay();
                                $dates['end_date'][$key] = Carbon::parse($dates['start_date'][$key])->addWeeks($phase->duration)->subDay(1);
                            }

                            $this->addPhasesToMeetings($dates['start_date'][$key], $phase->name, $therapy_plan_data->patient_id, $phase->id);
                        }
                    }
                } else {
                    // copy draft data to original array for publish changes to patient
                    $this->updatePatientTherapyPlanData($request->plan_id, $request->draft_id, 'publish');
                }
            }

            $request->session()->flash('alert-success', $message);
            return response()->json(['success' => $message, 'status' => 200], 200);
        }
    }

    function addLogOfPlanProcessStatus($request_type, $plan_id, $draft_id, $patient_id) {
        $log = array();
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        $log['plan_id'] = $plan_id;
        $log['draft_id'] = $draft_id;
        $log['organization_id'] = $organization_id;
        $log['patient_id'] = $patient_id;
        $log['user_id'] = Auth::user()->id;
        $log['request_type'] = $request_type;
        $log['status'] = 0;
        $newLog = PlanProcessLog::create($log);
    }

    function copyPatientTherapyPlanData($plan_id) {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        if($plan_id) {
            $templateData = PatientTherapyPlanTemplates::with(['Phases.Limitations', 'Phases.PhaseCourses','Phases.PhaseAssessments.PatientAssessmentTemplates.Categories.SubCategories.Schedules','Phases.PhaseAssessments.PatientAssessmentTemplates.BodyRegions','Phases.PhaseAssessments.PatientAssessmentTemplates.Schedules','Phases.PhaseAssessments.PatientAssessmentTemplates.Indications','Indications', 'BodyRegions', 'Phases.PhaseExercises.Exercises.BodyRegions', 'Phases.PhaseCourses.PatientCourse.CourseExercises.Exercises.BodyRegions', 'Phases.PhaseCourses.PatientCourse.BodyRegions','Phases.PhaseCourses.PatientCourse.Indications', 'AllExercises', 'AllCourses', 'AllAssessments']);
            $templateData = $templateData->find($plan_id);

            // copy therapy plan data
            if($templateData) {
                $data = $templateData->attributesToArray();
                $data = Arr::except($data, ['id', 'encrypted_plan_template_id']);
                $data['user_id'] = $templateData->user_id;
                $data['organization_id'] = $templateData->organization_id;
                $data['start_date'] = $templateData->start_date;
                $data['phases_editable_by'] = $templateData->phases_editable_by;
                $data['assessments_editable_by'] = $templateData->assessments_editable_by;
                $data['exercises_courses_editable_by'] = $templateData->exercises_courses_editable_by;
                $data['patient_id'] = $templateData->patient_id;
                $data['is_released'] = 0;
                $data['plan_owner'] = $templateData->plan_owner;
                $data['is_sent_to_patient'] = 1;
                $data['is_draft'] = 1;
                // create new Order based on Post's data (drft)
                $newPlan = PatientTherapyPlanTemplates::create($data); // create a draft
                $draft_id = $newPlan->id;
                $update_draft_id = PatientPlanNetwork::where('plan_id', $plan_id)
                    ->update(
                        ['draft_id' => $draft_id]
                );
                //copy indication data
                $newPlan->Indications()->sync($templateData->Indications);
                // copy body region data
                $newPlan->BodyRegions()->sync($templateData->BodyRegions);
                $total_duration = 0;
                $dates = array();
                $start_date = $templateData->start_date;
                // copy plan phase data
                if($templateData['phases'] && count($templateData['phases'])) {
                    foreach($templateData['phases'] as $key => $phase) {
                        $phaseData = $phase->attributesToArray();
                        $phaseData['therapy_plan_templates_id'] =  $newPlan->id;
                        $getUpdatePhase = array_values($templateData['phases']->toArray());
                        $phaseIndex = array_search($phaseData['id'], array_column($getUpdatePhase, 'id'));
                        if($phaseIndex !== false) {
                            $phaseData['name'] =  $getUpdatePhase[$phaseIndex]['name'];
                            $phaseData['duration'] =  $getUpdatePhase[$phaseIndex]['duration'];
                            $phaseData = Arr::except($phaseData, ['id', 'encrypted_phase_id']);
                            $newPhase = PatientPhases::create($phaseData);

                            // copy phase limitation data
                            if($phase['limitations'] && count($phase['limitations'])) {
                                foreach($phase['limitations'] as $limitation) {
                                    $limitationData = $limitation->attributesToArray();
                                    $limitationData = Arr::except($limitationData, ['id']);
                                    $limitationData['phase_id'] =  $newPhase->id;
                                    $newlimitation = PatientLimitations::create($limitationData);
                                }
                            }

                            // copy assesment data
                            if($phase['PhaseAssessments'] && count($phase['PhaseAssessments'])) {
                                foreach($phase['PhaseAssessments'] as $assessment) {
                                    $assessmentData = $assessment['PatientAssessmentTemplates']->attributesToArray();
                                    $assessmentData = Arr::except($assessmentData, ['id', 'encrypted_assessment_id']);
                                    $assessmentData['user_id'] = Auth::user()->id;
                                    $newassessment = PatientAssessmentTemplates::create($assessmentData);

                                    // copy phase and assessment relation
                                    $assessmentPhaseData['phases_id'] =  $newPhase->id;
                                    $assessmentPhaseData['assessment_templates_id'] =  $newassessment->id;
                                    $newassessmentPhase = PatientPhaseAssessments::create($assessmentPhaseData);

                                    //copy assessment schedules data
                                    $general_schedules_data = [];
                                    if($assessment['PatientAssessmentTemplates']['Schedules'] && count($assessment['PatientAssessmentTemplates']['Schedules'])) {
                                        foreach($assessment['PatientAssessmentTemplates']['Schedules'] as $schedules) {
                                            $scheduleData = $schedules->attributesToArray();
                                            $scheduleData = Arr::except($scheduleData, ['id', 'patient_assessment_templates_id']);
                                            $scheduleData['patient_assessment_templates_id'] =  $newassessment->id; 
                                            $newschedule = PatientAssessmentSchedules::create($scheduleData);
                                            $general_schedules_data[$newschedule->id] = $newschedule->name;
                                        }
                                    }

                                    // copy assessment categories data
                                    if($assessment['PatientAssessmentTemplates']['Categories'] && count($assessment['PatientAssessmentTemplates']['Categories'])) {
                                        foreach($assessment['PatientAssessmentTemplates']['Categories'] as $category) {
                                            if($category['is_active']) {
                                                $categoryData = $category->attributesToArray();
                                                $categoryData = Arr::except($categoryData, ['id', 'encrypted_cat_id', 'assessment_template_id']);
                                                $categoryData['assessment_template_id'] =  $newassessment->id; 
                                                $newcategory = PatientAssessmentCategories::create($categoryData);

                                                // copy assessment sub categories data
                                                if($category['SubCategories'] && count($category['SubCategories'])) {
                                                    foreach($category['SubCategories'] as $subCategories) {
                                                        if($subCategories['is_active']) {
                                                            $subCategoriesData = $subCategories->attributesToArray();
                                                            $subCategoriesData = Arr::except($subCategoriesData, ['id', 'chart_data', 'encrypted_sub_cat_id', 'translated_unit', 'assessment_category_id', 'patient_assessment_schedules_id', 'average_measurement_value', 'schedules_data', 'measurements_json']);
                                                            $subCategoriesData['assessment_category_id'] =  $newcategory->id;
                                                            //To check the assigned schedules exist or not
                                                            if(!empty($subCategories['patient_assessment_schedules_id']) && $subCategories['schedules_data']) {
                                                                $sche_ids = [];
                                                                foreach($subCategories['schedules_data'] as $sche_key => $sche_data) {
                                                                    $sche_name = $sche_data->name;
                                                                    $filtered_arr = array_filter($general_schedules_data, function ($var) use ($sche_name) {
                                                                        return ($var == $sche_name);
                                                                    });
                                                                    if(!empty($filtered_arr))
                                                                        $sche_ids[] = key($filtered_arr);
                                                                }
                                                                $subCategoriesData['patient_assessment_schedules_id'] =  implode(',', $sche_ids);
                                                            }
                                                            $newsubCategories = PatientAssessmentSubCategories::create($subCategoriesData);
                                                            $newsubCategories->measurements_json = $subCategories->measurements_json;
                                                            $newsubCategories->save();

                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    // copy assessment indication data
                                    if($assessment['PatientAssessmentTemplates']['Indications'] && count($assessment['PatientAssessmentTemplates']['Indications'])) {
                                        foreach($assessment['PatientAssessmentTemplates']['Indications'] as $indication) {
                                            $indications = Indication::find($indication->id);
                                            $newassessment->Indications()->attach($indications);
                                        }
                                    }

                                    //copy assessment body region data
                                    if($assessment['PatientAssessmentTemplates']['BodyRegions'] && count($assessment['PatientAssessmentTemplates']['BodyRegions'])) {
                                        foreach($assessment['PatientAssessmentTemplates']['BodyRegions'] as $bodyRegion) {
                                            $bodyRegions = BodyRegions::find($bodyRegion->id);
                                            $newassessment->BodyRegions()->attach($bodyRegions);
                                        }
                                    }
                                }
                            }
                            // copy phase exercise data with exercises data
                            if($phase['PhaseExercises'] && count($phase['PhaseExercises'])) {
                                foreach($phase['PhaseExercises'] as $phaseExercise) {
                                    $newexercise = NULL;
                                    
                                    $phaseExerciseData = $phaseExercise->attributesToArray();
                                    $phaseExerciseData = Arr::except($phaseExerciseData, ['id', 'frequency_data', 'round_info', 'course_feedback']);
                                    $phaseExerciseData['exercise_id'] =  $phaseExerciseData['exercise_id'];
                                    $phaseExerciseData['phase_id'] =  $newPhase->id;
                                    $newPhaseExercise = PatientPhaseExercises::create($phaseExerciseData);
                                    $newPhaseExercise->round_info = $phaseExercise->round_info;
                                    $newPhaseExercise->course_feedback = $phaseExercise->course_feedback;
                                    $newPhaseExercise->save();
                                }
                            }

                            // copy course, course exercises, exercises, phase course data
                            if($phase['PhaseCourses'] && count($phase['PhaseCourses'])) {
                                foreach($phase['PhaseCourses'] as $PhaseCourses) {
                                    $newcourses = NULL;
                                    if($PhaseCourses['PatientCourse']) {
                                        $courses = $PhaseCourses['PatientCourse'];
                                        $coursesData = $courses->attributesToArray();
                                        $coursesData = Arr::except($coursesData, ['id', 'encrypted_course_id', 'exercise_counts' ,'frequency_data', 'image_url', 'round_info', 'course_feedback']);
                                        $newcourses = PatientCourses::create($coursesData);
                                        $newcourses->round_info = $courses->round_info;
                                        $newcourses->course_feedback = $courses->course_feedback;
                                        $newcourses->save();
                                    }

                                    $PhaseCoursesData = $PhaseCourses->attributesToArray();
                                    $PhaseCoursesData = Arr::except($PhaseCoursesData, ['id', '']);
                                    $PhaseCoursesData['course_id'] =  $newcourses ? $newcourses->id : NULL;
                                    $PhaseCoursesData['phase_id'] =  $newPhase->id;
                                    $newPhaseCourses = PatientPhaseCourses::create($PhaseCoursesData);

                                    // copy course indication data
                                    if($courses['Indications'] && count($courses['Indications'])) {
                                        foreach($courses['Indications'] as $indication) {
                                            $indications = Indication::find($indication->id);
                                            $newcourses->Indications()->attach($indications);
                                        }
                                    }

                                    //copy course body region data
                                    if($courses['BodyRegions'] && count($courses['BodyRegions'])) {
                                        foreach($courses['BodyRegions'] as $bodyRegion) {
                                            $bodyRegions = BodyRegions::find($bodyRegion->id);
                                            $newcourses->BodyRegions()->attach($bodyRegions);
                                        }
                                    }

                                    if($courses['CourseExercises'] && count($courses['CourseExercises'])) {
                                        foreach($courses['CourseExercises'] as $courseExercise) {
                                            $newexerciseForCourse = NULL;

                                            $courseExerciseData = $courseExercise->attributesToArray();
                                            $courseExerciseData = Arr::except($courseExerciseData, ['id']);
                                            $courseExerciseData['course_id'] =  $newcourses->id;
                                            $newcourseExercise = PatientCourseExercises::create($courseExerciseData);
                                        }
                                    }
                                }
                            }
                        }

                    }
                }

            }
        }
    }

    function updatePatientTherapyPlanData($plan_id, $draft_id, $type) {

        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        if ($draft_id) {
            $draftData = PatientTherapyPlanTemplates::with(['Phases.Limitations', 'Phases.PhaseCourses','Phases.PhaseAssessments.PatientAssessmentTemplates.Categories.SubCategories.Schedules','Phases.PhaseAssessments.PatientAssessmentTemplates.BodyRegions','Phases.PhaseAssessments.PatientAssessmentTemplates.Schedules','Phases.PhaseAssessments.PatientAssessmentTemplates.Indications','Indications', 'BodyRegions', 'Phases.PhaseExercises.Exercises.BodyRegions', 'Phases.PhaseCourses.PatientCourse.CourseExercises.Exercises.BodyRegions', 'Phases.PhaseCourses.PatientCourse.BodyRegions','Phases.PhaseCourses.PatientCourse.Indications', 'AllExercises', 'AllCourses', 'AllAssessments']);
            $draftData = $draftData->find($draft_id);

            if ($type == 'publish') {
                $draftData->is_released = 1;
            } else {
                $draftData->is_released = 0;
            }
            $draftData->is_draft = 0;
            $draftData->save();
            $dates = array();
            // add new events in calendar of new plan
            if ($type == 'publish') {
                $start_date = $draftData->start_date;
                if($draftData->Phases && count($draftData->Phases)) {
                    foreach($draftData->Phases as $key => $phase) {
                        if ($key == 0) {
                            $dates['start_date'][$key] = Carbon::parse($start_date);
                            $dates['end_date'][$key] = Carbon::parse($start_date)->addWeeks($phase->duration)->subDay(1);
                        } else {
                            $dates['start_date'][$key] = Carbon::parse($dates['end_date'][$key - 1])->addDay();
                            $dates['end_date'][$key] = Carbon::parse($dates['start_date'][$key])->addWeeks($phase->duration)->subDay(1);
                        }
                        // dd('here', $phase->name);
                        $this->addPhasesToMeetings($dates['start_date'][$key], $phase->name, $draftData->patient_id, $phase->id);
                    }
                }
            }
            if ($type == 'publish') {
                // remove events from calendar of old plan
                $this->removePhasesFromAppointment($plan_id);
            }

            // make draft to plan
            // change plan id in network table
            $patientPlanNetwork = PatientPlanNetwork::where('patient_id', $draftData->patient_id)->where('plan_id', $plan_id)->update([
                'plan_id' => $draft_id,
                'draft_id' => null
            ]);
            // change plan id in process logs table
            $logs = PlanProcessLog::where('plan_id', $plan_id)->update([
                'plan_id' => $draft_id,
            ]);
            // change plan id in request table
            $patientPlanRequest = PatientPlanRequests::where('plan_id', $plan_id)->update([
                'plan_id' => $draft_id,
            ]);

            $scheduleArray = [];

            // copy therapy plan data
            if ($draftData) {
                $planData = PatientTherapyPlanTemplates::with(['Phases.Limitations', 'Phases.PhaseCourses','Phases.PhaseAssessments.PatientAssessmentTemplates.Categories.SubCategories.Schedules','Phases.PhaseAssessments.PatientAssessmentTemplates.BodyRegions','Phases.PhaseAssessments.PatientAssessmentTemplates.Schedules','Phases.PhaseAssessments.PatientAssessmentTemplates.Indications','Indications', 'BodyRegions', 'Phases.PhaseExercises.Exercises.BodyRegions', 'Phases.PhaseCourses.PatientCourse.CourseExercises.Exercises.BodyRegions', 'Phases.PhaseCourses.PatientCourse.BodyRegions','Phases.PhaseCourses.PatientCourse.Indications', 'AllExercises', 'AllCourses', 'AllAssessments']);
                $planData = $planData->find($plan_id);

                if ($planData) {
                    $planData->aborted = 1;
                    $planData->is_released = 0;
                    $planData->save();
                    // process of update...
                    $count = 1;
                    foreach ($planData->Phases as $planPhaseKey=> $phase) {
                        if ($count <= count($draftData->Phases)) {

                            foreach ($draftData->Phases as $draftPhaseKey => $draftPhase) {
                                if ($planPhaseKey == $draftPhaseKey) {

                                    // copy data of phase exercies
                                    if($phase->PhaseExercises && count($phase->PhaseExercises)) {
                                        foreach ($phase->PhaseExercises as $key => $phaseExercise) {
                                            if($draftPhase->PhaseExercises && count($draftPhase->PhaseExercises)) {
                                                foreach ($draftPhase->PhaseExercises as $draftKey => $draftPhaseExercise) {
                                                    if ($key == $draftKey) {
                                                        $updatePhaseExercise = PatientPhaseExercises::find($draftPhaseExercise->id);
                                                        $updatePhaseExercise->round_info = $phaseExercise->round_info;
                                                        $updatePhaseExercise->course_feedback = $phaseExercise->course_feedback;
                                                        $updatePhaseExercise->save();
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    // copy data of phase courses
                                    if($phase->PhaseCourses && count($phase->PhaseCourses)) {
                                        foreach ($phase->PhaseCourses as $key => $phaseCourse) {
                                            if ($phaseCourse->PatientCourse) {
                                                if($draftPhase->PhaseCourses && count($draftPhase->PhaseCourses)) {
                                                    foreach ($draftPhase->PhaseCourses as $draftKey => $draftPhaseCourse) {
                                                        if ($draftPhaseCourse->PatientCourse) {
                                                            if ($key == $draftKey) {
                                                                $updatePhaseCourse = PatientCourses::find($draftPhaseCourse->PatientCourse->id);
                                                                $updatePhaseCourse->round_info = $phaseCourse->PatientCourse->round_info;
                                                                $updatePhaseCourse->course_feedback = $phaseCourse->PatientCourse->course_feedback;
                                                                $updatePhaseCourse->save();
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    $scheduleArray = [];

                                    // copy data of phase assessents
                                    if($phase->PhaseAssessments && count($phase->PhaseAssessments)) {
                                        foreach ($phase->PhaseAssessments as $key => $assessment) {
                                            if($draftPhase->PhaseAssessments && count($draftPhase->PhaseAssessments)) {
                                                foreach ($draftPhase->PhaseAssessments as $draftKey => $draftAssessment) {
                                                    if ($key == $draftKey) {

                                                        //phases asseement schedule
                                                        foreach ($assessment->PatientAssessmentTemplates->Schedules as $planScheduleKey => $schedule) {
                                                            foreach ($draftAssessment->PatientAssessmentTemplates->Schedules as $draftScheduleKey => $draftSchedule) {
                                                                if ($planScheduleKey == $draftScheduleKey) {
                                                                    $scheduleArray[$schedule->id] = $draftSchedule->id;
                                                                }
                                                            }
                                                        }

                                                        foreach ($assessment->PatientAssessmentTemplates->Categories as $planCatKey => $category) {
                                                            foreach ($draftAssessment->PatientAssessmentTemplates->Categories as $draftCatKey => $draftCategory) {
                                                                if ($planCatKey == $draftCatKey) {
                                                                    if ($category->is_active && $draftCategory->is_active) {

                                                                        if($category->SubCategories && count($category->SubCategories)) {
                                                                            foreach($category->SubCategories as $planSubCatKey => $subCategories) {
                                                                                if($draftCategory->SubCategories && count($draftCategory->SubCategories)) {
                                                                                    foreach($draftCategory->SubCategories as $draftSubCatKey => $draftSubCategories) {
                                                                                        if ($planSubCatKey == $draftSubCatKey) {
                                                                                            if ($subCategories->is_active && $draftSubCategories->is_active) {

                                                                                                $decoded_json = (array)json_decode($subCategories->measurements_json);
                                                                                                $final_json = [];
                                                                                                foreach ($decoded_json as $key => $value) {
                                                                                                    $final_json[$key] = [];
                                                                                                    foreach ($value as $scheduleId => $assessmentData) {
                                                                                                        $final_json[$key][$scheduleArray[$scheduleId]] = (array)$assessmentData;
                                                                                                    }
                                                                                                }
                                                                                                $updateSubCategory = PatientAssessmentSubCategories::find($draftSubCategories->id);
                                                                                                $updateSubCategory->measurements_json = json_encode($final_json);
                                                                                                $updateSubCategory->save();

                                                                                            }
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                        }

                                                                    }
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }

                                }

                            }

                        }
                        $count++;
                    }
                    return true;
                }
            }
        }

    }

    //function to get the therapy plan Phases popup data
    public function getTherapyPlanPhasesPopupData(Request $request, $id = null) {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        $therapyPlanPhasesData = [];
        $therapyPlanPhasesData = PatientTherapyPlanTemplates::with(['Phases', 'Indications', 'BodyRegions'])->where('id', decrypt($id))->first();
        //to set the start and end date to phases
        $dates = array();
        $total_duration = 0;
        if (!empty($therapyPlanPhasesData)) {
            foreach ($therapyPlanPhasesData->Phases as $main_key => $data) {
                $start_date = $therapyPlanPhasesData->start_date;
                //Set the start/end date for every phases
                if ($main_key == 0) {
                    $dates['start_date'][$main_key] = Carbon::parse($start_date);
                    $dates['end_date'][$main_key] = Carbon::parse($start_date)->addWeeks($data->duration)->subDay(1);
                } else {
                    $dates['start_date'][$main_key] = Carbon::parse($dates['end_date'][$main_key - 1])->addDay();
                    $dates['end_date'][$main_key] = Carbon::parse($dates['start_date'][$main_key])->addWeeks($data->duration)->subDay(1);
                }
                $data->start_date = $dates['start_date'][$main_key];
                $data->end_date = $dates['end_date'][$main_key];
            }
        }
        return view('common-views.phases-add-edit-popup', compact('therapyPlanPhasesData', 'id'));
    }
    // function edit/update details of patient plan phases.
    public function updatePatientPlanPhasesDetail(Request $request, $id = null) {
        //to edit the assigned therapy plan
        $therapy_plan_templates = PatientTherapyPlanTemplates::find(decrypt($id));
        $phases_ids_array = array();
        $existing_phases_id = array();
        if(!empty($request->phase)) {
            foreach($request->phase as $phases) {
                if (array_key_exists('exists_id', $phases)) {
                    $update_phase = PatientPhases::find($phases["exists_id"]);
                } else {
                    //for insert the new phases
                    $update_phase = new PatientPhases;
                }
                $update_phase->therapy_plan_templates_id = $therapy_plan_templates->id;
                $update_phase->duration = $phases['duration'];
                $update_phase->name = $phases['name'];
                $update_phase->save();
                $existing_phases_id[] = $update_phase->id;
            }
        }
        //get existing phase details
        $existing_phases = PatientPhases::where('therapy_plan_templates_id', $therapy_plan_templates->id)->select('id')->get();
        if (!empty($existing_phases)) {
            //created the array of existing exercise-groups-id
            foreach ($existing_phases as $phase_data) {
                $phases_ids_array[] = $phase_data->id;
            }
        }
        //get the deleted phases
        $deleted_phases = array_diff($phases_ids_array, $existing_phases_id);
        foreach ($deleted_phases as $id) {
            //to remove the phases
            $delete_phases = PatientPhases::destroy($id);
        }

        $message = \Lang::get('lang.therapy-plan-template-update-msg');
        $request->session()->flash('alert-success', $message);
        return response()->json(['success' => \Lang::get('lang.therapy-plan-template-update-msg'), 'status' => 200, 'id' => encrypt($therapy_plan_templates->id), 'for' => 'assign-therapy-plan' ], 200);
    }

    private function discardChanges($plan_id, $draft_id) {
        $plan_network = PatientPlanNetwork::where('plan_id', $plan_id)
        ->update(['draft_id' => null]);
        $plan_template = PatientTherapyPlanTemplates::destroy($draft_id);
    }

}
