<?php

namespace App\Http\Controllers\Mobile;


use App\Http\Controllers\Auth\SocialiteController;
use Illuminate\Http\Request;

class WeixinAuthController extends SocialiteController
{

    protected $driver = 'weixin';

    /**
     * 绑定平台账号
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function bindSocialite()
    {
        $token = $this->getToken(session('socialite_token'));

        if (!array_get($token, 'token')) {
            return redirect('auth/login');
        }
        return view('mobile.auth.register-bind-socialite', compact('token'));
    }

    /**
     * 注册并绑定平台账号
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function regSocialite(Request $request)
    {
        $token = $this->getToken(session('socialite_token'));
        if (!array_get($token, 'token')) {
            return redirect('auth/login');
        }
        return view('mobile.auth.register-reg-socialite', compact('token'));
    }

    /**
     * 处理返回
     *
     * @param $user
     * @param null $error
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Symfony\Component\HttpFoundation\Response
     */
    public function handleLoginResponse($user, $error = null)
    {
        if ($error) {
            dd($error);
        } else {
            return redirect('/');
        }

    }
}
