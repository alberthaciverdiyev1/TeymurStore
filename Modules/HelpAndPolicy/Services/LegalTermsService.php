<?php

namespace Modules\HelpAndPolicy\Services;

use Illuminate\Support\Facades\Cache;
use Modules\HelpAndPolicy\Http\Entities\LegalTerm;
use Modules\HelpAndPolicy\Http\Resources\LegalTermResource;

class LegalTermsService
{
    private LegalTerm $model;

    function __construct(LegalTerm $model)
    {
        $this->model = $model;
    }

    public function getAll($request)
    {
        $params = $request->all();
        $cacheKey = 'legal_terms_list_' . md5(serialize($params));

        $data = Cache::remember($cacheKey, config('cache.legal_terms_list_cache_time'), function () use ($params) {
            $query = $this->model->query()->select(['id', 'title', 'description', 'type']);

            if (isset($params['type'])) $query->where('type', $params['type']);

            return $query->get();
        });

        return response()->json([
            'success' => 200,
            'message' => __('LegalTerms retrieved successfully.'),
            'data' => LegalTermResource::collection($data),
        ]);
    }

    public function add($request)
    {
        $validated = $request->validated();

        $legal_termss = handleTransaction(
            fn() => $this->model->create($validated)->refresh(),
            'LegalTerm added successfully.',
            LegalTermResource::class
        );

        Cache::forget('legal_terms_list_' . md5(serialize([])));

        return $legal_termss;
    }

    public function update($request,int $id)
    {
        $validated = $request->validated();

        $legal_terms = handleTransaction(
            function () use ($validated, $id) {
                $legal_terms = $this->model->findOrFail($id);
                $legal_terms->update($validated);
                return $legal_terms->refresh();
            },
            'LegalTerm updated successfully.',
            LegalTermResource::class
        );

        Cache::forget('legal_terms_list_*');

        return $legal_terms;
    }

    public function delete($id)
    {
        $response = handleTransaction(
            function () use ($id) {
                $legal_terms = $this->model->findOrFail($id);
                $legal_terms->delete();
                return $legal_terms;
            },
            'LegalTerm deleted successfully.'
        );

        Cache::forget('legal_terms_list_*');

        return $response;
    }
}
