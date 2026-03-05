<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\JsonResponse;

class AttendanceController extends Controller
{
    public function attendance(): JsonResponse
    {
        try {
            $attendance = Attendance::with('users')->get();

            if ($attendance->isEmpty()) {
                return new JsonResponse([
                    'success' => false,
                    'response' => 'No records available',
                ], 404);
            }

            return new JsonResponse([
                'success' => true,
                'response' => 'Records found total of ' . $attendance->count() . ' records',
                'data' => [
                    'attendance' => $attendance->map(fn($row) => [
                        'attendance_list' => [
                            'full_name' => $row?->full_name,
                            'time_in' => $row?->time_in,
                            'kiosk_terminal_in' => $row?->kiosk_terminal_in,
                            'time_out' => $row?->time_out,
                            'kiosk_terminal_out' => $row?->kiosk_terminal_out,
                            'date' => $row?->date,
                            'status' => $row?->status
                        ],
                        'user_account' => [
                            'username' => $row?->users?->username,
                            'email' => $row?->users?->email
                        ],
                    ]),
                ]
            ], 200);

        } catch (\Throwable $th) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to fetch attendance records: ' . $th->getMessage()
            ], 500);
        }
    }
}
