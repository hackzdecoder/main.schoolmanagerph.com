<?php

namespace App\Models;

use App\Providers\DatabaseManagerServiceProvider;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'student_records';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'student_id',
        'fullname',
        'nickname',
        'foreign_name',
        'gender',
        'course',
        'level',
        'school_level',
        'section',
        'school_name',
        'email',
        'mobile_number',
        'lrn',
        'profile_img'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Connection pool to avoid recreating connections
     */
    protected static $connectionPool = [];

    /**
     * Access current user_id from authenticated student user
     */
    protected $userId = null;

    /**
     * Construct database connection instance
     *
     * @param array $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (auth()->check()) {
            $dbName = $this->queryBuilder();

            if ($dbName && $dbName !== 'mysql') {
                if (!isset(self::$connectionPool[$dbName])) {
                    $provider = DatabaseManagerServiceProvider::source();
                    $data = json_decode($provider->getContent(), true);

                    if (isset($data['data']['connection_config'])) {
                        config([
                            "database.connections.{$dbName}" => $data['data']['connection_config']
                        ]);
                    }

                    self::$connectionPool[$dbName] = true;
                }

                $this->setConnection($dbName);
            }
        }
    }

    /**
     * Dispatch Query Builder
     *
     * @return string
     */
    public function queryBuilder()
    {
        $provider = DatabaseManagerServiceProvider::source();
        $data = json_decode($provider->getContent(), true);

        // Store current user_id
        if ($this->userId === null && isset($data['data']['user_id'])) {
            $this->userId = $data['data']['user_id'];
        }

        return $data['data']['database'] ?? 'main_connection';
    }

    /**
     * Get the current authenticated user's ID
     * 
     * @return string|null
     */
    public function getUserId()
    {
        // If it haven't stored it yet, try to fetch it
        if ($this->userId === null) {
            $this->queryBuilder(); // propagate userId
        }

        return $this->userId;
    }

    /**
     * Student Model -> User Model (belongsTo)
     * Get the student associated with this users record
     */
    public function users()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    /**
     * Student Model -> Attendance Model (has many)
     * Get the attendance records for the student
     */
    public function attendanceList()
    {
        return $this->hasMany(Attendance::class, 'user_id', 'user_id');
    }

    /**
     * Student Model -> Message Model (hasMany)
     * Get the messages associated with this student
     */
    public function messagesList()
    {
        return $this->hasMany(Message::class, 'user_id', 'user_id');
    }

    /**
     * Scope to get current student's own record using user_id
     * 
     * @return string|null
     */
    public function scopeStudentUserId($query)
    {
        $userId = $this->getUserId();

        if ($userId) {
            return $query->where('user_id', $userId);
        }

        return $query;
    }

    /**
     * Scope sorting by User Email
     * Prioritizes emails with values
     */
    public function scopeUserEmailSorting($query, $order)
    {
        return $query->orderBy(
            User::select('email')
                ->whereColumn('user_id', 'student_records.user_id'),
            $order
        );
    }
}