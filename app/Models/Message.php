<?php

namespace App\Models;

use App\Providers\DatabaseManagerServiceProvider;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'messages';

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
        'date',
        'subject',
        'message',
        'status',
        'full_name'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Connection pool to avoid recreating connections
     */
    protected static $connectionPool = [];

    /**
     * Current user ID from DatabaseManagerServiceProvider
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

        return $data['data']['database'] ?? 'database_connection';
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
     * Message Model -> Student Model (belongsTo)
     * Get the student that owns this message
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'user_id', 'user_id');
    }

    /**
     * Scope to filter messages by fullname
     * Searches the full_name field in messages table
     */
    public function scopeFilterByFullname($query, $fullname)
    {
        if ($fullname) {
            return $query->where('full_name', 'LIKE', '%' . $fullname . '%');
        }

        return $query;
    }

    /**
     * Scope to filter messages by date range
     * Uses created_at field
     */
    public function scopeFilterByDateRange($query, $startDate, $endDate)
    {
        if ($startDate && $endDate) {
            return $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return $query;
    }

    /**
     * Scope to get current student's own messages records
     * Uses user_id field
     */
    public function scopeMessagesUserId($query)
    {
        $userId = $this->getUserId();

        if ($userId) {
            return $query->where('user_id', $userId);
        }

        return $query;
    }

    /**
     * Scope to sort by Full Names
     * Uses fullname field
     */
    public function scopeSortByName($query, $order)
    {
        return $query->orderBy('full_name', $order);
    }

    /**
     * Scope to sort by date
     * Uses created_at field
     */
    public function scopeSortByDate($query, $order)
    {
        return $query->orderBy('created_at', $order);
    }
}