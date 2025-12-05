<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SessionReport extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'student_package_id',
        'taken_schedule_id',
        'session_number',
        'material',
        'score',
        'review',
        'document_path',
        'session_date',
    ];

    protected $casts = [
        'session_date' => 'date',
    ];

    public function studentPackage(): BelongsTo
    {
        return $this->belongsTo(StudentPackage::class);
    }

    public function takenSchedule(): BelongsTo
    {
        return $this->belongsTo(TakenSchedule::class);
    }
}
