<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = [
        'conversation_id',
        'name',
        'description',
        'image',
        'owner_id'
    ];

    public function conversation()
    {
        return $this->belongsTo(
            Conversation::class
        );
    }

    public function owner()
    {
        return $this->belongsTo(
            User::class,
            'owner_id'
        );
    }
}