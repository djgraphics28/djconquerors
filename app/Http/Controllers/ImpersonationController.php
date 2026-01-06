<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class ImpersonationController extends Controller
{
    /**
     * Log in as the given user using a signed temporary URL.
     * Note: the route is protected by the 'signed' middleware and should be generated
     * only by authorized admins. The link is temporary.
     */
    public function loginAs(Request $request, User $user)
    {
        // signed middleware already validated the signature and expiration
        // Log in as the target user and regenerate session to avoid fixation
        Auth::loginUsingId($user->id);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    /**
     * Stop impersonation: log out current user and redirect to login or users page.
     */
    public function stop(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
