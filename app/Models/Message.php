<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'type',
        'content',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'edited_at'
    ];

    protected $casts = [
        'edited_at' => 'datetime'
    ];

    public function sender()
    {
        return $this->belongsTo(
            User::class,
            'sender_id'
        );
    }

    public function conversation()
    {
        return $this->belongsTo(
            Conversation::class
        );
    }
}
