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

        $query = $this->model->query()->select(['id', 'type', 'html']);
        $query = $query->where('type', $params['type'] ?? 'main_page');

        $data = $query->get();

        return responseHelper('Legal Terms retrieved successfully.', 200, LegalTermResource::collection($data));
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
