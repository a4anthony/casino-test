<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpinGroup extends Model
{
    use HasFactory;

    protected $casts = [
        "spin_data" => "array",
    ];

    protected $fillable = ["group_id", "spin_data"];
}
