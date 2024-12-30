<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory; 
  
    protected $fillable = [
        'title',
        'description',
        'category',
        'credit_hours',
        'amount_paid',
        'program_id',
        'banner',
        // 'status',
        // 'visibility',
    ];
}
