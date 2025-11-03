<?php

namespace App\Repositories;

use App\Models\NetworkLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class NetworkLogRepository
{
    protected NetworkLog $model;

    public function __construct(NetworkLog $model)
    {
        $this->model = $model;
    }

    /**
     * Get all network logs with relationships
     */
    public function getAllWithRelations(array $relations = ['user']): Collection
    {
        return $this->model->with($relations)->get();
    }

    /**
     * Get paginated network logs with relationships
     */
    public function getPaginatedWithRelations(int $perPage = 10, array $relations = ['user']): LengthAwarePaginator
    {
        return $this->model->with($relations)
            ->latest('upload_date')
            ->paginate($perPage);
    }

    /**
     * Get network logs by user
     */
    public function getByUser(int $userId, array $relations = []): Collection
    {
        $query = $this->model->where('user_id', $userId);
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        return $query->get();
    }

    /**
     * Get network logs by status
     */
    public function getByStatus(string $status, array $relations = []): Collection
    {
        $query = $this->model->where('status', $status);
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        return $query->get();
    }

    /**
     * Get network logs by date range
     */
    public function getByDateRange(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate, array $relations = []): Collection
    {
        $query = $this->model->whereBetween('upload_date', [$startDate, $endDate]);
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        return $query->get();
    }

    /**
     * Get network log statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_logs' => $this->model->count(),
            'pending_logs' => $this->model->where('status', 'pending')->count(),
            'processed_logs' => $this->model->where('status', 'processed')->count(),
            'failed_logs' => $this->model->where('status', 'failed')->count(),
        ];
    }

    /**
     * Create a new network log
     */
    public function create(array $data): NetworkLog
    {
        return $this->model->create($data);
    }

    /**
     * Update a network log
     */
    public function update(NetworkLog $networkLog, array $data): bool
    {
        return $networkLog->update($data);
    }

    /**
     * Delete a network log
     */
    public function delete(NetworkLog $networkLog): bool
    {
        return $networkLog->delete();
    }

    /**
     * Find network log by ID
     */
    public function findById(int $id, array $relations = []): ?NetworkLog
    {
        $query = $this->model->where('id', $id);
        
        if (!empty($relations)) {
            $query->with($relations);
        }
        
        return $query->first();
    }

    /**
     * Get logs processed today
     */
    public function getProcessedToday(): int
    {
        return $this->model->whereDate('created_at', now())->count();
    }
}
