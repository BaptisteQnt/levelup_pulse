<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function index(Request $request): Response
    {
        $announcements = Announcement::query()
            ->with(['user:id,name,username'])
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Announcement $announcement) => [
                'id'           => $announcement->id,
                'title'        => $announcement->title,
                'content'      => $announcement->content,
                'published_at' => $announcement->published_at?->toIso8601String(),
                'created_at'   => $announcement->created_at?->toIso8601String(),
                'author'       => $announcement->user?->only(['id', 'name', 'username']),
            ]);

        return Inertia::render('admin/Announcements', [
            'announcements' => $announcements,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error'   => $request->session()->get('error'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title'   => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        Announcement::create([
            'user_id'      => $request->user()->id,
            'title'        => $validated['title'],
            'content'      => $validated['content'],
            'published_at' => now(),
        ]);

        return redirect()->route('admin.announcements.index')->with('success', 'Annonce publiée avec succès.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return redirect()->route('admin.announcements.index')->with('success', 'Annonce supprimée.');
    }
}
