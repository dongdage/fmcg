<?php

namespace App\Http\Controllers\Api\V1\Business;


use App\Http\Controllers\Api\V1\Controller;
use App\Http\Requests\Api\v1\DeleteMortgageGoodsRequest;
use App\Http\Requests\Api\v1\UpdateSalesmanVisitOrderGoodsRequest;
use App\Models\Order;
use App\Models\OrderGoods;
use App\Models\PromoApply;
use App\Models\SalesmanVisitOrder;
use App\Models\SalesmanVisitOrderGoods;
use App\Models\SalesmanCustomer;
use App\Models\ConfirmOrderDetail;
use App\Services\BusinessService;
use App\Services\OrderService;
use App\Services\ShippingAddressService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Gate;
use DB;

class SalesmanVisitOrderController extends Controller
{
    private $errMsg = null;

    /**
     * get orderForms by salesmanId
     *
     * @param \Illuminate\Http\Request $request
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function orderForms(Request $request)
    {
        $salesmenId = salesman_auth()->id();

        $data = $request->only(['status', 'start_date', 'end_date', 'customer']);
        $data = array_merge($data, ['type' => cons('salesman.order.type.order')]);
        $orders = (new BusinessService())->getOrders([$salesmenId], $data,
            ['salesmanCustomer.businessAddress', 'salesman', 'order', 'gifts', 'order.coupon', 'displayList']);
        return $this->success([
            'orders' => $orders->toArray()
        ]);
    }

    /**
     * get returnOrders by salesmanId
     *
     * @param \Illuminate\Http\Request $request
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function returnOrders(Request $request)
    {
        $salesmenId = salesman_auth()->id();

        $data = $request->only(['status', 'start_date', 'end_date', 'customer']);
        $data = array_merge($data, ['type' => cons('salesman.order.type.return_order')]);

        $orders = (new BusinessService())->getOrders([$salesmenId], $data);
        return $this->success(['orders' => $orders->toArray()]);
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
        if ($salesmanVisitOrder->orderGoods->isEmpty()) {
            return $this->error('操作失败:订货商品不能为空');
        }
        $attributes = $request->except('salesman_id', 'start_date', 'end_date', 'order_id');

        if ($salesmanVisitOrder->can_sync && isset($attributes['status'])) {
            $this->_updateDisplay([$salesmanVisitOrder]);
            $this->_syncOrders([$salesmanVisitOrder]);
        }

        return $salesmanVisitOrder->fill($attributes)->save() ? $this->success('操作成功') : $this->error('订单不存在');
    }

    /**
     * 修改订单陈列费
     *
     * @param \Illuminate\Http\Request $request
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function updateOrderDisplayFee(Request $request)
    {
        $orderId = $request->input('order_id');

        $salesmanVisitOrder = $this->_validateOrder($orderId);

        if (!$salesmanVisitOrder) {
            return $this->error('订单不存在');
        }

        $displayId = $request->input('id');

        $display = $salesmanVisitOrder->displayFees()->find($displayId);
        if (is_null($display)) {
            return $this->error('陈列费不存在');
        }

        $displayFee = (float)$request->input('display_fee', 0);
        if ($displayFee <= 0) {
            return $this->error('陈列费必须大于0');
        }
        //修改后的陈列费总金额
        $sumDisplayFee = bcadd(bcsub($salesmanVisitOrder->displayFees()->sum('used'), $display->used, 2), $displayFee,
            2);

        if ($sumDisplayFee >= $salesmanVisitOrder->amount) {
            return $this->error('陈列费不能大于订单金额');
        }
        $customerSurplusFee = (new BusinessService())->canSetDisplayFee($salesmanVisitOrder->salesmanCustomer,
            $display->month, $orderId);
        if ($displayFee > bcsub($customerSurplusFee['surplus'], $customerSurplusFee['nonConfirm'], 2)) {
            return $this->error('陈列费不能大于该月剩余');
        }

        return $display->fill(['used' => $displayFee])->save() ? $this->success('陈列费修改成功' . $sumDisplayFee) : $this->error('修改陈列费时遇到问题');

    }

    /**
     * 订单商品修改
     *
     * @param \App\Http\Requests\Api\v1\UpdateSalesmanVisitOrderGoodsRequest $request
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function updateOrderGoods(UpdateSalesmanVisitOrderGoodsRequest $request)
    {
        if ($orderId = $request->input('order_id')) {
            $salesmanVisitOrder = SalesmanVisitOrder::find($orderId);

            $result = $this->_updateOrderGoods($salesmanVisitOrder, $request);

        } else {
            $goodsId = $request->input('id');
            $orderGoods = SalesmanVisitOrderGoods::with('salesmanVisitOrder')->find($goodsId);
            if (is_null($orderGoods)) {
                return $this->error('订单不存在或订单信息已改变，请重新刷新');
            }
            $salesmanVisitOrder = $orderGoods->salesmanVisitOrder;
            //如果有陈列费 验证订单金额不能大于陈列金额
            if ($salesmanVisitOrder->displayFees()) {
                $orderAmount = bcadd(bcsub($salesmanVisitOrder->amount, $orderGoods->amount, 2),
                    bcmul($request->input('num'), $request->input('price'), 2), 2);
                $displayFeeAmount = $salesmanVisitOrder->displayFees()->sum('used');
                if ($displayFeeAmount >= $orderAmount) {
                    return $this->error('订单金额不能小于陈列费金额');
                }
            }

            $result = $this->_updateOrderGoods($salesmanVisitOrder, $request, $orderGoods);
        }

        return $result === 'success' ? $this->success('修改成功') : $this->error(is_string($result) ? $result : '修改订单时出现问题');
    }


    /**
     * 确认订单完成
     *
     * @param $orderId
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function orderComplete($orderId)
    {
        $order = Order::with('goods')->useful()->find($orderId);

        if (is_null($order) || Gate::forUser(salesman_auth()->user())->denies('validate-order', $order)) {
            return $this->error('订单不存在');
        }
        if (!$order->can_confirm_collections) {
            return $this->error('此订单不支持现金付款');
        }

        $result = new OrderService();

        return $result->orderComplete($order) ? $this->success('确认订单完成') : $this->error($result->getError());
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

        $orders = SalesmanVisitOrder::whereIn('id', $orderIds)->with('salesmanCustomer')->get();

        if (Gate::denies('validate-salesman-order', $orders)) {
            return $this->error('存在不合法订单');
        }

        // 订货单才同步
        $result = $orders->sum('type') == 0 ? $this->_syncOrders($orders) : 'success';
        if ($result == 'success' && SalesmanVisitOrder::whereIn('id',
                $orderIds)->update(['status' => cons('salesman.order.status.passed')])
        ) {
            $this->_updateDisplay($orders);
            return $this->success('操作成功');
        }

        return $this->error('操作失败，请重试');

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

        $result = $this->_syncOrders([$salesmanVisitOrder]);

        return $result === 'success' ? $this->success('同步成功') : $this->error($result['error']);
    }

    /**
     * 订单批量同步
     *
     * @param \Illuminate\Http\Request $request
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function batchSync(Request $request)
    {
        $orderIds = $request->input('order_id', null);
        if (is_null($orderIds)) {
            return $this->error('请选择要同步的订单');
        }
        $orders = SalesmanVisitOrder::whereIn('id', $orderIds)->with('orderGoods', 'salesmanCustomer')->get();

        if (Gate::denies('validate-salesman-order', $orders)) {
            return $this->error('存在不合法订单');
        }
        $result = $this->_syncOrders($orders);

        return $result === 'success' ? $this->success('同步成功') : $this->error($result['error']);
    }

    /**
     * 获取订货单信息
     *
     * @param $id
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function orderDetail($id)
    {
        $order = SalesmanVisitOrder::with([
            'orderGoods.goods' => function ($query) {
                $query->select('id', 'name', 'price_wholesaler', 'price_retailer', 'pieces_wholesaler',
                    'pieces_retailer');
            },
            'orderGoods.goods.goodsPieces' => function ($query) {
                $query->select('pieces_level_1', 'pieces_level_2', 'pieces_level_3', 'goods_id');
            },
            'displayList.mortgageGoods',
            'gifts' => function ($query) {
                $query->select('bar_code', 'id', 'name');
            },
            'gifts.goodsPieces'
        ])->find($id);
        if (is_null($order)) {
            return $this->error('订单不存在');
        }

        if ($order->type == cons('salesman.order.type.order')) {
            if (!$order->displayList->isEmpty()) {
                foreach ($order->displayList as $item) {
                    if ($item->mortgage_goods_id == 0) {
                        $displayFee[] = [
                            'month' => $item->month,
                            'created_at' => (string)$item->created_at,
                            'display_fee' => $item->used
                        ];
                    } else {
                        $month = $item->month;
                        $mortgageGoods = $item->mortgageGoods;
                        $mortgage[$month][] = [
                            'id' => $item->mortgage_goods_id,
                            'name' => $mortgageGoods->goods_name,
                            'pieces' => $mortgageGoods->pieces,
                            'num' => (int)$item->used,
                            'month' => $item->month,
                            'created_at' => (string)$item->created_at
                        ];

                    }
                }
                if (isset($displayFee)) {
                    $order->displayFee = $displayFee;
                }
                if (isset($mortgage)) {
                    $order->mortgage = $mortgage;
                }
            }

            if (isset($order->applyPromo)) {
                $order->setHidden(['applyPromo']);
                $order->promo = $order->applyPromo->promo->load(['condition', 'rebate']);
            }

            $order->load('order.coupon', 'order.deliveryMan');
            $order->setAppends(['after_rebates_price', 'order_status_name']);
        }

        return $this->success(compact('order'));
    }

    /**
     * 更新订单所有内容 （删除后添加）
     *
     * @param \Illuminate\Http\Request $request
     * @param $orderId
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function updateAll(Request $request, $orderId)
    {
        try {
            DB::beginTransaction();
            $order = $this->_validateOrder($orderId);
            if (!$order) {
                $this->errMsg = $this->error('订单不存在');
                throw new  \Exception();
            }
            $attributes = $request->all();
            $customer = $order->salesmanCustomer;
            $businessService = new BusinessService();

            $format = $this->_formatAttribute($attributes, $order);
            $attributes['amount'] = $format['amount'];

            //验证陈列费或抵费商品是否合法并返回结果
            if ($customer->display_type == cons('salesman.customer.display_type.cash') && isset($attributes['display_fee'])) {
                //验证陈列费
                $validate = $businessService->validateDisplayFee($attributes['display_fee'], $attributes['amount'],
                    $customer, $order);

                if (!$validate) {
                    $this->errMsg = $businessService->getError();
                    throw new  \Exception();
                }

            } elseif ($customer->display_type == cons('salesman.customer.display_type.mortgage') && isset($attributes['mortgage'])) {
                //验证抵费商品
                $validate = $businessService->validateMortgage($attributes['mortgage'], $customer, $order);
                if (!$validate) {
                    $this->errMsg = $businessService->getError();
                    throw new  \Exception();
                }
            }

            $orderConf = cons('salesman.order');
            $attributes['id'] = $order->id;
            $attributes['salesman_id'] = $order->salesman_id;
            $attributes['shop_id'] = $order->shop_id;
            $attributes['salesman_visit_id'] = $order->salesman_visit_id;
            $attributes['salesman_customer_id'] = $order->salesman_customer_id;
            $attributes['type'] = $orderConf['type']['order'];
            $attributes['created_at'] = $order->created_at;
            $oldApplyPromoId = $order->apply_promo_id ?? null;
            $order->delete();

            $apply_promo_id = array_get($attributes, 'apply_promo_id', null);
            if (!is_null($apply_promo_id)) {
                $salesmanApplyPromo = PromoApply::find($attributes['apply_promo_id']);
                if (!$salesmanApplyPromo) {
                    $this->errMsg = '促销活动错误!';
                    throw new  \Exception();
                }
                if ($oldApplyPromoId != $apply_promo_id && !is_null($salesmanApplyPromo->order)) {
                    $this->errMsg = '促销活动已在其他订单使用!';
                    throw new  \Exception();
                }
                $attributes['apply_promo_id'] = $salesmanApplyPromo->id;
            }

            $orderForm = SalesmanVisitOrder::create($attributes);
            if ($orderForm->exists) {
                $orderForm->orderGoods()->saveMany($format['orderGoodsArr']);
                //礼物
                if ($gifts = array_get($attributes, 'gifts')) {
                    $giftList = [];
                    foreach ($gifts as $gift) {
                        $giftList[$gift['id']] = [
                            'num' => $gift['num'],
                            'pieces' => $gift['pieces'],
                        ];
                    }
                    $orderForm->gifts()->sync($giftList);
                }

                if (isset($validate) && $customer->display_type != cons('salesman.customer.display_type.no')) {
                    $orderForm->displayList()->saveMany($validate);
                }
            }
            DB::commit();
            return $this->success('更新订单成功');
        } catch (\Exception $e) {
            DB::rollback();
            return $this->error($this->errMsg ?? '更新订单时出现问题');
        }
    }

    /**
     * 删除订单
     *
     * @param $orderId
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function destroy($orderId)
    {
        $order = $this->_validateOrder($orderId);
        return $order && $order->delete() ? $this->success('订单删除成功') : $this->error('订单不存在或不能删除');
    }

    /**
     * 删除订单商品
     *
     * @param $goodsId
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function goodsDelete($goodsId)
    {
        $orderGoods = SalesmanVisitOrderGoods::with('salesmanVisitOrder')->find($goodsId);
        if (is_null($orderGoods)) {
            return $this->error('订单商品不存在');
        }
        $order = $this->_validateOrder($orderGoods->salesman_visit_order_id);
        if (!$order) {
            return $this->error('订单商品不存在');
        }
        $result = DB::transaction(function () use ($orderGoods, $order) {
            $orderGoodsPrice = $orderGoods->amount;
            $orderGoods->delete();
            $orderGoodsPrice > 0 && $order->decrement('amount', $orderGoodsPrice);
            return 'success';
        });
        return $result == 'success' ? $this->success('删除订单商品成功') : $this->error('删除订单商品时出现问题');
    }

    /**
     * 删除陈列商品
     *
     * @param \App\Http\Requests\Api\v1\DeleteMortgageGoodsRequest $request
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function mortgageGoodsDelete(DeleteMortgageGoodsRequest $request)
    {
        $order = $this->_validateOrder($request->input('order_id'));
        if (!$order) {
            return $this->error('订单不存在');
        }
        $order->mortgageGoods()->detach($request->input('mortgage_goods_id'));
        return $this->success('删除陈列费商品成功');
    }

    /**
     * 删除赠品
     *
     * @param \Illuminate\Http\Request $request
     * @param $giftId
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function gift(Request $request, $giftId)
    {
        $order = $this->_validateOrder($request->input('order_id'));
        if (!$order) {
            return $this->error('订单不存在');
        }

        $order->gifts()->detach($giftId);
        return $this->success('删除赠品成功');
    }

    /**
     * 更新
     *
     * @param \Illuminate\Http\Request $request
     * @param $giftId
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function upGift(Request $request, $giftId)
    {
        $data = $request->all();
        $order = $this->_validateOrder(array_get($data, 'order_id'));
        if (!$order) {
            return $this->error('订单不存在');
        }

        if (!($num = array_get($data, 'num')) || !intval($num) || $num <= 0) {
            return $this->error('请正确填写数量');
        }

        $gift = $order->gifts()->with('goodsPieces')->find($giftId);

        if (is_null($gift)) {
            return $this->error('赠品不存在');
        }
        $pieces = $request->input('pieces');
        $piecesList = $gift->pieces_list;
        $pieces = $piecesList->contains($pieces) ? $pieces : head($piecesList);

        return $gift->pivot->fill([
            'num' => $num,
            'pieces' => $pieces
        ])->save() ? $this->success('修改成功') : $this->error('修改失败');
    }

    /**
     * 获取抵费商品剩余
     *
     * @param \Illuminate\Http\Request $request
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function mortgageGoodsSurplus(Request $request)
    {

        $order = SalesmanVisitOrder::find($request->input('order_id'));
        $data = (new BusinessService())->canSetMortgageGoods($order, $request->input('month'), $request->input('id'));

        return $this->success(['surplus' => $data['surplus'], 'nonConfirm' => $data['nonConfirm']]);


    }


    /**
     * 获取成列费剩余
     *
     * @param \Illuminate\Http\Request $request
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function displayFeeSurplus(Request $request)
    {
        $customer = SalesmanCustomer::find($request->input('customer_id'));
        $month = $request->input('month');
        $data = (new BusinessService())->canSetDisplayFee($customer, $month, $request->input('order_id'));
        return $this->success(['surplus' => $data['surplus'], 'nonConfirm' => $data['nonConfirm']]);
    }

    /**
     * 订单验证
     *
     * @param $orderId
     * @return bool
     */
    private function _validateOrder($orderId)
    {
        if (!$orderId) {
            return false;
        }
        $order = SalesmanVisitOrder::find($orderId);
        if (is_null($order) || $order->status == cons('salesman.order.status.passed')) {
            return false;
        }
        if (auth()->id()) {
            //网页登录的
            if (Gate::denies('validate-salesman', $order->salesman)) {
                return false;
            }
        } else {
            if ($order->salesman_id != salesman_auth()->id()) {
                return false;
            }
        }
        return $order;
    }

