<?php

namespace App\Http\Controllers;

use App\Models\ReplyTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReplyTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->can('reply-template.access')) {
                abort(403, 'Unauthorized access to reply templates.');
            }
            return $next($request);
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $templates = ReplyTemplate::ordered()->get();
        return view('reply-template.index', compact('templates'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('reply-template.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Get the highest order value and add 1
        $maxOrder = ReplyTemplate::max('order') ?? 0;
        $validated['order'] = $maxOrder + 1;

        ReplyTemplate::create($validated);

        return redirect()->route('reply-template.index')
            ->with('success', 'Reply template created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ReplyTemplate $replyTemplate)
    {
        return view('reply-template.show', compact('replyTemplate'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ReplyTemplate $replyTemplate)
    {
        return view('reply-template.edit', compact('replyTemplate'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ReplyTemplate $replyTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $replyTemplate->update($validated);

        return redirect()->route('reply-template.index')
            ->with('success', 'Reply template updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReplyTemplate $replyTemplate)
    {
        $replyTemplate->delete();

        return redirect()->route('reply-template.index')
            ->with('success', 'Reply template deleted successfully.');
    }

    /**
     * Update the order of templates.
     */
    public function updateOrder(Request $request)
    {
        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*' => 'required|integer|exists:reply_templates,id',
        ]);

        foreach ($validated['orders'] as $order => $id) {
            ReplyTemplate::where('id', $id)->update(['order' => $order]);
        }

        return response()->json(['success' => true, 'message' => 'Order updated successfully.']);
    }
}
