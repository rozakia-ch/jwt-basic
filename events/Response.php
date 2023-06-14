<?php

namespace app\events;

use Yii;

class Response
{

    public static function beforeSend($event)
    {
        if (Yii::$app->response->format == 'json') {
            $response = $event->sender;
            $data['requestTimestamp'] = Yii::$app->request->post('timestamp');
            $data['responseTimestamp'] = date('Y-m-d H:i:s');
            if ($response->isSuccessful) {
                $data['success'] = true;
                // $data = $response->data + $data;  
                $data = array_merge((array) $response->data, $data);
            } else {
                $data['success'] = false;
                switch ($response->data['type']) {
                    case 'app\exceptions\RecordNotFoundException':
                        $response->statusCode = 200;
                        $data['error_code'] = 'RecordNotFoundException';
                        $data['message'] = $response->data['message'];
                        break;
                    case 'Firebase\\JWT\\ExpiredException':
                        $response->statusCode = 401;
                        $data['error_code'] = 'ExpiredException';
                        $data['message'] = $response->data['message'];
                        break;
                    case "yii\\web\\UnauthorizedHttpException":
                        $response->statusCode = 401;
                        $data['error_code'] = 'UnauthorizedHttpException';
                        $data['message'] = $response->data['message'];
                        break;
                    case "Firebase\\JWT\\SignatureInvalidException":
                        $response->statusCode = 401;
                        $data['error_code'] = 'SignatureInvalidException';
                        $data['message'] = $response->data['message'];
                        break;
                    default:
                        $response->statusCode = 200;
                        $data['error_code'] = $response->data['type'];
                        $data['message'] = $response->data['message'];
                        $data['exception'] = $response->data;
                        break;
                }
            }
            $response->data = $data;
        }
    }
}
