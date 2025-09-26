<?php

namespace Modules\HelpAndPolicy\Http\Controllers;

use Illuminate\Http\Request;
use Modules\HelpAndPolicy\Http\Requests\Faq\FaqAddRequest;
use Modules\HelpAndPolicy\Http\Requests\Faq\FaqUpdateRequest;
use Modules\HelpAndPolicy\Services\FaqService;

class FaqController
{
    private FaqService $service;

    function __construct(FaqService $service)
    {
        $this->service = $service;
    }

    public function getAll(Request $request)
    {
        return $this->service->getAll($request);
    }

    public function add(FaqAddRequest $request)
    {
        return $this->service->add($request);
    }

    public function update(FaqUpdateRequest $request, int $id)
    {
        return $this->service->update($request, $id);
    }

    public function delete($id)
    {
        return $this->service->delete($id);
    }
}
