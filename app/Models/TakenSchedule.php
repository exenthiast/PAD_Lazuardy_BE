<?php

namespace App\Models;

use App\Enums\TakenScheduleStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TakenSchedule extends Model
{
    /** @use HasFactory<\Database\Factories\TakenScheduleFactory> */
    use HasFactory;
    public $timestamps = false; 

    protected $fillable = 
    [
        'user_id',
        'schedule_tutor_id',
        'subject_id',
        'date',
        'status',
        'meeting_link',
        'course_mode',
        'meeting_link_sent',
        'meeting_link_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TakenScheduleStatusEnum::class,
            'date' => 'date',
            'meeting_link_sent' => 'boolean',
            'meeting_link_sent_at' => 'datetime',
        ];
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scheduleTutor()
    {
        return $this->belongsTo(ScheduleTutor::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function studentAttendance(): HasOne
    {
        return $this->hasOne(StudentAttendance::class);
    }
}
