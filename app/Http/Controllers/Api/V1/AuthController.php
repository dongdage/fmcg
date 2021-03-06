<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2015/9/17
 * Time: 14:22
 */

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\v1\BackupPasswordRequest;
use App\Http\Requests\Api\v1\BackupSendSmsRequest;
use App\Http\Requests\Api\v1\RegisterRequest;
use App\Http\Requests\Api\v1\RegisterUserRequest;
use App\Http\Requests\Api\v1\RegisterSetPasswordRequest;
use App\Http\Requests\Api\v1\RegisterUserShopRequest;
use App\Models\User;
use App\Models\UserToken;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\Request;
use Hash;
use App\Services\ValidateService;
use App\Http\Requests\Api\v1\RegisterUserSendSmsRequest;

class AuthController extends Controller
{

    use AuthenticatesAndRegistersUsers, ThrottlesLogins;

    /**
     * 登录
     *
     * @param \Illuminate\Http\Request $request
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function postLogin(Request $request)
    {
        $inWindows = in_windows();
        $account = $request->input('account');
        $password = $request->input('password');
        $cookie = app('cookie');
        if ($inWindows && $request->cookie('login_error') >= 2) {
            if (!$request->input('geetest_challenge')) {
                return $this->error('请完成验证');
            }
            $res = (new ValidateService)->validateGeetest($request);
            if (!$res) {
                return $this->error('请完成验证');
            }
        }
        if (!$account || !$password) {
            return $this->invalidParam('password', '账号或密码不能为空');
        }
        $type = $request->input('type');

        $user = User::where(['user_name' => $account, 'type' => $type])->first();

        //帐号密码
        if (!$user || !Hash::check($password, $user->password)) {
            $loginError = 0;
            if ($inWindows) {
                $loginError = $request->cookie('login_error');
                $cookie->queue('login_error', $loginError ? $loginError + 1 : 1, 1);
            }
            return $this->invalidParams(['password' => ['账号或密码错误'], 'loginError' => ($loginError + 1)]);
        }

        //是否已锁定
        if ($user->status != cons('status.on')) {
            return $this->invalidParam('password', '账号已锁定');
        }
        //帐号审核
        if ($user->audit_status != cons('user.audit_status.pass')) {
            return $this->invalidParam('password', '账户未审核或审核不通过');
        }

        //使用期限是否过期
        if ($user->is_expire) {
            return $this->invalidParam('password', '账户已到期，请续费');
        }

        $user->setVisible(['id', 'user_name', 'type', 'audit_status', 'backup_mobile', 'shop']);
        if (!is_null($user->shop)) {
            $user->shop->address_name = $user->shop->address;
        }

        $nowTime = Carbon::now();


        //第三方绑定时绑定token
        $token = $request->input('token');

        if ($token) {
            if ($user->userTokens()->where(['type' => $token['type'], 'user_id' => $user->id])->first()) {
                return $this->invalidParam('password', '此账号与被绑定');
            }
            $user->userTokens()->create([
                'token' => $token['token'],
                'type' => cons('user.token.type.' . $token['type'])
            ]);
            $request->session()->forget('socialite_token');
        }

        if ($user->fill(['last_login_at' => $nowTime, 'last_login_ip' => $request->ip()])->save()) {
            auth()->login($user, true);
            $cookie->queue('login_error', null, -1);
            return $this->success(['user' => $user]);
        }
        return $this->invalidParam('password', '登录失败，请重试');
    }

    /**
     * 注册
     *
     * @param \App\Http\Requests\Api\v1\RegisterRequest $request
     * @return \WeiHeng\Responses\Apiv1Response
     * @throws \Exception
     */
    public function postRegister(RegisterRequest $request)
    {
        $userInput = $request->only(['user_name', 'password', 'backup_mobile', 'type']);
        //终端商不需要审核直接通过
        if ($userInput['type'] == cons('user.type.retailer')) {
            $userInput['audit_status'] = cons('user.audit_status.pass');
        }
        $user = User::Create($userInput);
        if ($user->exists) {
            //商店
            $shopInput = $request->except('user_name', 'password', 'backup_mobile', 'type');

            $shopModel = $user->shop();

            if ($user->type == cons('user.type.retailer')) {
                unset($shopInput['area']);
            }
            if ($shopModel->create($shopInput)->exists) {
                session(['shop.name' => $shopInput['name']]);
                $param = [
                    'account' => $userInput['user_name'],
                    'name' => $shopInput['name'],
                ];
                if ($user->type != cons('user.type.retailer')) {
                    app('pushbox.sms')->send('tip', cons('admin.phone'), $param);
                }
                //默认添加店铺地址为收货地址
                $this->_addShippingAddress($shopInput, $user);
                return $this->success('注册成功');
            } else {
                $user->delete();
                return $this->error('注册用户时遇到问题');
            }
        }
        return $this->error('注册用户时遇到问题');
    }

