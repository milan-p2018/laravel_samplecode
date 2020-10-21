<?php

namespace App\Patient;

use Illuminate\Database\Eloquent\Model;

class PatientPhases extends Model
{
    protected $guarded = [];  
    protected $appends = ['encrypted_phase_id'];

    public function TherapyPlanTemplates()
    {
        return $this->hasOne('App\Patient\PatientTherapyPlanTemplates', 'id','therapy_plan_templates_id');
    }

    public function PhaseExercises()
    {
        return $this->hasMany('App\Patient\PatientPhaseExercises', 'phase_id','id');
    }

    public function Limitations()
    {
        return $this->hasMany('App\Patient\PatientLimitations', 'phase_id','id');
    }

    public function PhaseCourses()
    {
        return $this->hasMany('App\Patient\PatientPhaseCourses', 'phase_id','id');
    }

    public function Courses()
    {
        return $this->hasMany('App\Patient\PatientCourses', 'phase_id','id');
    }

    public function PhaseAssessments()
    {
        return $this->hasMany('App\Patient\PatientPhaseAssessments', 'phases_id','id');
    }

    public function Assessment()
    {
         return $this->belongsToMany(PatientAssessmentTemplates::class, 'patient_phase_assessments', 'phases_id', 'assessment_templates_id')->withTimestamps();
    }
    //get the data of subcategories 
    public function TotalAseessments()
    {
        return $this->hasManyThrough(
            'App\Patient\PatientAssessmentTemplates', 'App\Patient\PatientPhaseAssessments',
             'assessment_id', 'assessment_category_id', 'id'
        );
    }

    //apend the encrypted_phase_id
    public function getencryptedPhaseIdAttribute()
    {
        return encrypt($this->id);
    }
}
