<?php

namespace Modules\Payment\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Nwidart\Modules\Facades\Module;

class PaymentController extends Controller
{

    public function __construct()
    {
        if (Module::find('Roles')->isEnabled()) {
            $this->middleware('permission:view payments')->only('index');
            $this->middleware('permission:create payment')->only('create');
            $this->middleware('permission:store payment')->only('store');
            $this->middleware('permission:edit payment')->only('edit');
            $this->middleware('permission:update payment')->only('update');
            $this->middleware('permission:destroy payment')->only('destroy');
        }
    }


    /**
    * Display a listing of the resource.
    */
    public function index()
    {
        return view('payment::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('payment::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {

            //TODO:STORE FUNCTIONS

            return response()->json(__('Data successfully created!'));
        } catch (Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    /**
     * Show the specified resource.
     */
    public function show()
    {
        return view('payment::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        return view('payment::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        try {

            //TODO:UPDATE FUNCTIONS

            return response()->json(__('Data successfully updated!'));
        } catch (Exception $e) {
            return response()->json($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy()
    {
        try {

            //TODO:DESTROY FUNCTIONS

            return response()->json(__('Data successfully deleted!'));
        } catch (Exception $e) {
            return response()->json($e->getMessage());
        }
    }
}
