<?php
declare(strict_types = 1);

namespace App\Controller;

use App\Component\Sms;
use App\Helper\ValidateHelper;
use App\JsonRpc\Contract\InterfaceUserService;
use Psr\Http\Message\ResponseInterface;

class AuthController extends AbstractController
{
    /**
     * 注册接口
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function register():ResponseInterface
    {
        $params = $this->request->all();
        if (!ValidateHelper::isPhone($params['mobile'])) {
            return $this->response->parmasError('手机号格式不正确...');
        }
        $rpcUser = $this->container->get(InterfaceUserService::class);
        $ret     = $rpcUser->register(
            $params['mobile'],
            $params['password'],
            $params['sms_code'],
            strip_tags($params['nickname'])
        );
        if (isset($ret['code']) && $ret['code'] === 1) {
            return $this->response->success('账号注册成功!');
        }
        return $this->response->error($ret['msg']);
    }

    /**
     * 登录
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function login():ResponseInterface
    {
        $params = $this->request->all();
        if (!ValidateHelper::isPhone($params['mobile'])) {
            return $this->response->parmasError('手机号格式不正确...');
        }
        $rpcUser = $this->container->get(InterfaceUserService::class);
        $ret     = $rpcUser->login($params['mobile'], $params['password']);

        if (isset($ret['code']) && $ret['code'] === 1) {
            return $this->response->success('登录成功!', [
                'authorize' => $ret['authorize'],
                'userInfo'  => $ret['user_info']
            ]);
        }
        return $this->response->error($ret['msg'] ?? '登录失败...');
    }

    /**
     * 退出登录
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function logout():ResponseInterface
    {
        $token   = $this->request->getHeaderLine('Authorization') ?? '';
        $rpcUser = $this->container->get(InterfaceUserService::class);
        $rpcUser->logout($token);
        return $this->response->success('退出成功!');
    }

    /**
     * 发送验证码
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function sendVerifyCode():ResponseInterface
    {
        $mobile = $this->request->post('mobile', '');
        $type   = $this->request->post('type', '');
        if (!di(Sms::class)->isUsages($type)) {
            return $this->response->error('验证码发送失败...');
        }
        if (!ValidateHelper::isPhone($mobile)) {
            return $this->response->error('手机号格式不正确...');
        }

        $rpcUser = $this->container->get(InterfaceUserService::class);
        $data    = $rpcUser->sendVerifyCode($mobile, $type);
        $data    = array_merge(['is_debug' => true], $data);
        if (isset($data['code']) && $data['code'] === 1) {
            return $this->response->success('验证码发送成功!', $data);
        }
        return $this->response->error($data['msg']);
    }

    /**
     * 忘记密码
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function forgetPassword():ResponseInterface
    {
        $mobile   = $this->request->post('mobile', '');
        $code     = $this->request->post('sms_code', '');
        $password = $this->request->post('password', '');
        $rpcUser  = $this->container->get(InterfaceUserService::class);
        $data     = $rpcUser->forgetPassword($mobile, $code, $password);
        if (isset($data['code']) && $data['code'] === 1) {
            return $this->response->success($data['msg']);
        }
        return $this->response->error('重置密码失败...');
    }
}
