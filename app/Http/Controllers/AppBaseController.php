<?php

namespace App\Http\Controllers;

use App\Exceptions\AppException;
use App\Models\ApiError;
use App\Models\Category;
use App\Models\JobsRedis;
use App\Services\LoggerService;
use App\Services\BinnacleService;
use App\Services\PlatformService;
use App\Services\SMSService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Response;
use InfyOm\Generator\Utils\ResponseUtil;
use League\Fractal\Serializer\ArraySerializer;

class AppBaseController extends Controller {
    protected $errors = false;

    protected function jsonResponse($resource) {
        $response = [
            'message' => $resource->message,
            'code' => $resource->code,
            'isError' => $resource->isError,
            'errors' => $resource->errors,
            'data' => '',
        ];

        if (isset($resource->contact)) {
            $response['contact'] = $resource->contact;
        }

        if (isset($resource->resource)) {
            $response['data'] = $resource->resource;
        } else {
            $response['data'] = $resource->modelo;
        }

        return response()->json(
            $response,
            $resource->code != 204 ? $resource->code : 200
        );
    }

    public function setErrors($errors) {
        $this->errors = $errors;
    }

    public function getErrors() {
        return $this->errors;
    }

    public function sendResponse($result, $message) {
        return Response::json(ResponseUtil::makeResponse($message, $result));
    }

    public function sendError($error, $code = 404) {
        return Response::json(ResponseUtil::makeError($error), $code);
    }

    public function sendSuccess($message) {
        return Response::json(
            [
                'success' => true,
                'message' => $message,
            ],
            200
        );
    }
}
