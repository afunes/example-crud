<?php

namespace App\Services\Api;

use App\Exceptions\AppException;
use App\Http\Controllers\AppBaseController;
use App\Models\ApiError;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ActivityServiceApi extends AppBaseController
{
    public function validator(array $data) {
        $rules = [
            'name'  => 'required|unique:activities|string',
            'color' => ['nullable', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/']
        ];
        return Validator::make($data, $rules);
    }

    public function find($id) {
        return Activity::where('id', $id)->where('trash', false)->first();
    }

    public function add(Request $request) {
        $apiError =  new ApiError();
        try {
            $v = $this->validator($request->all());

            if ($v->fails()) {
                $apiError->setErrors(json_decode(json_encode($v->errors()), true));
                throw new AppException(__('messages.validation_error'));
            }

            $input = $request->all();

            $apiError->data = Activity::create($input);
            $apiError->setCode(201);
        } catch (AppException $ex ) {
            $apiError->setError($ex->getMessage());
        } catch (\Exception $ex) {
            $apiError->setError($ex->getMessage());
        }
        return $apiError;
    }

    public function update($id, Request $request) {
        $apiError =  new ApiError();
        try {
            $v = $this->validator($request->all());

            if ($v->fails()) {
                $apiError->setErrors(json_decode(json_encode($v->errors()), true));
                throw new AppException(__('messages.validation_error'));
            }

            $input = $request->all();
            $row = $this->find($id);

            if(empty($row)) throw new AppException(__('messages.record_not_found'));

            $row->update($input);

            $apiError->data = $row;
        } catch (AppException $ex ) {
            $apiError->setError($ex->getMessage());
        } catch (\Exception $ex) {
            $apiError->setError($ex->getMessage());
        }
        return $apiError;
    }

    public function all() {
        $apiError =  new ApiError();
        try {
            $rows = Activity::where('trash', false)->get();

            $apiError->data = $rows ?? [];
        } catch (AppException $ex ) {
            $apiError->setError($ex->getMessage());
        } catch (\Exception $ex) {
            $apiError->setError($ex->getMessage());
        }
        return $apiError;
    }

    public function getById($id) {
        $apiError =  new ApiError();
        try {
            $row = $this->find($id);

            if(empty($row)) throw new AppException(__('messages.record_not_found'));

            $apiError->data = $row;

        } catch (AppException $ex ) {
            $apiError->setError($ex->getMessage());
        } catch (\Exception $ex) {
            $apiError->setError($ex->getMessage());
        }
        return $apiError;
    }

    public function delete($id) {
        $apiError =  new ApiError();
        try {
            $row = $this->find($id);
            if(empty($row)) throw new AppException(__('messages.record_not_found'));

            $row->trash = !$row->trash;
            $row->save();
            $apiError->data = $row;
            $apiError->setMessage(__('messages.delete_success'));
            $apiError->setCode(204);
        } catch (AppException $ex ) {
            $apiError->setError($ex->getMessage());
        } catch (\Exception $ex) {
            $apiError->setError($ex->getMessage());
        }
        return $apiError;
    }

}
