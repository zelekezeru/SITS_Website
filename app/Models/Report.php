<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    // Fillable Properties
    protected $fillable = [
        'task_id', 'summary', 'performance_graph'
    ];

    // Relationship Functions
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

}
