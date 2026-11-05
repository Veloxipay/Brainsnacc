<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_PARENT = 'parent';
    public const ROLE_STUDENT = 'student';
    public const ROLE_TEACHER = 'teacher';

    protected $fillable = ['name', 'username', 'email', 'password', 'role', 'parent_id', 'level_id'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** What this person types to log in: their username if they have one, otherwise their email. */
    public function loginName(): string
    {
        return $this->username ?: (string) $this->email;
    }

    /** Rules for a username: 3-30 letters, numbers, dots, dashes or underscores. */
    public static function usernameRules(?int $ignoreId = null): array
    {
        return ['nullable', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9._-]+$/', \Illuminate\Validation\Rule::unique('users', 'username')->ignore($ignoreId)];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isParent(): bool
    {
        return $this->role === self::ROLE_PARENT;
    }

    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }

    public function isTeacher(): bool
    {
        return $this->role === self::ROLE_TEACHER;
    }

    /** Parents and teachers look after students. */
    public function isSupervisor(): bool
    {
        return $this->isParent() || $this->isTeacher();
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    public function parentUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    /** Students this parent or teacher looks after. */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'student_supervisors', 'supervisor_id', 'student_id')->withTimestamps();
    }

    /** Parents and teachers who look after this student. */
    public function supervisors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'student_supervisors', 'student_id', 'supervisor_id')->withTimestamps();
    }

    /** Old name kept so existing code keeps working. */
    public function children(): BelongsToMany
    {
        return $this->students();
    }

    public function completedLessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'lesson_progress')
            ->withPivot('completed_at')
            ->withTimestamps();
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuestionAttempt::class);
    }
}
