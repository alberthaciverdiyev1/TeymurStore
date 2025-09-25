<?php

namespace Modules\Balance\Services;

use Modules\Balance\Http\Entities\Balance;
use App\Enums\BalanceType;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Modules\Balance\Http\Resources\BalanceResource;

class BalanceService
{
    private Balance $model;

    public function __construct(Balance $model)
    {
        $this->model = $model;
    }


    public function deposit($request): JsonResponse
    {
        $validated = $request->validated();
        return handleTransaction(
            fn() => $this->model->create([
                'user_id' => $validated['user_id'],
                'type'    => BalanceType::DEPOSIT->value,
                'amount'  => (float)$validated['amount'],
                'note'    => $validated['note'] ?? null,
            ])->refresh(),
            'Balance deposited successfully.',
            BalanceResource::class
        );
    }

    public function withdraw($request): JsonResponse
    {
        $validated = $request->validated();
        return handleTransaction(
            fn() => $this->model->create([
                'user_id' => $validated['user_id'],
                'type'    => BalanceType::WITHDRAWAL->value,
                'amount'  => $validated['amount'],
                'note'    => $validated['note'] ?? null,
            ])->refresh(),
            'Balance withdrawn successfully.',
            BalanceResource::class
        );
    }


    public function getBalance(int $userId = null): JsonResponse
    {
        $userId = $userId ?? auth()->id();

        $totalBalance = $this->model
            ->where('user_id', $userId)
            ->sum(DB::raw("
                CASE
                    WHEN type IN ('deposit','refund','bonus') THEN amount
                    ELSE -amount
                END
            "));

        return response()->json([
            'success' => 200,
            'message' => 'User balance retrieved successfully.',
            'data' => [
                'user_id' => $userId,
                'balance' => $totalBalance,
            ]
        ]);
    }

    public function getBalanceHistory(int $userId = null): JsonResponse
    {
        $userId = $userId ?? auth()->id();

        $history = $this->model
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => 200,
            'message' => 'Balance history retrieved successfully.',
            'data' => BalanceResource::collection($history),
        ]);
    }
}
