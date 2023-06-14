<?php

namespace app\controllers;

use app\components\JwtAuth;
use yii\filters\ContentNegotiator;
use yii\filters\Cors;
use yii\filters\VerbFilter;
use yii\web\Response;

class BaseController extends \yii\rest\Controller
{

  /**
   * @inheritdoc
   */
  public function behaviors()
  {
    date_default_timezone_set("Asia/Jakarta");
    $behaviors = parent::behaviors();

    $behaviors['corsFilter'] = [
      'class' => Cors::class,
      'cors' => [
        // restrict access to
        'Origin' => ['*'],
        // Allow only POST and PUT methods
        'Access-Control-Request-Method' => ['*'],
        // Allow only headers 'X-Wsse'
        'Access-Control-Allow-Headers' => ['*'],
        'Access-Control-Request-Headers' => ['*'],
        // Allow credentials (cookies, authorization headers, etc.) to be exposed to the browser
        'Access-Control-Allow-Credentials' => false,
        // Allow OPTIONS caching
        'Access-Control-Max-Age' => 360000,
        // Allow the X-Pagination-Current-Page header to be exposed to the browser.
        'Access-Control-Expose-Headers' => ['*'],
      ],
    ];

    $behaviors['verbs'] = [
      'class' => VerbFilter::class,
      'actions' => [
        'index'               => ['GET'],
        'show'                => ['GET'],
        'all'                 => ['GET'],
        'create'              => ['POST'],
        'update'              => ['PUT'],
        'destroy'             => ['DELETE'],
        'offline-disconnect'  => ['PATCH'],
        'activate'            => ['PATCH'],
        'approval'            => ['PATCH']
      ],
    ];

    $behaviors['contentNegotiator'] = [
      'class' => ContentNegotiator::class,
      'formats' => [
        'application/json' => Response::FORMAT_JSON,
      ],
    ];

    $behaviors['authenticator'] = [
      'class' => JwtAuth::class,
      'optional' => [
        'login',
        'refresh-token',
        'options',
      ],
    ];

    return $behaviors;
  }
}
