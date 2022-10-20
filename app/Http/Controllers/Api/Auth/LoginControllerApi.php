<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Api\LoginService;

class LoginControllerApi extends Controller
{
    private $service;

    public function __construct(LoginService $service)
    {
        $this->service = $service;
    }

    public function login(Request $request) {
        $result = $this->service->login($request);
        return $result;
    }

    public function logout(Request $request) {
        $result = $this->service->logout($request);
        return $result;
    }

}
