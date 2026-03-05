<?php

namespace App\Models;

use App\Providers\DatabaseManagerProvider;
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
     * Construct database connection instance
     *
     * @param array $attributes
     * @return void
     */
    public function __construct(array $attributes = [])
    {

        $dbName = $this->queryBuilder();

        if ($dbName && $dbName !== 'mysql') {

            if (!isset(self::$connectionPool[$dbName])) {
                $provider = DatabaseManagerProvider::source();
                $data = json_decode($provider->getContent(), true);

                if (isset($data['data']['connection_config'])) {
                    config([
                        "database.connections.{$dbName}" => $data['data']['connection_config']
                    ]);
                }

                self::$connectionPool[$dbName] = true;
            }

            $this->setConnection($dbName);
        } else {
            $this->setConnection('database_connection');
        }
    }

    /**
     * Dispatch Query Builder
     *
     * @return string
     */
    public function queryBuilder()
    {
        $provider = DatabaseManagerProvider::source();

        $data = json_decode($provider->getContent(), true);

        return $data['data']['database'] ?? 'database_connection';
    }

    /**
     * User Model -> Student Model
     */
    public function users()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
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