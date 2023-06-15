<?php

namespace app\controllers;

use app\models\User;
use app\models\UserRefreshToken;
use Yii;

class AuthController extends BaseController
{
  /**
   * @return \yii\web\Response
   */
  public function actionLogin()
  {
    $params = Yii::$app->request->getBodyParams();
    $user = User::findIdentity($params['id']);
    $token = $this->generateJwt($user);
    $refreshToken = $this->generateRefreshToken($user);
    return [
      'data' => [
        'user' => $user,
        'accessToken' => (string) $token,
        'refreshToken' => $refreshToken->urf_token
      ]
    ];
  }

  public function actionRefreshToken()
  {
    $refreshToken = Yii::$app->request->headers->get('refreshToken');
    if (!$refreshToken)
      return new \yii\web\UnauthorizedHttpException('No refresh token found.');

    $userRefreshToken = UserRefreshToken::findOne(['urf_token' => $refreshToken]);
    // Getting new JWT after it has expired
    if (!$userRefreshToken)
      return new \yii\web\UnauthorizedHttpException('The refresh token no longer exists.');

    if (Yii::$app->request->getMethod() == 'POST') {
      $user = User::findIdentity($userRefreshToken->urf_userID);  //adapt this to your needs
      if (!$user) {
        $userRefreshToken->delete();
        return new \yii\web\UnauthorizedHttpException('The user is inactive.');
      }
      $token = $this->generateJwt($user);
      return ['data' => [
        'refreshToken' => $userRefreshToken->urf_token,
        'accessToken' => (string) $token
      ]];
    } elseif (Yii::$app->request->getMethod() == 'DELETE') {
      // Logging out
      if ($userRefreshToken && !$userRefreshToken->delete())
        return new \yii\web\ServerErrorHttpException('Failed to delete the refresh token.');
      return ['data' => 'ok'];
    } else {
      return new \yii\web\UnauthorizedHttpException('The user is inactive.');
    }
  }

  public function actionData()
  {
    $data = Yii::$app->session['user'];
    return ['data' => $data];
  }

  private function generateJwt(User $user)
  {
    $jwt = Yii::$app->jwt;
    $signer = $jwt->getSigner('HS256');
    $key = $jwt->getKey();
    $time = time();

    $jwtParams = Yii::$app->params['jwt'];

    return $jwt->getBuilder()
      ->issuedBy($jwtParams['issuer'])
      ->permittedFor($jwtParams['audience'])
      ->identifiedBy($jwtParams['id'], true)
      ->issuedAt($time)
      ->expiresAt($time + $jwtParams['expire'])
      ->withClaim('uid', $user->id)
      ->getToken($signer, $key);
  }

  /**
   * @throws yii\base\Exception
   */
  private function generateRefreshToken(User $user)
  {
    $userRefreshToken = UserRefreshToken::findOne(['urf_userID' => $user->id]);
    if (!$userRefreshToken) {
      $userRefreshToken = new UserRefreshToken();
      $userRefreshToken->urf_created = date('Y-m-d H:i:s');
    }
    $userRefreshToken->urf_userID     = $user->id;
    $userRefreshToken->urf_token      = Yii::$app->security->generateRandomString(64);
    $userRefreshToken->urf_ip         = Yii::$app->request->userIP;
    $userRefreshToken->urf_user_agent = Yii::$app->request->userAgent;
    if (!$userRefreshToken->save())
      throw new \yii\web\ServerErrorHttpException('Failed to save the refresh token: ' . $userRefreshToken->getErrorSummary(true));
    // Send the refresh-token to the user in a HttpOnly cookie that Javascript can never read and that's limited by path
    // Yii::$app->response->cookies->add(new \yii\web\Cookie([
    //   'name' => 'refreshToken',
    //   'value' => $userRefreshToken->urf_token,
    //   'httpOnly' => true,
    //   'sameSite' => 'none',
    //   'secure' => true,
    //   'path' => 'auth/refresh-token',  //endpoint URI for renewing the JWT token using this refresh-token, or deleting refresh-token
    // ]));
    return $userRefreshToken;
  }
}
