<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\JsonResponse;

class StudentController extends Controller
{
    public function students(): JsonResponse
    {
        $students = Student::with('users')
            ->userEmailSorting('desc')
            ->get();

        if ($students->isEmpty()) {
            return new JsonResponse([
                'success' => true,
                'response' => 'No records found',
            ], 200);
        }

        return new JsonResponse([
            'success' => true,
            'response' => 'Records available',
            'data' => [
                'students' => $students->map(fn($row) => [
                    'student_info' => [
                        'student_id' => $row?->student_id ?? 'Student ID not available',
                        'fullname' => $row?->fullname,
                        'nickname' => $row?->nickname ?? 'Nickname not available',
                        'foreign_name' => $row?->foreign_name ?? 'Foreign name not available',
                        'gender' => $row?->gender,
                        'course' => $row?->course,
                        'level' => $row?->level,
                        'school_level' => $row?->school_level,
                        'section' => $row?->section,
                        'school_name' => $row?->school_name,
                        'mobile_number' => $row?->mobile_number,
                        'lrn' => $row?->lrn ?? 'LRN not available',
                        'profile_img' => $row?->foreign_name ?? 'Profile image not available'
                    ],
                    'user_account' => [
                        'username' => $row?->users?->username,
                        'email' => $row?->users?->email
                    ],
                ])
            ]
        ], 200);
    }

    /**
     * Get student profile with user data using Eloquent relationship
     */
    // public function studentProfile(Request $request): JsonResponse
    // {

    //     try {
    //         // Get authenticated user
    //         $authUser = $request->user();

    //         // Check if user has school_code
    //         if (!$authUser->school_code) {
    //             return new JsonResponse([
    //                 'success' => false,
    //                 'error' => 'User has no school code assigned'
    //             ], 400);
    //         }

    //         // Use authenticated user's ID
    //         $student = Student::findByUserId($authUser->user_id)->first();

    //         if (!$student) {
    //             return new JsonResponse([
    //                 'success' => false,
    //                 'error' => 'User not found'
    //             ], 404);
    //         }

    //         return new JsonResponse([
    //             'success' => true,
    //             'data' => $student->user_id
    //         ], 200);

    //     } catch (\Throwable $th) {
    //         return new JsonResponse([
    //             'success' => false,
    //             'error' => 'Failed to get student profile: ' . $th->getMessage()
    //         ], 500);
    //     }
    // }
}