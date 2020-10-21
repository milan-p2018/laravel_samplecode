<?php

namespace App\Patient;

use Illuminate\Database\Eloquent\Model;

class PatientAssessmentTemplates extends Model
{
    protected $guarded = [];  
    protected $appends = ['encrypted_assessment_id'];
    //relation with AssessmentCategories
    public function Categories()
    {
        return $this->hasMany('App\Patient\PatientAssessmentCategories', 'assessment_template_id', 'id');
    }

    //relation with Phase Assessment
    public function PhaseAssessments()
    {
        return $this->hasOne('App\Patient\PatientPhaseAssessments', 'assessment_templates_id','id');
    }

    //relation with indication
    public function Indications()
    {
        return $this->belongsToMany(PatientIndication::class, 'patient_assessment_indications', 'assessment_templates_id', 'indication_id')->withTimestamps();
    }

    //relation with body_regions
    public function BodyRegions()
    {
        return $this->belongsToMany(PatientBodyRegions::class, 'patient_assessment_body_regions', 'assessment_templates_id', 'body_regions_id')->withTimestamps();
    }

    //relation with the patient_assessment_schedules
    public function Schedules() {
        return $this->hasMany('App\Patient\PatientAssessmentSchedules', 'patient_assessment_templates_id', 'id');
    }

    //apend the encrypted_assessment_id
    public function getencryptedAssessmentIdAttribute()
    {
        return encrypt($this->id);
    }

    //get the data of subcategories 
    public function SubCategoriesData()
    {
        return $this->hasManyThrough(
            'App\Patient\PatientAssessmentSubCategories', 'App\Patient\PatientAssessmentCategories',
             'assessment_template_id', 'assessment_category_id', 'id'
        );
    }
}
