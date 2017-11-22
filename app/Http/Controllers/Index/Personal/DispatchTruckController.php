<?php

namespace App\Http\Controllers\Index\Personal;

use App\Http\Controllers\Index\Controller;
use App\Models\DispatchTruck;
use App\Services\DispatchTruckService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DispatchTruckController extends Controller
{
    /**
     * 列表
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getIndex(Request $request)
    {
        $user = auth()->user();
        $data = $request->all();
        $carbon = new Carbon();
        $data['start_at'] = array_get($data, 'start_at') ? array_get($data, 'start_at') : $carbon->subDay(7)->startOfDay()->toDateString();
        $data['end_at'] = array_get($data, 'end_at') ? array_get($data, 'end_at') : $carbon->now()->toDateString();
        $dispatchTrucks = $user->shop->dispatchTrucks()->with([
            'orders',
            'truck'
        ])->orderBy('dispatch_time','DESC')->condition($data)->paginate(20);
        $deliveryMans = $user->shop->deliveryMans()->active()->get();
        return view('index.personal.dispatch-truck-index', compact('dispatchTrucks', 'deliveryMans', 'data'));
    }

    public function getDetail($dispatchTruckId)
    {
        $dispatchTruck = DispatchTruck::with('orders', 'truck')->find($dispatchTruckId);
        //未收总额
        $dispatchTruck->unpaidAmount = bcadd($dispatchTruck->orders->filter(function ($order) {
            return $order->pay_status != cons('order.pay_status.payment_success');
        })->sum('price'), 0, 2);
        //已收总额
        $dispatchTruck->alreadyPaidAmount = bcadd($dispatchTruck->orders->filter(function ($order) {
            return $order->pay_status == cons('order.pay_status.payment_success');
        })->sum('price'), 0, 2);

        $dispatchTruck->order_goods_statis = DispatchTruckService::goodsStatistical($dispatchTruck->orders);
        $dispatchTruck->return_order_goods_statis = DispatchTruckService::returnOrderGoodsStatistical($dispatchTruck->returnOrders ?? []);
        return view('index.personal.dispatch-truck-detail', compact('dispatchTruck'));
    }
}