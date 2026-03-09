<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    /**
     * Get overall Attendance
     * Route: GET /attendance
     */
    public function attendance(): JsonResponse
    {
        try {
            // Load attendance with both user and student relationships
            $attendance = Attendance::with(['users', 'student'])->get();

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
                    'attendance' => $attendance?->map(fn($row) => [
                        'full_name' => $row?->full_name ?? $row?->student?->fullname,
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
                'error' => 'Failed to fetch attendance records: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Filter attendance by overall (date range + fullname)
     * Route: POST /attendance/filter/overall
     */
    public function attendance_filter(Request $request): JsonResponse
    {
        try {
            $fullname = $request->input('full_name');
            $dateStart = $request->input('date_start');
            $dateEnd = $request->input('date_end');

            // Validate date range - both must be provided together
            if (($dateStart && !$dateEnd) || (!$dateStart && $dateEnd)) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Both date_start and date_end must be provided together',
                ], 400);
            }

            $query = Attendance::with(['student'])->attendanceUserId();

            // Apply date range filter if provided
            if ($dateStart && $dateEnd) {
                // Convert mm/dd/YYYY to Y-m-d format
                $startDate = \Carbon\Carbon::createFromFormat('m/d/Y', $dateStart)->format('Y-m-d');
                $endDate = \Carbon\Carbon::createFromFormat('m/d/Y', $dateEnd)->addDay()->format('Y-m-d');

                $query->filterByDateRange($startDate, $endDate);
                $dateDisplay = $dateStart . ' to ' . $dateEnd;
            } else {
                // Default to last 30 days if no date range provided
                $startDate = now()->subDays(30)->format('Y-m-d');
                $endDate = now()->format('Y-m-d');

                $query->filterByDateRange($startDate, $endDate);
                $dateDisplay = now()->subDays(30)->format('m/d/Y') . ' to ' . now()->format('m/d/Y');
            }

            // Apply fullname filter if provided (and not empty)
            if ($fullname && trim($fullname) !== '') {
                $query->filterByFullname(trim($fullname));
                $fullnameApplied = $fullname;
            } else {
                $fullnameApplied = null;
            }

            // Always sort by date descending (newest first)
            $attendance = $query->sortByDate('desc')->get();

            // Check if records exist
            if ($attendance->isEmpty()) {
                $message = 'No attendance records found';

                if ($fullnameApplied && ($dateStart && $dateEnd)) {
                    $message = 'No attendance records found for "' . $fullnameApplied . '" between ' . $dateDisplay;
                } elseif ($fullnameApplied) {
                    $message = 'No attendance records found for "' . $fullnameApplied . '"';
                } elseif ($dateStart && $dateEnd) {
                    $message = 'No attendance records found between ' . $dateDisplay;
                }

                return new JsonResponse([
                    'success' => false,
                    'response' => $message,
                ], 404);
            }

            // Prepare response message
            $message = 'Found ' . $attendance->count() . ' attendance records';

            if ($fullnameApplied && ($dateStart && $dateEnd)) {
                $message = 'Found ' . $attendance->count() . ' attendance records for "' . $fullnameApplied . '" between ' . $dateDisplay;
            } elseif ($fullnameApplied) {
                $message = 'Found ' . $attendance->count() . ' attendance records for "' . $fullnameApplied . '"';
            } elseif ($dateStart && $dateEnd) {
                $message = 'Found ' . $attendance->count() . ' attendance records between ' . $dateDisplay;
            }

            return new JsonResponse([
                'success' => true,
                'response' => $message,
                'data' => [
                    'attendance' => $attendance->map(fn($row) => [
                        'full_name' => $row?->full_name,
                        'time_in' => $row?->time_in,
                        'kiosk_terminal_in' => $row?->kiosk_terminal_in,
                        'time_out' => $row?->time_out,
                        'kiosk_terminal_out' => $row?->kiosk_terminal_out,
                        'date' => $row?->date,
                        'status' => $row?->status
                    ]),
                    'filters_applied' => [
                        'date_range' => ($dateStart && $dateEnd) ? $dateDisplay : 'Last 30 days (default)',
                        'fullname' => $fullnameApplied ?? 'All records'
                    ]
                ]
            ], 200);

        } catch (\Throwable $th) {
            if ($th instanceof \Carbon\Exceptions\InvalidFormatException) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Invalid date format. Please use mm/dd/YYYY format',
                ], 400);
            }

            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to filter attendance: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Filter attendance by fullname
     * Route: POST /attendance/filter/fullname
     */
    public function attendance_filter_by_fullname(Request $request): JsonResponse
    {
        try {
            $fullname = $request->input('fullname');

            // Default display
            $query = Attendance::with(['student'])->attendanceUserId();

            // Apply fullname filter only if provided
            if ($fullname) {
                $query->filterByFullname($fullname);
            }

            // Always sort by name
            $attendance = $query->sortByName('desc')->get();

            // Check if records exist
            if ($attendance->isEmpty()) {
                $message = $fullname
                    ? 'Attendance records not found for "' . $fullname . '"'
                    : 'No attendance records found';

                return new JsonResponse([
                    'success' => false,
                    'response' => $message,
                ], 404);
            }

            // Prepare response message
            $message = $fullname
                ? 'Found ' . $attendance->count() . ' attendance records for "' . $fullname . '"'
                : 'Found ' . $attendance->count() . ' attendance records';

            return new JsonResponse([
                'success' => true,
                'response' => $message,
                'data' => [
                    'attendance' => $attendance->map(fn($row) => [
                        'full_name' => $row?->full_name,
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
                'error' => 'Failed to filter attendance: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Filter attendance by Date Range
     * Route: POST /attendance/filter/date
     */
    public function attendance_filter_by_date_range(Request $request): JsonResponse
    {
        try {
            $dateStart = $request->input('date_start');
            $dateEnd = $request->input('date_end');

            // Set default date range (last 30 days) if not provided
            if (!$dateStart || !$dateEnd) {
                $endDate = now()->format('Y-m-d');
                $startDate = now()->subDays(30)->format('Y-m-d');

                $dateDisplay = now()->subDays(30)->format('m/d/Y') . ' to ' . now()->format('m/d/Y');
            } else {
                // Convert mm/dd/YYYY to Y-m-d format
                $startDate = \Carbon\Carbon::createFromFormat('m/d/Y', $dateStart)->format('Y-m-d');
                $endDate = \Carbon\Carbon::createFromFormat('m/d/Y', $dateEnd)->addDay()->format('Y-m-d');
                $dateDisplay = $dateStart . ' to ' . $dateEnd;
            }

            // Get attendance records filtered by date range
            $attendance = Attendance::with(['student'])
                ->attendanceUserId()
                ->filterByDateRange($startDate, $endDate)
                ->sortByDate('desc')
                ->get();

            if ($attendance->isEmpty()) {
                return new JsonResponse([
                    'success' => false,
                    'response' => 'No attendance records found between ' . $dateDisplay,
                ], 404);
            }

            return new JsonResponse([
                'success' => true,
                'response' => 'Found ' . $attendance->count() . ' attendance records between ' . $dateDisplay,
                'data' => [
                    'attendance' => $attendance->map(fn($row) => [
                        'full_name' => $row?->full_name,
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
            if ($th instanceof \Carbon\Exceptions\InvalidFormatException) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Invalid date format. Please use mm/dd/YYYY format',
                ], 400);
            }

            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to filter attendance by date range: ' . $th->getMessage()
            ], 500);
        }
    }
}