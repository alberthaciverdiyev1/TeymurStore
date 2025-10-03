<?php

namespace Modules\HelpAndPolicy\Services;

use Illuminate\Support\Facades\Cache;
use Modules\HelpAndPolicy\Http\Entities\Faq;
use Modules\HelpAndPolicy\Http\Resources\FaqResource;

class FaqService
{
    private Faq $model;

    function __construct(Faq $model)
    {
        $this->model = $model;
    }

    public function getAll($request)
    {
        $params = $request->all();
        $cacheKey = 'faq_list_' . md5(serialize($params));

        $data = Cache::remember($cacheKey, config('cache.faq_list_cache_time'), function () use ($params) {
            $query = $this->model->query()->select(['id', 'title', 'description', 'type']);

            if (isset($params['type'])) $query->where('type', $params['type']);

            return $query->get();
        });

        return responseHelper( 'Faqs retrieved successfully.',200, FaqResource::collection($data));
    }

    public function add($request)
    {
        $validated = $request->validated();

        $faqs = handleTransaction(
            fn() => $this->model->create($validated)->refresh(),
            'Faq added successfully.',
            FaqResource::class
        );

        Cache::forget('faq_list_' . md5(serialize([])));

        return $faqs;
    }

    public function update($request,int $id)
    {
        $validated = $request->validated();

        $faq = handleTransaction(
            function () use ($validated, $id) {
                $faq = $this->model->findOrFail($id);
                $faq->update($validated);
                return $faq->refresh();
            },
            'Faq updated successfully.',
            FaqResource::class
        );

        Cache::forget('faq_list_*');

        return $faq;
    }

    public function delete($id)
    {
        $response = handleTransaction(
            function () use ($id) {
                $faq = $this->model->findOrFail($id);
                $faq->delete();
                return $faq;
            },
            'Faq deleted successfully.'
        );

        Cache::forget('faq_list_*');

        return $response;
    }
}
