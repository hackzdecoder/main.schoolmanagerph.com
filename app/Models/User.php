<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'database_connection';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'user_id';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'username',
        'email',
        'password',
        'fullname',
        'school_code',
        'account_status',
        'gs_access_status',
        'assigned_admin_email',
        'terms',
        'usage_policy',
        'privacy_policy',
        'terms_policy_date',
        'first_user_token',
        'first_user_token_expiry_at',
        'reset_password_token',
        'reset_token_expires_at',
        'otp_code',
        'otp_code_expired_at',
        'otp_verified_at',
        'last_successfull_login',
        'password_update_by'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'reset_password_token',
        'otp_code',
        'first_user_token'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'otp_verified_at' => 'datetime',
        'otp_code_expired_at' => 'datetime',
        'reset_token_expires_at' => 'datetime',
        'first_user_token_expiry_at' => 'datetime',
        'terms_policy_date' => 'datetime',
        'last_successfull_login' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'password' => 'hashed'
    ];

    /**
     * Scope find username by User
     * User Credential Login verification
     */
    public function scopeFindUser($query, $username)
    {
        return $query->where('username', $username);
    }
}