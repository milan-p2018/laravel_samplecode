<?php

namespace App\Patient;

use Illuminate\Database\Eloquent\Model;

class PatientPhaseExercises extends Model
{
    protected $guarded = [];  

    protected $appends = ['round_info', 'course_feedback', 'frequency_data'];

    //relation to phase exercises list
    public function Exercises()
    {
        return $this->belongsTo('App\Exercises', 'exercise_id','id');
    }

    //relation with phase table
    public function Phases()
    {
        return $this->hasOne('App\Patient\PatientPhases', 'id', 'phase_id');
    }

    public function getRoundInfoAttribute()
    {
        return !empty($this->attributes['round_info']) ?  json_decode($this->attributes['round_info'], true) : NULL;
    }

    public function getProgressStatusAttribute()
    {
        $roundInfo = !empty($this->attributes['round_info']) ?  json_decode($this->attributes['round_info'], true) : NULL;
        if($roundInfo) {
            return (100*$this->count)/count($roundInfo['data']);
        }
        return 0;
    }

    public function getCourseFeedbackAttribute()
    {
        return !empty($this->attributes['course_feedback']) ?  json_decode($this->attributes['course_feedback'], true) : NULL;
    }

    //append the freequncy array
    public function getFrequencyDataAttribute()
    {
        return gettype(json_decode($this->frequency)) == "object" ? json_decode($this->frequency, true) : [];
    }
    
}
