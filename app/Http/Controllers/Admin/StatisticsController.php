<?php

namespace App\Http\Controllers\Admin;

use App\Models\DataStatistics;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use App\Services\DataStatisticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\DB;

class StatisticsController extends Controller
{
    /**
     * Display a listing of the resource
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getIndex(Request $request)
    {
        $startTime = new Carbon($request->input('start_time'));
        $endTime = new Carbon($request->input('end_time'));

        $dayEnd = $endTime->copy()->endOfDay();
        $dayStart = $startTime->copy()->startOfDay();
        $nowTime = Carbon::now();

        /**
         * 总用户数
         *
         * $totalUser = User::select(DB::raw('count(*) as num , type'))->whereBetween('created_at',
         * [$dayStart, $dayEnd])->groupBy('type')->lists('num', 'type');
         */
        if ($dayStart == $nowTime->startOfDay()) {
            $statistics = DataStatisticsService::getTodayDataStatistics($nowTime->copy()->addDay(), false);
        } else {
            //登录数
            $statistics = DataStatistics::whereBetween('created_at', [
                $dayStart,
                $dayEnd
            ])->select(DB::raw(
                'sum(`retailer_login_num`) as retailer_login_num
                ,sum(`wholesaler_login_num`) as wholesaler_login_num
                ,sum(`supplier_login_num`) as supplier_login_num
                '))->first();

            $userType = cons('user.type');
            //注册数
            $regCount = User::select(DB::raw('count(*) as num,type'))->whereBetween('created_at',
                [$dayStart, $dayEnd])->groupBy('type')->lists('num', 'type');
            $statistics->wholesaler_reg_num = array_get($regCount, array_get($userType, 'wholesaler'), 0);
            $statistics->supplier_reg_num = array_get($regCount, array_get($userType, 'supplier'), 0);
            $statistics->retailer_reg_num = array_get($regCount, array_get($userType, 'retailer'), 0);

        }

        //历史最高注册数和登录数

        /**
         * 'wholesaler' => 1,       //批发商
         * 'retailer' => 2,       //零售商
         * 'supplier' => 3,
         */

        $dataStatistics = DataStatistics::whereBetween('created_at', [$dayStart, $dayEnd])->get();

        $maxArray = [
            'max_wholesaler_login_num' => $dataStatistics->sortByDesc('wholesaler_login_num')->first(),
            'max_retailer_login_num' => $dataStatistics->sortByDesc('retailer_login_num')->first(),
            'max_supplier_login_num' => $dataStatistics->sortByDesc('supplier_login_num')->first(),
            'max_wholesaler_reg_num' => $dataStatistics->sortByDesc('wholesaler_reg_num')->first(),
            'max_retailer_reg_num' => $dataStatistics->sortByDesc('retailer_reg_num')->first(),
            'max_supplier_reg_num' => $dataStatistics->sortByDesc('supplier_reg_num')->first(),
        ];

        //订单统计
        $orders = Order::whereBetween('created_at', [$dayStart, $dayEnd])->where('is_cancel', cons('order.is_cancel.off'))->get();

        $orderEveryday = []; //当日订单统计

        if (!$orders->isEmpty()) {

            $userIds = $orders->pluck('user_id')->all();

            $users = User::whereIn('id', array_unique($userIds))->lists('type', 'id');  // 买家类型

            $userType = cons('user.type');
            $payType = cons('pay_type');

            foreach ($orders as $order) {
                //买家
                if (!$order->user_id || $users[$order->user_id] == $userType['retailer']) {
                    //终端
                    $orderEveryday['retailer']['count'] = isset($orderEveryday['retailer']['count']) ? ++$orderEveryday['retailer']['count'] : 1;
                    $orderEveryday['retailer']['amount'] = isset($orderEveryday['retailer']['amount']) ? bcadd($orderEveryday['retailer']['amount'],
                        $order->price, 2) : $order->price;
                    if ($order->pay_type == $payType['online']) {
                        //线上总金额
                        $orderEveryday['retailer']['onlineAmount'] = isset($orderEveryday['retailer']['onlineAmount']) ? bcadd($orderEveryday['retailer']['onlineAmount'],
                            $order->price, 2) : $order->price;
                    } else {
                        //线下总金额
                        $orderEveryday['retailer']['codAmount'] = isset($orderEveryday['retailer']['codAmount']) ? bcadd($orderEveryday['retailer']['codAmount'],
                            $order->price, 2) : $order->price;
                    }

                } elseif ($users[$order->user_id] == $userType['wholesaler']) {
                    //批发
                    $orderEveryday['wholesaler']['count'] = isset($orderEveryday['wholesaler']['count']) ? ++$orderEveryday['wholesaler']['count'] : 1;
                    $orderEveryday['wholesaler']['amount'] = isset($orderEveryday['wholesaler']['amount']) ? bcadd($orderEveryday['wholesaler']['amount'],
                        $order->price, 2) : $order->price;
                    if ($order->pay_type == $payType['online']) {
                        //线上总金额
                        $orderEveryday['wholesaler']['onlineAmount'] = isset($orderEveryday['wholesaler']['onlineAmount']) ? bcadd($orderEveryday['wholesaler']['onlineAmount'],
                            $order->price, 2) : $order->price;

                    } else {
                        //线下总金额
                        $orderEveryday['wholesaler']['codAmount'] = isset($orderEveryday['wholesaler']['codAmount']) ? bcadd($orderEveryday['wholesaler']['codAmount'],
                            $order->price, 2) : $order->price;
                    }
                }
            }
        }


        /*
        * 每日成单统计
        */
        $completeOrders = Order::whereBetween('finished_at',
            [$dayStart, $dayEnd])->with('systemTradeInfo')->where('is_cancel',
            cons('order.is_cancel.off'))->get();

        $orderSellerEveryday = [];  //对于卖家每日统计

        if (!$completeOrders->isEmpty()) {
            $userIds = $completeOrders->pluck('user_id')->all();
            $users = User::whereIn('id', array_unique($userIds))->lists('type', 'id');  // 买家类型

            $shopIds = $completeOrders->pluck('shop_id')->all();
            $shops = Shop::whereIn('id', array_unique($shopIds))->with('user')->get(['id', 'user_id']);
            $sellerTypes = [];                                                          // 卖家类型
            foreach ($shops as $shop) {
                $sellerTypes[$shop->id] = $shop->user_type;
            }

            $userType = cons('user.type');
            $payType = cons('pay_type');

            foreach ($completeOrders as $order) {
                if ($sellerTypes[$order->shop_id] == $userType['wholesaler']) {
                    //批发商
                    //成单数
                    $orderSellerEveryday['wholesaler']['count'] = isset($orderSellerEveryday['wholesaler']['count']) ? ++$orderSellerEveryday['wholesaler']['count'] : 1;

                    if ($order->created_at >= $dayStart) {
                        //当天下单成单数
                        $orderSellerEveryday['wholesaler']['countToday'] = isset($orderSellerEveryday['wholesaler']['countToday']) ? ++$orderSellerEveryday['wholesaler']['countToday'] : 1;
                        //当天下单成单总额
                        $orderSellerEveryday['wholesaler']['amountToday'] = isset($orderSellerEveryday['wholesaler']['amountToday']) ? bcadd($orderSellerEveryday['wholesaler']['amountToday'],
                            $order->price, 2) : $order->price;
                    }

                    //成单总金额
                    $orderSellerEveryday['wholesaler']['amount'] = isset($orderSellerEveryday['wholesaler']['amount']) ? bcadd($orderSellerEveryday['wholesaler']['amount'],
                        $order->price, 2) : $order->price;
                    if ($order->pay_type == $payType['online']) {
                        // 线上完成总金额
                        $orderSellerEveryday['wholesaler']['onlineSuccessAmount'] = isset($orderSellerEveryday['wholesaler']['onlineSuccessAmount']) ? bcadd($orderSellerEveryday['wholesaler']['onlineSuccessAmount'],
                            $order->price, 2) : $order->price;
                        //线上完成总金额当天下单并完成的金额
                        if ($order->created_at >= $dayStart) {
                            $orderSellerEveryday['wholesaler']['onlineSuccessAmountToday'] = isset($orderSellerEveryday['wholesaler']['onlineSuccessAmountToday']) ? bcadd($orderSellerEveryday['wholesaler']['onlineSuccessAmountToday'],
                                $order->price, 2) : $order->price;
                        }

                    } else {
                        //线下完成总金额
                        $orderSellerEveryday['wholesaler']['codSuccessAmount'] = isset($orderSellerEveryday['wholesaler']['codSuccessAmount']) ? bcadd($orderSellerEveryday['wholesaler']['codSuccessAmount'],
                            $order->price, 2) : $order->price;
                        //线下完成总金额当天下单并完成的金额
                        if ($order->created_at >= $dayStart) {
                            $orderSellerEveryday['wholesaler']['codSuccessAmountToday'] = isset($orderSellerEveryday['wholesaler']['codSuccessAmountToday']) ? bcadd($orderSellerEveryday['wholesaler']['codSuccessAmountToday'],
                                $order->price, 2) : $order->price;
                        }
                        //线下pos机完成总金额
                        if ($order->systemTradeInfo && $order->systemTradeInfo->pay_type == cons('trade.pay_type.pos')) {
                            $orderSellerEveryday['wholesaler']['posSuccessAmount'] = isset($orderSellerEveryday['wholesaler']['posSuccessAmount']) ? bcadd($orderSellerEveryday['wholesaler']['posSuccessAmount'],
                                $order->price, 2) : $order->price;
                            //线下pos机完成总金额当天下单并完成的金额
                            if ($order->created_at >= $dayStart) {
                                $orderSellerEveryday['wholesaler']['posSuccessAmountToday'] = isset($orderSellerEveryday['wholesaler']['posSuccessAmountToday']) ? bcadd($orderSellerEveryday['wholesaler']['posSuccessAmountToday'],
                                    $order->price, 2) : $order->price;
                            }
                        }
                    }
                } elseif ($sellerTypes[$order->shop_id] == $userType['supplier']) {
                    //供应商
                    if ($order->user_id && $users[$order->user_id] == $userType['wholesaler']) {
                        //对于批发商
                        //成单数
                        $orderSellerEveryday['supplier']['wholesaler']['count'] = isset($orderSellerEveryday['supplier']['wholesaler']['count']) ? ++$orderSellerEveryday['supplier']['wholesaler']['count'] : 1;
                        //成单总金额
                        $orderSellerEveryday['supplier']['wholesaler']['amount'] = isset($orderSellerEveryday['supplier']['wholesaler']['amount']) ? bcadd($orderSellerEveryday['supplier']['wholesaler']['amount'],
                            $order->price, 2) : $order->price;
                        if ($order->created_at >= $dayStart) {
                            //成单数当天下单
                            $orderSellerEveryday['supplier']['wholesaler']['countToday'] = isset($orderSellerEveryday['supplier']['wholesaler']['countToday']) ? ++$orderSellerEveryday['supplier']['wholesaler']['countToday'] : 1;
                            //成单总金额当天下单
                            $orderSellerEveryday['supplier']['wholesaler']['amountToday'] = isset($orderSellerEveryday['supplier']['wholesaler']['amountToday']) ? bcadd($orderSellerEveryday['supplier']['wholesaler']['amountToday'],
                                $order->price, 2) : $order->price;
                        }
                        if ($order->pay_type == $payType['online']) {
                            //线上成单总金额
                            $orderSellerEveryday['supplier']['wholesaler']['onlineSuccessAmount'] = isset($orderSellerEveryday['supplier']['wholesaler']['onlineSuccessAmount']) ? bcadd($orderSellerEveryday['supplier']['wholesaler']['onlineSuccessAmount'],
                                $order->price, 2) : $order->price;
                            //线上完成总金额当天下单并完成的金额
                            if ($order->created_at >= $dayStart) {
                                $orderSellerEveryday['supplier']['wholesaler']['onlineSuccessAmountToday'] = isset($orderSellerEveryday['supplier']['wholesaler']['onlineSuccessAmountToday']) ? bcadd($orderSellerEveryday['supplier']['wholesaler']['onlineSuccessAmountToday'],
                                    $order->price, 2) : $order->price;
                            }

                        } else {
                            //线下成单总金额
                            $orderSellerEveryday['supplier']['wholesaler']['codSuccessAmount'] = isset($orderSellerEveryday['supplier']['wholesaler']['codSuccessAmount']) ? bcadd($orderSellerEveryday['supplier']['wholesaler']['codSuccessAmount'],
                                $order->price, 2) : $order->price;
                            //线下完成总金额当天下单并完成的金额
                            if ($order->created_at >= $dayStart) {
                                $orderSellerEveryday['supplier']['wholesaler']['codSuccessAmountToday'] = isset($orderSellerEveryday['supplier']['wholesaler']['codSuccessAmountToday']) ? bcadd($orderSellerEveryday['supplier']['wholesaler']['codSuccessAmountToday'],
                                    $order->price, 2) : $order->price;
                            }

                            //线下pos机成单总金额
                            if ($order->systemTradeInfo && $order->systemTradeInfo->pay_type == cons('trade.pay_type.pos')) {
                                $orderSellerEveryday['supplier']['wholesaler']['posSuccessAmount'] = isset($orderSellerEveryday['supplier']['wholesaler']['posSuccessAmount']) ? bcadd($orderSellerEveryday['supplier']['wholesaler']['posSuccessAmount'],
                                    $order->price, 2) : $order->price;
                                //线下pos机完成总金额当天下单并完成的金额
                                if ($order->created_at >= $dayStart) {
                                    $orderSellerEveryday['supplier']['wholesaler']['posSuccessAmountToday'] = isset($orderSellerEveryday['supplier']['wholesaler']['posSuccessAmountToday']) ? bcadd($orderSellerEveryday['supplier']['wholesaler']['posSuccessAmountToday'],
                                        $order->price, 2) : $order->price;
                                }
                            }
                        }
                    } else {
                        //对于终端商
                        //成单数
                        $orderSellerEveryday['supplier']['retailer']['count'] = isset($orderSellerEveryday['supplier']['retailer']['count']) ? ++$orderSellerEveryday['supplier']['retailer']['count'] : 1;
                        //成单总金额
                        $orderSellerEveryday['supplier']['retailer']['amount'] = isset($orderSellerEveryday['supplier']['retailer']['amount']) ? bcadd($orderSellerEveryday['supplier']['retailer']['amount'],
                            $order->price, 2) : $order->price;
                        if ($order->created_at >= $dayStart) {
                            //成单数当日下单
                            $orderSellerEveryday['supplier']['retailer']['countToday'] = isset($orderSellerEveryday['supplier']['retailer']['countToday']) ? ++$orderSellerEveryday['supplier']['retailer']['countToday'] : 1;
                            //成单总金额当日下单
                            $orderSellerEveryday['supplier']['retailer']['amountToday'] = isset($orderSellerEveryday['supplier']['retailer']['amountToday']) ? bcadd($orderSellerEveryday['supplier']['retailer']['amountToday'],
                                $order->price, 2) : $order->price;
                        }
                        if ($order->pay_type == $payType['online']) {
                            //线上完成总金额
                            $orderSellerEveryday['supplier']['retailer']['onlineSuccessAmount'] = isset($orderSellerEveryday['supplier']['retailer']['onlineSuccessAmount']) ? bcadd($orderSellerEveryday['supplier']['retailer']['onlineSuccessAmount'],
                                $order->price, 2) : $order->price;
                            //线上完成总金额当天下单并当天完成的金额
                            if ($order->created_at >= $dayStart) {
                                $orderSellerEveryday['supplier']['retailer']['onlineSuccessAmountToday'] = isset($orderSellerEveryday['supplier']['retailer']['onlineSuccessAmountToday']) ? bcadd($orderSellerEveryday['supplier']['retailer']['onlineSuccessAmountToday'],
                                    $order->price, 2) : $order->price;
                            }
                        } else {
                            //线下完成总金额
                            $orderSellerEveryday['supplier']['retailer']['codSuccessAmount'] = isset($orderSellerEveryday['supplier']['retailer']['codSuccessAmount']) ? bcadd($orderSellerEveryday['supplier']['retailer']['codSuccessAmount'],
                                $order->price, 2) : $order->price;
                            //线下完成总金额当天下单并当天完成的金额
                            if ($order->created_at >= $dayStart) {
                                $orderSellerEveryday['supplier']['retailer']['codSuccessAmountToday'] = isset($orderSellerEveryday['supplier']['retailer']['codSuccessAmountToday']) ? bcadd($orderSellerEveryday['supplier']['retailer']['codSuccessAmountToday'],
                                    $order->price, 2) : $order->price;
                            }
                            //线下pos机完成总金额
                            if ($order->systemTradeInfo && $order->systemTradeInfo->pay_type == cons('trade.pay_type.pos')) {
                                $orderSellerEveryday['supplier']['retailer']['posSuccessAmount'] = isset($orderSellerEveryday['supplier']['retailer']['posSuccessAmount']) ? bcadd($orderSellerEveryday['supplier']['retailer']['posSuccessAmount'],
                                    $order->price, 2) : $order->price;
                                //线下pos机完成总金额当天下单并当天完成的金额
                                if ($order->created_at >= $dayStart) {
                                    $orderSellerEveryday['supplier']['retailer']['posSuccessAmountToday'] = isset($orderSellerEveryday['supplier']['retailer']['posSuccessAmountToday']) ? bcadd($orderSellerEveryday['supplier']['retailer']['posSuccessAmountToday'],
                                        $order->price, 2) : $order->price;
                                }
                            }
                        }
                    }
                }
            }
        }

        return view('admin.statistics.statistics',
            [
                'statistics' => $statistics,
                'maxArray' => $maxArray,
                'startTime' => $dayStart,
                'endTime' => $dayEnd,
                'orderEveryday' => $orderEveryday,
                'orderSellerEveryday' => $orderSellerEveryday
            ]);
    }
}
