<?php

namespace App\Patient;

use Illuminate\Database\Eloquent\Model;
use Helper;
use App\Patient\PatientAssessmentMeasurements;
use App\Patient\PatientAssessmentSchedules;

class PatientAssessmentSubCategories extends Model
{
    protected $guarded = [];  
    protected $appends = ['chart_data', 'encrypted_sub_cat_id', 'translated_unit', 'average_measurement_value', 'schedules_data'];

    //relation with Assessment Sub Categories
    public function AssessmentCategories()
    {
        return $this->hasOne('App\Patient\PatientAssessmentCategories', 'id','assessment_category_id');
    }

    //relation with Assessment Measurement
    public function Measurement()
    {
        return $this->hasMany('App\Patient\PatientAssessmentMeasurements', 'patient_assessment_sub_categories_id', 'id');
    }
    
    //relation with Patient Assessment Schedules
    public function Schedules()
    {
        return $this->hasOne('App\Patient\PatientAssessmentSchedules', 'id','patient_assessment_schedules_id');
    }

    public function getchartDataAttribute()
    {
        $start_value = $this->start_value;
        $end_value =  $this->end_value;
        $random_numbers = Helper::getChartData($start_value, $end_value);
        return $random_numbers;
    }

    //append the encrypted_sub_cate_id to assessment-sub-category table response
    public function getencryptedSubCatIdAttribute()
    {
        return encrypt($this->id);
    }

    //append the translated unit to assessment-sub-category table response
    public function gettranslatedUnitAttribute()
    {
        return \Lang::get('lang.'.$this->unit);
    }

    //append the average measurement count of specific sub_category
    public function getAverageMeasurementValueAttribute()
    {
        $assessment_measurements = PatientAssessmentMeasurements::where('patient_assessment_sub_categories_id', $this->id)->select('value')->get();
        if(count($assessment_measurements)) {
            $total = 0;
            $count = count($assessment_measurements);
            foreach($assessment_measurements as $values) {
                $total  = $total + (int)$values->value;
            }
            return $total/$count;
        }
        return 0;
    }

    //append the schedules details
    public function getschedulesDataAttribute() {
        $schedules = [];
        if(!empty($this->patient_assessment_schedules_id)) {
            $schedules = explode(",", $this->patient_assessment_schedules_id);
            $schedules_data = PatientAssessmentSchedules::find($schedules);
            $schedules = $schedules_data;
        }
        return $schedules;
    }
}
