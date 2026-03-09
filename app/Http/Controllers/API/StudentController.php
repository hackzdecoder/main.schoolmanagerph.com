<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Student;
use Illuminate\Http\JsonResponse;

class StudentController extends Controller
{
    /**
     * Get overall Student
     * Route: POST /students
     */
    public function students(): JsonResponse
    {
        try {
            $students = Student::with('users')
                ->studentUserId()
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
                'response' => 'Found ' . $students->count() . ' records',
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

        } catch (\Throwable $th) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to fetch your student: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Get attendance for a specific student by user_id
     * Route: POST /students/attendance
     */
    public function student_attendance(): JsonResponse
    {
        try {
            // Get the current student with their attendance records
            $student = Student::with([
                'attendanceList' => function ($query) {
                    $query->prioritySortUnread('desc')->sortByDate('desc');
                }
            ])->studentUserId()->first();

            if (!$student) {
                return new JsonResponse([
                    'success' => false,
                    'response' => 'No records found',
                ], 404);
            }

            $attendance = $student->attendanceList;

            if ($attendance->isEmpty()) {
                return new JsonResponse([
                    'success' => false,
                    'response' => 'No attendance not found',
                ], 404);
            }

            return new JsonResponse([
                'success' => true,
                'response' => 'Found ' . $attendance->count() . ' records',
                'data' => [
                    'attendance' => $attendance->map(fn($row) => [
                        'full_name' => $row?->full_name ?? $student->fullname,
                        'time_in' => $row?->time_in,
                        'kiosk_terminal_in' => $row?->kiosk_terminal_in,
                        'time_out' => $row?->time_out,
                        'kiosk_terminal_out' => $row?->kiosk_terminal_out,
                        'date' => $row?->date,
                        'status' => $row?->status
                    ]),
                ]
            ], 200);

        } catch (\Throwable $th) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to fetch your attendance: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Get student fullname list
     * Route: GET /students/attendance/fullname
     */
    public function student_attendance_fullname(): JsonResponse
    {
        try {
            // Get the current student with their attendance full_name records
            $student = Student::with([
                'attendanceList' => function ($query) {
                    $query->select('user_id', 'full_name')->distinct();
                }
            ])
                ->studentUserId()
                ->first();

            if (!$student) {
                return new JsonResponse([
                    'success' => false,
                    'response' => 'No records found',
                ], 404);
            }

            // Get unique fullnames from attendance records
            $uniqueFullnames = $student->attendanceList
                ->unique('full_name')
                ->values()
                ->map(fn($row) => [
                    'fullname' => $row->full_name
                ]);

            return new JsonResponse([
                'success' => true,
                'response' => 'Found ' . $uniqueFullnames->count() . ' records',
                'data' => [
                    'students' => $uniqueFullnames
                ]
            ], 200);

        } catch (\Throwable $th) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to fetch student fullnames: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Get messages for a specific student by user_id
     * Route: GET /students/messages
     */
    public function student_messages(): JsonResponse
    {
        try {
            // Get the current student with their messages
            $student = Student::with([
                'messagesList' => function ($query) {
                    $query->sortByDate('desc');
                }
            ])->studentUserId()->first();

            if (!$student) {
                return new JsonResponse([
                    'success' => false,
                    'response' => 'No records found',
                ], 404);
            }

            $messages = $student->messagesList;

            if ($messages->isEmpty()) {
                return new JsonResponse([
                    'success' => false,
                    'response' => 'No messages found',
                ], 404);
            }

            return new JsonResponse([
                'success' => true,
                'response' => 'Found ' . $messages->count() . ' messages',
                'data' => [
                    'messages' => $messages->map(fn($row) => [
                        'id' => $row->id,
                        'full_name' => $row->full_name,
                        'subject' => $row->subject,
                        'message' => $row->message,
                        'status' => $row->status,
                        'date' => $row->date ? $row->date->format('Y-m-d') : null,
                        'created_at' => $row->created_at->toDateTimeString(),
                        'updated_at' => $row->updated_at->toDateTimeString(),
                    ]),
                ]
            ], 200);

        } catch (\Throwable $th) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to fetch messages: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Get messages fullname list
     * Route: GET /students/messages/fullname
     */
    public function student_messages_fullname(): JsonResponse
    {
        try {
            // Get the current student with their attendance full_name records
            $student = Student::with([
                'messagesList' => function ($query) {
                    $query->select('user_id', 'full_name')->distinct();
                }
            ])
                ->studentUserId()
                ->first();

            if (!$student) {
                return new JsonResponse([
                    'success' => false,
                    'response' => 'No records found',
                ], 404);
            }

            // Get unique fullnames from attendance records
            $uniqueFullnames = $student->attendanceList
                ->unique('full_name')
                ->values()
                ->map(fn($row) => [
                    'fullname' => $row->full_name
                ]);

            return new JsonResponse([
                'success' => true,
                'response' => 'Found ' . $uniqueFullnames->count() . ' records',
                'data' => [
                    'students' => $uniqueFullnames
                ]
            ], 200);

        } catch (\Throwable $th) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to fetch student fullnames: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Get overall Student
     * Route: POST /students
     */
    public function student_profile(): JsonResponse
    {
        try {
            $students = Student::with('users')
                ->studentUserId()
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
                'response' => 'Found ' . $students->count() . ' records',
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

        } catch (\Throwable $th) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to fetch your student: ' . $th->getMessage()
            ], 500);
        }
    }
}