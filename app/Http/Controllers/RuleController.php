<?php

namespace App\Http\Controllers;

use App\Models\Rule;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class RuleController extends Controller
{
    /**
     * Display a listing of rules.
     */
    public function index(Request $request)
    {
        $query = Rule::with('creator:id,name');

        // Search filter
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('signature', 'like', "%{$search}%")
                  ->orWhere('sid', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        // Severity filter
        if ($request->has('severity') && $request->severity) {
            $query->where('severity', $request->severity);
        }

        // Status filter
        if ($request->has('status')) {
            $query->where('enabled', $request->status === 'enabled');
        }

        $rules = $query->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn($rule) => [
                'id' => $rule->id,
                'name' => $rule->name,
                'signature' => $rule->signature,
                'category' => $rule->category,
                'severity' => $rule->severity,
                'description' => $rule->description,
                'enabled' => $rule->enabled,
                'sid' => $rule->sid,
                'rev' => $rule->rev,
                'protocol' => $rule->protocol,
                'action' => $rule->action,
                'alert_count' => $rule->alert_count,
                'last_triggered_at' => $rule->last_triggered_at?->format('Y-m-d H:i:s'),
                'created_by' => $rule->creator ? $rule->creator->name : 'System',
                'created_at' => $rule->created_at->format('Y-m-d H:i:s'),
            ]);

        $statistics = [
            'total_rules' => Rule::count(),
            'enabled_rules' => Rule::where('enabled', true)->count(),
            'disabled_rules' => Rule::where('enabled', false)->count(),
            'critical_rules' => Rule::where('severity', 'critical')->count(),
            'total_triggers' => Rule::sum('alert_count'),
        ];

        return Inertia::render('Rules/Index', [
            'rules' => $rules,
            'statistics' => $statistics,
            'filters' => [
                'search' => $request->search,
                'category' => $request->category,
                'severity' => $request->severity,
                'status' => $request->status,
            ]
        ]);
    }

    /**
     * Show the form for creating a new rule.
     */
    public function create()
    {
        return Inertia::render('Rules/Create');
    }

    /**
     * Store a newly created rule.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'signature' => ['required', 'string'],
            'category' => ['required', 'in:malware,exploit,dos,scan,policy,trojan,web-attack,misc,custom'],
            'severity' => ['required', 'in:critical,high,medium,low'],
            'description' => ['nullable', 'string'],
            'enabled' => ['boolean'],
            'sid' => ['required', 'integer', 'unique:rules,sid'],
            'rev' => ['integer', 'min:1'],
            'protocol' => ['nullable', 'string', 'in:tcp,udp,icmp,ip'],
            'source_ip' => ['nullable', 'string'],
            'source_port' => ['nullable', 'string'],
            'destination_ip' => ['nullable', 'string'],
            'destination_port' => ['nullable', 'string'],
            'action' => ['required', 'in:alert,log,pass,drop,reject'],
        ]);

        $validated['created_by'] = auth()->id();

        $rule = Rule::create($validated);

        return redirect()->route('rules.index')
            ->with('success', 'Rule created successfully!');
    }

    /**
     * Display the specified rule.
     */
    public function show(Rule $rule)
    {
        $rule->load('creator:id,name');

        return Inertia::render('Rules/Show', [
            'rule' => [
                'id' => $rule->id,
                'name' => $rule->name,
                'signature' => $rule->signature,
                'category' => $rule->category,
                'severity' => $rule->severity,
                'description' => $rule->description,
                'enabled' => $rule->enabled,
                'sid' => $rule->sid,
                'rev' => $rule->rev,
                'protocol' => $rule->protocol,
                'source_ip' => $rule->source_ip,
                'source_port' => $rule->source_port,
                'destination_ip' => $rule->destination_ip,
                'destination_port' => $rule->destination_port,
                'action' => $rule->action,
                'metadata' => $rule->metadata,
                'alert_count' => $rule->alert_count,
                'last_triggered_at' => $rule->last_triggered_at?->format('Y-m-d H:i:s'),
                'created_by' => $rule->creator ? $rule->creator->name : 'System',
                'created_at' => $rule->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $rule->updated_at->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified rule.
     */
    public function edit(Rule $rule)
    {
        return Inertia::render('Rules/Edit', [
            'rule' => [
                'id' => $rule->id,
                'name' => $rule->name,
                'signature' => $rule->signature,
                'category' => $rule->category,
                'severity' => $rule->severity,
                'description' => $rule->description,
                'enabled' => $rule->enabled,
                'sid' => $rule->sid,
                'rev' => $rule->rev,
                'protocol' => $rule->protocol,
                'source_ip' => $rule->source_ip,
                'source_port' => $rule->source_port,
                'destination_ip' => $rule->destination_ip,
                'destination_port' => $rule->destination_port,
                'action' => $rule->action,
            ]
        ]);
    }

    /**
     * Update the specified rule.
     */
    public function update(Request $request, Rule $rule)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'signature' => ['required', 'string'],
            'category' => ['required', 'in:malware,exploit,dos,scan,policy,trojan,web-attack,misc,custom'],
            'severity' => ['required', 'in:critical,high,medium,low'],
            'description' => ['nullable', 'string'],
            'enabled' => ['boolean'],
            'sid' => ['required', 'integer', 'unique:rules,sid,' . $rule->id],
            'rev' => ['integer', 'min:1'],
            'protocol' => ['nullable', 'string', 'in:tcp,udp,icmp,ip'],
            'source_ip' => ['nullable', 'string'],
            'source_port' => ['nullable', 'string'],
            'destination_ip' => ['nullable', 'string'],
            'destination_port' => ['nullable', 'string'],
            'action' => ['required', 'in:alert,log,pass,drop,reject'],
        ]);

        $rule->update($validated);

        return redirect()->route('rules.index')
            ->with('success', 'Rule updated successfully!');
    }

    /**
     * Remove the specified rule.
     */
    public function destroy(Rule $rule)
    {
        $rule->delete();

        return redirect()->route('rules.index')
            ->with('success', 'Rule deleted successfully!');
    }

    /**
     * Toggle rule enabled status.
     */
    public function toggle(Rule $rule)
    {
        $rule->update(['enabled' => !$rule->enabled]);

        return back()->with('success', 'Rule status updated successfully!');
    }

    /**
     * Import rules from a file.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:rules,txt', 'max:10240'], // Max 10MB
        ]);

        try {
            $file = $request->file('file');
            $content = file_get_contents($file->getRealPath());
            $lines = explode("\n", $content);
            
            $imported = 0;
            $skipped = 0;
            $errors = [];

            foreach ($lines as $lineNum => $line) {
                $line = trim($line);
                
                // Skip empty lines and comments
                if (empty($line) || str_starts_with($line, '#')) {
                    continue;
                }

                try {
                    $parsed = Rule::parseSignature($line);
                    
                    if (!$parsed['sid']) {
                        $errors[] = "Line " . ($lineNum + 1) . ": Missing SID";
                        $skipped++;
                        continue;
                    }

                    // Check if rule already exists
                    if (Rule::where('sid', $parsed['sid'])->exists()) {
                        $skipped++;
                        continue;
                    }

                    Rule::create([
                        'name' => $parsed['msg'] ?? 'Imported Rule ' . $parsed['sid'],
                        'signature' => $line,
                        'category' => 'custom',
                        'severity' => 'medium',
                        'description' => $parsed['msg'],
                        'enabled' => true,
                        'sid' => $parsed['sid'],
                        'rev' => $parsed['rev'] ?? 1,
                        'protocol' => $parsed['protocol'],
                        'source_ip' => $parsed['source_ip'],
                        'source_port' => $parsed['source_port'],
                        'destination_ip' => $parsed['destination_ip'],
                        'destination_port' => $parsed['destination_port'],
                        'action' => $parsed['action'] ?? 'alert',
                        'created_by' => auth()->id(),
                    ]);

                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Line " . ($lineNum + 1) . ": " . $e->getMessage();
                    $skipped++;
                }
            }

            $message = "Import completed: {$imported} rules imported, {$skipped} skipped.";
            if (!empty($errors)) {
                $message .= " Errors: " . implode(', ', array_slice($errors, 0, 3));
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Export rules to a file.
     */
    public function export(Request $request)
    {
        $query = Rule::query();

        // Apply filters if provided
        if ($request->has('enabled')) {
            $query->where('enabled', $request->enabled);
        }

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $rules = $query->get();

        $content = "# Snort Rules Export - " . now()->format('Y-m-d H:i:s') . "\n";
        $content .= "# Total rules: " . $rules->count() . "\n\n";

        foreach ($rules as $rule) {
            if ($rule->description) {
                $content .= "# " . $rule->name . "\n";
                $content .= "# " . $rule->description . "\n";
            }
            $content .= $rule->signature . "\n\n";
        }

        $filename = 'snort_rules_' . now()->format('Y_m_d_His') . '.rules';

        return response()->streamDownload(function() use ($content) {
            echo $content;
        }, $filename, ['Content-Type' => 'text/plain']);
    }
}
