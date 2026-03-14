<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Company extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\CompanyFactory> */
    use HasFactory, InteractsWithMedia;

    protected $table = 'company';

    protected $fillable = ['name', 'owner_id'];

    public function getLogoAttribute(): string
    {
        return $this->getFirstMediaUrl('logos');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function isOwned(): bool
    {
        return ! is_null($this->owner_id);
    }
}
