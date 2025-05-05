<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @package App\Models
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property ?string $phone_number
 * @property ?string $address
 * @property ?string $image_path
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Contact extends Model
{
    protected $fillable = [
        'first_name',
        'last_name',
        'phone_number',
        'address',
        'image_path',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
