<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Api\ActivityServiceApi;
use Illuminate\Http\Request;

class ActivityControllerApi extends Controller
{
    private $service;

    public function __construct(ActivityServiceApi $service) {
        $this->service = $service;
    }

    public function index() {
        $result = $this->service->all();
        return response()->json($result);
    }

    public function store(Request $request) {
        $result = $this->service->add($request);
        return response()->json($result);
    }

    public function show(int $id) {
        $result = $this->service->getById($id);
        return response()->json($result);
    }

    public function update(int $id, Request $request) {
        $result = $this->service->update($id, $request);
        return response()->json($result);
    }

    public function destroy(int $id) {
        $result = $this->service->delete($id);
        return response()->json($result);
    }
}
