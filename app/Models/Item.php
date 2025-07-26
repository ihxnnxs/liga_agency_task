<?php

namespace App\Models;

use App\Enums\Status;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
    ];


    protected $casts = [

        'status' => Status::class,
    ];

    public function scopeAllowed(Builder $query): void
    {
        $query->where('status', Status::Allowed);
    }

}