    /**
     * 同步订单
     *
     * @param $salesmanVisitOrders
     * @return \WeiHeng\Responses\Apiv1Response
     */
    private function _syncOrders($salesmanVisitOrders)
    {

        $result = DB::transaction(function () use ($salesmanVisitOrders) {
            $syncConf = cons('salesman.order.sync');
            $orderConf = cons('order');
            $shippingAddressService = new ShippingAddressService();
            $shopId = auth()->user()->shop_id;
            foreach ($salesmanVisitOrders as $salesmanVisitOrder) {
                if (!$salesmanVisitOrder->can_sync) {
                    return ['error' => '存在不能同步的订单'];
                }
                $orderData = [
                    'user_id' => $salesmanVisitOrder->customer_user_id,
                    'shop_id' => $shopId,
                    'price' => $salesmanVisitOrder->amount,
                    'pay_type' => $syncConf['pay_type'],
                    'pay_way' => $syncConf['pay_way'],
                    'type' => '1',
                    'apply_promo_id' => $salesmanVisitOrder->apply_promo_id ?? '',
                    'status' => $orderConf['status']['non_send'],
                    'display_fee' => !empty($salesmanVisitOrder->displayFees()->sum('used')) ? $salesmanVisitOrder->displayFees()->sum('used') : '0.00',
                    'numbers' => (new OrderService())->getNumbers($shopId),
                    'shipping_address_id' => $shippingAddressService->copySalesmanCustomerShippingAddressToSnapshot($salesmanVisitOrder->SalesmanCustomer),
                    'remark' => ($salesmanVisitOrder->order_remark ? '订单备注:' . $salesmanVisitOrder->order_remark . ';' : '') . ($salesmanVisitOrder->display_remark ? '陈列费备注:' . $salesmanVisitOrder->display_remark : '')
                ];

                if (!$orderData['shipping_address_id']) {
                    return ['error' => $shippingAddressService->getError()];
                }

                $orderTemp = Order::create($orderData);
                if ($orderTemp->exists) {//添加订单成功,修改orderGoods中间表信息
                    $orderGoods = [];
                    foreach ($salesmanVisitOrder->orderGoods as $goods) {
                        // 添加订单商品
                        $orderGoods[] = new OrderGoods([
                            'goods_id' => $goods->goods_id,
                            'price' => $goods->price,
                            'num' => $goods->num,
                            'pieces' => $goods->pieces,
                            'total_price' => $goods->amount,
                        ]);

                        $confirmOrderDetail = ConfirmOrderDetail::where([
                            'goods_id' => $goods->goods_id,
                            'customer_id' => $salesmanVisitOrder->salesman_customer_id
                        ])->first();
                        if (empty($confirmOrderDetail)) {
                            ConfirmOrderDetail::create([
                                'goods_id' => $goods->goods_id,
                                'price' => $goods->price,
                                'pieces' => $goods->pieces,
                                'shop_id' => !empty($salesmanVisitOrder->salesmanCustomer->shop_id) ? $salesmanVisitOrder->salesmanCustomer->shop_id : 0,
                                'customer_id' => $salesmanVisitOrder->salesman_customer_id,
                            ]);
                        } else {
                            $confirmOrderDetail->fill(['price' => $goods->price, 'pieces' => $goods->pieces])->save();
                        }
                    }
                    foreach ($salesmanVisitOrder->mortgageGoods as $goods) {
                        // 添加抵费商品
                        $orderGoods[] = new OrderGoods([
                            'type' => cons('order.goods.type.mortgage_goods'),
                            'goods_id' => $goods->goods_id,
                            'price' => 0,
                            'num' => $goods->pivot->used,
                            'pieces' => $goods->pieces,
                            'total_price' => 0,
                        ]);
                    }
                    //促销活动添加使用时间
                    if ($salesmanVisitOrder->apply_promo_id) {
                        $this->_usePromo($salesmanVisitOrder);
                    }
                    /*foreach ($salesmanVisitOrder->promoGoods as $goods) {
                        // 添加促销商品
                        $orderGoods[] = new OrderGoods([
                            'type' => cons('order.goods.type.promo_goods'),
                            'goods_id' => $goods->id,
                            'price' => 0,
                            'num' => $goods->pivot->quantity,
                            'pieces' => $goods->pivot->piece,
                            'total_price' => 0,
                        ]);
                    }*/
                    //礼物
                    if ($gifts = $salesmanVisitOrder->gifts) {
                        foreach ($gifts as $gift) {
                            $orderGoods[] = new OrderGoods([
                                'type' => cons('order.goods.type.gift_goods'),
                                'goods_id' => $gift->id,
                                'price' => 0,
                                'num' => $gift->pivot->num,
                                'pieces' => $gift->pivot->pieces,
                                'total_price' => 0,
                            ]);
                        }
                    }

                    if (!empty($orderGoods)) {
                        //保存抵费商品
                        if (!$orderTemp->orderGoods()->saveMany($orderGoods)) {
                            return ['error' => '同步时出现错误，请重试'];
                        }
                    }

                } else {
                    return ['error' => '同步时出现错误，请重试'];
                }
                if (!$salesmanVisitOrder->fill(['order_id' => $orderTemp->id])->save()) {
                    return ['error' => '同步时出现错误，请重试'];
                }
            }

            return 'success';
        });

        return $result;
    }

