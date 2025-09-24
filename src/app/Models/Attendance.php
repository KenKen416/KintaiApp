<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\BreakTime;
use App\Models\AttendanceCorrection;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in',
        'clock_out',
        'note',
    ];
    protected $casts = [
        'work_date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class);
    }
    public function attendanceCorrections()
    {
        return $this->hasMany(AttendanceCorrection::class);
    }
}
