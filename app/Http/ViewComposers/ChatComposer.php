<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2015/8/25
 * Time: 20:51
 */
namespace App\Http\ViewComposers;

use Illuminate\Contracts\View\View;

class ChatComposer
{
    /**
     * Bind data to the view.
     *
     * @param  View $view
     * @return void
     */
    public function compose(View $view)
    {
        $pushConf = config('push.im');
        $config = [
            'pwd' => $pushConf['message_password'],
            'key' => $pushConf['app_key']
        ];
        $view->with('chatConf', $config);
    }

}
