<?php

namespace app\components;

use app\models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Yii;
use yii\filters\auth\AuthMethod;

class JwtAuth extends AuthMethod
{
  /**
   * @see yii\filters\auth\HttpBasicAuth
   */
  public $auth;

  /**
   * @inheritdoc
   */
  public function authenticate($user, $request, $response)
  {
    $token = Yii::$app->request->headers->get('authorization');
    if ($token !== null) {
      $decoded = JWT::decode(str_replace('Bearer ', '', $token), new Key(Yii::$app->params['jwtSecretKey'], 'HS256'));
      if ($decoded->exp <= time())
        $this->handleFailure($response);
      $identity = User::findIdentity($decoded->uid);
      if (!$identity)
        $this->handleFailure($response);
      Yii::$app->session->set('user', $identity);
      return $identity;
    }
    return null;
  }
}
