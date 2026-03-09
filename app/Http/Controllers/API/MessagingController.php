<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MessagingController extends Controller
{
    /**
     * Get overall Messages
     * Route: GET /messages
     */
    public function messages(): JsonResponse
    {
        try {
            /**
             * Get overall Message
             * Route: POST /messages
             */
            $messages = Message::sortByDate('desc')->limit(5)->get();

            // Check if messages exist
            if ($messages->isEmpty()) {
                return new JsonResponse([
                    'success' => false,
                    'response' => 'No records available',
                ], 404);
            }

            // Return messages with proper mapping
            return new JsonResponse([
                'success' => true,
                'response' => 'Found ' . $messages?->count() . ' records',
                'data' => [
                    'messages' => $messages?->map(fn($row) => [
                        'id' => $row?->id,
                        'user_id' => $row?->user_id,
                        'full_name' => $row?->full_name,
                        'subject' => $row?->subject,
                        'message' => $row?->message,
                        'status' => $row?->status,
                        'date' => $row?->date,
                        'created_at' => $row?->created_at->toDateTimeString(),
                        'updated_at' => $row?->updated_at->toDateTimeString(),
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
     * Filter messages by overall (date range + fullname)
     * Route: POST /messages/filter/overall
     */
    public function messages_filter(Request $request): JsonResponse
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

            // Build query with base filters
            $query = Message::with(['student'])
                ->messagesUserId(); // Always filter by current user

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
            $messages = $query->sortByDate('desc')->get();

            // Check if records exist
            if ($messages->isEmpty()) {
                $message = 'No messages found';

                if ($fullnameApplied && ($dateStart && $dateEnd)) {
                    $message = 'No messages found for "' . $fullnameApplied . '" between ' . $dateDisplay;
                } elseif ($fullnameApplied) {
                    $message = 'No messages found for "' . $fullnameApplied . '"';
                } elseif ($dateStart && $dateEnd) {
                    $message = 'No messages found between ' . $dateDisplay;
                }

                return new JsonResponse([
                    'success' => false,
                    'response' => $message,
                ], 404);
            }

            // Prepare response message
            $message = 'Found ' . $messages->count() . ' messages';

            if ($fullnameApplied && ($dateStart && $dateEnd)) {
                $message = 'Found ' . $messages->count() . ' messages for "' . $fullnameApplied . '" between ' . $dateDisplay;
            } elseif ($fullnameApplied) {
                $message = 'Found ' . $messages->count() . ' messages for "' . $fullnameApplied . '"';
            } elseif ($dateStart && $dateEnd) {
                $message = 'Found ' . $messages->count() . ' messages between ' . $dateDisplay;
            }

            return new JsonResponse([
                'success' => true,
                'response' => $message,
                'data' => [
                    'messages' => $messages->map(fn($row) => [
                        'id' => $row?->id,
                        'user_id' => $row?->user_id,
                        'full_name' => $row?->full_name,
                        'subject' => $row?->subject,
                        'message' => $row?->message,
                        'status' => $row?->status,
                        'date' => $row?->date ? $row?->date->format('Y-m-d') : null,
                        'created_at' => $row?->created_at->toDateTimeString(),
                        'updated_at' => $row?->updated_at->toDateTimeString(),
                    ]),
                    'filters_applied' => [
                        'date_range' => ($dateStart && $dateEnd) ? $dateDisplay : 'Last 30 days (default)',
                        'fullname' => $fullnameApplied ?? 'All messages'
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
                'error' => 'Failed to filter messages: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Filter messages by fullname
     * Route: POST /message/filter/fullname
     */
    public function messages_filter_by_fullname(Request $request): JsonResponse
    {
        try {
            $fullname = $request->input('fullname');

            // Default display
            $query = Message::with(['student'])->messagesUserId();

            // Apply fullname filter only if provided
            if ($fullname) {
                $query->filterByFullname($fullname);
            }

            // Always sort by name (assuming you have a sortByName scope)
            $messages = $query->sortByDate('desc')->get();

            // Check if records exist
            if ($messages->isEmpty()) {
                $message = $fullname
                    ? 'Message records not found for "' . $fullname . '"'
                    : 'No messages found';

                return new JsonResponse([
                    'success' => false,
                    'response' => $message,
                ], 404);
            }

            // Prepare response message
            $message = $fullname
                ? 'Found ' . $messages->count() . ' messages for "' . $fullname . '"'
                : 'Found ' . $messages->count() . ' messages';

            return new JsonResponse([
                'success' => true,
                'response' => $message,
                'data' => [
                    'messages' => $messages->map(fn($row) => [
                        'id' => $row?->id,
                        'user_id' => $row?->user_id,
                        'full_name' => $row?->full_name,
                        'subject' => $row?->subject,
                        'message' => $row?->message,
                        'status' => $row?->status,
                        'date' => $row?->date ? $row?->date->format('Y-m-d') : null,
                        'created_at' => $row?->created_at->toDateTimeString(),
                        'updated_at' => $row?->updated_at->toDateTimeString(),
                    ]),
                ]
            ], 200);

        } catch (\Throwable $th) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Failed to filter messages: ' . $th->getMessage()
            ], 500);
        }
    }

    /**
     * Filter messages by Date Range
     * Route: POST /messages/filter/date-range
     */
    public function messages_filter_by_date_range(Request $request): JsonResponse
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

            // Get messages filtered by date range
            $messages = Message::with(['student'])
                ->messagesUserId()
                ->filterByDateRange($startDate, $endDate)
                ->sortByDate('desc')
                ->get();

            if ($messages->isEmpty()) {
                return new JsonResponse([
                    'success' => false,
                    'response' => 'No messages found between ' . $dateDisplay,
                ], 404);
            }

            return new JsonResponse([
                'success' => true,
                'response' => 'Found ' . $messages->count() . ' messages between ' . $dateDisplay,
                'data' => [
                    'messages' => $messages->map(fn($row) => [
                        'id' => $row?->id,
                        'user_id' => $row?->user_id,
                        'full_name' => $row?->full_name,
                        'subject' => $row?->subject,
                        'message' => $row?->message,
                        'status' => $row?->status,
                        'date' => $row?->date ? $row?->date->format('Y-m-d') : null,
                        'created_at' => $row?->created_at->toDateTimeString(),
                        'updated_at' => $row?->updated_at->toDateTimeString(),
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
                'error' => 'Failed to filter messages by date range: ' . $th->getMessage()
            ], 500);
        }
    }
}