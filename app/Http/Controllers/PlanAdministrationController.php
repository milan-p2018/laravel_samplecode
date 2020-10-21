<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\AssessmentTemplates;
use App\AssessmentSubCategories;
use App\AssessmentCategories;
use App\Indication;
use App\BodyRegions;
use App\Exercises;
use App\Courses;
use View;
use Helper;
use Validator;
use Storage;
use File;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use App\FavoriteCourses;
use App\FavoriteExercises;
use App\CourseExercises;
use App\Exports\AssessmentExport;
use Maatwebsite\Excel\Facades\Excel;
use App\TherapyPlanTemplates;
use Carbon\Carbon;
use App\Phases;
use App\Limitations;
use App\PatientsMeetings;
use App\PhaseExercises;
use App\PhaseCourses;
use App\TherapyPlanCourses;
use App\TherapyPlanCourseExercises;
use App\OrganizationDistributor;
use App\OrganizationPatient;
use App\TherapyPlanAssessmentTemplates;
use App\TherapyPlanAssessmentCategories;
use App\TherapyPlanAssessmentSubCategories;
use App\PhaseAssessments;
use App\Patient\PatientTherapyPlanTemplates;
use App\Patient\PatientPhases;
use App\Patient\PatientExercises;
use App\Patient\PatientPhaseCourses;
use App\Patient\PatientCourses;
use App\ExerciseMaterialsLists;
use App\AssessmentPatientSchedules;
use App\TherapyPlanAssessmentSchedules;

class PlanAdministrationController extends Controller
{
    public function __construct()
    {
        //Module_id = 7 [Plan Admin]
        //Sub_module_id = 1 [Plan Admin]
        $this->middleware('check-permission:7__1__view', ['only' => ['getTherapyPlanTemplates','getTherapyPlanDetails', 'getAssessmentData', 'getExercisesData', 'getCoursesData', 'getAssessmentDetails']]);
        $this->middleware('check-permission:7__1__multi-purpose-func', ['only' => ['getAssessmentPopupData', 
            'getExercisePopupData', 'getCoursePopupData', 'getTherapyPlanExercisesPopupData', 'getTherapyPlanPopupData']]);
    }
    //function to get the treatement plan templates
    public function getTherapyPlanTemplates(Request $request, $id = null)
    {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        $templateData = TherapyPlanTemplates::with(['Phases', 'Indications', 'BodyRegions', 'AllExercises', 'AllCourses', 'AllAssessments']);
        $indications = Indication::all();
        $body_regions = BodyRegions::all();
        //to get the all the assessment data
        $defaultTemplates = [];
        $ownTemplates = [];
        $templateData = $templateData->where(function($query) use ($organization_id){
            $query->whereNull('user_id');
            $query->whereNull('organization_id');
            $query->orWhere(function($query) use ( $organization_id ) {
                $query->where('organization_id', $organization_id);
            });
        });
        $own_plan_template_count = 0;
        $default_plan_template_count = 0;
        //to get total count of exercises
        $count_plan_template = $templateData->get();
        foreach($count_plan_template as $ex) {
            if($ex->is_default == 1) {
                $default_plan_template_count = $default_plan_template_count + 1;
            } else {
                $own_plan_template_count = $own_plan_template_count + 1;
            }
        }
        if($request->has('show_data') && $request->show_data) {
            $search_value = $request->search_input;
            $indication_id = $request->indication;
            $body_region_id = $request->body_region;
            $sort_by = $request->sort_by;

            //sort by indication
            if(!empty($indication_id)) {
                $templateData = $templateData->whereHas('Indications', function($query) use ($indication_id) {
                    $query->whereIn('indication_id', $indication_id);
                });
            }
            //sort by body_regions
            if(!empty($body_region_id)) {
                $templateData = $templateData->whereHas('BodyRegions', function($query) use ($body_region_id) {
                    $query->whereIn('body_regions_id', $body_region_id);
                });
            }
            //sort by fields
            if(!empty($sort_by)) {
                if($sort_by != 'created_by') {
                    $templateData = $templateData->orderBy('name', $sort_by);
                } else {
                    $templateData = $templateData->orderBy('created_at', 'desc');
                }
            } else {
                $templateData = $templateData->orderBy('id', 'desc');
            }
            //search functionality
            if(!empty($search_value)) {
                $templateData = $templateData->where(function($query) use ($search_value){
                    $searchColumn = ['name'];
                    foreach ($searchColumn as $singleSearchColumn) {
                        $query->Where($singleSearchColumn, "LIKE", '%' . $search_value . '%');
                    }
                });
            }
            $templateData = $templateData->get();
            //to distinguish the own and default template
            foreach($templateData as $templates) {
                $total_duration = 0;
                foreach ($templates->Phases as $key => $data) {
                    $total_duration = $total_duration + $data->duration;
                }
                $templates->total_duration = $total_duration;
                if($templates->is_default == 1) {
                    $defaultTemplates[] = $templates;
                } else {
                    $ownTemplates[] = $templates;
                }
            }
            return view('plan-administration.therapy-plan-templates.therapy-plan-templates-view', compact('templateData', 'ownTemplates', 'defaultTemplates', 'own_plan_template_count', 'default_plan_template_count'));
        }
        $common_data = $this->setCommonData();
        View::share('common_data', $common_data);
        return view('plan-administration.therapy-plan-templates.therapy-plan-templates', compact('indications', 'body_regions'));
    }

    //function to get the detail fo therapy plan template
    public function getTherapyPlanDetails(Request $request, $id) {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        $assessments = AssessmentTemplates::where(function($query) use ($organization_id){
                $query->whereNull('user_id');
                $query->whereNull('organization_id');
                $query->orWhere(function($query) use ( $organization_id ) {
                    $query->where('organization_id', $organization_id);
                });
            })->get();
        $templateData = TherapyPlanTemplates::with(['Phases.Limitations', 'Phases.PhaseCourses','Phases.PhaseAssessments.TherapyPlanAssessments.Categories.SubCategories','Indications', 'BodyRegions', 'Phases.PhaseExercises.Exercises', 'Phases.PhaseCourses.TherapyPlanCourses.TherapyPlanCourseExercises.Exercises', 'Phases.PhaseCourses.TherapyPlanCourses.BodyRegions', 'AllExercises', 'AllCourses', 'AllAssessments']);
        $templateData = $templateData->find(decrypt($id));
        $dates = array();
        $total_duration = 0;
        if (!empty($templateData)) {
            foreach ($templateData->Phases as $main_key => $data) {
                $start_date = $templateData->start_date;
                $total_duration = $total_duration + $data->duration;
            }
        }
        $common_name = array(
            'name' => $templateData->name,
        );
        $phaseData = $templateData->Phases;
        $back_url = route('administration.therapy-plan-templates');
        $common_data = $this->setCommonData($common_name, false, $back_url);
        View::share('common_data', $common_data);
        return view('plan-administration.therapy-plan-templates.plan-details', compact('templateData', 'total_duration', 'id', 'assessments', 'phaseData'));
    }

    //function to get the therapy plan popup data
    public function getTherapyPlanPopupData(Request $request, $id = null) {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        //to check the whch type of user belongs to current organization
        $rights_user = OrganizationDistributor::with(['OrganizationData.WorkerData', 'Group'])->whereHas('Group', function ($query) use ($organization_id) {
            $query->where('id', $organization_id);
        })->whereHas('OrganizationData', function ($query) use ($organization_id) {
            $query->whereNotNull('lanr')->where('worker_data_species_id', 'lang_doc');
            $query->orWhere('worker_data_species_id', 'lang_physio');
        })->whereNotNull('verified_at')->get();
        $user_access = [];
        foreach($rights_user as $user) {
            if($user->OrganizationData->worker_data_species_id == 'lang_doc' && !empty($user->OrganizationData->lanr)) {
                !in_array("doctor", $user_access) ? ($user_access[1] = 'doctor') : '';
            } else if($user->OrganizationData->worker_data_species_id == 'lang_physio') {
                !in_array("physio", $user_access) ? ($user_access[2] = 'physio') : '';
            }
        }
        if(empty($user_access)) {
            $user_access[1] = 'doctor';
            $user_access[2] = 'physio';
        }
        $therapyPlanPhasesData = [];
        $model_type = '';
        $type = $request->type;
        $body_regions = BodyRegions::all();
        $indications = Indication::all();
        $total_duration = 0;
        if($request->has('model_type')) {
            $model_type = $request->model_type;
        }
        if (!empty($id) && $request->type == 'add-edit-therapy-plan') {
            //to get the specific therapy plan template data
            $therapyPlanPhasesData = TherapyPlanTemplates::with(['Phases', 'Indications', 'BodyRegions'])->where('id', decrypt($id))->first();
        } else if(!empty($id) && $request->type == 'assign-therapy-plan') {
            $therapyPlanPhasesData = PatientTherapyPlanTemplates::with(['Phases', 'Indications', 'BodyRegions'])->where('id', decrypt($id))->first();
            //to set the start and end date to phases
            $dates = array();
            if (!empty($therapyPlanPhasesData)) {
                foreach ($therapyPlanPhasesData->Phases as $main_key => $data) {
                    $start_date = $therapyPlanPhasesData->start_date;
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
                }
            }
        }
        if($request->type == "add-edit-therapy-plan" || $request->type == 'assign-therapy-plan') {
            return view('common-views.therapy-plan-phases-add-edit-popup', compact('therapyPlanPhasesData', 'id', 'total_duration', 'body_regions', 'model_type', 'indications', 'user_access', 'type'));
        }
    }

