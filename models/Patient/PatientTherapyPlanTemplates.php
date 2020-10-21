<?php

namespace App\Patient;

use Illuminate\Database\Eloquent\Model;

class PatientTherapyPlanTemplates extends Model
{
    protected $appends = ['encrypted_plan_template_id'];
    protected $guarded = [];  
    //relation with phase table
    public function Phases()
    {
        return $this->hasMany('App\Patient\PatientPhases', 'therapy_plan_templates_id', 'id');
    }

     //relation with indication
    public function Indications()
    {
        return $this->belongsToMany(PatientIndication::class, 'patient_therapy_plan_indications', 'therapy_plan_templates_id', 'indication_id')->withTimestamps();
    }

    //relation with body_regions
    public function BodyRegions()
    {
        return $this->belongsToMany(PatientBodyRegions::class, 'patient_therapy_plan_body_regions', 'therapy_plan_templates_id', 'body_regions_id')->withTimestamps();
    }

    //relation with the patient_assessment_schedules
    public function Schedules() {
        return $this->hasMany('App\PatientAssessmentSchedules', 'patient_assessment_templates_id', 'id');
    }

    //apend the encrypted_assessment_id
    public function getencryptedPlanTemplateIdAttribute()
    {
        return encrypt($this->id);
    }

    public function AllExercises()
    {
        return $this->hasManyThrough(
            'App\Patient\PatientPhaseExercises', 'App\Patient\PatientPhases',
             'therapy_plan_templates_id', 'phase_id', 'id'
        );
    }
    public function AllCourses()
    {
        return $this->hasManyThrough(
            'App\Patient\PatientPhaseCourses', 'App\Patient\PatientPhases',
             'therapy_plan_templates_id', 'phase_id', 'id'
        );
    }
    //get the data of AllAssessments 
    public function AllAssessments()
    {
        return $this->hasManyThrough(
            'App\Patient\PatientPhaseAssessments', 'App\Patient\PatientPhases',
             'therapy_plan_templates_id', 'phases_id', 'id'
        );
    }

    //relation with patient_plan_network table
    public function PatientPlanNetwork(){
        return $this->hasMany('App\Patient\PatientPlanNetwork', 'plan_id', 'id');
    }
}
