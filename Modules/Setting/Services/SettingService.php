<?php

namespace Modules\Setting\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Modules\Setting\Http\Entities\Setting;
use Modules\Setting\Http\Resources\SettingResource;

class SettingService
{
    private Setting $model;

    public function __construct(Setting $model)
    {
        $this->model = $model;
    }

    /**
     * Get settings list
     */
    public function list(): JsonResponse
    {
        $cacheKey = 'settings_list';

        $data = Cache::remember(
            $cacheKey,
            config('cache.setting_list_cache_time', 3600),
            fn () => $this->model->all()
        );

        return responseHelper('Settings retrieved successfully.', 200, SettingResource::collection($data));
    }

    /**
     * Update setting
     */
    public function update($request): JsonResponse
    {
        $validated = $request->validated();

        $setting = handleTransaction(
            function () use ($validated) {
                $setting = $this->model->first();
                $setting->update($validated);
                return $setting->refresh();
            },
            'Setting updated successfully.',
            SettingResource::class
        );

        Cache::forget('settings_list');

        return $setting;
    }

    public function changeLocale($request)
    {
        $request->validate([
            'locale' => ['required', 'in:az,en,ru,tr'],
        ]);

        return responseHelper('Locale changed successfully.', 200, [
            'locale' => $request->locale,
        ]);
    }
}