    //function to get the phase details
    public function getPhaseDetails(Request $request, $id)
    {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        if($request->has('for') && $request->for == 'assigned-plan') {
            $therapy_plans = PatientPhases::with(['TherapyPlanTemplates', 'Limitations'])->whereHas('TherapyPlanTemplates', function ($query) use ($id, $request, $organization_id) {
                            $query->where('id', decrypt($request->id));
                        })->get();
        } else {
            $therapy_plans = Phases::with(['TherapyPlanTemplates', 'Limitations'])->whereHas('TherapyPlanTemplates', function ($query) use ($id, $request, $organization_id) {
                $query->where('id', decrypt($request->id));
            })->get();
        }
        $dates = array();
        $main_data = array();
        $ids = array();
        if (!empty($therapy_plans)) {
            foreach ($therapy_plans as $main_key => $data) {
                //Set the start/end date for every phases
                if ($main_key == 0) {
                    $dates['start_date'][$main_key] = Carbon::parse($data->TherapyPlanTemplates->start_date);
                    $dates['end_date'][$main_key] = Carbon::parse($data->TherapyPlanTemplates->start_date)->addWeeks($data->duration)->subDay(1);
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
        //get the deleted limitations
        $deleted_limitations = array_diff($limitations_ids_array, $existing_limitations_id);
        foreach ($deleted_limitations as $id) {
            //to remove the exercise groups from the database
            $delete_limitations = Limitations::destroy($id);
        }
        $message = \Lang::get('lang.phase-limitation-successfully-updates');
        $request->session()->flash('alert-success', $message);
        return response()->json(['success' => \Lang::get('lang.phase-limitation-successfully-updates'), 'status' => 200], 200);
    }

    //function to save tht therapy-plan details
    public function saveTherapyPlan(Request $request, $id = null)
    {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        $therapy_templates = TherapyPlanTemplates::where(function($query) use ( $organization_id )  {
                            $query->whereNull('user_id');
                            $query->whereNull('organization_id');
                            $query->orWhere(function($query) use ( $organization_id ) {
                                $query->where('organization_id', $organization_id);
                            });
                        });
        if ($request->type == 'assign-therapy-plan') {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'indications' => 'required',
                'start_date' => 'nullable',
                'body_regions' => 'required',
                'phase_rights' => 'required',
                'assessment_rights' => 'required',
                'exe_rights' => 'required',
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'indications' => 'required',
                'start_date' => 'nullable',
                'body_regions' => 'required',
                'phase_rights' => 'required',
                'assessment_rights' => 'required',
                'exe_rights' => 'required',
                'phase' => 'required'
            ]);
        }
        if ($validator->fails()) {
            $err_response  = [
                'success' => false,
                'errors' => $validator->messages()
            ];
            return response()->json($err_response, 400);
        }
        if($request->type == 'create-therapy-plan') {
            $therapy_templates = $therapy_templates->select('name')->get();
            foreach($therapy_templates as $template) {
                if(strtolower($request->name) == strtolower($template->name)) {
                    return response()->json(['error' => \Lang::get('lang.assessment-name-exist-error'), 'status' => 400, 'name_error' => true], 400);
                }
            }
            $therapy_plan_templates = new TherapyPlanTemplates;
            $therapy_plan_templates->name = $request->name;
            // $res = explode("/", $request->start_date);
            // $changedDate = $res[1] . "-" . $res[0] . "-" . $res[2];
            // $therapy_plan_templates->start_date = Carbon::createFromFormat('d-m-Y', $changedDate);
            $therapy_plan_templates->user_id = Auth::user()->id;
            $therapy_plan_templates->organization_id = $organization_id;
            $therapy_plan_templates->created_by = Auth::user()->id;
            $therapy_plan_templates->updated_by = Auth::user()->id;
            $therapy_plan_templates->phases_editable_by = $request->phase_rights;
            $therapy_plan_templates->assessments_editable_by = $request->assessment_rights;
            $therapy_plan_templates->exercises_courses_editable_by = $request->exe_rights;   
            $therapy_plan_templates->save();
            //to save the indications in the therapy_plan_indications table(pivot)
            $indications = Indication::find($request->indications);
            $therapy_plan_templates->Indications()->attach($indications);
            //to save the body_regions in the therapy_plan_body_regions table(pivot)
            $body_regions = BodyRegions::find($request->body_regions);
            $therapy_plan_templates->BodyRegions()->attach($body_regions);
            if(!empty($request->phase)) {
                foreach($request->phase as $key => $phases) {
                    $phase = New Phases;
                    $phase->therapy_plan_templates_id = $therapy_plan_templates->id;
                    $phase->duration = $phases['duration'];
                    $phase->name = $phases['name'];
                    $phase->save();
                    if($key == 1) {
                        session(['therapy_plan_current_phase' => $phase->id]);    
                    }
                }
            }
            $message = \Lang::get('lang.therapy-plan-template-create-msg');
            $request->session()->flash('alert-success', $message);
            session(['show_therapy_plan' => 'Eigene']);
            return response()->json(['success' => \Lang::get('lang.therapy-plan-template-create-msg'), 'status' => 200, 'id' => encrypt($therapy_plan_templates->id), 'for' => 'add-plan'], 200);

        } else if ($request->type == 'edit-therapy-plan' && !empty($id)) {
            $therapy_templates = $therapy_templates->where('id', '!=', decrypt($id))->select('name')->get();
            foreach($therapy_templates as $template) {
                if(strtolower($request->name) == strtolower($template->name)) {
                    return response()->json(['error' => \Lang::get('lang.assessment-name-exist-error'), 'status' => 400, 'name_error' => true], 400);
                }
            }
            $therapy_plan_templates = TherapyPlanTemplates::find(decrypt($id));
            $therapy_plan_templates->name = $request->name;
            $therapy_plan_templates->user_id = Auth::user()->id;
            $therapy_plan_templates->organization_id = $organization_id;
            $therapy_plan_templates->updated_by = Auth::user()->id;
            $therapy_plan_templates->phases_editable_by = $request->phase_rights;
            $therapy_plan_templates->assessments_editable_by = $request->assessment_rights;
            $therapy_plan_templates->exercises_courses_editable_by = $request->exe_rights;   
            $therapy_plan_templates->save();
            //to save the indications in the therapy_plan_indications table(pivot)
            $indications = Indication::find($request->indications);
            $therapy_plan_templates->Indications()->sync($indications);
            //to save the body_regions in the therapy_plan_body_regions table(pivot)
            $body_regions = BodyRegions::find($request->body_regions);
            $therapy_plan_templates->BodyRegions()->sync($body_regions);
            $phases_ids_array = array();
            $existing_phases_id = array();
            if(!empty($request->phase)) {
                foreach($request->phase as $phases) {
                    if (array_key_exists('exists_id', $phases)) {
                        $update_phase = Phases::find($phases["exists_id"]);
                    } else {
                        //for insert the new phases
                        $update_phase = new Phases;
                    }
                    $update_phase->therapy_plan_templates_id = $therapy_plan_templates->id;
                    $update_phase->duration = $phases['duration'];
                    $update_phase->name = $phases['name'];
                    $update_phase->save();
                    $existing_phases_id[] = $update_phase->id;
                }
            }
            //get existing phase details
            $existing_phases = Phases::where('therapy_plan_templates_id', $therapy_plan_templates->id)->select('id')->get();
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
            $message = \Lang::get('lang.therapy-plan-template-update-msg');
            $request->session()->flash('alert-success', $message);
            session(['show_therapy_plan' => 'Eigene']);
            return response()->json(['success' => \Lang::get('lang.therapy-plan-template-update-msg'), 'status' => 200, 'id' => encrypt($therapy_plan_templates->id), 'for' => 'edit-plan' ], 200);

        } else if( $request->type == 'assign-therapy-plan') {
            //to edit the assigned therapy plan
            $therapy_plan_templates = PatientTherapyPlanTemplates::find(decrypt($id));
            $therapy_plan_templates->name = $request->name;
            $res = explode(".", $request->start_date);
            $changedDate = $res[0] . "-" . $res[1] . "-" . $res[2];
            $therapy_plan_templates->start_date = Carbon::parse($changedDate)->format('Y-m-d H:i:s');
            $therapy_plan_templates->user_id = Auth::user()->id;
            $therapy_plan_templates->organization_id = $organization_id;
            $therapy_plan_templates->updated_by = Auth::user()->id;
            $therapy_plan_templates->phases_editable_by = $request->phase_rights;
            $therapy_plan_templates->assessments_editable_by = $request->assessment_rights;
            $therapy_plan_templates->exercises_courses_editable_by = $request->exe_rights;   
            $therapy_plan_templates->save();
            //to save the indications in the therapy_plan_indications table(pivot)
            $indications = Indication::find($request->indications);
            $therapy_plan_templates->Indications()->sync($indications);
            //to save the body_regions in the therapy_plan_body_regions table(pivot)
            $body_regions = BodyRegions::find($request->body_regions);
            $therapy_plan_templates->BodyRegions()->sync($body_regions);
            $message = \Lang::get('lang.therapy-plan-template-update-msg');
            $request->session()->flash('alert-success', $message);
            return response()->json(['success' => \Lang::get('lang.therapy-plan-template-update-msg'), 'status' => 200, 'id' => encrypt($therapy_plan_templates->id), 'for' => 'assign-therapy-plan' ], 200);
        }
    }

    //fuunction to save the phase exercises
    public function saveTherapyPlanExercises(Request $request) {
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
                    $phase_exercises = New PhaseExercises();
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
            if(!empty($request->exercise_val) && $request->add_to_catalog == "false") {
                $exercises = Exercises::find(decrypt($request->exercise_val));
                $exercises->phase_id = decrypt($request->phase_id);
                $exercises->save();
            }
            $message = \Lang::get('lang.exercises-successfully-added-msg');
            $request->session()->flash('alert-success', $message);
            return response()->json(['success' => \Lang::get('lang.exercises-successfully-added-msg'), 'status' => 200], 200);
        } else if($request->save_type == 'edit-model') {
            $updated_exe_ids = array();
            if(!empty($request->exercise)) {
                foreach($request->exercise as $exe) {
                    $frequency = [];
                    $phase_exercises = New PhaseExercises();
                    if(array_key_exists('exist_phase_exe_id', $exe)) {
                        $phase_exercises = PhaseExercises::find($exe['exist_phase_exe_id']);
                    }
                    $phase_exercises->exercise_id = $exe['exercise_id'];
                    $phase_exercises->phase_id = decrypt($request->phase_id);
                    $phase_exercises->type = $exe['type'];
                    // $phase_exercises->frequency = $exe['frequency'];
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
            $existing_exercises = PhaseExercises::where('phase_id', decrypt($request->phase_id))->select('id')->get();
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
                $delete_categories_data = PhaseExercises::destroy($id);
            }
            if(!empty($request->exercise_val) && $request->add_to_catalog == "false") {
                $exercises = Exercises::find(decrypt($request->exercise_val));
                $exercises->phase_id = decrypt($request->phase_id);
                $exercises->save();
            }
            $message = \Lang::get('lang.exercises-successfully-added-msg');
            $request->session()->flash('alert-success', $message);
            return response()->json(['success' => \Lang::get('lang.exercises-successfully-added-msg'), 'status' => 200], 200);
        }
    }

    //function to get the treatement plan templates
    public function getAssessmentData(Request $request, $id = null)
    {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        $assessmentData = AssessmentTemplates::with(['Categories.SubCategories', 'Indications', 'BodyRegions']);
        if (!empty($id)) {
            //to get the specific assessment data
            $assessmentData = $assessmentData->where('id', decrypt($id))->get();
            $data = array(
                'name' => $assessmentData[0]->template_name,
            );
            $back_url = route('administration.assessments');
            $common_data = $this->setCommonData($data, false, $back_url);
            View::share('common_data', $common_data);
            return view('plan-administration.assessments-details', compact('id', 'assessmentData'));
        }
        $indications = Indication::all();
        $body_regions = BodyRegions::all();
        //to get the all the assessment data
        $defaultAssessmentTemplates = [];
        $ownAssessmentTemplates = [];
        $assessmentData = $assessmentData->where(function($query) use ($organization_id){
            $query->whereNull('user_id');
            $query->whereNull('organization_id');
            $query->orWhere(function($query) use ( $organization_id ) {
                $query->where('organization_id', $organization_id);
            });
        });
        $own_asmt_count = 0;
        $default_asmt_count = 0;
        //to get total count of exercises
        $count_asmt = $assessmentData->get();
        foreach($count_asmt as $ex) {
            if($ex->is_default == 1) {
                $default_asmt_count = $default_asmt_count + 1;
            } else {
                $own_asmt_count = $own_asmt_count + 1;
            }
        }
        if($request->has('show_data') && $request->show_data) {
            $search_value = $request->search_input;
            $indication_id = $request->indication;
            $body_region_id = $request->body_region;
            $sort_by = $request->sort_by;

            //sort by indication
            if(!empty($indication_id)) {
                $assessmentData = $assessmentData->whereHas('Indications', function($query) use ($indication_id) {
                    $query->whereIn('indication_id', $indication_id);
                });
            }
            //sort by body_regions
            if(!empty($body_region_id)) {
                $assessmentData = $assessmentData->whereHas('BodyRegions', function($query) use ($body_region_id) {
                    $query->whereIn('body_regions_id', $body_region_id);
                });
            }
            //sort by fields
            if(!empty($sort_by)) {
                if($sort_by != 'created_by') {
                    $assessmentData = $assessmentData->orderBy('template_name', $sort_by);
                } else {
                    $assessmentData = $assessmentData->orderBy('created_at', 'desc');
                }
            } else {
                $assessmentData = $assessmentData->orderBy('id', 'desc');
            }
            //search functionality
            if(!empty($search_value)) {
                $assessmentData = $assessmentData->where(function($query) use ($search_value){
                    $searchColumn = ['template_name'];
                    foreach ($searchColumn as $singleSearchColumn) {
                        $query->Where($singleSearchColumn, "LIKE", '%' . $search_value . '%');
                    }
                });
            }
            $assessmentData = $assessmentData->get();
            //to distinguish the own and default template
            foreach($assessmentData as $assessments) {
                if($assessments->is_default == 1) {
                    $defaultAssessmentTemplates[] = $assessments;
                } else {
                    $ownAssessmentTemplates[] = $assessments;
                }
            }
            return view('plan-administration.assessment-view', compact('assessmentData', 'ownAssessmentTemplates', 'defaultAssessmentTemplates' , 'own_asmt_count', 'default_asmt_count'));
        }
        $common_data = $this->setCommonData();
        View::share('common_data', $common_data);
        return view('plan-administration.assessments', compact('indications', 'body_regions'));
    }

    //function to get the exercises
    public function getExercisesData(Request $request, $id = null)
    {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        $exercisesData = Exercises::with(['FavoriteExercises'])->where(function($query) use ($organization_id){
                            $query->whereNull('user_id');
                            $query->whereNull('organization_id');
                            $query->orWhere(function($query) use ( $organization_id ) {
                                $query->where('organization_id', $organization_id);
                            });
                            $query->whereNull('course_id');
                            $query->whereNull('phase_id');
                            $query->whereNull('patient_id');
                        });
        $defaultExercises = [];
        $ownExercises = [];
        $own_exercise_count = 0;
        $default_exercise_count = 0;
        //to get total count of exercises
        $count_exercises = $exercisesData->get();
        foreach($count_exercises as $ex) {
            if($ex->is_default == 1) {
                $default_exercise_count = $default_exercise_count + 1;
            } else {
                $own_exercise_count = $own_exercise_count + 1;
            }
        }
        $body_regions = BodyRegions::all();
        $indications = Indication::all();
        $materials = ExerciseMaterialsLists::all();
        if($request->has('show_data') && $request->show_data) {
            $search_value = $request->search_input;
            $position = $request->position;
            $target = $request->target;
            $difficulty = $request->difficulty;
            $tools = $request->tools;
            $body_region_id = $request->body_region;
            $sort_by = $request->sort_by;
            $is_favorite = $request->is_favorite;
            $indication_id = $request->indication;

            //sort by position
            if(!empty($position)) {
                $exercisesData = $exercisesData->whereIn('position', $position);
            }
            //sort by target
            if(!empty($target)) {
                $exercisesData = $exercisesData->whereIn('target', $target);
            }
            //sort by difficulty
            if(!empty($difficulty)) {
                $exercisesData = $exercisesData->whereIn('difficulty', $difficulty);
            }
            //sort by tools
            if(!empty($tools)) {
                $exercisesData = $exercisesData->whereHas('Materials', function($query) use ($tools) {
                    $query->whereIn('exercise_materials_lists_id', $tools);
                });
            }
            //sort by body_regions
            if(!empty($body_region_id)) {
                $exercisesData = $exercisesData->whereHas('BodyRegions', function($query) use ($body_region_id) {
                    $query->whereIn('body_regions_id', $body_region_id);
                });
            }
            //sort by indication
            if(!empty($indication_id)) {
                $exercisesData = $exercisesData->whereHas('Indications', function($query) use ($indication_id) {
                    $query->whereIn('indication_id', $indication_id);
                });
            }
            //sort by fields
            if(!empty($sort_by)) {
                if($sort_by != 'created_by') {
                    $exercisesData = $exercisesData->orderBy('name', $sort_by);
                } else {
                    $exercisesData = $exercisesData->orderBy('created_at', 'desc');
                }
            } else {
                $exercisesData = $exercisesData->orderBy('id', 'desc');
            }
            //sort by favorites
            if($is_favorite == 'true') {
                $exercisesData = $exercisesData->whereHas('FavoriteExercises', function($query) use ($organization_id) {
                        $query->where('organization_id', $organization_id);
                });
            }
            //search functionality
            if(!empty($search_value)) {
                $exercisesData = $exercisesData->where(function($query) use ($search_value){
                    $searchColumn = ['name'];
                    foreach ($searchColumn as $singleSearchColumn) {
                        $query->Where($singleSearchColumn, "LIKE", '%' . $search_value . '%');
                    }
                });
            }
            $exercisesData = $exercisesData->whereNull('patient_id')->get();
            //to distinguish the own and default exercises
            foreach($exercisesData as $exercises) {
                if($exercises->is_default == 1) {
                    $defaultExercises[] = $exercises;
                } else {
                    $ownExercises[] = $exercises;
                }
            }
            return view('plan-administration.exercises.exercise-view', compact('exercisesData', 'ownExercises', 'defaultExercises', 'own_exercise_count', 'default_exercise_count'));
        }
        $common_data = $this->setCommonData();
        View::share('common_data', $common_data);
        return view('plan-administration.exercises.exercises', compact('body_regions', 'indications', 'materials'));
    }

    //function to get the courses data
    public function getCoursesData(Request $request) {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        $coursesData = Courses::with(['FavoriteCourses'])->where(function($query) use ($organization_id){
                            $query->whereNull('user_id');
                            $query->whereNull('organization_id');
                            $query->orWhere(function($query) use ( $organization_id ) {
                                $query->where('organization_id', $organization_id);
                            });
                        });
        $defaultCourses = [];
        $ownCourses = [];
        $own_course_count = 0;
        $default_course_count = 0;
        //to get total count of courses
        $count_courses = $coursesData->get();
        foreach($count_courses as $co) {
            if($co->is_default == 1) {
                $default_course_count = $default_course_count + 1;
            } else {
                $own_course_count = $own_course_count + 1;
            }
        }
        $body_regions = BodyRegions::all();
        $indications = Indication::all();
        $materials = ExerciseMaterialsLists::all();
        if($request->has('show_data') && $request->show_data) {
            $search_value = $request->search_input;
            $body_region_id = $request->body_region;
            $indication = $request->indication;
            $sort_by = $request->sort_by;
            $is_favorite = $request->is_favorite;
            //sort by body_regions
            if(!empty($body_region_id)) {
                $coursesData = $coursesData->whereHas('BodyRegions', function($query) use ($body_region_id) {
                    $query->whereIn('body_regions_id', $body_region_id);
                });
            }
            //sort by indication
            if(!empty($indication)) {
                $coursesData = $coursesData->whereHas('Indications', function($query) use ($indication) {
                    $query->whereIn('indication_id', $indication);
                });
            }
            //sort by fields
            if(!empty($sort_by)) {
                if($sort_by != 'created_at') {
                    $coursesData = $coursesData->orderBy('name', $sort_by);
                } else {
                    $coursesData = $coursesData->orderBy('created_at', 'desc');
                }
            } else {
                $coursesData = $coursesData->orderBy('id', 'desc');
            }
            //search functionality
            if(!empty($search_value)) {
                $coursesData = $coursesData->where(function($query) use ($search_value){
                    $searchColumn = ['name'];
                    foreach ($searchColumn as $singleSearchColumn) {
                        $query->Where($singleSearchColumn, "LIKE", '%' . $search_value . '%');
                    }
                });
            }

            //sort by favorites
            if($is_favorite == 'true') {
                $coursesData = $coursesData->whereHas('FavoriteCourses', function($query) use ($organization_id) {
                        $query->where('organization_id', $organization_id);
                });
            }
            $coursesData = $coursesData->get();
            //to distinguish the own and default courses
            foreach($coursesData as $courses) {
                if($courses->is_default == 1) {
                    $defaultCourses[] = $courses;
                } else {
                    $ownCourses[] = $courses;
                }
            }
            return view('plan-administration.courses.courses-view', compact('coursesData', 'defaultCourses', 'ownCourses', 'own_course_count', 'default_course_count'));
        }
        $common_data = $this->setCommonData();
        View::share('common_data', $common_data);
        return view('plan-administration.courses.courses', compact('body_regions', 'indications', 'materials'));
    } 

    //function to get the assessment template details of the plan
    public function getAssessmentDetails($id)
    {
        $assessmentDetails = AssessmentSubCategories::with(['AssessmentCategories.AssessmentTemplates'])
                                    ->whereHas('AssessmentCategories.AssessmentTemplates', function ($query) use ($id) {
                                        $query->where('id', decrypt($id));
                                    })
                                    ->get();
        if ($assessmentDetails) {
            return response()->json(['data' => $assessmentDetails]);
        }
    }

    //function to change the status of the subcategory or category
    public function changeStatusOfAssessment(Request $request, $id) {
        if($request->type) {
            if($request->type == 'subcategory') {
                $plan_sub_category = AssessmentSubCategories::find(decrypt($id));
                $plan_sub_category->is_active = $request->status;
                $plan_sub_category->save();
            } else if($request->type == 'category') {
                $plan_category = AssessmentCategories::find(decrypt($id));
                $plan_category->is_active = $request->status;
                $plan_category->save();
            }
            return response()->json(['success' => \Lang::get('lang.assessment-status-changed-message'), 'status' => 200], 200);
        } else {
            return response()->json(['error' => \Lang::get('lang.general-error-message'), 'status' => 400], 400);
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
        if($request->has('for') && $request->for == 'assign-therapy-plan') {
            $assessment_type = 'assign-therapy-plan';
        }
        if (!empty($id)) {
            //to get the specific assessment data
            if($request->has('for') && $request->for == 'assign-therapy-plan' && $request->from != 'catalog') {
                $assessmentData = TherapyPlanAssessmentTemplates::with(['Categories.SubCategories.Schedules', 'Indications', 'BodyRegions', 'Schedules'])->where('id', decrypt($id))->first();
            } else {
                $assessmentData = AssessmentTemplates::with(['Categories.SubCategories.Schedules', 'Indications', 'BodyRegions', 'Schedules'])->where('id', decrypt($id))->first();
            }
        }
        if($request->usage == 'for-assigned') {
            $for = '';
            return view('common-views.assessment-modal-template', compact('assessmentData', 'id', 'indications', 'body_regions', 'type', 'assessment_type', 'assessments' , 'for'));
        }
        return view('common-views.assessment-popup', compact('assessmentData', 'id', 'indications', 'body_regions', 'type', 'assessment_type'));
    }

    //function to show the assessment modal popup 
    public function getExercisePopupData(Request $request, $id = null ) {
        $exerciseData = [];
        $model_type = '';
        $body_regions = BodyRegions::all();
        $indications = Indication::all();
        $materials = ExerciseMaterialsLists::all();
        if($request->has('model_type')) {
            $model_type = $request->model_type;
        }
        if (!empty($id)) {
            //to get the specific assessment data
            $exerciseData = Exercises::with(['BodyRegions', 'FavoriteExercises', 'Indications', 'Materials'])->where('id', decrypt($id))->first();
        }
        if($request->type == "exercise-details") {
            $for = $request->has('for') ? $request->for : '';
            return view('common-views.exercise-detail-popup', compact('exerciseData', 'id', 'body_regions', 'indications', 'materials', 'for'));
        }
        if($request->type == "add-edit-exercise") {
            return view('common-views.exercise-add-edit-popup', compact('exerciseData', 'id', 'body_regions', 'model_type', 'indications', 'materials'));
        }
    }
    
    //function to show the courses modal popup 
    public function getCoursePopupData(Request $request, $id = null ) {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        $courseData = [];
        $body_regions = BodyRegions::all();
        $indications = Indication::all();
        $materials = ExerciseMaterialsLists::all();
        $courses = [];
        $course_type = 'courses';  
        $model_type = $request->model_type;
        $exercisesData = Exercises::with(['BodyRegions'])->where(function($query) use ($organization_id){
                            $query->whereNull('user_id');
                            $query->whereNull('organization_id');
                            $query->orWhere(function($query) use ( $organization_id ) {
                                $query->where('organization_id', $organization_id);
                            });
                        })->whereNull('patient_id')->get();
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
            //to get the specific courses data->
            $courseData = Courses::with(['CourseExercises.Exercises', 'BodyRegions', 'Indications'])->where('id', decrypt($id))->first();
            if($request->has('for') && $request->for == 'assign-therapy-plan') {
                $courseData = therapyPlanCourses::with(['TherapyPlanCourseExercises.Exercises', 'BodyRegions', 'Indications'])->where('id', decrypt($id))->first();
            }
        }
        if($request->type == "course-details") {
            return view('common-views.courses-detail-popup', compact('exercisesData', 'courseData','id', 'body_regions'));
        }
        if($request->type == "add-edit-course") {
            return view('common-views.course-add-edit-popup', compact('exercisesData', 'courseData', 'id', 'body_regions', 'indications', 'courses', 'course_type', 'materials', 'model_type'));
        }
    }

    //function to show the exercise popup for therapy plan
    public function getTherapyPlanExercisesPopupData(Request $request, $id = null) {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        $courseData = [];
        $body_regions = BodyRegions::all();
        $indications = Indication::all();
        $materials = ExerciseMaterialsLists::all();
        $type = $request->form_type;
        $exercisesData = Exercises::with(['BodyRegions'])->where(function($query) use ($organization_id){
                            $query->whereNull('user_id');
                            $query->whereNull('organization_id');
                            $query->orWhere(function($query) use ( $organization_id ) {
                                $query->where('organization_id', $organization_id);
                            });
                        })->whereNull('patient_id')->get();
        if (!empty($id)) {
            //to get the specific courses data
            $phaseData = Phases::with(['PhaseExercises.Exercises'])->where('id', decrypt($id))->first();
        }
        if($request->type == "add-edit-exercises") {
            return view('common-views.therapy_plan_exercises', compact('exercisesData','phaseData','id', 'body_regions', 'type', 'indications', 'materials'));
        }
        if($request->type == "add-edit-course") {
            return view('common-views.course-add-edit-popup', compact('exercisesData', 'courseData', 'id', 'body_regions', 'indications', 'materials'));
        }
    }

    //function to save the exercise data
    public function saveExercise(Request $request, $id = null) {
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
        $exercisesData = Exercises::where(function($query) use ($organization_id){
            $query->whereNull('user_id');
            $query->whereNull('organization_id');
            $query->orWhere(function($query) use ( $organization_id ) {
                $query->where('organization_id', $organization_id);
            })->whereNull('patient_id');
        });
        //to copy or new create exercise
        if($request->type == 'create-exercise') {
            $exercisesData = $exercisesData->select('name')->get();
            //to check the exercise name is already exist or not
            foreach($exercisesData as $exercise) {
                if(strtolower($request->name) == strtolower($exercise->name)) {
                    return response()->json(['error' => [\Lang::get('lang.exercise-name-exist-error')], 'status' => 400], 400);
                }
            }

            $exercise = New Exercises;
            $exercise->name = $request->name;
            $exercise->description = $request->description;
            $exercise->position = $request->position;
            $exercise->target = $request->target;
            // $exercise->tools = $request->tools;
            $exercise->difficulty = $request->difficulty;
            $exercise->user_id = Auth::user()->id;
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
            } else if(!empty($request->copied_from)) {
                //if file not uploaded then get it from the exercise that copied
                $old_exercise = Exercises::find(decrypt($request->copied_from));
                if(!empty($old_exercise->image)) {
                    //copy the image from the main exercise for new exercise
                    $file = storage_path($old_exercise->image);
                    $file_extenstion = pathinfo($file, PATHINFO_EXTENSION);
                    $destination = '/exercises/'.preg_replace('/\s+/', '', $request->name) . '_' . time() . '.'.$file_extenstion;
                    Storage::disk('public')->copy($old_exercise->image, $destination);
                    $exercise->image = $destination;
                }
                if(!empty($old_exercise->video)) {
                    //copy the video from the main exercise for new exercise
                    $file = storage_path($old_exercise->video);
                    $file_extenstion = pathinfo($file, PATHINFO_EXTENSION);
                    $destination = '/exercises/videos/'.preg_replace('/\s+/', '', $request->name) . '_' . time() . '.'.$file_extenstion;
                    Storage::disk('public')->copy($old_exercise->video, $destination);
                    $exercise->video = $destination;
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

            //to change the heart icon for exercises
            if($request->favorite == "true") {
                $favorite_exercises = new FavoriteExercises;
                $favorite_exercises->organization_id = $organization_id;
                $favorite_exercises->exercise_id = $exercise->id;
                $favorite_exercises->updated_by = Auth::user()->id;
                $favorite_exercises->save(); 
            }
            $message = \Lang::get('lang.exercise-successfully-created-msg');
            $request->session()->flash('alert-success', $message);
            session(['show_exercise' => 'Eigene']);
            return response()->json(['success' => \Lang::get('lang.exercise-successfully-created-msg'), 'status' => 200, 'id' => encrypt($exercise->id) ], 200);

        } else if($request->type == 'edit-exercise') {
            //to check the exercise name already exist or not
            $exercisesData = $exercisesData->where('id', '!=', decrypt($id))->select('name')->get();
            foreach($exercisesData as $exercise) {
                if(strtolower($request->name) == strtolower($exercise->name)) {
                    return response()->json(['error' => [\Lang::get('lang.exercise-name-exist-error')], 'status' => 400], 400);
                }
            }
            $exercise = Exercises::find(decrypt($id));
            $exercise->name = $request->name;
            $exercise->description = $request->description;
            $exercise->position = $request->position;
            $exercise->target = $request->target;
            // $exercise->tools = $request->tools;
            $exercise->difficulty = $request->difficulty;
            $exercise->updated_by = Auth::user()->id;
            if($request->image_exist == 0) {
                if ($exercise->image != '' && $exercise->image != NULL) {
                    $file_name = explode('/', $exercise->image)[2];
                    Storage::delete('public/exercises/' . $file_name);
                    $exercise->image = Null;
                }
            }
            if($request->video_exist == 0) {
                if ($exercise->video != '' && $exercise->video != NULL) {
                    $video_name = explode('/', $exercise->video)[3];
                    Storage::delete('public/exercises/videos/' . $video_name);
                    $exercise->video = NULL;
                }
            }
            //if file upload
            if($request->has('image') || $request->has('video')) {
                if ($request->has('image') && $request->image != '' && $request->image != NULL) {
                    //if new file uploaded then remove existing image file         
                    if ($exercise->image != '' && $exercise->image != NULL) {
                        $file_name = explode('/', $exercise->image)[2];
                        Storage::delete('public/exercises/' . $file_name);
                    }
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
                    //if new file uploaded then remove existing video file         
                    if ($exercise->video != '' && $exercise->video != NULL) {
                        $video_name = explode('/', $exercise->video)[3];
                        Storage::delete('public/exercises/videos/' . $video_name);
                    }
                    $name = preg_replace('/\s+/', '', $request->name) . '_' . time();
                    // Define folder path
                    $folder = '/exercises/videos/';
                    // Make a file path where image will be stored [ folder path + file name + file extension]
                    $filePath = $folder . $name . '.' . $video->getClientOriginalExtension();
                    // Upload image
                    $this->uploadOne($video, $folder, 'public', $name);
                    // Set user profile image path in database to filePath
                    $exercise->video = $filePath;
                    if(empty($request->image) && empty($exercise->image)) {
                        $exercise->image = Helper::getThumbnailForExerciseVideo($name, $filePath);
                    }
                }
            }
            $exercise->save();
            //to save the body_regions in the exercise_body_regions table(pivot)
            $body_regions = BodyRegions::find(explode(',',$request->body_regions));
            $exercise->BodyRegions()->sync($body_regions);
            //to save the indications in the exercise_indications table(pivot)
            $indications = Indication::find(explode(',',$request->indications));
            $exercise->Indications()->sync($indications);
            //to save the materials in the exercise_materials table(pivot)
            $exe_materials = ExerciseMaterialsLists::find(explode(',',$request->tools));
            $exercise->Materials()->sync($exe_materials);
            //to change the like of exercise
            $favorite_exercises = FavoriteExercises::where('exercise_id', decrypt($id))->where('organization_id', $organization_id)->first();
            if(!empty($favorite_exercises)) {
                if($request->favorite == 'false') {
                    $favorite_exercises->delete();    
                } else {
                    $favorite_exercises->updated_by = Auth::user()->id;
                    $favorite_exercises->save();
                }
            } else {
                if($request->favorite == 'true') {
                    $favorite_exercises = new FavoriteExercises;
                    $favorite_exercises->organization_id = $organization_id;
                    $favorite_exercises->exercise_id = decrypt($id);
                    $favorite_exercises->updated_by = Auth::user()->id;
                    $favorite_exercises->save();
                }
            }
            $message = \Lang::get('lang.exercise-successfully-updated-msg');
            $request->session()->flash('alert-success', $message);
            return response()->json(['success' => \Lang::get('lang.exercise-successfully-updated-msg'), 'status' => 200], 200);
        }
    }
    //function to save the course data
    public function saveCourse(Request $request, $id = null) {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        $coursesData = Courses::with(['FavoriteCourses'])->where(function($query) use ($organization_id){
                            $query->whereNull('user_id');
                            $query->whereNull('organization_id');
                            $query->orWhere(function($query) use ( $organization_id ) {
                                $query->where('organization_id', $organization_id);
                            });
                        });
        $rules = [
            'name' => 'required',
            'description' => 'required',
            'indication' => 'required',
            'body_regions' => 'required',
            'exercise' => 'required',
        ];
        if($request->is_image_exist == 0){
            $rules['image'] = 'required|mimes:jpeg,png,jpg,gif,svg|mimetypes:image/*';
        } else {
            $rules['image'] = 'nullable|mimes:jpeg,png,jpg,gif,svg|mimetypes:image/*';
        }

        //to check the validation rules
        if($request->form_type != 'create-course' && $request->form_type != 'edit-course') {
            $rules['frequency'] = 'required';
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
        if($request->form_type == 'create-course') {
            $save_for = 'courses';
            $coursesData = $coursesData->select('name')->get();
            //to check the exercise name is already exist or not
            foreach($coursesData as $course) {
                if(strtolower($request->name) == strtolower($course->name)) {
                    return response()->json(['error' => [\Lang::get('lang.course-name-exist-error')], 'status' => 400], 400);
                }
            }
            
            $course = New Courses;
            $course->name = $request->name;
            $course->description = $request->description;
            $course->duration = $request->course_time;
            $course->round = $request->round;
            // foreach ($request->frequency as $key => $value) {
            //     $frequency[$value] = $request->count; 
            // }
            //$course->frequency = json_encode($frequency);
            $course->user_id = Auth::user()->id;
            $course->organization_id = $organization_id;
            $course->updated_by = Auth::user()->id;
            //if file upload
            if($request->has('image') && $request->image != '' && $request->image != NULL) {
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

            } else if(!empty($request->copied_from)) {
                //if file not uploaded then get it from the exercise that copied
                $old_courses = Courses::find(decrypt($request->copied_from));
                if(!empty($old_courses->image)) {
                    //copy the image from the main exercise for new exercise
                    $file = storage_path($old_courses->image);
                    $file_extenstion = pathinfo($file, PATHINFO_EXTENSION);
                    $destination = '/courses/'.preg_replace('/\s+/', '', $request->name) . '_' . time() . '.'.$file_extenstion;
                    Storage::disk('public')->copy($old_courses->image, $destination);
                    $course->image = $destination;
                }
            }
            $course->save();
            //to save the body_regions in the exercise_body_regions table(pivot)
            $body_regions = BodyRegions::find($request->body_regions);
            $course->BodyRegions()->attach($body_regions);
            $indications = Indication::find($request->indication);
            $course->Indications()->attach($indications);
            if(!empty($request->exercise)) {
                foreach($request->exercise as $exercise) {
                    $course_exercises = new CourseExercises;
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
            if(!empty($request->exercise_id) && $request->save_to_catalog_exe == 'false') {
                $exercises = Exercises::find(decrypt($request->exercise_id));
                $exercises->course_id = $course->id;
                $exercises->save();
            }
            $message = \Lang::get('lang.course-successfully-created-msg');
            $request->session()->flash('alert-success', $message);
            session(['show_courses' => 'Eigene']);
            return response()->json(['success' => \Lang::get('lang.course-successfully-updated-msg'), 'status' => 200, 'save_for' => $save_for], 200);

        } else if($request->form_type == 'edit-course') {
            $save_for = 'courses';
            $coursesData = $coursesData->where('id', '!=', decrypt($id))->select('name')->get();
            //to check the exercise name is already exist or not
            foreach($coursesData as $course) {
                if(strtolower($request->name) == strtolower($course->name)) {
                    return response()->json(['error' => [\Lang::get('lang.course-name-exist-error')], 'status' => 400], 400);
                }
            }
            
            $course = Courses::find(decrypt($id));
            $course->name = $request->name;
            $course->description = $request->description;
            $course->duration = $request->course_time;
            $course->round = $request->round;
            // foreach ($request->frequency as $key => $value) {
            //     $frequency[$value] = $request->count; 
            // }
            // $course->frequency = json_encode($frequency);
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
            //to save the body_regions in the exercise_body_regions table(pivot)
            $body_regions = BodyRegions::find($request->body_regions);
            $course->BodyRegions()->sync($body_regions);
            $indications = Indication::find($request->indication);
            $course->Indications()->sync($indications);
            if(!empty($request->exercise)) {
                foreach($request->exercise as $exercise) {
                    $course_exercises = new CourseExercises;
                    // if(array_key_exists('exist_course_id', $exercise)) {
                    //     $course_exercises = CourseExercises::find($exercise['exist_course_id']);
                    // }
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
            if(!empty($request->exercise_id) && $request->save_to_catalog_exe == 'false') {
                $exercises = Exercises::find(decrypt($request->exercise_id));
                $exercises->course_id = $course->id;
                $exercises->save();
            }
            $existing_exercises = CourseExercises::where('course_id', decrypt($id))->select('id')->get();
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
                $delete_categories_data = CourseExercises::destroy($id);
            }
            $message = \Lang::get('lang.course-successfully-updated-msg');
            $request->session()->flash('alert-success', $message);
            session(['show_courses' => 'Eigene']);
            return response()->json(['success' => \Lang::get('lang.course-successfully-updated-msg'), 'status' => 200], 200);
        } else if ($request->form_type == 'add-therapy-plan-course') {

            if($request->add_to_catalog == "true")
            {
                $coursesData = $coursesData->select('name')->get();
                //to check the course name is already exist or not
                foreach($coursesData as $course) {
                    if(strtolower($request->name) == strtolower($course->name)) {
                        return response()->json(['error' => [\Lang::get('lang.course-name-exist-error')], 'status' => 400], 400);
                    }
                }
            }
            //to add the corse for therapy plan
            $save_for = 'phases';
            $course = New TherapyPlanCourses;
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
                $phase_course = New PhaseCourses;
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
                    $course_exercises = new TherapyPlanCourseExercises();
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
            //for adding the course to catalog
            if($request->has('phase_id') && !empty($request->phase_id) && $request->add_to_catalog == "true") {
                $cat_course = New Courses;
                $cat_course->name = $request->name;
                $cat_course->description = $request->description;
                $cat_course->duration = $request->course_time;
                $cat_course->round = $request->round;
                // $cat_course->frequency = $request->frequency;
                // $cat_course->count = $request->count;
                $cat_course->user_id = Auth::user()->id;
                $cat_course->organization_id = $organization_id;
                $cat_course->updated_by = Auth::user()->id;
                if(!empty($course->image)) {
                    //copy the image from the main exercise for new exercise
                    $file = storage_path($course->image);
                    $file_extenstion = pathinfo($file, PATHINFO_EXTENSION);
                    $destination = '/courses/'.preg_replace('/\s+/', '', $cat_course->name) . '_' . (time() + (60)) . '.'.$file_extenstion;
                    Storage::disk('public')->copy($course->image, $destination);
                    $cat_course->image = $destination;
                }
                $cat_course->image = $course->image;
                $cat_course->save();
                //to save the body_regions in the exercise_body_regions table(pivot)
                $body_regions = BodyRegions::find($request->body_regions);
                $cat_course->BodyRegions()->attach($body_regions);
                $indications = Indication::find($request->indication);
                $cat_course->Indications()->attach($indications);
                if(!empty($request->exercise)) {
                    foreach($request->exercise as $exercise) {
                        $cat_course_exercises = new CourseExercises;
                        $cat_course_exercises->course_id = $cat_course->id;
                        $cat_course_exercises->type = $exercise['type'];
                        if($exercise['type'] == 1  || !array_key_exists('exercise_id', $exercise)) {
                            $cat_course_exercises->value_of_type = $exercise['value_of_type'];
                        } else {
                            $val_of_type = ((int)$exercise['dur_min'] * 60) + (int)$exercise['dur_sec'];
                            $cat_course_exercises->value_of_type = $val_of_type;
                        }
                        if(array_key_exists('exercise_id', $exercise)) {
                            $cat_course_exercises->exercise_id = $exercise['exercise_id'];   
                        }
                        $cat_course_exercises->save();
                    }    
                }
            }
            if(!empty($request->exercise_id) && $request->save_to_catalog_exe == 'false') {
                $exercises = Exercises::find(decrypt($request->exercise_id));
                $exercises->phase_id = decrypt($request->phase_id);
                $exercises->save();
            }
            $message = \Lang::get('lang.course-successfully-created-msg');
            $request->session()->flash('alert-success', $message);
            return response()->json(['success' => \Lang::get('lang.course-successfully-updated-msg'), 'status' => 200, 'save_for' => $save_for], 200);
        } else if ($request->form_type == 'edit-therapy-plan-course') {
            if($request->add_to_catalog == "true")
            {
                $coursesData = $coursesData->select('name')->get();
                //to check the course name is already exist or not
                foreach($coursesData as $course) {
                    if(strtolower($request->name) == strtolower($course->name)) {
                        return response()->json(['error' => [\Lang::get('lang.course-name-exist-error')], 'status' => 400], 400);
                    }
                }
            }

            // to edit the phase courses
            $save_for = 'phases';
            $course = TherapyPlanCourses::find(decrypt($id));
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
                    $course_exercises = new TherapyPlanCourseExercises;
                    // if(array_key_exists('exist_course_id', $exercise)) {
                    //     $course_exercises = TherapyPlanCourseExercises::find($exercise['exist_course_id']);
                    // }
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
            if(!empty($request->exercise_id) && $request->save_to_catalog_exe == 'false') {
                $exercises = Exercises::find(decrypt($request->exercise_id));
                $exercises->phase_id = decrypt($request->phase_id);
                $exercises->save();
            }
            $existing_exercises = TherapyPlanCourseExercises::where('course_id', decrypt($id))->select('id')->get();
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
                $delete_categories_data = TherapyPlanCourseExercises::destroy($id);
            }
            $message = \Lang::get('lang.course-successfully-updated-msg');
            $request->session()->flash('alert-success', $message);
            return response()->json(['success' => \Lang::get('lang.course-successfully-updated-msg'), 'status' => 200], 200);
        }
    }

    //function to save the assessment data
    public function saveAssessment(Request $request, $id = null) {
        $procedure_types_array = ['pain-level', 'rating', 'sensors', 'scoring/questionnaire'];
        $only_single_point_units_array = ['bpm', 'mmhg', 'mikrovolts', 'step-sensor', 'koos', 'lsi', 'rtaa'];
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        $assessment_templates = AssessmentTemplates::where(function($query) use ( $organization_id )  {
                            $query->whereNull('user_id');
                            $query->whereNull('organization_id');
                            $query->orWhere(function($query) use ( $organization_id ) {
                                $query->where('organization_id', $organization_id);
                            });
                        });
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
        if($request->type == 'copy-template') {
            $save_for = 'assessment';
            $assessment_templates = $assessment_templates->select('template_name')->get();
            foreach($assessment_templates as $template) {
                if(strtolower($request->template_name) == strtolower($template->template_name)) {
                    return response()->json(['error' => \Lang::get('lang.assessment-name-exist-error'), 'status' => 400, 'name_error' => true], 400);
                }
            }
            $treatementPlan = new AssessmentTemplates();
            $treatementPlan->template_name = $request->template_name;
            $treatementPlan->description = $request->description;
            $treatementPlan->user_id = Auth::user()->id;
            $treatementPlan->organization_id = $organization_id;
            $treatementPlan->created_by = Auth::user()->id;
            $treatementPlan->updated_by = Auth::user()->id;
            $treatementPlan->save();
            //to save the indications in the assessment_indications table(pivot)
            $indications = Indication::find($request->indications);
            $treatementPlan->Indications()->attach($indications);
            //to save the body_regions in the assessment_body_regions table(pivot)
            $body_regions = BodyRegions::find($request->body_regions);
            $treatementPlan->BodyRegions()->attach($body_regions);

            //to save the schedules for assessment
            $schedules_array = [];
            if(!empty($request->schedules)) {
                foreach($request->schedules as $key => $schedules) {
                    $schedules_data = new AssessmentPatientSchedules;
                    $schedules_data->assessment_templates_id = $treatementPlan->id;
                    $schedules_data->name = $schedules['name'];
                    $schedules_data->time = $schedules['time'];
                    $schedules_data->save();
                    $schedules_array[$key] = $schedules_data->id;
                }
            }
            if(!empty($request->category)) {
                foreach($request->category as $main_category) {
                    $category = New AssessmentCategories;
                    $category->name = $main_category['name'];
                    $category->is_active = array_key_exists('is_active', $main_category) ? 1 : 0;
                    $category->assessment_template_id = $treatementPlan->id;
                    $category->save();
                    if(array_key_exists('subcategory', $main_category)) {
                        foreach($main_category['subcategory']  as $sub_category) {
                            $subcategory = New AssessmentSubCategories;
                            $subcategory->type = $sub_category['type'];
                            $subcategory->name = $sub_category['name'];
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
                            if(!in_array($subcategory->unit, $only_single_point_units_array)) {
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
                                $subcategory->assessment_patient_schedules_id = implode(',', $sche_ids);
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
            session(['show_asmt_template' => 'Eigene']);
            $message = \Lang::get('lang.assessment-successfully-created-msg');
            $request->session()->flash('alert-success', $message);
        } else if($request->type == 'edit-template') {
            $save_for = 'assessment';
            //to edit the template 
            $treatementPlan = AssessmentTemplates::find(decrypt($id));
            if( $treatementPlan->is_default == 1) {
                //if the template is default then no change
                $message = \Lang::get('lang.no-access-to-update-default-assmt-msg');
                $request->session()->flash('alert-danger', $message);
                return response()->json(['success' => \Lang::get('lang.assessment-successfully-created-msg'), 'status' => 200], 200);
            } else {
                //to check the template name already exist or not
                $assessment_templates = $assessment_templates->where('id', '!=', decrypt($id))->select('template_name')->get();
                foreach($assessment_templates as $template) {
                    if(strtolower($request->template_name) == strtolower($template->template_name)) {
                        return response()->json(['error' => \Lang::get('lang.assessment-name-exist-error'), 'status' => 400, 'name_error' => true], 400);
                    }
                }
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
                        $schedules_data = new AssessmentPatientSchedules;
                        //if existing id then update the data
                        if(array_key_exists('exist_id', $schedules)) {
                            $schedules_data = AssessmentPatientSchedules::find($schedules['exist_id']);
                        }
                        $schedules_data->assessment_templates_id = $treatementPlan->id;
                        $schedules_data->name = $schedules['name'];
                        $schedules_data->time = $schedules['time'];
                        $schedules_data->save();
                        $updates_schedules_ids[] = $schedules_data->id;
                        $schedules_array[$key] = $schedules_data->id;
                    }
                }
                if(!empty($request->category)) {
                    foreach($request->category as $main_category) {
                        $category = New AssessmentCategories;
                        if(array_key_exists('is_exist', $main_category)) {
                            $category = AssessmentCategories::find($main_category['is_exist']);
                        }
                        $category->name = $main_category['name'];
                        $category->is_active = array_key_exists('is_active', $main_category) ? 1 : 0;
                        $category->assessment_template_id = $treatementPlan->id;
                        $category->save();
                        $updated_cat_ids[] = $category->id;
                        if(array_key_exists('subcategory', $main_category)) {
                            foreach($main_category['subcategory']  as $sub_category) {
                                $subcategory = New AssessmentSubCategories;
                                if(array_key_exists('is_exist', $sub_category)) {
                                    $subcategory = AssessmentSubCategories::find($sub_category['is_exist']);
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
                                $subcategory->assessment_patient_schedules_id = NULL;
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
                                    $subcategory->assessment_patient_schedules_id = implode(',', $sche_ids);
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
                $existing_schedules = AssessmentPatientSchedules::where('assessment_templates_id', decrypt($id))->select('id')->get();
                $existing_schedules_ids = array();
                if (!empty($existing_schedules)) {
                    //created the array of existing schedules_id
                    foreach ($existing_schedules as $schedules) {
                        $existing_schedules_ids[] = $schedules->id;
                    }
                }
                $deleted_schedules = array_diff($existing_schedules_ids, $updates_schedules_ids);
                foreach ($deleted_schedules as $deleted_sche_id) {
                    //to remove the schedules
                    $delete_schedules_data = AssessmentPatientSchedules::destroy($deleted_sche_id);
                }

                //get all the categories id 
                $existing_categories = AssessmentCategories::where('assessment_template_id', decrypt($id))->select('id')->get();
                $existing_cate_ids = array();
                $existing_sub_cat_ids = array();
                if (!empty($existing_categories)) {
                    //created the array of existing categories_id
                    foreach ($existing_categories as $category_data) {
                        $existing_cate_ids[] = $category_data->id;
                    }
                }
                //get all the sub categories id 
                $existing_sub_categories = AssessmentSubCategories::with(['AssessmentCategories.AssessmentTemplates'])->select('id')
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
                    $delete_categories_data = AssessmentCategories::destroy($id);
                }
                //get deleted sub_categories
                $deleted_sub_categories = array_diff($existing_sub_cat_ids, $updated_sub_cat_ids);
                foreach ($deleted_sub_categories as $id) {
                    //to remove the sub categories
                    $deleted_sub_categories_data = AssessmentSubCategories::destroy($id);
                }
            }
            $message = \Lang::get('lang.assessment-successfully-updated-msg');
            $request->session()->flash('alert-success', $message);
        } else if($request->type == 'create-therapy-plan-assessment') {
            $save_for = 'therapy-plan';
            if($request->save_as_template == 'true') {
                $assessment_templates = $assessment_templates->select('template_name')->get();
                foreach($assessment_templates as $template) {
                    if(strtolower($request->template_name) == strtolower($template->template_name)) {
                        return response()->json(['error' => \Lang::get('lang.assessment-name-exist-error'), 'status' => 400, 'name_error' => true], 400);
                    }
                }
            }
            $treatementPlan = new TherapyPlanAssessmentTemplates();
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
                    $schedules_data = new TherapyPlanAssessmentSchedules;
                    $schedules_data->therapyplan_assessment_templates_id = $treatementPlan->id;
                    $schedules_data->name = $schedules['name'];
                    $schedules_data->time = $schedules['time'];
                    $schedules_data->save();
                    $schedules_array[$key] = $schedules_data->id;
                }
            }

            if(!empty($request->category)) {
                foreach($request->category as $main_category) {
                    $category = New TherapyPlanAssessmentCategories;
                    $category->name = $main_category['name'];
                    $category->is_active = array_key_exists('is_active', $main_category) ? 1 : 0;
                    $category->therapyplan_assessment_templates_id = $treatementPlan->id;
                    $category->save();
                    if(array_key_exists('subcategory', $main_category)) {
                        foreach($main_category['subcategory']  as $sub_category) {
                            $subcategory = New TherapyPlanAssessmentSubCategories;
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
                            if(!in_array($subcategory->unit, $only_single_point_units_array)) {
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
                                $subcategory->therapyplan_assessment_schedules_id = implode(',', $sche_ids);
                            }
                            $subcategory->therapyplan_assessment_category_id = $category->id;
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
            if($request->has('phase_id') && !empty($request->phase_id)) {
                $phase_assessments = New PhaseAssessments;
                $phase_assessments->phases_id = decrypt($request->phase_id);
                $phase_assessments->therapy_plan_assessment_templates_id = $treatementPlan->id;
                $phase_assessments->save();
            }
            if($request->save_as_template == 'true') {
                $calalogtreatementPlan = new AssessmentTemplates();
                $calalogtreatementPlan->template_name = $request->template_name;
                $calalogtreatementPlan->description = $request->description;
                $calalogtreatementPlan->user_id = Auth::user()->id;
                $calalogtreatementPlan->organization_id = $organization_id;
                $calalogtreatementPlan->created_by = Auth::user()->id;
                $calalogtreatementPlan->updated_by = Auth::user()->id;
                $calalogtreatementPlan->save();
                //to save the indications in the assessment_indications table(pivot)
                $indications = Indication::find($request->indications);
                $calalogtreatementPlan->Indications()->attach($indications);
                //to save the body_regions in the assessment_body_regions table(pivot)
                $body_regions = BodyRegions::find($request->body_regions);
                $calalogtreatementPlan->BodyRegions()->attach($body_regions);

                //to save the schedules for assessment
                $schedules_array = [];
                if(!empty($request->schedules)) {
                    foreach($request->schedules as $key => $schedules) {
                        $schedules_data = new AssessmentPatientSchedules;
                        $schedules_data->assessment_templates_id = $calalogtreatementPlan->id;
                        $schedules_data->name = $schedules['name'];
                        $schedules_data->time = $schedules['time'];
                        $schedules_data->save();
                        $schedules_array[$key] = $schedules_data->id;
                    }
                }
                if(!empty($request->category)) {
                    foreach($request->category as $main_category) {
                        $catalog_category = New AssessmentCategories;
                        $catalog_category->name = $main_category['name'];
                        $catalog_category->is_active = array_key_exists('is_active', $main_category) ? 1 : 0;
                        $catalog_category->assessment_template_id = $calalogtreatementPlan->id;
                        $catalog_category->save();
                        if(array_key_exists('subcategory', $main_category)) {
                            foreach($main_category['subcategory']  as $sub_category) {
                                $catalog_subcategory = New AssessmentSubCategories;
                                $catalog_subcategory->name = $sub_category['name'];
                                $catalog_subcategory->type = $sub_category['type'];
                                $catalog_subcategory->is_active = array_key_exists('is_active', $sub_category) ? 1 : 0;
                                $catalog_subcategory->title = $sub_category['title'];
                                $catalog_subcategory->unit = $sub_category['unit'];
                                if(!in_array($catalog_subcategory->unit, $procedure_types_array)) {
                                    $catalog_subcategory->measurement_type = $sub_category['measurement_type'];
                                    $catalog_subcategory->start_value = $sub_category['start_value'];
                                    $catalog_subcategory->end_value = $sub_category['end_value'];
                                    $catalog_subcategory->target = $sub_category['target'];
                                    $catalog_subcategory->target_area = $sub_category['target_area'];
                                }
                                if(!in_array($catalog_subcategory->unit, $only_single_point_units_array)) {
                                    $catalog_subcategory->comparison_range = $sub_category['comparison_range'];
                                }
                                $catalog_subcategory->description = $sub_category['description'];
                                if(array_key_exists('is_patient_assessment', $sub_category)) {
                                    $catalog_subcategory->is_patient_assessment = 1;
                                    $catalog_subcategory->routine = $sub_category['routine'];
                                    $catalog_subcategory->frequency = implode(",",$sub_category['frequency']);
                                    $sche_ids = [];
                                    foreach($sub_category['schedule_id'] as $sche_key => $sche_value) {
                                        $filtered_arr = array_filter($schedules_array, function ($var, $key_q) use ($sche_value) {
                                            return ((int)$key_q == (int)$sche_value);
                                        }, ARRAY_FILTER_USE_BOTH);
                                        if(!empty($filtered_arr))
                                            $sche_ids[] = (int)array_shift($filtered_arr);
                                    }
                                    $catalog_subcategory->assessment_patient_schedules_id = implode(',', $sche_ids);
                                }
                                $catalog_subcategory->assessment_category_id = $catalog_category->id;
                                if(array_key_exists('save_max_target', $sub_category) && !empty($sub_category['max_target'])) {
                                    $catalog_subcategory->max_target = $sub_category['max_target'];
                                } else {
                                    $catalog_subcategory->max_target = NULL;
                                }
                                $catalog_subcategory->save();
                            }
                        }
                    }
                }
            }
            $message = \Lang::get('lang.assessment-successfully-created-msg');
            $request->session()->flash('alert-success', $message);
        } else if($request->type == 'edit-therapy-plan-assessment') {
            
            $save_for = 'therapy-plan';
            //to edit the template 
            $treatementPlan = TherapyPlanAssessmentTemplates::find(decrypt($id));
            //to check the template name already exist or not
            if($request->save_as_template == 'true') {
                $assessment_templates = $assessment_templates->select('template_name')->get();
                foreach($assessment_templates as $template) {
                    if(strtolower($request->template_name) == strtolower($template->template_name)) {
                        return response()->json(['error' => \Lang::get('lang.assessment-name-exist-error'), 'status' => 400, 'name_error' => true], 400);
                    }
                }
            }  
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
                    $schedules_data = new TherapyPlanAssessmentSchedules;
                    //if existing id then update the data
                    if(array_key_exists('exist_id', $schedules)) {
                        $schedules_data = TherapyPlanAssessmentSchedules::find($schedules['exist_id']);
                    }
                    $schedules_data->therapyplan_assessment_templates_id = $treatementPlan->id;
                    $schedules_data->name = $schedules['name'];
                    $schedules_data->time = $schedules['time'];
                    $schedules_data->save();
                    $updates_schedules_ids[] = $schedules_data->id;
                    $schedules_array[$key] = $schedules_data->id;
                }
            }
            if(!empty($request->category)) {
                foreach($request->category as $main_category) {
                    $category = New TherapyPlanAssessmentCategories;
                    if(array_key_exists('is_exist', $main_category)) {
                        $category = TherapyPlanAssessmentCategories::find($main_category['is_exist']);
                    }
                    $category->name = $main_category['name'];
                    $category->is_active = array_key_exists('is_active', $main_category) ? 1 : 0;
                    $category->therapyplan_assessment_templates_id = $treatementPlan->id;
                    $category->save();
                    $updated_cat_ids[] = $category->id;
                    if(array_key_exists('subcategory', $main_category)) {
                        foreach($main_category['subcategory']  as $sub_category) {
                            $subcategory = New TherapyPlanAssessmentSubCategories;
                            if(array_key_exists('is_exist', $sub_category)) {
                                $subcategory = TherapyPlanAssessmentSubCategories::find($sub_category['is_exist']);
                            }
                            $subcategory->name = $sub_category['name'];
                            $subcategory->is_active = array_key_exists('is_active', $sub_category) ? 1 : 0;
                            $subcategory->title = $sub_category['title'];
                            $subcategory->type = $sub_category['type'];
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
                            $subcategory->therapyplan_assessment_schedules_id = NULL;
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
                                $subcategory->therapyplan_assessment_schedules_id = implode(',', $sche_ids);
                            }
                            $subcategory->description = $sub_category['description'];
                            $subcategory->therapyplan_assessment_category_id = $category->id;
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
            $existing_schedules = TherapyPlanAssessmentSchedules::where('therapyplan_assessment_templates_id', decrypt($id))->select('id')->get();
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
                $delete_schedules_data = TherapyPlanAssessmentSchedules::destroy($deleted_sche_id);
            }

            //get all the categories id 
            $existing_categories = TherapyPlanAssessmentCategories::where('therapyplan_assessment_templates_id', decrypt($id))->select('id')->get();
            $existing_cate_ids = array();
            $existing_sub_cat_ids = array();
            if (!empty($existing_categories)) {
                //created the array of existing categories_id
                foreach ($existing_categories as $category_data) {
                    $existing_cate_ids[] = $category_data->id;
                }
            }
            //get all the sub categories id 
            $existing_sub_categories = TherapyPlanAssessmentSubCategories::with(['TherapyPlanAssessmentCategories.TherapyPlanAssessmentTemplates'])->select('id')
                                ->whereHas('TherapyPlanAssessmentCategories.TherapyPlanAssessmentTemplates', function ($query) use ($id) {
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
                $delete_categories_data = TherapyPlanAssessmentCategories::destroy($id);
            }
            //get deleted sub_categories
            $deleted_sub_categories = array_diff($existing_sub_cat_ids, $updated_sub_cat_ids);
            foreach ($deleted_sub_categories as $id) {
                //to remove the sub categories
                $deleted_sub_categories_data = TherapyPlanAssessmentSubCategories::destroy($id);
            }
            $message = \Lang::get('lang.assessment-successfully-updated-msg');
            $request->session()->flash('alert-success', $message);
        }
        return response()->json(['success' => \Lang::get('lang.assessment-successfully-created-msg'), 'status' => 200, 'id' => encrypt($treatementPlan->id), 'save_for' => $save_for], 200);
    }

    //function to remove the assessment data (subcategory)
    public function removeAssessmentData(Request $request, $id) {
        if($request->type == "sub_cat") {
            $sub_categories = AssessmentSubCategories::find(decrypt($id));
            if(!empty($sub_categories)) {
                AssessmentSubCategories::destroy(decrypt($id));
                $message = \Lang::get('lang.remove-assessment-success-message');
                $request->session()->flash('alert-success', $message);
                return response()->json(['success' => \Lang::get('lang.remove-assessment-success-message'), 'status' => 200, 'type' => 'sub_cat'], 200);
            }
        } else if ($request->type == "assessment") {
            $assessment = AssessmentTemplates::find(decrypt($id));
            if(!empty($assessment)) {
                $assessment->Indications()->detach();
                $assessment->BodyRegions()->detach();
                $assessment->delete();
                $message = \Lang::get('lang.remove-assessment-success-message');
                $request->session()->flash('alert-success', $message);
                return response()->json(['success' => \Lang::get('lang.remove-assessment-success-message'), 'status' => 200, 'type' => 'assessment'], 200);
            }
        }
    }

    //function to remove the exercise
    public function removeExercise(Request $request) {
        $exercises = Exercises::find(decrypt($request->id));
        if(!empty($exercises)) {
            $exercises->BodyRegions()->detach();
            $exercises->Indications()->detach();
            $exercises->Materials()->detach();
            if(!empty($exercises->image)) {
                $file_name = explode('/', $exercises->image)[2];
                Storage::delete('public/exercises/' . $file_name);
            }
            if(!empty($exercises->video)) {
                $video_name = explode('/', $exercises->video)[3];
                Storage::delete('public/exercises/videos/' . $video_name);
            }
            Exercises::destroy(decrypt($request->id));
        }
        return response()->json(['success' => 'successfully removed', 'status' => 200], 200);
    }
    
    //function to get the chart data
    public function getChartData(Request $request) {
        $start_value = $request->start_value;
        $end_value = $request->end_value;
        $data = Helper::getChartData($start_value, $end_value);
        return response()->json(['data' => $data]);
    }

    //function to set the common data for every modules
    public function setCommonData($data = null, $shaw_tabs = true, $back_url = '') {
        $common_data = array(
            'showtabs' => $shaw_tabs,
            'back_url' => $back_url,
        );
        if(!empty($data)) {
            $common_data['name'] = ucfirst($data['name']);
        }
        return $common_data;
    }

    //function to set the favorites the courses or exercises
    public function setAsFavorites(Request $request, $id) {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        $type = $request->type; 
        if($type == 'course') {
            $favorite_courses = FavoriteCourses::where('course_id', decrypt($id))->where('organization_id', $organization_id)->first();
            if(!empty($favorite_courses)) {
                $favorite_courses->delete();
                return response()->json(['success' => \Lang::get('lang.success-removed-favorite-msg'), 'status' => 200], 200);
            } else {
                $favorite_courses = new FavoriteCourses;
                $favorite_courses->organization_id = $organization_id;
                $favorite_courses->course_id = decrypt($id);
                $favorite_courses->updated_by = Auth::user()->id;
                $favorite_courses->save();
                return response()->json(['success' => \Lang::get('lang.success-added-favorite-msg'), 'status' => 200], 200);
            }
        } else if($type == 'exercise') {
            $favorite_exercises = FavoriteExercises::where('exercise_id', decrypt($id))->where('organization_id', $organization_id)->first();
            if(!empty($favorite_exercises)) {
                $favorite_exercises->delete();
                return response()->json(['success' => \Lang::get('lang.success-removed-favorite-msg'), 'status' => 200], 200);
            } else {
                $favorite_exercises = new FavoriteExercises;
                $favorite_exercises->organization_id = $organization_id;
                $favorite_exercises->exercise_id = decrypt($id);
                $favorite_exercises->updated_by = Auth::user()->id;
                $favorite_exercises->save();
                return response()->json(['success' => \Lang::get('lang.success-added-favorite-msg'), 'status' => 200], 200);
            }
        }
    }

    //function to remove the course
    public function removeAssignedCourses(Request $request, $id) {
        if($request->for == 'therapy-plan') {
            //to reomve from the therapy plan templates
            $course_details = PhaseCourses::where('course_id', decrypt($id))->where('phase_id', decrypt($request->phase_id))->first();
            $therapy_plan_courses = TherapyPlanCourses::find($course_details->course_id);
            $therapy_plan_courses->Indications()->detach();
            $therapy_plan_courses->BodyRegions()->detach();
            //to remove the course image
            if ($therapy_plan_courses->image != '' && $therapy_plan_courses->image != NULL) {
                $file_name = explode('/', $therapy_plan_courses->image)[2];
                Storage::delete('public/courses/' . $file_name);
            }
            $therapy_plan_courses->delete();
            $course_details->delete();
            return response()->json(['success' => 'Removed successfully', 'status' => 200, 'for' => 'courses'], 200);
        } else if($request->for == 'assign-therapy-plan') {
            //to reomve from the assigned therapy plan templates
            $course_details = PatientPhaseCourses::where('course_id', decrypt($id))->where('phase_id', decrypt($request->phase_id))->update(['status' => 0]);
            // $patient_plan_courses = PatientCourses::find($course_details->course_id);
            // $patient_plan_courses->Indications()->detach();
            // $patient_plan_courses->BodyRegions()->detach();
            // $patient_plan_courses->delete();
            // $course_details->delete();
            return response()->json(['success' => 'Removed successfully', 'status' => 200], 200);
        }
        else if($request->for == 'course-template') {
            //to reomve from the course templates
            $course_details = Courses::find(decrypt($id));
            $course_details->Indications()->detach();
            $course_details->BodyRegions()->detach();
            //to remove the course image
            if ($course_details->image != '' && $course_details->image != NULL) {
                $file_name = explode('/', $course_details->image)[2];
                Storage::delete('public/courses/' . $file_name);
            }
            $course_details->delete();
            return response()->json(['success' => 'Removed successfully', 'status' => 200], 200);
        }
    }

    //function to remove the therapy plan template
    public function removeTherapyPlanTemplates(Request $request, $id) {
        $plan_templates = TherapyPlanTemplates::with(['Phases', 'Phases.PhaseCourses','Phases.PhaseAssessments.TherapyPlanAssessments', 'Phases.PhaseExercises.Exercises', 'Phases.PhaseCourses.TherapyPlanCourses'])->find(decrypt($id));
        if(!empty($plan_templates)) {
            foreach($plan_templates->Phases as $phase) {
                //to remove the assessments details
                if(!$phase->PhaseAssessments->isEmpty()) {
                    foreach($phase->PhaseAssessments as $phase_asmt) {
                        $phase_asmt->TherapyPlanAssessments->delete();
                        $phase_asmt->TherapyPlanAssessments->Indications()->detach();
                        $phase_asmt->TherapyPlanAssessments->BodyRegions()->detach();
                    }
                }
                //to remove the courses details
                if(!$phase->PhaseCourses->isEmpty()) {
                    foreach($phase->PhaseCourses as $phase_courses) {
                        if ($phase_courses->TherapyPlanCourses->image != '' && $phase_courses->TherapyPlanCourses->image != NULL) {
                            $file_name = explode('/', $phase_courses->TherapyPlanCourses->image)[2];
                            Storage::delete('public/courses/' . $file_name);
                        }
                        $phase_courses->TherapyPlanCourses->delete();
                        $phase_courses->TherapyPlanCourses->Indications()->detach();
                        $phase_courses->TherapyPlanCourses->BodyRegions()->detach();
                    }
                }
            }
            $plan_templates->delete();
            $plan_templates->Indications()->detach();
            $plan_templates->BodyRegions()->detach();
            return response()->json(['success' => 'Removed successfully', 'status' => 200, 'for' => 'therapy-plan-templates'], 200);
        }
        
    }

    //function to load the exersise listing for general purpose
    //function to get the exercises
    public function getListingOfAllExercises(Request $request, $id = null)
    {
        $organization_id = Session::has("organization_id") ? decrypt(Session::get("organization_id")) : '';
        $exercisesData = Exercises::with(['FavoriteExercises'])->where(function($query) use ($organization_id){
                            $query->whereNull('user_id');
                            $query->whereNull('organization_id');
                            $query->orWhere(function($query) use ( $organization_id ) {
                                $query->where('organization_id', $organization_id);
                            });
                            $query->whereNull('course_id');
                            $query->whereNull('phase_id');
                            $query->whereNull('patient_id');
                        });
        $body_regions = BodyRegions::all();
        $indications = Indication::all();
        $materials = ExerciseMaterialsLists::all();
        if($request->has('show_data') && $request->show_data) {
            $search_value = $request->search_input;
            $position = $request->position;
            $target = $request->target;
            $difficulty = $request->difficulty;
            $tools = $request->tools;
            $body_region_id = $request->body_region;
            $sort_by = $request->sort_by;
            $is_favorite = $request->is_favorite;
            $indication_id = $request->indication;

            //sort by position
            if(!empty($position)) {
                $exercisesData = $exercisesData->whereIn('position', $position);
            }
            //sort by target
            if(!empty($target)) {
                $exercisesData = $exercisesData->whereIn('target', $target);
            }
            //sort by difficulty
            if(!empty($difficulty)) {
                $exercisesData = $exercisesData->whereIn('difficulty', $difficulty);
            }
            //sort by tools
            if(!empty($tools)) {
                $exercisesData = $exercisesData->whereHas('Materials', function($query) use ($tools) {
                    $query->whereIn('exercise_materials_lists_id', $tools);
                });
            }
            //sort by body_regions
            if(!empty($body_region_id)) {
                $exercisesData = $exercisesData->whereHas('BodyRegions', function($query) use ($body_region_id) {
                    $query->whereIn('body_regions_id', $body_region_id);
                });
            }
            //sort by indication
            if(!empty($indication_id)) {
                $exercisesData = $exercisesData->whereHas('Indications', function($query) use ($indication_id) {
                    $query->whereIn('indication_id', $indication_id);
                });
            }
            //sort by fields
            if(!empty($sort_by)) {
                if($sort_by != 'created_by') {
                    $exercisesData = $exercisesData->orderBy('name', $sort_by);
                } else {
                    $exercisesData = $exercisesData->orderBy('created_at', 'desc');
                }
            } else {
                $exercisesData = $exercisesData->orderBy('id', 'desc');
            }
            //sort by favorites
            if($is_favorite == 'true') {
                $exercisesData = $exercisesData->whereHas('FavoriteExercises', function($query) use ($organization_id) {
                        $query->where('organization_id', $organization_id);
                });
            }
            //search functionality
            if(!empty($search_value)) {
                $exercisesData = $exercisesData->where(function($query) use ($search_value){
                    $searchColumn = ['name'];
                    foreach ($searchColumn as $singleSearchColumn) {
                        $query->Where($singleSearchColumn, "LIKE", '%' . $search_value . '%');
                    }
                });
            }
            $exercisesData = $exercisesData->whereNull('patient_id')->get();
            if($request->has('in_courses') && $request->in_courses) {
                $course_id = $request->course_id;
                if(!empty($course_id)) {
                    $course_exercise = Exercises::where('course_id', decrypt($course_id))->whereNull('patient_id')->get();
                    $exercisesData = $exercisesData->merge($course_exercise);
                }
                return view('common-views.courses-exercises-views', compact('exercisesData'));
            }
            if($request->has('in_phases') && $request->in_phases) {
                $phase_id = $request->phase_id;
                if(!empty($phase_id)) {
                    $phase_exercise = Exercises::where('phase_id', decrypt($phase_id))->whereNull('patient_id')->get();
                    $exercisesData = $exercisesData->merge($phase_exercise);
                }
                return view('common-views.courses-exercises-views', compact('exercisesData'));
            }
            if($request->has('in_assigned_phases') && $request->in_assigned_phases) {
                $patient_id = !empty($request->patient_id) ? $request->patient_id : '';
                if(!empty($patient_id)) {
                    $phase_exercise = Exercises::where('patient_id', decrypt($patient_id))->get();
                    // dd($phase_exercise);
                    $exercisesData = $exercisesData->merge($phase_exercise);
                }
                return view('common-views.courses-exercises-views', compact('exercisesData'));
            }
            if($request->has('is_assigned_course') && $request->is_assigned_course) {
                $patient_id = !empty($request->patient_id) ? $request->patient_id : '';
                if(!empty($patient_id)) {
                    $phase_exercise = Exercises::where('patient_id', decrypt($patient_id));
                    $phase_exercise = $phase_exercise->get();
                    $exercisesData = $exercisesData->merge($phase_exercise);
                }
                return view('common-views.courses-exercises-views', compact('exercisesData'));
            }
            
        }
    }

    //function to export the assessment data
    public function exportAssessment(Request $request, $id = null) {
        $assessment = AssessmentTemplates::with(['Categories.SubCategories', 'BodyRegions', 'Indications', 'SubCategoriesData'])->where('id', decrypt($id))->get();
        $name = preg_replace('/\s+/', '', $assessment[0]->template_name) . '_' . time(). '.xlsx';
        return Excel::download(new AssessmentExport($assessment), $name);
    }

    // function to upload the file at specific location
    public function uploadOne(UploadedFile $uploadedFile, $folder = null, $disk = 'public', $filename = null)
    {
        $name = !is_null($filename) ? $filename : str_random(25);

        $file = $uploadedFile->storeAs($folder, $name . '.' . $uploadedFile->getClientOriginalExtension(), $disk);

        return $file;
    }

    //function to set the current phase in session
    function setCurrentPhaseInSession(Request $request) {
        if($request->type == 'therapy-plan') {
            $therapy_plans = Phases::with(['TherapyPlanTemplates'])->whereHas('TherapyPlanTemplates', function ($query) use ($request) {
                $query->where('id', decrypt($request->id));
            })->get();
            if(!empty($therapy_plans)) {
                session(['therapy_plan_current_phase' => $therapy_plans[0]->id]);    

            }
        } else if($request->type == 'phases'){
            session(['therapy_plan_current_phase' => $request->id]);    
        } else if($request->type == 'therapy-plan-list') {
            session(['show_therapy_plan' => $request->id]);  
        } else if($request->type == 'exercises-templates') {
            session(['show_exercise' => $request->id]);  
        } else if($request->type == 'assessment-templates') {
            session(['show_asmt_template' => $request->id]);  
        } else if($request->type == 'course-templates') {
            session(['show_courses' => $request->id]);  
        }
        return response()->json(['success' => 'success', 'status' => 200, 'type' => $request->type], 200);    
    }
}
