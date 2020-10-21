<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TherapyPlanTemplates extends Model
{
	protected $appends = ['encrypted_plan_template_id'];
    //relation with phase table
    public function Phases()
    {
        return $this->hasMany('App\Phases', 'therapy_plan_templates_id', 'id');
    }

     //relation with indication
    public function Indications()
    {
        return $this->belongsToMany(Indication::class, 'therapy_plan_indications')->withTimestamps();
    }

    //relation with body_regions
    public function BodyRegions()
    {
        return $this->belongsToMany(BodyRegions::class, 'therapy_plan_body_regions')->withTimestamps();
    }

    //apend the encrypted_assessment_id
    public function getencryptedPlanTemplateIdAttribute()
    {
        return encrypt($this->id);
    }

    public function AllExercises()
    {
        return $this->hasManyThrough(
            'App\PhaseExercises', 'App\Phases',
             'therapy_plan_templates_id', 'phase_id', 'id'
        );
    }
    public function AllCourses()
    {
        return $this->hasManyThrough(
            'App\PhaseCourses', 'App\Phases',
             'therapy_plan_templates_id', 'phase_id', 'id'
        );
    }
    //get the data of AllAssessments 
    public function AllAssessments()
    {
        return $this->hasManyThrough(
            'App\PhaseAssessments', 'App\Phases',
             'therapy_plan_templates_id', 'phases_id', 'id'
        );
    }
}
