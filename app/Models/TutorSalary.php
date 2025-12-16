<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TutorSalary extends Model
{
    protected $fillable = [
        'tutor_user_id',
        'admin_user_id',
        'amount',
        'invoice_file_url',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'paid_at' => 'date',
        'amount' => 'integer',
    ];

    /**
     * Get the tutor user
     */
    public function tutor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tutor_user_id');
    }

    /**
     * Get the admin user who processed the salary
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
