<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class ApiUserController extends Controller
{
    /**
     * GET /api/user
     * Return authenticated user with computed attributes.
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user()->load(['team', 'managerLevel']);

        return response()->json([
            'status'  => true,
            'message' => 'Profile retrieved',
            'data'    => new UserResource($user),
        ]);
    }

    /**
     * PUT /api/user
     * Update profile fields.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name'               => 'sometimes|string|max:255',
            'phone_number'       => 'sometimes|string|max:30',
            'birth_date'         => 'sometimes|date|before:-18 years',
            'gender'             => 'sometimes|nullable|in:male,female',
            'occupation'         => 'sometimes|nullable|string|max:255',
            'primary_language'   => 'sometimes|nullable|string|max:10',
            'secondary_language' => 'sometimes|nullable|string|max:10',
            'bonchat_id'         => 'sometimes|nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation Error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user->update($validator->validated());

        return response()->json([
            'status'  => true,
            'message' => 'Profile updated',
            'data'    => new UserResource($user->fresh(['team', 'managerLevel'])),
        ]);
    }

    /**
     * POST /api/user/avatar
     * Upload / replace avatar image.
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation Error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $user->clearMediaCollection('avatar');
        $user->addMediaFromRequest('avatar')->toMediaCollection('avatar');

        return response()->json([
            'status'     => true,
            'message'    => 'Avatar updated',
            'avatar_url' => $user->getFirstMediaUrl('avatar'),
        ]);
    }

    /**
     * PUT /api/user/password
     * Change password.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password'      => 'required|string',
            'password'              => ['required', 'confirmed', Password::min(8)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation Error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json([
            'status'  => true,
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * DELETE /api/user
     * Soft-delete account and revoke all tokens.
     */
    public function destroy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation Error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (! Hash::check($request->password, $user->password)) {
            return response()->json([
                'status'  => false,
                'message' => 'Password is incorrect.',
            ], 422);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Account deleted',
        ]);
    }

    /**
     * GET /api/user/notifications
     * Return all notifications for the authenticated user.
     */
    public function notifications(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate(20);

        return response()->json([
            'status' => true,
            'data'   => $notifications,
        ]);
    }

    /**
     * POST /api/user/notifications/read-all
     * Mark all notifications as read.
     */
    public function markAllNotificationsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'status'  => true,
            'message' => 'All notifications marked as read',
        ]);
    }

    /**
     * POST /api/user/notifications/{id}/read
     * Mark single notification as read.
     */
    public function markNotificationRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json([
            'status'  => true,
            'message' => 'Notification marked as read',
        ]);
    }
}
