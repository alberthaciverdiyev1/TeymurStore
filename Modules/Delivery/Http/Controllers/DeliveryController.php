<?php

namespace Modules\Delivery\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Nwidart\Modules\Facades\Module;

class DeliveryController extends Controller
{

    public function __construct()
    {
        if (Module::find('Roles')->isEnabled()) {
            $this->middleware('permission:view deliverys')->only('index');
            $this->middleware('permission:create delivery')->only('create');
            $this->middleware('permission:store delivery')->only('store');
            $this->middleware('permission:edit delivery')->only('edit');
            $this->middleware('permission:update delivery')->only('update');
            $this->middleware('permission:destroy delivery')->only('destroy');
        }
    }


    /**
    * Display a listing of the resource.
    */
    public function index()
    {
        return view('delivery::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('delivery::create');
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
        return view('delivery::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        return view('delivery::edit');
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