    /**
     * 促销活动添加使用时间
     *
     * @param $salesmanVisitOrders
     */
    public function _usePromo($salesmanVisitOrder)
    {
        return $salesmanVisitOrder->applyPromo->update(['use_date' => Carbon::now()]);
    }

    /**
     * 更新客户陈列费
     *
     * @param $salesmanVisitOrders
     */
    private function _updateDisplay($salesmanVisitOrders)
    {
        foreach ($salesmanVisitOrders as $salesmanVisitOrder) {
            $displayList = $salesmanVisitOrder->displayList;

            if (is_null($displayList)) {
                continue;
            }
            $salesmanCustomer = $salesmanVisitOrder->salesmanCustomer;
            foreach ($displayList as $item) {
                $displaySurplus = $salesmanCustomer->displaySurplus()->where([
                    'month' => $item->month,
                    'mortgage_goods_id' => $item->mortgage_goods_id
                ])->first();

                if ($displaySurplus) {
                    $displaySurplus->decrement('surplus', $item->used);
                } else {
                    if ($item->mortgage_goods_id == 0) {
                        //陈列费
                        $salesmanCustomer->displaySurplus()->create([
                            'month' => $item->month,
                            'mortgage_goods_id' => 0,
                            'surplus' => bcsub($salesmanCustomer->display_fee, $item->used)
                        ]);
                    } else {
                        //抵费商品
                        $surplus = $salesmanCustomer->mortgageGoods()->find($item->mortgage_goods_id);

                        if ($surplus) {
                            $salesmanCustomer->displaySurplus()->create([
                                'month' => $item->month,
                                'mortgage_goods_id' => $item->mortgage_goods_id,
                                'surplus' => bcsub($surplus->pivot->total, $item->used)
                            ]);
                        }
                    }
                }
            }
        }
    }

