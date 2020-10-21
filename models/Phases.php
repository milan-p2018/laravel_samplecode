<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Phases extends Model
{
    protected $appends = ['encrypted_phase_id'];
    public function TherapyPlanTemplates()
    {
        return $this->hasOne('App\TherapyPlanTemplates', 'id','therapy_plan_templates_id');
    }

    public function PhaseExercises()
    {
        return $this->hasMany('App\PhaseExercises', 'phase_id','id');
    }

    public function Limitations()
    {
        return $this->hasMany('App\Limitations', 'phase_id','id');
    }

    public function PhaseCourses()
    {
        return $this->hasMany('App\PhaseCourses', 'phase_id','id');
    }

    public function Courses()
    {
        return $this->hasMany('App\Courses', 'phase_id','id');
    }

    public function PhaseAssessments()
    {
        return $this->hasMany('App\PhaseAssessments', 'phases_id','id');
    }

    public function Assessment()
    {
         return $this->belongsToMany(TherapyPlanAssessmentTemplates::class, 'therapyplan_assessment_templates', 'therapy_plan_assessment_templates_id', 'phases_id')->withTimestamps();
    }
    

    //apend the encrypted_phase_id
    public function getencryptedPhaseIdAttribute()
    {
        return encrypt($this->id);
    }
}
