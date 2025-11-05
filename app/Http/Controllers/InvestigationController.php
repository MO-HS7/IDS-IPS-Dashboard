<?php

namespace App\Http\Controllers;

use App\Models\Investigation;
use App\Models\Alert;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvestigationController extends Controller
{
    public function index(Request $request)
    {
        $query = Investigation::with(['alerts', 'assignedTo:id,name', 'creator:id,name']);

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority') && $request->priority) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $investigations = $query->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn($inv) => [
                'id' => $inv->id,
                'title' => $inv->title,
                'description' => $inv->description,
                'status' => $inv->status,
                'priority' => $inv->priority,
                'alert_count' => $inv->alerts->count(),
                'assigned_to' => $inv->assignedTo ? $inv->assignedTo->name : 'Unassigned',
                'created_by' => $inv->creator->name,
                'created_at' => $inv->created_at->format('Y-m-d H:i:s'),
            ]);

        $statistics = [
            'total' => Investigation::count(),
            'open' => Investigation::where('status', 'open')->count(),
            'in_progress' => Investigation::where('status', 'in_progress')->count(),
            'resolved' => Investigation::where('status', 'resolved')->count(),
        ];

        $analysts = User::where('role', 'Analyst')->orWhere('role', 'Admin')->get(['id', 'name']);

        return Inertia::render('Investigations/Index', [
            'investigations' => $investigations,
            'statistics' => $statistics,
            'analysts' => $analysts,
            'filters' => [
                'status' => $request->status,
                'priority' => $request->priority,
                'search' => $request->search,
            ]
        ]);
    }

    public function create()
    {
        $analysts = User::where('role', 'Analyst')->orWhere('role', 'Admin')->get(['id', 'name']);
        $alerts = Alert::whereDoesntHave('investigations')->latest()->limit(50)->get(['id', 'attack_type', 'severity', 'detected_at']);

        return Inertia::render('Investigations/Create', [
            'analysts' => $analysts,
            'availableAlerts' => $alerts,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', 'in:critical,high,medium,low'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'alert_ids' => ['nullable', 'array'],
            'alert_ids.*' => ['exists:alerts,id'],
        ]);

        $investigation = Investigation::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'priority' => $validated['priority'],
            'assigned_to' => $validated['assigned_to'] ?? null,
            'created_by' => auth()->id(),
            'started_at' => now(),
            'status' => 'open',
        ]);

        if (!empty($validated['alert_ids'])) {
            $investigation->alerts()->attach($validated['alert_ids']);
        }

        $investigation->addTimelineEvent('Investigation created', 'Investigation opened by ' . auth()->user()->name);

        return redirect()->route('investigations.show', $investigation->id)
            ->with('success', 'Investigation created successfully!');
    }

    public function show(Investigation $investigation)
    {
        $investigation->load(['alerts', 'assignedTo:id,name', 'creator:id,name']);

        return Inertia::render('Investigations/Show', [
            'investigation' => [
                'id' => $investigation->id,
                'title' => $investigation->title,
                'description' => $investigation->description,
                'status' => $investigation->status,
                'priority' => $investigation->priority,
                'assigned_to' => $investigation->assignedTo ? ['id' => $investigation->assignedTo->id, 'name' => $investigation->assignedTo->name] : null,
                'created_by' => $investigation->creator->name,
                'started_at' => $investigation->started_at?->format('Y-m-d H:i:s'),
                'resolved_at' => $investigation->resolved_at?->format('Y-m-d H:i:s'),
                'timeline' => $investigation->timeline ?? [],
                'evidence' => $investigation->evidence ?? [],
                'resolution_notes' => $investigation->resolution_notes,
                'created_at' => $investigation->created_at->format('Y-m-d H:i:s'),
                'alerts' => $investigation->alerts->map(fn($alert) => [
                    'id' => $alert->id,
                    'attack_type' => $alert->attack_type,
                    'severity' => $alert->severity,
                    'source_ip' => $alert->source_ip,
                    'detected_at' => $alert->detected_at->format('Y-m-d H:i:s'),
                ]),
            ]
        ]);
    }

    public function edit(Investigation $investigation)
    {
        $analysts = User::where('role', 'Analyst')->orWhere('role', 'Admin')->get(['id', 'name']);
        $investigation->load('assignedTo:id,name');

        return Inertia::render('Investigations/Edit', [
            'investigation' => [
                'id' => $investigation->id,
                'title' => $investigation->title,
                'description' => $investigation->description,
                'status' => $investigation->status,
                'priority' => $investigation->priority,
                'assigned_to' => $investigation->assignedTo ? ['id' => $investigation->assignedTo->id, 'name' => $investigation->assignedTo->name] : null,
                'resolution_notes' => $investigation->resolution_notes,
            ],
            'analysts' => $analysts,
        ]);
    }

    public function update(Request $request, Investigation $investigation)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:open,in_progress,resolved,closed'],
            'priority' => ['required', 'in:critical,high,medium,low'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'resolution_notes' => ['nullable', 'string'],
        ]);

        $oldStatus = $investigation->status;
        $investigation->update($validated);

        if ($oldStatus !== $validated['status']) {
            if ($validated['status'] === 'resolved') {
                $investigation->update(['resolved_at' => now()]);
            }
            $investigation->addTimelineEvent("Status changed to {$validated['status']}", "Updated by " . auth()->user()->name);
        }

        return redirect()->route('investigations.show', $investigation->id)
            ->with('success', 'Investigation updated successfully!');
    }

    public function destroy(Investigation $investigation)
    {
        $investigation->delete();

        return redirect()->route('investigations.index')
            ->with('success', 'Investigation deleted successfully!');
    }

    public function addAlert(Request $request, Investigation $investigation)
    {
        $validated = $request->validate([
            'alert_id' => ['required', 'exists:alerts,id'],
        ]);

        $investigation->alerts()->attach($validated['alert_id']);
        $investigation->addTimelineEvent('Alert linked', 'Alert #' . $validated['alert_id'] . ' linked to investigation');

        return back()->with('success', 'Alert added to investigation!');
    }

    public function removeAlert(Request $request, Investigation $investigation, Alert $alert)
    {
        $investigation->alerts()->detach($alert->id);
        $investigation->addTimelineEvent('Alert unlinked', 'Alert #' . $alert->id . ' removed from investigation');

        return back()->with('success', 'Alert removed from investigation!');
    }
}
