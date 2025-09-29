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
        $query = $this->model->query()->select(['id', 'type', 'html']);

        $data = $query->get();

        return response()->json([
            'success' => 200,
            'message' => __('LegalTerms retrieved successfully.'),
            'data' => LegalTermResource::collection($data),
        ]);
    }

    public function details($request,$type)
    {
        $params = $request->all();
        $query = $this->model->query()->select(['id', 'type', 'html']);
        $query = $query->where('type', $type ?? 'terms_and_conditions');
        $data = $query->get();

        return response()->json(isset($params['is_application']) ?  LegalTermResource::collection($data) : [
            'success' => 200,
            'message' => __('Legal Terms retrieved successfully.'),
            'data' => LegalTermResource::collection($data),
        ]);
    }


    public function update($request,$type)
    {
        $validated = $request->validated();

        $legalTerm = handleTransaction(
            fn() => tap($this->model->where('type', $type)->firstOrFail())
                ->update($validated),
            'Legal Terms updated successfully.',
            LegalTermResource::class
        );

        return $legalTerm;
    }
}
