<?php

namespace App\Controllers\Api;


use App\Controllers\BaseController;

use App\Models\User;
use App\Services\Factory;
use App\Utils\Hash;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Contracts\Codes\Auth as AuthCode;
use App\Contracts\Codes\Cfg;
use App\Models\InviteCode;
use App\Utils\Check;
use App\Utils\Http;
use App\Utils\Tools;

/**
 *  ApiController.
 */
class TokenController extends BaseController implements AuthCode, Cfg
{
    /**
     *  @SWG\Definition(
     *   definition="LoginParam",
     *   type="object",
     *   allOf={
     *       @SWG\Schema(ref="#/definitions/LoginParam"),
     *       @SWG\Schema(
     *           required={"email","password"},
     *           @SWG\Property(property="email", format="string", type="integer"),
     *           @SWG\Property(property="password", format="string", type="integer")
     *       )
     *   }
     * )
     */

    /**
     * @SWG\Post(
     *     path="/token",
     *     tags={"Auth"},
     *     operationId="createToken",
     *     summary="Create token with email and password",
     *     description="",
     *     consumes={"application/json"},
     *     produces={"application/json"},
     *     @SWG\Parameter(
     *         name="body",
     *         in="body",
     *         description="Email and password",
     *         required=true,
     *         @SWG\Schema(ref="#/definitions/LoginParam"),
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="Success",
     *     ),
     *     @SWG\Response(
     *         response=400,
     *         description="Email or passwrod wring",
     *     ),
     * )
     */
    /**
     * @param Request $request
     * @param $response
     * @param $args
     * @return mixed
     */
    public function store(Request $request, $response, $args)
    {

        $email = $request->getParam('email');
        $email = strtolower($email);
        $passwd = $request->getParam('password');
        $rememberMe = $request->getParam('remember_me');
        $this->logger->debug($email . Hash::passwordHash($passwd));
        // Handle Login
        $user = User::where('email', '=', $email)->first();

        if ($user == null) {
            return $this->echoJson($response, [
                'error_code' => self::UserNotExist,
                'msg' => lang('auth.login-fail'),
            ], 400);
        }

        if (!Hash::checkPassword($user->pass, $passwd)) {
            return $this->echoJson($response, [
                'error_code' => self::UserPasswordWrong,
                'msg' => lang('auth.login-fail'),
                'hash' => Hash::passwordHash($passwd),
            ], 400);
        }
        // @todo
        $ttl = config('auth.session_timeout');
        if ($rememberMe) {
            $ttl = 3600 * 24 * 7;
        }
        $token = Factory::getTokenStorage()->store($user, $ttl);
        $this->logger->info(sprintf("login user %d token: %s  ttl:  %d", $user->id, $token->getAccessToken(), $ttl));

        return $this->echoJson($response, [
            'msg' => '',
            'data' => [
                'token' => $token->getAccessToken(),
                'user_id' => $user->id,
            ]
        ]);
    }

    public function show(Request $request, Response $response, $args)
    {
        $token = Factory::getTokenStorage()->get($args['token']);
        if (!$token) {
            return $this->echoJson($response, [], 404);
        }
        return $this->echoJson($response, [
            'msg' => '',
            'data' => [
                'token' => $token->getAccessToken(),
                'user' => $token->getUser()->getId(),
            ]
        ]);
    }


    public function createUser(Request $request, $response, $args)
    {
        $name = $request->getParam('userName');
        $email = $request->getParam('email');
        $email = strtolower($email);
        $passwd = $request->getParam('password');
        $repasswd = $request->getParam('passwordRepeat');
        $code = $request->getParam('inviteCode');
        $verifycode = $request->getParam('verifycode');

        // check code
        $c = InviteCode::where('code', $code)->first();
        if ($c == null) {
            return $this->echoJson($response, [
                'error_code' => self::InviteCodeWrong,
            ], 400);
        }

        // check email format
        if (!Check::isEmailLegal($email)) {
            return $this->echoJson($response, [
                'error_code' => self::EmailWrong,
            ], 400);
        }
        // check pwd length
        if (strlen($passwd) < 8) {
            return $this->echoJson($response, [
                'error_code' => self::PasswordTooShort,
            ], 400);
        }

        // check pwd re
        if ($passwd != $repasswd) {
            return $this->echoJson($response, [
                'error_code' => self::NewPasswordRepeatWrong,
            ], 400);
        }

        // check email
        $user = User::where('email', $email)->first();
        if ($user != null) {
            return $this->echoJson($response, [
                'error_code' => self::EmailUsed,
            ], 400);
        }


        // check ip limit
        $ip = Http::getClientIP();
        $ipRegCount = Check::getIpRegCount($ip);
        if ($ipRegCount >= 100) {
            // @todo
        }

        // do reg user
        $user = new User();
        $user->user_name = $name;
        $user->email = $email;
        $user->pass = Hash::passwordHash($passwd);
        $user->passwd = Tools::genRandomChar(6);
        $user->port = Tools::getLastPort() + 1;
        $user->t = 0;
        $user->u = 0;
        $user->d = 0;
        $user->transfer_enable = Tools::toGB(db_config(self::DefaultTraffic, 1));
        $user->invite_num = db_config(self::DefaultInviteNum, 10);
        $user->reg_ip = Http::getClientIP();
        $user->ref_by = $c->user_id;

        if ($user->save()) {
            $c->delete();

            $ttl = config('auth.session_timeout');
            $token = Factory::getTokenStorage()->store($user, $ttl);
            $this->logger->info(sprintf("login user %d token: %s  ttl:  %d", $user->id, $token->getAccessToken(), $ttl));

            return $this->echoJson($response, [
                'msg' => '',
                'data' => [
                    'token' => $token->getAccessToken(),
                    'user_id' => $user->id,
                ],
                'user' => $user,
            ]);
        }

        return $this->echoJson($response, [
        ], 500);
    }

}