    /**
     * 注册用户
     *
     * @param \App\Http\Requests\Api\v1\RegisterUserRequest $request
     * @return \WeiHeng\Responses\Apiv1Response
     * @throws \Exception
     */
    public function postRegisterUser(RegisterUserRequest $request)
    {
        $data = $request->only('user_name', 'backup_mobile', 'type');
        //验证验证码
        $code = $request->input('code');
        if (!app('pushbox.sms')->verifyCode('register', $data['backup_mobile'], $code)) {
            return $this->error('短信验证码错误');
        }
        session(['user' => $data]);
        return $this->success('验证成功');

    }

    /**
     * 设置密码
     *
     * @param \App\Http\Requests\Api\v1\RegisterSetPasswordRequest $request
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function postSetPassword(RegisterSetPasswordRequest $request)
    {
        $data = $request->only('user_name', 'backup_mobile', 'type');
        if (in_windows() && array_diff_assoc($data, session('user'))) {
            return $this->error('注册用户出现问题');
        }
        session(['user' => $request->all()]);
        return $this->success('设置密码成功');
    }

    /**
     * 注册
     *
     * @param \App\Http\Requests\Api\v1\RegisterRequest $request
     * @return \WeiHeng\Responses\Apiv1Response
     * @throws \Exception
     */
    public function postBindSocialite(RegisterRequest $request)
    {
        $userInput = $request->only(['user_name', 'password', 'backup_mobile', 'type', 'code']);

        if (!app('pushbox.sms')->verifyCode('register', $userInput['backup_mobile'], $userInput['code'])) {
            return $this->error('短信验证码错误');
        }

        !array_get($userInput, 'user_name') && ($userInput['user_name'] = $userInput['backup_mobile']);
        //终端商不需要审核直接通过
        if ($userInput['type'] == cons('user.type.retailer')) {
            $userInput['audit_status'] = cons('user.audit_status.pass');
        }
        $user = User::Create($userInput);
        if ($user->exists) {
            //商店
            $shopInput = $request->except('user_name', 'password', 'backup_mobile', 'type');

            !array_get($shopInput, 'contact_info') && ($shopInput['contact_info'] = $userInput['backup_mobile']);

            $shopModel = $user->shop();

            if ($user->type == cons('user.type.retailer')) {
                unset($shopInput['area']);
            }

            if ($shopModel->create($shopInput)->exists) {
                session(['shop.name' => $shopInput['name']]);
                $param = [
                    'account' => $userInput['user_name'],
                    'name' => $shopInput['name'],
                ];
                if ($user->type != cons('user.type.retailer')) {
                    app('pushbox.sms')->send('tip', cons('admin.phone'), $param);
                }
                //第三方绑定时绑定token
                $token = $request->input('token');
                if ($token) {
                    $user->userTokens()->create($token);
                    $request->session()->forget('socialite_token');
                }
                //默认添加店铺地址为收货地址
                $this->_addShippingAddress($shopInput, $user);
                return $this->success('注册成功');
            } else {
                $user->delete();
                return $this->error('注册用户时遇到问题');
            }
        }
        return $this->error('注册用户时遇到问题');
    }

