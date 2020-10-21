<?php

namespace App\Patient;

use Illuminate\Database\Eloquent\Model;
use App\CourseExercises;
use Storage;

class PatientCourses extends Model
{
    protected $guarded = [];  

    protected $appends = ['encrypted_course_id', 'exercise_counts','image_url', 'round_info', 'frequency_data', 'course_feedback'];
    //relation with indication
    public function Indications()
    {
        return $this->belongsToMany(PatientIndication::class, 'patient_courses_indications', 'courses_id', 'indication_id')->withTimestamps();
    }

    //relation with body_regions
    public function BodyRegions()
    {
        return $this->belongsToMany(PatientBodyRegions::class, 'patient_courses_body_regions', 'courses_id', 'body_regions_id')->withTimestamps();
    }

    //relation with Courses Exercises
    public function CourseExercises()
    {
        return $this->hasMany('App\Patient\PatientCourseExercises', 'course_id', 'id');
    }

    //relation with Favorite Course
    public function FavoriteCourses()
    {
        return $this->hasMany('App\Patient\PatientFavoriteCourses', 'course_id', 'id');
    }

    //apend the encrypted_exercises_id
    public function getencryptedCourseIdAttribute()
    {
        return encrypt($this->id);
    }

    //apend the exercise count 
    public function getExerciseCountsAttribute()
    {
        $exercise_count = PatientCourseExercises::with('Exercises')->where('course_id', $this->id)->whereNotNull('exercise_id')->count();
        return $exercise_count;
    }

    public function Course()
    {
        return $this->hasMany('App\Patient\PatientCourse', 'course_id','id');
    }

    public function getimageUrlAttribute()
    {
        return !empty($this->image) && Storage::disk('public')->exists($this->image) ?  url('/storage') . $this->image : NULL;
    }

    public function getRoundInfoAttribute()
    {
        return !empty($this->attributes['round_info']) ?  json_decode($this->attributes['round_info'], true) : NULL;
    }

    public function getProgressStatusAttribute()
    {
        // $roundInfo = !empty($this->attributes['round_info']) ?  json_decode($this->attributes['round_info'], true) : NULL;
        // if($roundInfo) {
        //     return (100*$this->count)/count($roundInfo['data']);
        // }
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
