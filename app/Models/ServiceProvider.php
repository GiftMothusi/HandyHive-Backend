<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServiceProvider extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'category',
        'description',
        'hourly_rate',
        'rating',
        'availability',
        'experience',
        'certifications',
        'profile_image',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'availability' => 'array',
        'experience' => 'array',
        'certifications' => 'array',
        'rating' => 'float',
    ];

    /**
     * Get the user that the service provider belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the bookings for the service provider.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'provider_id');
    }

    /**
     * Get the reviews for the service provider.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'ratee_id');
    }
}
