<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\AppointmentResource;
use App\Http\Resources\API\UserResource;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiAppointmentController extends Controller
{
    /**
     * GET /api/appointments
     * List appointments for the authenticated user.
     * ?filter=upcoming|past|all (default: all)
     */
    public function index(Request $request): JsonResponse
    {
        $filter = $request->query('filter', 'all');
        $query  = $request->user()->appointments()->with(['host']);

        if ($filter === 'upcoming') {
            $query->where('start_time', '>=', now())
                  ->whereIn('status', ['pending', 'confirmed']);
        } elseif ($filter === 'past') {
            $query->where('start_time', '<', now())
                  ->orWhereIn('status', ['cancelled', 'completed']);
        }

        $appointments = $query->latest('start_time')->paginate(15);

        return response()->json([
            'status' => true,
            'data'   => AppointmentResource::collection($appointments)->response()->getData(true),
        ]);
    }

    /**
     * POST /api/appointments
     * Book a new appointment.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'host_user_id'    => 'required|exists:users,id',
            'start_time'      => 'required|date|after:now',
            'end_time'        => 'required|date|after:start_time',
            'notes'           => 'nullable|string|max:1000',
            'venue'           => 'nullable|string|max:255',
            'is_sure_investor' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation Error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        // Check time slot availability
        if (! Appointment::isTimeAvailable($request->start_time, $request->end_time)) {
            return response()->json([
                'status'  => false,
                'message' => 'The selected time slot is not available. Please choose a different time.',
            ], 422);
        }

        $appointment = Appointment::create([
            'user_id'          => $request->user()->id,
            'host_user_id'     => $request->host_user_id,
            'start_time'       => $request->start_time,
            'end_time'         => $request->end_time,
            'notes'            => $request->notes,
            'venue'            => $request->venue,
            'is_sure_investor' => $request->boolean('is_sure_investor', false),
            'status'           => 'pending',
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Appointment booked successfully',
            'data'    => new AppointmentResource($appointment->load('host')),
        ], 201);
    }

    /**
     * GET /api/appointments/{appointment}
     * Show a single appointment owned by the authenticated user.
     */
    public function show(Request $request, Appointment $appointment): JsonResponse
    {
        if ($appointment->user_id !== $request->user()->id) {
            return response()->json(['status' => false, 'message' => 'Not found.'], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => new AppointmentResource($appointment->load('host')),
        ]);
    }

    /**
     * PATCH /api/appointments/{appointment}/cancel
     * Cancel an appointment owned by the authenticated user.
     */
    public function cancel(Request $request, Appointment $appointment): JsonResponse
    {
        if ($appointment->user_id !== $request->user()->id) {
            return response()->json(['status' => false, 'message' => 'Not found.'], 404);
        }

        if (! in_array($appointment->status, ['pending', 'confirmed'])) {
            return response()->json([
                'status'  => false,
                'message' => 'Only pending or confirmed appointments can be cancelled.',
            ], 422);
        }

        if ($appointment->start_time->isPast()) {
            return response()->json([
                'status'  => false,
                'message' => 'Cannot cancel a past appointment.',
            ], 422);
        }

        $appointment->update(['status' => 'cancelled']);

        return response()->json([
            'status'  => true,
            'message' => 'Appointment cancelled',
            'data'    => new AppointmentResource($appointment->refresh()->load('host')),
        ]);
    }

    /**
     * GET /api/appointments/hosts
     * List users who can be booked as hosts (currently active users that are assistants).
     */
    public function hosts(Request $request): JsonResponse
    {
        $hosts = User::where('is_active', true)
            ->whereHas('assistedUsers')
            ->select('id', 'name', 'email')
            ->get();

        return response()->json([
            'status' => true,
            'data'   => UserResource::collection($hosts),
        ]);
    }
}
