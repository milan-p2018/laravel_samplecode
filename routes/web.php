<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// LOGIN ROUTES
Auth::routes(['verify' => true, 'reset' => true]);
Route::post('login', 'AuthController@authenticate');
Route::get('set-password/{id}', 'PatientController@showSetPassword')->name('set-password');
Route::post('set-password-save', 'PatientController@savePassword')->name('set-password-save');
//path for open the register manual 
Route::get('download-register-manual/{type}', 'Controller@downloadManual')->name('download-register-manual');

// BACKEND ROUTES
Route::group(['middleware' => ['auth', 'verified']], function () {
    Route::get('/', 'OrganizationController@index')->name('home');
    Route::resource('group', 'GroupController');
    Route::get('profil/update-profile', 'UserController@create')->name('profil.update-profile');
    Route::post('profil/store/{id?}', 'UserController@store')->name('profil.store');
    Route::get("user/welcome", function () {
        return View::make("user.welcome");
    });
    Route::get('profil/edit', 'UserController@edit')->name('profil.edit');
});

Route::group(['middleware' => ['auth', 'verified']], function () {

    //Routes that are only be accessed if user belongs to any organization
    Route::group(['middleware' => 'belong-to-organization'], function () {
        Route::post('edit-organization', 'OrganizationController@update')->name('organization.update');
        Route::post('organization/deregister-device', 'OrganizationController@deRegisterDevice');
        Route::post('remove-organization', 'OrganizationController@removeOrganization');
        Route::POST('change-organization-profile/{id}', 'OrganizationController@updateOrganizationImage')->name('organization.profile.store');

        //new routes added here
        Route::post('organization/update', 'OrganizationController@update')->name('organization.update');
        Route::get('select-organization/{id}', 'OrganizationController@selectOrganization')->name('organization.select');
        Route::get('set-session', 'OrganizationController@setSession')->name('set-session');
        
        //get the organization business hours modal
        Route::get('get-business-hours-details/{id}', 'OrganizationController@getBusinessHoursPopupData');

        //patient routes
        Route::get('patients', 'PatientController@index')->name('patients.index');
        Route::post('get-patient-listing', 'PatientController@getOrganizationPatientListing');
        Route::get('patients/base-data/{id}', 'PatientController@getPatientProfileData')->name('patients.base-data');
        Route::post('patients/abort-request/{id}', 'PatientController@abortPatientRequest')->name('patients.abort-request');
        Route::get('patients/therapy-plans/{id}', 'PatientController@patientTherapyPlans')->name('patients.therapy-plans');
        Route::post('patients/assign-therapy-plans/{id}', 'PatientController@assignPatientTherapyPlans')->name('patients.assign-therapy-plans');
        Route::post('get-asssign-therapy-plan-details/{id?}', 'PatientController@getTherapyPlanAssignPopupData');
        
        Route::post('load-rework-modal', 'PatientController@loadReworkRequestPopup');
        Route::post('load-action-confirmation-modal', 'PatientController@loadPlanActionConfirmationPopup');
        Route::post('modify-treatment-request', 'PatientController@modifyTreamentRequest');
        Route::post('rework-request', 'PatientController@submitReworkRequest');
        Route::post('patients/change-process-status', 'PatientController@changeTheProcessStatusOfOrganization');
        //for old template
        Route::get('patients/therapy-plans-old/{id}', 'PatientController@getPatientTherapyPlansOld');
        Route::get('patients/therapy-plans-old1/{id}', 'PatientController@getPatientTherapyPlans');

        Route::post('save-therapy-plans', 'PatientController@saveTherapyPlan')->name('save-therapy-plans');
        Route::get('get-basedata/{id}', 'PatientController@getPatientProfileData');
        Route::post('edit-patient/{id}', 'PatientController@editPatientProfile');
        Route::get('resend-confirmation-mail/{id}', 'PatientController@resendConfirmationMail')->name('resend-confirmation-mail');
        //get exercise groups
        Route::post('get-exercise-groups', 'PatientController@getExerciseGroups')->name('get-exercise-groups');
        //save exercise groups
        Route::post('save-exercise-groups', 'PatientController@saveExerciseGroups')->name('save-exercise-groups');
        //get exercises from exercise groups
        Route::post('get-exercises', 'PatientController@getExercises')->name('get-exercises');
        //save exercise to phase exercises list table
        Route::post('save-phase-exercises', 'PatientController@savePhaseExercise')->name('save-phase-exercises');
        //save exercise to phase exercises table
        Route::post('save-exercise', 'PatientController@saveExercises')->name('save-exercise');
        //create-patient 
        Route::post('create-patient', 'PatientController@createPatient')->name('create-patient');
        //get phase details of specific phase
        Route::post('get-phase-details/{id}', 'PatientController@getPhaseDetails');
        //update the phase details
        Route::post('save-phase-details/{id}', 'PatientController@savePhaseDetails');

        //plan-administration routes
        Route::get('plan-administration/therapy-plan-templates/{id?}', 'PlanAdministrationController@getTherapyPlanTemplates')->name('administration.therapy-plan-templates');
        Route::get('plan-administration/assessments/{id?}', 'PlanAdministrationController@getAssessmentData')->name('administration.assessments');
        Route::get('plan-administration/exercises', 'PlanAdministrationController@getExercisesData')->name('administration.exercises');
        Route::get('plan-administration/courses', 'PlanAdministrationController@getCoursesData')->name('administration.courses');
        Route::get('assessment-details/{id}', 'PlanAdministrationController@getAssessmentDetails')->name('assessment.details');
        Route::post('change-status/{id}', 'PlanAdministrationController@changeStatusOfAssessment');
        Route::post('update-assessment/{id?}', 'PlanAdministrationController@saveAssessment')->name('update.assessment');
        Route::post('show-assessment-modal/{id?}', 'PlanAdministrationController@getAssessmentPopupData');
        Route::post('remove-assessment/{id}', 'PlanAdministrationController@removeAssessmentData');
        Route::post('get-asssessment-listing', 'PlanAdministrationController@getAssessmentData');
        Route::post('get-chart-data', 'PlanAdministrationController@getChartData');
        Route::post('get-exercises-listing', 'PlanAdministrationController@getExercisesData');
        Route::post('get-courses-listing', 'PlanAdministrationController@getCoursesData');
        Route::post('get-exercise-details/{id?}', 'PlanAdministrationController@getExercisePopupData');
        Route::post('save-exercises-details/{id?}', 'PlanAdministrationController@saveExercise');
        Route::get('export-assessment-excel/{id}', 'PlanAdministrationController@exportAssessment')->name('assessment.export');
        Route::post('save-course-details/{id?}', 'PlanAdministrationController@saveCourse');
        Route::post('remove-exercise', 'PlanAdministrationController@removeExercise');
        Route::post('get-plan-template-listing', 'PlanAdministrationController@getTherapyPlanTemplates');
        Route::get('plan-administration/plan-details/{id}', 'PlanAdministrationController@getTherapyPlanDetails')->name('plan-details');
        Route::post('get-therapy-plan-details/{id?}', 'PlanAdministrationController@getTherapyPlanPopupData');
        Route::post('get-plan-phase-details/{id}', 'PlanAdministrationController@getPhaseDetails');
        Route::post('save-phase-limitation-details/{id}', 'PlanAdministrationController@savePhaseDetails');
        Route::post('save-therapy-plans-templates/{id?}', 'PlanAdministrationController@saveTherapyPlan')->name('save-therapy-plans-templates');
        Route::post('save-therapy-plans-exercises/', 'PlanAdministrationController@saveTherapyPlanExercises');
        
        Route::post('patients/get-plan-phases-details/{id}', 'PatientController@getTherapyPlanPhasesPopupData');
        Route::post('patients/save-patient-plan-phases/{id}', 'PatientController@updatePatientPlanPhasesDetail')->name('save-patient-plan-phases');
    
        Route::post('get-course-details/{id?}', 'PlanAdministrationController@getCoursePopupData');
        Route::post('get-therapy-plan-exercises-details/{id?}', 'PlanAdministrationController@getTherapyPlanExercisesPopupData');
        Route::post('set-favorite/{id}', 'PlanAdministrationController@setAsFavorites');
        Route::post('save-assigned-phase-limitation-details/{id}', 'PatientController@saveAssignedPhaseDetails');
        Route::post('get-assigned-therapy-plan-exercises-details/{id?}', 'PatientController@getAssignedTherapyPlanExercisesPopupData');
        Route::post('show-assigned-assessment-modal/{id?}', 'PatientController@getAssessmentPopupData');
        Route::post('assigned-update-assessment/{id?}', 'PatientController@saveAssessment')->name('assigned.assessment.update');
        Route::post('save-assigned-therapy-plans-exercises/', 'PatientController@saveAssignedTherapyPlanExercises');
        Route::post('save-assigned-exercises-details/{id}', 'PatientController@saveAssignedExercise');
        Route::post('get-assgied-course-details/{id?}', 'PatientController@getCoursePopupData');
        Route::post('save-assigned-course-details/{id?}', 'PatientController@saveCourse');
        Route::post('set-current-phase-session', 'PlanAdministrationController@setCurrentPhaseInSession');
        Route::post('remove-course/{id}', 'PlanAdministrationController@removeAssignedCourses');
        Route::post('remove-plan/{id}', 'PlanAdministrationController@removeTherapyPlanTemplates');
        Route::post('remove-assign-plan', 'PatientController@removeAssignedPlanTemplates');

        //Rights Management Routes
        Route::get('access-permissions/roles', 'RightsController@getUserRoles')->name('access-permissions.roles');
        Route::post('show-role-details-modal/{id?}', 'RightsController@showRoleDetails')->name('role-details');
        Route::get('access-permissions/user-rights', 'RightsController@showUserRightsPage')->name('access-permissions.user-rights');
        Route::post('get-users-listing', 'RightsController@getUserListing');
    });
});
// patient verify route
Route::get('verifyPatient/{id}', 'PatientController@sendResetPasswordEmail')->name('verifyPatient');

// cron
Route::get('cron/unverified-user-delete', 'PatientController@DeleteUnverifiedUser');
