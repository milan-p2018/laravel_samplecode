<?php

namespace App\Patient;

use Illuminate\Database\Eloquent\Model;
use Storage;

class PatientExercises extends Model
{
    protected $guarded = [];  

    protected $appends = ['encrypted_exercise_id', 'image_url', 'video_url'];

    //relation with body_regions
    public function BodyRegions()
    {
        return $this->belongsToMany(PatientBodyRegions::class, 'patient_exercises_body_regions', 'exercises_id', 'body_regions_id')->withTimestamps();
    }

    //apend the encrypted_exercises_id
    public function getencryptedExerciseIdAttribute()
    {
        return encrypt($this->id);
    }

    //relation to phase exercises list
    public function PhaseExercises()
    {
        return $this->hasMany('App\Patient\PatientPhaseExercises', 'exercise_id','id');
    }

    //relation with Favorite Exercises
    // public function FavoriteExercises()
    // {
    //     return $this->hasMany('App\Patient\PatientFavoriteExercises', 'exercise_id', 'id');
    // }

    public function getimageUrlAttribute()
    {
        return !empty($this->image) && Storage::disk('public')->exists($this->image) ?  url('/storage') . $this->image : NULL;
    }

    public function getvideoUrlAttribute()
    {
        return !empty($this->video) && Storage::disk('public')->exists($this->video) ?  url('/storage') . $this->video : NULL;
    }
}
