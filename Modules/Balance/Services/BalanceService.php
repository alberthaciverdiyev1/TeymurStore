<?php

namespace Modules\Balance\Services;

use Illuminate\Http\Request;
use Modules\Balance\Http\Entities\Balance;
use App\Enums\BalanceType;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Modules\Balance\Http\Resources\BalanceResource;
use Modules\Payment\Service\EPointService;

class BalanceService
{
    private Balance $model;

    public function __construct(Balance $model)
    {
        $this->model = $model;
    }


    public function deposit($request): JsonResponse
    {

        return "google.com";
//        $validated = $request->validated();
//
//        $paymentResponse = EPointService::typeCard(
//            env('EPOINT_PRIVATE_KEY'),
//            env('EPOINT_PUBLIC_KEY'),
//            $orderId,
//            (float)$validated['amount'],
//            "Payment for order #{$orderId}",
//            route('api.payment.success', ['transaction_id' => $validated['transaction_id']]),
//            route('api.payment.error', ['transaction_id' => $validated['transaction_id']])
//        );
////
//        $validated['user_id'] = $validated['user_id'] ?? auth()->id();
//
//        return handleTransaction(
//            fn() => $this->model->create([
//                'user_id' => $validated['user_id'],
//                'type' => BalanceType::DEPOSIT->value,
//                'amount' => (float)$validated['amount'],
//                'note' => $validated['note'] ?? null,
//            ])->refresh(),
//            'Balance deposited successfully.',
//            BalanceResource::class
//        );
    }

    public function callbackDeposit($userId, $amount, $note): JsonResponse
    {
        return handleTransaction(
            fn() => $this->model->create([
                'user_id' => $userId,
                'type' => BalanceType::DEPOSIT->value,
                'amount' => (float)$amount,
                'note' => $note ?? null,
            ])->refresh(),
            'Balance deposited successfully.',
            BalanceResource::class,
            200
        );
    }

//    public function withdraw(Request $request): JsonResponse
//    {
//        $validated = $request->validated();
//        return handleTransaction(
//            fn() => $this->model->create([
//                'user_id' => $validated['user_id'],
//                'type' => BalanceType::WITHDRAWAL->value,
//                'amount' => (float)$validated['amount'],
//                'note' => $validated['note'] ?? null,
//            ])->refresh(),
//            'Balance withdrawn successfully.',
//            BalanceResource::class
//        );
//    }

    public function withdraw(int $user_id, float $amount, string $note = null): JsonResponse
    {
        return handleTransaction(
            fn() => $this->model->create([
                'user_id' => $user_id,
                'type' => BalanceType::WITHDRAWAL->value,
                'amount' => $amount,
                'note' => $note,
            ])->refresh(),
            'Balance withdrawn successfully.',
            BalanceResource::class,
            200
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
        return responseHelper('Balance retrieved successfully.', 200, [
            'user_id' => $userId,
            'balance' => (float)$totalBalance,
        ]);

    }

    public function getBalanceHistory(int $userId = null): JsonResponse
    {
        $userId = $userId ?? auth()->id();

        $history = $this->model
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return responseHelper('Balance history retrieved successfully.', 200, BalanceResource::collection($history));
    }
}
