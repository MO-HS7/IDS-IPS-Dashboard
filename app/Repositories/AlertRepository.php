<?php

namespace App\Repositories;

use App\Models\Alert;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AlertRepository
{
    protected Alert $model;

    public function __construct(Alert $model)
    {
        $this->model = $model;
    }

    /**
     * Get all alerts with relationships
     */
    public function getAllWithRelations(array $relations = ['networkLog.user', 'mlModel']): Collection
    {
        return $this->model->with($relations)->get();
    }

    /**
     * Get paginated alerts with relationships
     */
    public function getPaginatedWithRelations(int $perPage = 10, array $relations = ['networkLog.user', 'mlModel']): LengthAwarePaginator
    {
        return $this->model->with($relations)
            ->latest('detected_at')
            ->paginate($perPage);
    }

    /**
     * Get alerts by severity
     */
    public function getBySeverity(string $severity, array $relations = []): Collection
    {
        $query = $this->model->where('severity', $severity);
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        return $query->get();
    }

    /**
     * Get recent alerts
     */
    public function getRecent(int $limit = 5, array $relations = ['networkLog.user', 'mlModel']): Collection
    {
        return $this->model->with($relations)
            ->orderBy('detected_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get alerts by date range
     */
    public function getByDateRange(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate, array $relations = []): Collection
    {
        $query = $this->model->whereBetween('detected_at', [$startDate, $endDate]);
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        return $query->get();
    }

    /**
     * Get alert statistics
     */
    public function getStatistics(): array
    {
        return $this->model->selectRaw('
            count(*) as total_alerts,
            sum(case when severity = ? then 1 else 0 end) as critical_alerts,
            sum(case when severity = ? then 1 else 0 end) as high_alerts,
            sum(case when severity = ? then 1 else 0 end) as medium_alerts,
            sum(case when severity = ? then 1 else 0 end) as low_alerts
        ', ['critical', 'high', 'medium', 'low'])->first()->toArray();
    }

    /**
     * Get attack type distribution
     */
    public function getAttackTypeDistribution(\Carbon\Carbon $since = null, int $limit = 10): Collection
    {
        $query = $this->model->select('attack_type')
            ->selectRaw('count(*) as count')
            ->groupBy('attack_type')
            ->orderBy('count', 'desc');
            
        if ($since) {
            $query->where('detected_at', '>=', $since);
        }
        
        return $query->limit(max(1, $limit))->get();
    }

    /**
     * Get severity distribution
     */
    public function getSeverityDistribution(): Collection
    {
        return $this->model->select('severity')
            ->selectRaw('count(*) as count')
            ->groupBy('severity')
            ->get();
    }

    /**
     * Create a new alert
     */
    public function create(array $data): Alert
    {
        return $this->model->create($data);
    }

    /**
     * Update an alert
     */
    public function update(Alert $alert, array $data): bool
    {
        return $alert->update($data);
    }

    /**
     * Delete an alert
     */
    public function delete(Alert $alert): bool
    {
        return $alert->delete();
    }

    /**
     * Find alert by ID
     */
    public function findById(int $id, array $relations = []): ?Alert
    {
        $query = $this->model->where('id', $id);
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        return $query->first();
    }
}
