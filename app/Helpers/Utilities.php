<?php

namespace App\Helpers;

class Utilities
{
    /**
     * Generate database name from school code and environment
     */
    public static function setSchoolDatabase(array $school, $env)
    {
        if (!isset($school['school_code']) || !is_string($school['school_code'])) {
            throw new \InvalidArgumentException('Valid school code is required');
        }

        $schoolCode = strtolower(trim($school['school_code']));

        if (empty($schoolCode) || !preg_match('/^[a-zA-Z0-9_]+$/', $schoolCode)) {
            throw new \InvalidArgumentException('School code contains invalid characters');
        }

        // Return database name based on environment
        return $env === 'dev'
            ? 'sm_' . $schoolCode . '_dev'
            : 'u141085058_' . $schoolCode;
    }
}