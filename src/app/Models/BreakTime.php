<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Attendance;

class BreakTime extends Model
{
    use HasFactory;
    protected $fillable = [
        'attendance_id',
        'break_start',
        'break_end',
    ];
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
