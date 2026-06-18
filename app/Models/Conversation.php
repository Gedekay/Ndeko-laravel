<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'type',
        'created_by'
    ];

    public function members()
    {
        return $this->belongsToMany(
            User::class,
            'conversation_members'
        );
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function group()
    {
        return $this->hasOne(Group::class);
    }
    public function lastMessage()
    {
        return $this->hasOne(Message::class)
            ->latestOfMany();
    }
}