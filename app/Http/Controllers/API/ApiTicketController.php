<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\TicketResource;
use App\Http\Resources\API\TicketCommentResource;
use App\Models\Ticket;
use App\Models\TicketComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiTicketController extends Controller
{
    /**
     * GET /api/tickets
     * List authenticated user's tickets.
     * ?status=open|in_progress|resolved|closed
     */
    public function index(Request $request): JsonResponse
    {
        $query = $request->user()->tickets()->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $tickets = $query->paginate(15);

        return response()->json([
            'status' => true,
            'data'   => TicketResource::collection($tickets)->response()->getData(true),
        ]);
    }

    /**
     * POST /api/tickets
     * Create a new support ticket.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subject'     => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'category'    => 'required|string|max:100',
            'priority'    => 'nullable|in:low,medium,high',
            'attachment'  => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation Error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $ticket = $request->user()->tickets()->create([
            'subject'     => $request->subject,
            'description' => $request->description,
            'category'    => $request->category,
            'priority'    => $request->input('priority', 'medium'),
            'status'      => 'open',
        ]);

        if ($request->hasFile('attachment')) {
            $ticket->addMediaFromRequest('attachment')
                   ->toMediaCollection('attachments');
        }

        return response()->json([
            'status'  => true,
            'message' => 'Ticket submitted',
            'data'    => new TicketResource($ticket->load('comments')),
        ], 201);
    }

    /**
     * GET /api/tickets/{ticket}
     * Show ticket with public comment thread.
     */
    public function show(Request $request, Ticket $ticket): JsonResponse
    {
        if ($ticket->user_id !== $request->user()->id) {
            return response()->json(['status' => false, 'message' => 'Not found.'], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => new TicketResource($ticket->load('publicComments.user')),
        ]);
    }

    /**
     * POST /api/tickets/{ticket}/comments
     * Add a reply to a ticket.
     */
    public function addComment(Request $request, Ticket $ticket): JsonResponse
    {
        if ($ticket->user_id !== $request->user()->id) {
            return response()->json(['status' => false, 'message' => 'Not found.'], 404);
        }

        if (in_array($ticket->status, ['resolved', 'closed'])) {
            return response()->json([
                'status'  => false,
                'message' => 'Cannot reply to a resolved or closed ticket.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'body' => 'required|string|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation Error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $comment = $ticket->comments()->create([
            'user_id'     => $request->user()->id,
            'body'        => $request->body,
            'is_internal' => false,
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Reply added',
            'data'    => new TicketCommentResource($comment->load('user')),
        ], 201);
    }
}
