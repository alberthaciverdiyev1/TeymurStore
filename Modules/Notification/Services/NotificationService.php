<?php

namespace Modules\Notification\Services;

use Modules\Notification\Http\Entities\Notification;
use Modules\Notification\Http\Resources\NotificationResource;

class NotificationService
{
    private Notification $model;

    public function __construct(Notification $model)
    {
        $this->model = $model;
    }

    public function add(array $data): Notification
    {
        return $this->model->create([
            'title' => $data['title'] ?? '',
            'description' => $data['body'] ?? '',
            'user_id' => $data['user_id'] ?? null,
        ]);
    }


    public function addMultiple(array $notifications): bool
    {
        $dataToInsert = array_map(function ($data) {
            return [
                'title' => $data['title'] ?? '',
                'body' => $data['body'] ?? '',
                'user_id' => $data['user_id'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $notifications);

        return $this->model->insert($dataToInsert);
    }

    public function list($request)
    {
        $filters = $request->all();

        $query = $this->model->query();

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['title'])) {
            $query->where('title', 'like', '%' . $filters['title'] . '%');
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $notifications = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json([
            'success' => 200,
            'message' => __('Notifications retrieved successfully.'),
            'data' => NotificationResource::collection($notifications),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }
}
