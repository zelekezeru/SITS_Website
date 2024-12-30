<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedbacks'; 
    
    // Fillable Properties
    protected $fillable = [
        'task_id',
        'user_id',
        'comment',
        'feedback_id',
    ];

    // Relationship Functions
    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function repliedTo()
    {
        return $this->belongsTo(Feedback::class);
    }

    public function replies()
    {
        return $this->hasMany(Feedback::class);
    }
}
