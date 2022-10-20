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

    protected function serializeArray() {
        return new ArraySerializer();
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

    public function setLog($controller, $method, $message, $trace) {
        $log = new LoggerService();
        $response = $log->saveLog($controller, $method, $message, $trace);

        if($response->isError) {
            throw new AppException($response->message);
        }

        return json_decode($response->data);
    }

    public function setBinnacle($controller, $module, $type, $description) {
        $apiError = new ApiError();
        try {
            $log = new BinnacleService();
            $log->saveLog($controller, $module, $type, $description, parse_url($_SERVER['REMOTE_ADDR'] ?? 'N/A', PHP_URL_PATH));

            $apiError->setMessage(__('messages.save_success'));
        } catch (AppException $ex){
            $this->setLog("AppBaseController", "saveLog", $ex->getMessage(), $ex);
            $apiError->setError($ex->getMessage());
        } catch (\Exception $ex) {
            $this->setLog("AppBaseController", "saveLog", $ex->getMessage(), $ex);
            $apiError->setError($ex->getMessage());
        }
        return $apiError;
    }

    public function getState($id) {
        $platform = new PlatformService();
        $state = $platform->getState($id);

        $states = [];
        foreach ($state->data as $state) {
            array_push($states, [
                'id' => $state['stateId'],
                'name' => $state['name'],
                'enabled' => $state['enabled'],
            ]);
        }
        $states = collect($states);
        $states = $states->where('enabled', true);

        return response()->json($states);
    }

    public function getStateName($id) {
        $platform = new PlatformService();
        $stateId = $platform->getStateId($id);

        $state = collect($stateId->data);

        return $state['name'];
    }
    
    public function getCountry() {
        $platform = new PlatformService();
        $country = $platform->getCountry();

        $countries = [];
        foreach ($country->data as $country) {
            array_push($countries, ["id" => $country['countryId'], "name" => $country['name'], "iso3Code" => $country['iso3Code'], "enabled" => $country['enabled']]);
        }
        $countries = collect($countries);
        $countries = $countries->where('enabled', true);

        return $countries;
    }

    public function getCountryName($id) {
        $platform = new PlatformService();
        $countryId = $platform->getCountryId($id);

        $country = collect($countryId->data);

        return $country['name'];
    }

    public function getUseCfdi() {
        $platform = new PlatformService();
        $cfdiUse = $platform->getUseCfdi();

        $useCfdi = [];
        foreach ($cfdiUse->data as $cfdi) {
            array_push($useCfdi, ["id" => $cfdi['useCfdiId'], "name" => $cfdi['name'], "key" => $cfdi['key'], "enabled" => $cfdi['enabled'], "deleted" => $cfdi['deleted']]);
        }
        $useCfdi = collect($useCfdi);
        $useCfdi = $useCfdi->where('enabled', true)->where('deleted', false);

        return $useCfdi;
    }

    public function getTaxRegimenName($id) {
        $platform = new PlatformService();
        $taxId = $platform->getTaxRegimen($id);

        $tax = collect($taxId->data);

        return $tax;
    }

    public function getTaxRegimen($id) {
        $platform = new PlatformService();
        $tax = $platform->getTaxRegimen($id);

        $taxes = [];
        foreach ($tax->data as $regime) {
            $arr = array_push($taxes, [
                'id' => $regime['taxRegimeId'],
                'name' => $regime['name'],
                'key' => $regime['key'],
                'enabled' => $regime['enabled'],
            ]);
        }
        $taxes = collect($taxes);
        $taxes = $taxes->where('enabled', true);

        return response()->json($taxes);
    }

    public function getCategories() {
        $apiError =  new ApiError();
        try {
            $rows = Category::whereTenantid(Auth::user()->tenantId)->whereEnabled(true)->whereTrash(false)->get();

            $apiError->data = $rows;
        } catch (AppException $ex ) {
            $apiError->setError($ex->getMessage());
            $this->setLog("AppBaseController", "getCategories", $ex->getMessage(), $ex);
        } catch (\Exception $ex) {
            $apiError->setError($ex->getMessage());
            $this->setLog("AppBaseController", "getCategories", $ex->getMessage(), $ex);
        }
        return response()->json($apiError);
    }

    public function getPackage() {
        $sms = app(SMSService::class)->getPackage();
        return response()->json($sms);
    }

    public function sendSMS(Request $request) {
        $result = app(SMSService::class)->sendSMS($request);
        return response()->json($result);
    }

    public function sendMail($params, $type, $to, $cc = null, $bcc = null) {
        $apiError = new ApiError();
        try {
            JobsRedis::create();

            $jobs = JobsRedis::whereType('email')
                ->orderBy('id', 'desc')
                ->get()
                ->first();

            $attachment = null;

            if ($type == "resetEmail") {
                $html = view('emails.user.resetEmail')
                    ->with('name', $params[0])
                    ->with('email', $params[1])
                    ->with('token', $params[2])
                    ->render();
                $subject = __("modals.send_change");
            }

            if ($type == 1) {
                $html = view('emails.user.emailVerified')
                    ->with('name', $params[0])
                    ->with('token', $params[1])
                    ->render();
                $subject =  __("modals.verify_mail");
            }

            if ($type == 2) {
                $html = view('emails.user.welcome')
                    ->with('name', $params[0])
                    ->render();
                $subject = __("modals.welcome_mail");
            }

            if ($type == 3) {
                $html = view('emails.user.resetPassword')
                    ->with('name', $params[0])
                    ->with('token', $params[1])
                    ->render();

                $subject =  __("modals.recovery_password");
            }

            if ($type == "newUser") {
                $html = view('emails.user.newUser')
                    ->with('data', collect($params))
                    ->render();

                $subject = __("modals.welcome_mail");
            }

            if ($type == 4) {
                $attachment = $params[9];
                $html = view('emails.activities.training')
                    ->with('name', $params[0])
                    ->with('title', $params[1])
                    ->with('description', $params[2])
                    ->with('day', $params[3])
                    ->with('tz', $params[4])
                    ->with('url', $params[5])
                    ->with('google', $params[6])
                    ->with('yahoo', $params[7])
                    ->with('outlook', $params[8])
                    ->render();
                $subject = __("calendar.training").': '.$params[1];
            }

            if ($type == 5) {
                $attachment = $params[10];
                $html = view('emails.activities.activity')
                    ->with('action', $params[0])
                    ->with('type', $params[1])
                    ->with('name', $params[2])
                    ->with('user', $params[3])
                    ->with('day', $params[4])
                    ->with('tz', $params[5])
                    ->with('description', $params[6])
                    ->with('google', $params[7])
                    ->with('yahoo', $params[8])
                    ->with('outlook', $params[9])
                    ->render();
                $subject = __("calendar.task").': '.$params[0].' '. __("calendar.to").' '.$params[1];
            }

            if ($type == 6) {
                $attachment = $params[11];
                $html = view('emails.activities.'.$params[0])
                    ->with('type', $params[2])
                    ->with('user', $params[3])
                    ->with('guest', $params[4])
                    ->with('day', $params[5])
                    ->with('tz', $params[6])
                    ->with('description', $params[7])
                    ->with('google', $params[8])
                    ->with('yahoo', $params[9])
                    ->with('outlook', $params[10])
                    ->render();
                $subject = __("calendar.task") .': '.$params[1].' '.__("calendar.to").' '.$params[2];
            }

            if ($type == 7) {
                $attachment = $params[3];
                $html = view('emails.payments.mail')
                    ->with('type', $params[2])
                    ->with('name', $params[0])
                    ->render();
                $subject = $params[2].' #'.$params[1];
            }

            if ($type == 8) {
                $html = view('emails.user.documents')
                    ->with('name', $params[0])
                    ->render();
                $subject = __("modals.document_request");
            }

            if ($type == 9) {
                $html = view('emails.user.newUserOrganism')
                    ->with('name', $params[0])
                    ->with('lastName',$params[1])
                    ->with('email',$params[2])
                    ->with('cellphone',$params[3])
                    ->with('phone',$params[4])
                    ->with('ext',$params[5])
                    ->with('position',$params[6])
                    ->with('businessName',$params[7])
                    ->with('address',$params[8])
                    ->with('city',$params[9])
                    ->with('state',$params[10])
                    ->with('country',$params[11])
                    ->with('user',$params[12])
                    ->render();
                $subject = __("modals.new_register");
            }

            if ($type == 10) {
                $html = view('emails.user.deleteAccount')
                    ->with('name', $params[0])
                    ->with('codeDelete', $params[1])
                    ->render();
                $subject = __("modals.delete_account");
            }

            if ($type == "InvitationEvent") {
                $attachment = $params[8];
                $html = view('emails.activities.event')
                    ->with('title', $params[0])
                    ->with('name', $params[1])
                    ->with('date', $params[2])
                    ->with('description', $params[3])
                    ->with('google', $params[4])
                    ->with('yahoo', $params[5])
                    ->with('outlook', $params[6])
                    ->with('url', $params[7])
                    ->render();
                $subject = __("calendar.event").': '.$params[0];
            }

            if ($type == "IncidenceNotification") {
                $html = view('emails.inciden.notification')
                    ->with('personName', $params[0])
                    ->with('folio', $params[1])
                    ->with('attendName', $params[2])
                    ->with('description', $params[3])
                    ->render();
                $subject = __("incidences.incidence").': '.$params[1];
            }

            if ($type == "forms") {
                $html = view('emails.forms.form')
                    ->with('name', $params[0])
                    ->with('url', $params[1])
                    ->render();
                $subject = __("modals.forms");
            }

            if ($type == "payments") {
                $html = view('emails.payments.'.$params[0])
                    ->with('folio', $params[1])
                    ->with('name', $params[2])
                    ->with('url', $params[3])
                    ->with('total', $params[4])
                    ->with('bank', $params[5])
                    ->with('expiry', $params[6])
                    ->with('reference', collect($params[7]))
                    ->render();
                $subject = __("memberships.process_email").': '.$params[1];
            }

            if ($type == "invoiceExpired") {
                $html = view('emails.invoices.invoiceExpired')
                    ->with('expiryDate', $params[0])
                    ->with('name', $params[1].' '.$params[2])
                    ->render();
                $subject = __("emails.invoiceExpired");
            }

            if ($type == "billing") {
                $html = view('emails.payments.billing')
                    ->with('data', collect($params))
                    ->render();
                $subject = 'Han solicitado la factura con el folio: '.$params['folio'];
            }
            
            $htmlReplace = str_replace('"', '\'', $html);
            $connection = Redis::connection('emails');
            
            $data = [
                'platformKey' => 'salexopps',
                'subject' => $subject,
                'countryId' => 1,
                'emailType' => 'GENERAL',
                'body' => '',
                'to' => [$to],
                'cc' => !empty($cc) ? [$cc] : [],
                'bcc' => !empty($bcc) ? [$bcc] : [],
                'platformReference' => $jobs->id,
                'attachmentsId' => !empty($attachment) ? [$attachment] : []
            ];
            
            $converted = (json_encode($data));
            $converted = (json_encode($converted));
            $htmlReplace = str_replace(array("\r", "\n"), '',  $htmlReplace);
            $htmlReplace = str_replace('\"body\":\"\"', '\"body\":\"' . $htmlReplace . '\"', $converted);
            $connection->rpush('emails', $htmlReplace);

        } catch (AppException $ex) {
            $apiError->setError($ex->getMessage());
            $this->setLog("AppBaseController", "sendMail", $ex->getMessage(), $ex);
        } catch (\Exception $ex) {
            $apiError->setError($ex->getMessage());
            $this->setLog("AppBaseController", "sendMail", $ex->getMessage(), $ex);
        }
        return $apiError;
    }
}
