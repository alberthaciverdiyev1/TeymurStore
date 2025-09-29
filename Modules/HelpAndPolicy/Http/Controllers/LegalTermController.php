<?php

namespace Modules\HelpAndPolicy\Http\Controllers;

use Illuminate\Http\Request;
use Modules\HelpAndPolicy\Http\Requests\LegalTerm\LegalTermAddRequest;
use Modules\HelpAndPolicy\Http\Requests\LegalTerm\LegalTermUpdateRequest;
use Modules\HelpAndPolicy\Services\LegalTermsService;

class LegalTermController
{
    private LegalTermsService $service;

    function __construct(LegalTermsService $service)
    {
        $this->service = $service;
    }

    public function getAll(Request $request)
    {
        return $this->service->getAll($request);
    }
    public function details(Request $request,$type)
    {
        return $this->service->details($request,$type);
    }

    public function update(LegalTermAddRequest $request,$type)
    {
        return $this->service->update($request,$type);
    }

}
