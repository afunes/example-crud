<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

    public $table = 'activities';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public $fillable = [
        'name',
        'color',
        'trash'
    ];

    public $casts = [
        'name'  => 'string',
        'color' => 'string',
        'trash' => 'boolean',
    ];

}
