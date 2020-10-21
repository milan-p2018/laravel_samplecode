<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('login','Apis\UserController@authenticate');
Route::post('register','Apis\UserController@register');

//for reset password
Route::post('reset-password','Apis\UserController@resetPassword');

//user routes
Route::post('check-user-exists', 'Apis\UserController@checkUserExist');

Route::group(['middleware' => ['auth']], function () {
    Route::get('data/{api}', 'Apis\RestApiController@data');
    Route::get('getkey/{api}/{id}','Apis\RestApiController@getKey');
    Route::post('adddata/{api}','Apis\RestApiController@adddata');
    Route::post('aconnect/{api}/{id}','Apis\RestApiController@connect');
    Route::post('dates/register', 'Apis\DatesController@register');
    Route::get('dates/{id?}', 'Apis\DatesController@getDates');

    //update profile route
    Route::post('/update-profile', 'Apis\UserController@updateProfile');

    //patient-plans route
    Route::get('get-all-plans', 'Apis\PatientController@getAllPlans');
	
    //get exercise groups 
    Route::get('plan-detail', 'Apis\PatientController@getExerciseGroups');
    Route::post('add-exercise', 'Apis\PatientController@createExercise');
    Route::post('exercise-progress', 'Apis\PatientController@exerciseProgress');
    Route::post('course-feedback', 'Apis\PatientController@courseFeedback');
	
    //patient events api
    Route::get('get-patient-events', 'Apis\PatientController@getPatientEvents');
    Route::post('create-patient-events', 'Apis\PatientController@updateOrCreatemeetings');
    Route::post('update-patient-events', 'Apis\PatientController@updateOrCreatemeetings');
    Route::post('delete-patient-event', 'Apis\PatientController@deleteMeetings');

    //patient-details api
    Route::get('get-patient-details', 'Apis\UserController@getPatientData');

    //add-measurements to assessment
    Route::post('add-assessment-measurements', 'Apis\PatientController@addMeasurementsOfAssessments');    
});
