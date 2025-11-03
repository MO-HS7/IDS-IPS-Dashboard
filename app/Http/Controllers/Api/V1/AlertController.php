<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Services\AlertService;
use App\Http\Requests\StoreAlertRequest;
use App\Http\Requests\UpdateAlertRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AlertController extends Controller
{
    protected AlertService $alertService;

    public function __construct(AlertService $alertService)
    {
        $this->alertService = $alertService;
    }

    /**
     * Display a listing of alerts.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Alert::class);

        $perPage = $request->get('per_page', 10);
        $severity = $request->get('severity');
        $status = $request->get('status');

        $alerts = Alert::with(['networkLog.user', 'mlModel'])
            ->when($severity, fn($query) => $query->where('severity', $severity))
            ->when($status, fn($query) => $query->where('status', $status))
            ->latest('detected_at')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $alerts->items(),
            'meta' => [
                'current_page' => $alerts->currentPage(),
                'last_page' => $alerts->lastPage(),
                'per_page' => $alerts->perPage(),
                'total' => $alerts->total(),
            ]
        ]);
    }

    /**
     * Store a newly created alert.
     */
    public function store(StoreAlertRequest $request): JsonResponse
    {
        $alert = $this->alertService->createAlert($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Alert created successfully',
            'data' => $alert->load(['networkLog.user', 'mlModel'])
        ], 201);
    }

    /**
     * Display the specified alert.
     */
    public function show(Alert $alert): JsonResponse
    {
        $this->authorize('view', $alert);

        return response()->json([
            'success' => true,
            'data' => $alert->load(['networkLog.user', 'mlModel'])
        ]);
    }

    /**
     * Update the specified alert.
     */
    public function update(UpdateAlertRequest $request, Alert $alert): JsonResponse
    {
        $validated = $request->validated();
        $alert->update(array_merge($validated, [
            'status' => $validated['status'] ?? $alert->status
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Alert updated successfully',
            'data' => $alert->load(['networkLog.user', 'mlModel'])
        ]);
    }

    /**
     * Remove the specified alert.
     */
    public function destroy(Alert $alert): JsonResponse
    {
        $this->authorize('delete', $alert);

        $alert->delete();

        return response()->json([
            'success' => true,
            'message' => 'Alert deleted successfully'
        ]);
    }

    /**
     * Get alert statistics.
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', Alert::class);

        $stats = $this->alertService->getAlertStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