    /**
     * 更新订单商品
     *
     * @param $salesmanVisitOrder
     * @param $request
     * @param null $orderGoods
     * @return bool
     */
    private function _updateOrderGoods($salesmanVisitOrder, $request, $orderGoods = null)
    {
        if (is_null($salesmanVisitOrder) || Gate::denies('validate-salesman-order', $salesmanVisitOrder)) {
            return '订单不存在';
        }
        if (!$salesmanVisitOrder->can_pass) {
            return '已通过订单不能修改';
        }

        $result = DB::transaction(function () use ($salesmanVisitOrder, $request, $orderGoods) {
            if ($orderGoods) {
                $goodsTypes = cons('salesman.order.goods.type');
                $attributes = [];
                //商品原总金额
                $goodsOldAmount = $orderGoods->amount;

                if ($orderGoods->type == $goodsTypes['order']) {
                    //订单
                    $attributes['price'] = $request->input('price');
                    $attributes['num'] = $request->input('num');
                    $attributes['pieces'] = $request->input('pieces');
                    $attributes['amount'] = bcmul($attributes['price'], intval($attributes['num']), 2);
                    if ($orderGoods->fill($attributes)->save()) {
                        $salesmanVisitOrder->fill(['amount' => $salesmanVisitOrder->amount - $goodsOldAmount + $attributes['amount']])->save();
                    }
                } elseif ($orderGoods->type == $goodsTypes['return']) {
                    //退货单
                    $attributes['num'] = $request->input('num');
                    $attributes['amount'] = $request->input('amount');
                    $attributes['pieces'] = $request->input('pieces');
                    if ($orderGoods->fill($attributes)->save()) {
                        $salesmanVisitOrder->fill(['amount' => $salesmanVisitOrder->amount - $goodsOldAmount + $attributes['amount']])->save();
                    }
                }
            } else {
                //抵费商品
                $goodsId = $request->input('id');
                $num = $request->input('num');
                if (!$goodsId || !$mortgage = $salesmanVisitOrder->mortgageGoods()->find($goodsId)) {
                    return '陈列商品不存在';
                }

                $customer = $salesmanVisitOrder->salesmanCustomer;


                //获取客户剩余
                $surplusMortgage = (new BusinessService())->canSetMortgageGoods($salesmanVisitOrder,
                    $request->input('month'), $goodsId);
                if (($surplusMortgage['surplus'] - $surplusMortgage['nonConfirm']) < $num) {
                    return '陈列商品数量不能大于当月剩余量';
                }
                $salesmanVisitOrder->displayList()->where([
                    'salesman_customer_id' => $request->input('customer_id'),
                    'month' => $request->input('month'),
                    'mortgage_goods_id' => $goodsId
                ])->first()->fill(['used' => $num])->save();
            }

            return 'success';
        });
        return $result;
    }

