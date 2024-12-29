<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    // Fillable Properties
    protected $fillable = [
        'title',
        'description',
        'duration',
        'budget',
        'status'
    ];

    // Relationship Functions
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function kpis()
    {
        return $this->hasMany(Kpi::class);
    }

    public function feedback()
    {
        return $this->hasMany(Feedback::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }
}
