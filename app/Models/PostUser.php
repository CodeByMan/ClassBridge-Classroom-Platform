<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostUser extends Model
{
    use HasFactory;

    protected $table = 'post_user';

    protected $fillable = [
        'post_id',
        'user_id',
        'finished',
        'post_answer',
        'submission_path',
        'submission_name',
        'grade',
        'graded_at',
        'returned_at',
    ];

    protected function casts(): array
    {
        return [
            'finished' => 'boolean',
            'grade' => 'integer',
            'graded_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