    /**
     * 添加商户信息
     *
     * @param \App\Http\Requests\Api\v1\RegisterUserShopRequest $request
     *
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function postUserShop(RegisterUserShopRequest $request)
    {
        if (empty($request->input('user_name'))) {
            return $this->error('注册用户时遇到问题');
        }
        $user = User::where('user_name', $request->input('user_name'))->first();
        $shopInput = $request->except('username');
        $shopModel = $user->shop();

        if ($user->type == cons('user.type.retailer')) {
            unset($shopInput['area']);
        }

        if ($shopModel->create($shopInput)->exists) {
            return $this->success('注册成功');
        } else {
            $user->delete();
            return $this->error('注册用户时遇到问题');
        }
    }

    /**
     * 找回密码
     *
     * @param \App\Http\Requests\Api\v1\BackupPasswordRequest $request
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function postBackup(BackupPasswordRequest $request)
    {
        $data = $request->all();
        $user = User::with('shop')->where('user_name', $data['user_name'])->first();
        if (!$user || $user->backup_mobile != $data['backup_mobile']) {
            return $this->error('用户不存在或密保手机不正确');
        }
        //验证验证码
        $code = $request->input('code');
        if (!app('pushbox.sms')->verifyCode('code', $data['backup_mobile'], $code)) {
            return $this->error('短信验证码错误');
        }

        if ($user->fill(['password' => $data['password']])->save()) {
            return $this->success('修改密码成功');
        }
        return $this->success('修改密码时出现错误');
    }

    /**
     * 发送短信验证码
     *
     * @param \App\Http\Requests\Api\v1\BackupSendSmsRequest $request
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function postSendSms(BackupSendSmsRequest $request)
    {
        $data = $request->all();

        $user = User::with('shop')->where('user_name', $data['user_name'])->first();
        if ($user->status != cons('status.on')) {
            return $this->error('账号已锁定');
        }

        if ($user->audit_status != cons('user.audit_status.pass')) {
            return $this->error('账户未审核');
        }
        if (!$user || $user->backup_mobile != $data['backup_mobile']) {
            return $this->error('密保手机错误');
        }

        return app('pushbox.sms')->sendCode('code',
            $data['backup_mobile']) ? $this->success('发送成功') : $this->error('发送短信过于频繁');
    }

    /**
     * 退出登录
     *
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function getLogout()
    {
        auth()->logout();
        return $this->success();
    }

    /**
     *  打印上传数据
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function anyRequest(Request $request)
    {
        $result = $request->all();
        $result['has_file'] = count($request->file());
        return $result;
    }

    /**
     * 发送注册验证码
     *
     * @param \App\Http\Requests\Api\v1\RegisterUserSendSmsRequest $request
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function postRegSendSms(RegisterUserSendSmsRequest $request)
    {
        if (in_windows() && !$res = (new ValidateService)->validateGeetest($request)) {
            return $this->invalidParam('backup_mobile', '请完成验证');
        }
        if (app('pushbox.sms')->sendCode('register', $request->input('backup_mobile'))) {
            return $this->success('发送成功');
        } else {
            return $this->error('发送短信过于频繁');
        }

    }

    /**
     * 判断是否已绑定平台账号
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\Response|\WeiHeng\Responses\Apiv1Response
     */
    public function postUserToken(Request $request)
    {
        $token = $request->input('token');
        if (!$token) {
            return $this->error('token不能为空');
        }
        $types = cons('user.token.type');

        $type = $request->input('type');
        if (!$type || !array_key_exists($type, $types)) {
            return $this->error('type不能为空或不合法');
        }

        $userToken = UserToken::where(['type' => $types[$type], 'token' => $token])->with('user')->first();

        $appRoleType = $request->input('app_role_type');

        if (!$appRoleType) {
            return $this->error('登录失败');
        }

        if ($userToken) {
            $user = $userToken->user;

            if ($appRoleType == 'seller' && $user->type == cons('user.type.retailer')){
                return $this->error('登录失败，请使用买家app登录');
            }

            if ($appRoleType == 'buyer' && $user->type == cons('user.type.supplier')){
                return $this->error('登录失败，请使用卖家app登录');
            }
            return $this->_handleLogin($userToken);
        } else {
            return $this->success([]);
        }

    }

    /**
     * 默认添加店铺地址为收货地址
     *
     * @param $attribute
     * @param $user
     * @return bool
     */
    private function _addShippingAddress($attribute, User $user)
    {
        if ($user->type == cons('user.type.maker')) {
            return true;
        }

        $shippingAddressData = [
            'consigner' => $attribute['contact_person'],
            'phone' => $attribute['contact_info'],
            'is_default' => 1,
            'x_lng' => array_get($attribute, 'x_lng', 0),
            'y_lat' => array_get($attribute, 'y_lat', 0),
        ];

        $shippingAddress = $user->shippingAddress()->create($shippingAddressData);
        if ($shippingAddress->exists) {
            $shippingAddress->address()->create($attribute['address']);
        }
        return true;
    }

    /**
     * 处理登录
     *
     * @param $userToken
     * @return \Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    private function _handleLogin($userToken)
    {
        $request = app('request');
        $cookie = app('cookie');

        $user = $userToken->user;
        //是否已锁定
        if ($user->status != cons('status.on')) {
            return $this->error('账号已锁定');
        }
        //帐号审核
        if ($user->audit_status != cons('user.audit_status.pass')) {
            return $this->error('账户未审核或审核不通过');
        }

        //使用期限是否过期
        if ($user->is_expire) {
            return $this->error($user, '账户已到期，请续费');
        }

        $user->setVisible(['id', 'user_name', 'type', 'audit_status', 'backup_mobile', 'shop']);
        if (!is_null($user->shop)) {
            $user->shop->address_name = $user->shop->address;
        }

        $nowTime = Carbon::now();

        if ($user->fill(['last_login_at' => $nowTime, 'last_login_ip' => $request->ip()])->save()) {
            auth()->login($user, true);
            $cookie->queue('login_error', null, -1);
            $userToken->increment('login_count');
            return $this->success(['user' => $user]);
        }
        return $this->invalidParam('password', '登录失败，请重试');
    }
}