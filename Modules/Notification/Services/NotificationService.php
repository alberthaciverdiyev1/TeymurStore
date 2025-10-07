<?php

namespace Modules\Notification\Services;

use Illuminate\Support\Facades\DB;
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
            'body' => $data['body'] ?? '',
            'user_id' => $data['user_id'] ?? null,
            'all' => $data['all'] ?? false,
            'data' => $data['data'] ?? null,
        ]);
    }

    public function addMultiple(array $notificationData, array $userIds, bool $all = false): bool
    {
        try {
            DB::beginTransaction();

            if ($all) {
                $this->model->create([
                    'title' => $notificationData['title'] ?? '',
                    'body' => $notificationData['body'] ?? '',
                    'user_id' => null,
                    'all' => true,
                    'data' => $notificationData['data'] ?? null,
                ]);
            } else {
                $dataToInsert = array_map(function ($userId) use ($notificationData) {
                    return [
                        'title' => $notificationData['title'] ?? '',
                        'body' => $notificationData['body'] ?? '',
                        'user_id' => $userId,
                        'all' => false,
                        'data' => $notificationData['data'] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }, $userIds);

                $this->model->insert($dataToInsert);
            }

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollback();
            return false;
        }
    }

    /**
     * Notification list
     */
    public function listAdmin($request)
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

        return responseHelper('Notifications retrieved successfully.', 200, NotificationResource::collection($notifications));
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

    public function list($request)
    {
        $filters = $request->all();
        $query = $this->model->query();
        $query->where('user_id', auth()->id())->orWhere('all', true);


        $notifications = $query->orderBy('created_at', 'desc')->paginate(20);

        return responseHelper('Notifications retrieved successfully.', 200, NotificationResource::collection($notifications));
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
