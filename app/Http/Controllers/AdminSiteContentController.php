<?php

namespace App\Http\Controllers;

use App\Models\SiteContent;
use Illuminate\Http\Request;

class AdminSiteContentController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->canManageAllBranches(), 403);

        $contents = SiteContent::query()
            ->orderBy('key')
            ->get()
            ->keyBy('key');

        return view('admin.content.index', compact('contents'));
    }

    public function update(Request $request)
    {
        abort_unless($request->user()->canManageAllBranches(), 403);

        $validated = $request->validate([
            'content' => ['required', 'array'],
            'content.project_intro' => ['required', 'string'],
            'content.usage_instructions' => ['required', 'string'],
            'content.contact_info' => ['required', 'string'],
            'content.supporters' => ['nullable', 'string'],
            'content.social_links' => ['nullable', 'string'],
        ]);

        foreach ($validated['content'] as $key => $value) {
            SiteContent::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return back()->with('success', 'Homepage content updated successfully.');
    }
}
