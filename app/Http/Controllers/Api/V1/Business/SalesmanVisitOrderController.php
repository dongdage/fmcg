<?php

namespace App\Http\Controllers\Api\V1\Business;


use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\v1\UpdateSalesmanVisitOrderGoodsRequest;
use App\Models\Order;
use App\Models\OrderGoods;
use App\Models\SalesmanVisitOrder;
use App\Models\SalesmanVisitOrderGoods;
use App\Services\BusinessService;
use App\Services\ShippingAddressService;
use App\Services\ShopService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Gate;
use DB;

class SalesmanVisitOrderController extends Controller
{

    /**
     * get orderForms by salesmanId
     *
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function orderForms()
    {
        $salesmenId = salesman_auth()->id();
        $orders = (new BusinessService())->getOrders($salesmenId, cons('salesman.order.type.order'));
        return $this->success(['orders' => $orders]);
    }

    /**
     * get returnOrders by salesmanId
     *
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function returnOrders()
    {
        $salesmenId = salesman_auth()->id();
        $orders = (new BusinessService())->getOrders($salesmenId, cons('salesman.order.type.return_order'));
        return $this->success(['orders' => $orders]);
    }
    
    /**
     * 订单操作
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\SalesmanVisitOrder $salesmanVisitOrder
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function update(Request $request, SalesmanVisitOrder $salesmanVisitOrder)
    {
        if (Gate::denies('validate-salesman-order', $salesmanVisitOrder)) {
            return $this->error('订单不存在');
        }
        $attributes = $request->all();
        return $salesmanVisitOrder->fill($attributes)->save() ? $this->success('操作成功') : $this->error('订单不存在');
    }

    /**
     * 订单商品修改
     *
     * @param \App\Http\Requests\Api\v1\UpdateSalesmanVisitOrderGoodsRequest $request
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function updateOrderGoods(UpdateSalesmanVisitOrderGoodsRequest $request)
    {
        $goodsId = $request->input('id');
        $orderGoods = SalesmanVisitOrderGoods::with('salesmanVisitOrder')->find($goodsId);
        $salesmanVisitOrder = $orderGoods->salesmanVisitOrder;
        if (is_null($orderGoods) || Gate::denies('validate-salesman-order', $orderGoods->salesmanVisitOrder)) {
            return $this->error('订单不存在');
        }

        $goodsTypes = cons('salesman.order.goods.type');


        $result = DB::transaction(function () use ($orderGoods, $goodsTypes, $request, $salesmanVisitOrder) {
            $attributes = [];
            //商品原总金额
            $goodsOldAmount = $orderGoods->amount;

            if ($orderGoods->type == $goodsTypes['order']) {
                //订单
                $attributes['price'] = $request->input('price');
                $attributes['num'] = $request->input('num');
                $attributes['pieces'] = $request->input('pieces');
                $attributes['amount'] = $attributes['price'] * intval($attributes['num']);
                if ($orderGoods->fill($attributes)->save()) {
                    $salesmanVisitOrder->fill(['amount' => $salesmanVisitOrder->amount - $goodsOldAmount + $attributes['amount']])->save();
                }
            } elseif ($orderGoods->type == $goodsTypes['return']) {
                //退货单
                $attributes['num'] = $request->input('num');
                $attributes['amount'] = $request->input('amount');
                if ($orderGoods->fill($attributes)->save()) {
                    $salesmanVisitOrder->fill(['amount' => $salesmanVisitOrder->amount - $goodsOldAmount + $attributes['amount']])->save();
                }
            } else {
                //货抵
                $attributes['num'] = $request->input('num');
                $orderGoods->fill($attributes)->save();
            }
            return 'success';
        });

        return $result === 'success' ? $this->success('修改成功') : $this->error('修改订单时出现问题');
    }

    /**
     * 订单批量通过
     *
     * @param \Illuminate\Http\Request $request
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function batchPass(Request $request)
    {
        $orderIds = $request->input('order_id');

        if (empty($orderIds)) {
            return $this->error('请选择要通过的订单');
        }
        $orders = SalesmanVisitOrder::whereIn('id', $orderIds)->get();
        if (Gate::denies('validate-salesman-order', $orders)) {
            return $this->error('存在不合法订单');
        }
        return SalesmanVisitOrder::whereIn('id',
            $orderIds)->update(['status' => cons('salesman.order.status.passed')]) ? $this->success('操作成功') : $this->error('操作失败，请重试');
    }

    /**
     * 订单同步
     *
     * @param \App\Models\SalesmanVisitOrder $salesmanVisitOrder
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function sync(SalesmanVisitOrder $salesmanVisitOrder)
    {
        if (Gate::denies('validate-salesman-order', $salesmanVisitOrder)) {
            return $this->error('存在不合法订单');
        }
        return $this->_syncOrders([$salesmanVisitOrder]);
    }

    /**
     * 订单批量同步
     *
     * @param \Illuminate\Http\Request $request
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function batchSync(Request $request)
    {
        $orderIds = $request->input('order_id');
        if (is_null($orderIds)) {
            return $this->error('请选择要同步的订单');
        }
        $orders = SalesmanVisitOrder::whereIn('id', $orderIds)->with('orderFormGoods')->get();

        if (Gate::denies('validate-salesman-order', $orders)) {
            return $this->error('存在不合法订单');
        }
        return $this->_syncOrders($orders);
    }

    /**
     * 同步订单
     *
     * @param $orders
     * @return \WeiHeng\Responses\Apiv1Response
     */
    private function _syncOrders($orders)
    {
        $result = DB::transaction(function () use ($orders) {
            $syncConf = cons('salesman.order.sync');
            $orderConf = cons('order');
            $shippingAddressService = new ShippingAddressService();
            foreach ($orders as $order) {
                if (!$order->can_sync) {
                    return ['error' => '同步失败：存在不能同步的订单'];
                }
                $shippingAddress = ShopService::getDefaultShippingAddress($order->customer_shop_id);
                $orderData = [
                    'user_id' => $order->customer_shop_id,
                    'shop_id' => auth()->user()->shop->id,
                    'price' => $order->amount,
                    'pay_type' => $syncConf['pay_type'],
                    'pay_way' => $syncConf['pay_way'],
                    'pay_status' => $orderConf['pay_status']['payment_success'],
                    'status' => $orderConf['status']['finished'],
                    'finished_at' => Carbon::now(),
                    'shipping_address_id' => $shippingAddressService->copyToSnapshot($shippingAddress->id),
                    'remark' => '业务同步订单'
                ];
                if (!$orderData['shipping_address_id']) {
                    return ['error' => '该客户平台id不存在默认收货地址'];
                }
                $orderTemp = Order::create($orderData);
                if ($orderTemp->exists) {//添加订单成功,修改orderGoods中间表信息
                    $orderGoods = [];
                    foreach ($order->orderFormGoods as $goods) {
                        $orderGoods[] = new OrderGoods([
                            'goods_id' => $goods->goods_id,
                            'price' => $goods->price,
                            'num' => $goods->num,
                            'pieces' => $goods->pieces,
                            'total_price' => $goods->amount,
                        ]);
                    }

                    if (!$orderTemp->orderGoods()->saveMany($orderGoods)) {
                        return ['error' => '同步时出现错误，请重试'];
                    }

                } else {
                    return ['error' => '同步时出现错误，请重试'];
                }
                if (!$order->fill(['is_synced' => cons('salesman.order.is_synced.synced')])->save()) {
                    return ['error' => '同步时出现错误，请重试'];
                }

            }

            return 'success';
        });

        return $result === 'success' ? $this->success('同步成功') : $this->error($result['error']);

    }
}