    /**
     * 格式化订单属性
     *
     * @param $attributes
     * @param $order
     * @return array
     */
    private function _formatAttribute($attributes, SalesmanVisitOrder $order)
    {
        $amount = 0;
        $orderGoodsArr = [];
        /*  $mortgageGoodsArr = [];*/
        if (isset($attributes['goods'])) {
            foreach ($attributes['goods'] as $orderGoods) {
                $orderGoods['amount'] = bcmul($orderGoods['price'], $orderGoods['num'], 2);
                $orderGoods['salesman_visit_id'] = $order->salesman_visit_id;
                $orderGoods['type'] = $order->type;
                $orderGoods['goods_id'] = $orderGoods['id'];
                $orderGoodsArr[] = new SalesmanVisitOrderGoods($orderGoods);
                $amount = bcadd($amount, $orderGoods['amount'], 2);
            }
        }
        /*  if (isset($attributes['mortgage'])) {
              foreach ($attributes['mortgage'] as $month => $mortgageGoods) {
                  $mortgageGoodsArr[$mortgageGoods['id']] = [
                      'num' => $mortgageGoods['num'],
                      'month' => $mortgageGoods['month'],
                      'salesman_customer_id' => $order->salesman_customer_id
                  ];
              }
          }*/
        return compact('amount', 'orderGoodsArr'/*, 'mortgageGoodsArr'*/);
    }

}
