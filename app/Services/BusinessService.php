<?php

namespace App\Services;


use App\Models\SalesmanCustomer;
use App\Models\SalesmanCustomerDisplayList;
use App\Models\SalesmanCustomerDisplaySurplus;
use App\Models\SalesmanVisitGoodsRecord;
use App\Models\SalesmanVisitOrder;
use App\Models\Shop;

/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2015/8/17
 * Time: 17:45
 */
class BusinessService extends BaseService
{
    /**
     * 获取订单详情
     *
     * @param $salesmanVisitOrder
     * @param $load
     * @return array
     */
    public function getOrderData(
        SalesmanVisitOrder $salesmanVisitOrder,
        $load = ['mortgageGoods.goods', 'orderGoods.goods']
    ) {
        $orderTypeConf = cons('salesman.order');
        $data = [
            'order' => $salesmanVisitOrder
        ];

        $salesmanVisitOrder->load($load);

        if ($salesmanVisitOrder->type == $orderTypeConf['type']['order']) {
            $data['displayFee'] = $salesmanVisitOrder->displayFees;
            $data['mortgageGoods'] = $this->getOrderMortgageGoods([$salesmanVisitOrder]);
        }
        $data['orderGoods'] = $salesmanVisitOrder->orderGoods;
        $data['goods_total_num'] = 0;
        $data['goods_total_amount'] = 0;
        if (count($data['orderGoods']) > 0) {
            foreach ($data['orderGoods'] as $goods) {
                $data['goods_total_num'] += $goods['num'];
                $data['goods_total_amount'] += $goods['amount'];
            }
        }
        return $data;
    }

    /**
     * 获取店铺业务员订单信息
     *
     * @param $shop
     * @param $startDate
     * @param $endDate
     * @return mixed
     */
    public function getSalesmanOrders($shop, $startDate, $endDate, $salesmenName = null)
    {
        $salesmen = $shop instanceof Shop ? $shop->salesmen() : $shop;
        $user = auth()->user();
        $salesmen = $salesmen->ofName($salesmenName)->with([
            'orders.order',
            'orders.salesmanCustomer',
            'visits.salesmanCustomer'
        ])->get()->each(function ($salesman) use (
            $startDate,
            $user,
            $endDate
        ) {
            //所有订单
            $orders = $salesman->orders->filter(function ($order) use (
                $salesman,
                $startDate,
                $endDate,
                $user
            ) {

                return $order->salesmanCustomer->shop_id != $user->shop_id && ($order->created_at >= $startDate && $order->created_at <= $endDate);
            });

            $salesman->usefulOrders = $orders;

            //所有平台订货
            $orderForms = $orders->filter(function ($order) use ($user) {
                $userType = cons('user.type');
                if ($user->type == $userType['maker']) {
                    if (!$order->salesman_visit_id && $order->salesmanCustomer->type < $userType['supplier']) {
                        return false;
                    }
                }
                return $order->type == cons('salesman.order.type.order');
            });

            //所有拜访订单
            $visitOrderForms = $orderForms->filter(function ($order) {
                return $order->salesman_visit_id > 0;
            });
            //所有拜访
            $visits = $salesman->visits->filter(function ($visit) use (
                $startDate,
                $endDate,
                $user
            ) {
                return $visit->salesmanCustomer->shop_id != $user->shop->id && $visit->created_at >= $startDate && $visit->created_at <= $endDate;
            });

            //拜访客户数
            $salesman->visitCustomerCount = $visits->pluck('salesman_customer_id')->toBase()->unique()->count();

            //订货总金额
            $salesman->orderFormSumAmount = $orderForms->filter(function ($order) use ($user) {
                if ($order->order) {
                    if ($order->order->status < cons('order.status.invalid') && $order->order->pay_status < cons('order.pay_status.refund')) {
                        return true;
                    }
                    return false;
                }
                return true;
            })->sum('amount');
            //业务订单总金额
            $salesman->visitOrderFormSumAmount = $visitOrderForms->filter(function ($order) {
                return ($order->order ? $order->order->status < cons('order.status.invalid') : true) && ($order->order ? $order->order->pay_status < cons('order.pay_status.refund') : true);
            })->sum('amount');

            //平台订货单数
            $salesman->orderFormCount = $orderForms->count();
            //业务订货单数
            $salesman->visitOrderFormCount = $visitOrderForms->count();
            //已配送订单数
            $salesman->deliveryFinishCount = $orders->filter(function ($order) use ($startDate, $endDate) {
                $order = $order->order;
                return !is_null($order) && !is_null($order->delivery_finished_at);
            })->count();

            $finishedOrder = $orders->filter(function ($order) use ($startDate, $endDate, $user) {
                $userType = cons('user.type');
                if ($user->type == $userType['maker']) {
                    if (!$order->salesman_visit_id && $order->salesmanCustomer->type < $userType['supplier']) {
                        return false;
                    }
                }
                return !is_null($order->order) && $order->order->status == cons('order.status.finished');
            });
            //已收金额
            $salesman->finishedAmount = $finishedOrder->sum('amount');

            //完成家数
            $salesman->finishedCount = $finishedOrder->pluck('salesman_customer_id')->toBase()->unique()->count();

            //未收金额
            $salesman->notFinishedAmount = $orders->filter(function ($order) use ($startDate, $endDate, $user) {
                $userType = cons('user.type');
                if ($user->type == $userType['maker']) {
                    if (!$order->salesman_visit_id && $order->salesmanCustomer->type < $userType['supplier']) {
                        return false;
                    }
                }
                return !is_null($order->order) ? ($order->order->status > cons('order.status.non_confirm') && $order->order->status < cons('order.status.finished') && $order->order->pay_status < cons('order.pay_status.refund')) : $order->type == cons('order.type.order');
            })->sum('amount');

            //退货单
            $returnOrders = $orders->reject(function ($order) {
                return $order->type == cons('salesman.order.type.order');
            });
            //退货单总金额
            $salesman->returnOrderSumAmount = $returnOrders->sum('amount');

            //退货单数
            $salesman->returnOrderCount = $returnOrders->count();
        });
        return $salesmen;
    }

    /**
     * 根据类型获取所有订单
     *
     * @param $salesmenId
     * @param $data
     * @param array $with
     * @return mixed
     */
    public function getOrders($salesmenId, $data = [], $with = ['salesmanCustomer.businessAddress', 'salesman'])
    {
        $shop_id = auth()->user()->shop_id ?? salesman_auth()->user()->shop_id;
        if ($salesmanId = array_get($data, 'salesman_id')) {
            $exists = $salesmenId->toBase()->contains($salesmanId);
            if ($exists) {
                $orders = SalesmanVisitOrder::CheckShop($shop_id)->OfData($data)->with($with)->orderBy('id',
                    'desc')->paginate();
            } else {
                $orders = SalesmanVisitOrder::CheckShop($shop_id)->whereIn('salesman_id',
                    $salesmenId)->OfData(array_except($data,
                    'salesman_id'))->with($with)->orderBy('id', 'desc')->paginate();
            }
        } else {
            $orders = SalesmanVisitOrder::CheckShop($shop_id)->whereIn('salesman_id',
                $salesmenId)->OfData($data)->with($with)->orderBy('updated_at', 'desc')->paginate();
        }
        return $orders;
    }

    /**
     * 格式化访问数据
     *
     * @param $visits
     * @param bool $hasGoodsImage
     * @return array
     */
    public function formatVisit($visits, $hasGoodsImage = false)
    {
        $orderConf = cons('salesman.order');

        $visitData = [];

        $visitStatistics = [];

        foreach ($visits as $visit) {
            $customerId = $visit->salesman_customer_id;
            $customer = $visit->salesmanCustomer;

            //拜访客户信息
            $visitData[$customerId]['visit_id'] = $visit->id;
            $visitData[$customerId]['created_at'] = (string)$visit->created_at;
            $visitData[$customerId]['customer_name'] = $customer->name;
            $visitData[$customerId]['contact'] = $customer->contact;
            $visitData[$customerId]['business_address_lng'] = $customer->business_address_lng;
            $visitData[$customerId]['business_address_lat'] = $customer->business_address_lat;
            $visitData[$customerId]['lng'] = $visit->x_lng;
            $visitData[$customerId]['lat'] = $visit->y_lat;
            $visitData[$customerId]['address'] = $visit->address;
            $visitData[$customerId]['number'] = $customer->number;
            $visitData[$customerId]['contact_information'] = $customer->contact_information;
            $visitData[$customerId]['shipping_address_name'] = $customer->shipping_address_name;
            $visitData[$customerId]['business_address_name'] = $customer->business_address_name;
            $visitData[$customerId]['visit_count'] = isset($visitData[$customerId]['visit_count']) ? $visitData[$customerId]['visit_count'] + 1 : 1;
            $visitData[$customerId]['photos'] = $visit->photos_url ?? [];


            //订单货单
            $orderForm = $visit->orders->filter(function ($item) use ($orderConf) {
                return !is_null($item) && $item->type == $orderConf['type']['order'];
            });
            //退货单

            $returnOrderForm = $visit->orders->filter(function ($item) use ($orderConf) {
                return !is_null($item) && $item->type == $orderConf['type']['return_order'];
            });
            if ($orderForm->count()) {
                $order = $orderForm->first();
                //拜访订货单数
                $visitStatistics['order_form_count'] = isset($visitStatistics['order_form_count']) ? ++$visitStatistics['order_form_count'] : 1;
                //拜访订货金额
                $visitStatistics['order_form_amount'] = isset($visitStatistics['order_form_amount']) ? bcadd($visitStatistics['order_form_amount'],
                    $order->amount, 2) : $order->amount;


                //赠品
                if (!is_null($gifts = $order->gifts)) {
                    foreach ($gifts as $item) {

                        $visitData[$customerId]['gifts'][] = [
                            'name' => $item->name,
                            'num' => $item->pivot->num,
                            'pieces' => $item->pivot->pieces
                        ];
                    }

                }

                //促销活动
                if (!is_null($promo = $order->applyPromo)) {
                    $visitData[$customerId]['promo']['condition'] = $order->applyPromo->promo->condition;
                    $visitData[$customerId]['promo']['rebate'] = $order->applyPromo->promo->rebate;
                    $visitData[$customerId]['promo']['type'] = $order->applyPromo->promo->type;
                    $visitData[$customerId]['promo']['name'] = $order->applyPromo->promo->name;
                    $visitData[$customerId]['promo']['start_at'] = $order->applyPromo->promo->start_at;
                    $visitData[$customerId]['promo']['end_at'] = $order->applyPromo->promo->end_at;
                }

                //陈列费
                if (!is_null($order->displayList)) {
                    foreach ($order->displayList as $item) {
                        if ($item->mortgage_goods_id == 0) {
                            $visitData[$customerId]['display_fee'][] = [
                                'month' => $item->month,
                                'created_at' => (string)$order->salesmanVisit->created_at,
                                'display_fee' => $item->used
                            ];
                        } else {
                            $month = $item->month;
                            $mortgage = $item->mortgageGoods;
                            $visitData[$customerId]['mortgage'][$month][] = [
                                'id' => $item->mortgage_goods_id,
                                'name' => $mortgage->goods_name,
                                'pieces' => $mortgage->pieces,
                                'num' => (int)$item->used,
                                'month' => $item->month,
                                'created_at' => (string)$order->salesmanVisit->created_at
                            ];
                        }
                    }
                }
            }

            if ($returnOrder = $returnOrderForm->first()) {
                //拜访退货单数
                $visitStatistics['return_order_count'] = isset($visitStatistics['return_order_count']) ? ++$visitStatistics['return_order_count'] : 1;

                //拜访退货金额
                $visitStatistics['return_order_amount'] = isset($visitStatistics['return_order_amount']) ? bcadd($visitStatistics['return_order_amount'],
                    $returnOrder->amount, 2) : $returnOrder->amount;
            }

            //拜访商品记录
            $goodsRecord = $visit->goodsRecord;
            $goodsRecodeData = [];
            foreach ($goodsRecord as $record) {
                if (!is_null($record)) {
                    $goodsRecodeData[$record->goods_id] = $record;
                }
            }


            $visitData[$customerId]['amount'] = isset($visitData[$customerId]['amount']) ? $visitData[$customerId]['amount'] : 0;
            $visitData[$customerId]['return_amount'] = isset($visitData[$customerId]['return_amount']) ? $visitData[$customerId]['return_amount'] : 0;
            $allGoods = $orderGoods = $visit->orders->pluck('orderGoods')->collapse();

            foreach ($goodsRecord as $key => $record) {
                $tag = false;
                foreach ($orderGoods as $goods) {
                    if ($record->goods_id == $goods->goods_id && $record->salesman_visit_id == $goods->salesman_visit_id) {
                        $tag = true;
                        break;
                    }
                }
                if (!$tag) {
                    $allGoods->push($record);
                }
            }


            foreach ($allGoods as $goods) {
                if ($goods instanceof SalesmanVisitGoodsRecord) {
                    $customerTypeName = $customer->shop_id && $customer->shop ? array_search($customer->shop->user_type,
                        cons('user.type')) : 'retailer';
                }
                if ($goods->type == $orderConf['goods']['type']['order']) {
                    $visitData[$customerId]['statistics'][$goods->goods_id]['order_num'] = isset($visitData[$customerId]['statistics'][$goods->goods_id]['order_num']) ? $visitData[$customerId]['statistics'][$goods->goods_id]['order_num'] + $goods->num : $goods->num;
                    $visitData[$customerId]['statistics'][$goods->goods_id]['order_amount'] = isset($visitData[$customerId]['statistics'][$goods->goods_id]['order_amount']) ? bcadd($visitData[$customerId]['statistics'][$goods->goods_id]['order_amount'],
                        $goods->amount, 2) : $goods->amount;
                    $visitData[$customerId]['statistics'][$goods->goods_id]['price'] = $goods instanceof SalesmanVisitGoodsRecord ? '- -' : $goods->price;
                    $visitData[$customerId]['statistics'][$goods->goods_id]['pieces'] = $goods instanceof SalesmanVisitGoodsRecord ? null : $goods->pieces;

                    $visitData[$customerId]['amount'] = bcadd($visitData[$customerId]['amount'], $goods->amount, 2);

                } elseif ($goods->type == $orderConf['goods']['type']['return']) {
                    $visitData[$customerId]['statistics'][$goods->goods_id]['return_order_num'] = isset($visitData[$customerId]['statistics'][$goods->goods_id]['return_order_num']) ? $visitData[$customerId]['statistics'][$goods->goods_id]['return_order_num'] + intval($goods->num) : intval($goods->num);
                    $visitData[$customerId]['statistics'][$goods->goods_id]['return_amount'] = isset($visitData[$customerId]['statistics'][$goods->goods_id]['return_amount']) ? bcadd($visitData[$customerId]['statistics'][$goods->goods_id]['return_amount'],
                        $goods->amount, 2) : $goods->amount;

                    $visitData[$customerId]['return_amount'] = bcadd($visitData[$customerId]['return_amount'],
                        $goods->amount, 2);
                }

                $hasGoodsImage && ($visitData[$customerId]['statistics'][$goods->goods_id]['image_url'] = $goods->goods_image);
                $visitData[$customerId]['statistics'][$goods->goods_id]['goods_id'] = $goods->goods_id;
                $visitData[$customerId]['statistics'][$goods->goods_id]['goods_name'] = $goods instanceof SalesmanVisitGoodsRecord ? $goods->goods->name : $goods->goods_name;
                $visitData[$customerId]['statistics'][$goods->goods_id]['stock'] = isset($goodsRecodeData[$goods->goods_id]) ? $goodsRecodeData[$goods->goods_id]->stock : 0;
                $visitData[$customerId]['statistics'][$goods->goods_id]['production_date'] = isset($goodsRecodeData[$goods->goods_id]) ? $goodsRecodeData[$goods->goods_id]->production_date : ' - - ';
                $visitData[$customerId]['statistics'][$goods->goods_id]['order_amount'] = isset($visitData[$customerId]['statistics'][$goods->goods_id]['order_amount']) ? $visitData[$customerId]['statistics'][$goods->goods_id]['order_amount'] : 0;
                $visitData[$customerId]['statistics'][$goods->goods_id]['return_order_num'] = isset($visitData[$customerId]['statistics'][$goods->goods_id]['return_order_num']) ? $visitData[$customerId]['statistics'][$goods->goods_id]['return_order_num'] : 0;
                $visitData[$customerId]['statistics'][$goods->goods_id]['return_amount'] = isset($visitData[$customerId]['statistics'][$goods->goods_id]['return_amount']) ? $visitData[$customerId]['statistics'][$goods->goods_id]['return_amount'] : 0;
                $visitData[$customerId]['statistics'][$goods->goods_id]['order_num'] = isset($visitData[$customerId]['statistics'][$goods->goods_id]['order_num']) ? $visitData[$customerId]['statistics'][$goods->goods_id]['order_num'] : 0;
            }
        }

        return compact('visitData', 'visitStatistics');
    }

    /**
     * 格式化客户订单
     *
     * @param $orders
     * @return \Illuminate\Support\Collection
     */
    public function formatOrdersByCustomer($orders)
    {
        $result = [];

        if ($orders->count()) {
            $customers = SalesmanCustomer::whereIn('id',
                $orders->pluck('salesman_customer_id')->toBase()->unique())->with('businessAddress',
                'shop')->get()->keyBy('id');

            foreach ($orders as $order) {
                $customerId = $order->salesman_customer_id;
                $result[$customerId]['number'] = $customers[$customerId]['number'];
                $result[$customerId]['shop_name'] = $customers[$customerId]->shop ? $customers[$customerId]->shop->name : '';
                $result[$customerId]['contact'] = $customers[$customerId]->contact;
                $result[$customerId]['contact_information'] = $customers[$customerId]->contact_information;
                $result[$customerId]['business_address'] = $customers[$customerId]->businessAddress->address_name;
                $result[$customerId]['orders'] = isset($result[$customerId]['orders']) ? $result[$customerId]['orders']->push($order) : collect([$order]);

                foreach ($order->orderGoods as $orderGoods) {
                    $result[$customerId]['orderGoods'][$orderGoods->goods_id]['name'] = $orderGoods->goods_name;
                    $result[$customerId]['orderGoods'][$orderGoods->goods_id]['order_num'] = isset($result[$customerId]['orderGoods'][$orderGoods->goods_id]['order_num']) ? ($result[$customerId]['orderGoods'][$orderGoods->goods_id]['order_num'] + $orderGoods->num) : $orderGoods->num;
                    $result[$customerId]['orderGoods'][$orderGoods->goods_id]['order_amount'] = isset($result[$customerId]['orderGoods'][$orderGoods->goods_id]['order_amount'])
                        ? bcadd($result[$customerId]['orderGoods'][$orderGoods->goods_id]['order_amount'],
                            $orderGoods->amount, 2) : $orderGoods->amount;
                }

            }

        }

        return collect($result);
    }

    /**
     * 获取订单抵货商品
     *
     * @param $orders
     * @return array
     */
    public function getOrderMortgageGoods($orders)
    {
        $mortgagesGoods = collect([]);
        foreach ($orders as $order) {
            if ($goods = $order->mortgageGoods) {
                foreach ($goods as $good) {
                    $item = $good->goods;
                    $mortgagesGoods->push([
                        'id' => $good->id,
                        'name' => $good->goods_name,
                        'image_url' => $item ? $item->image_url : asset('images/goods_default.png'),
                        'promotion_info' => $item ? $item->promotion_info : '',
                        'pieces' => $good->pieces,
                        'num' => (int)$good->pivot->used,
                        'month' => $good->pivot->month,
                        'created_at' => (string)$order->created_at
                    ]);

                }
            }
        }
        return $mortgagesGoods;
    }

    /**
     * 获取订单陈列费
     *
     * @param $orders
     * @return \Illuminate\Support\Collection
     */
    public function getOrderDisplayFees($orders)
    {
        $displayFees = collect([]);
        foreach ($orders as $order) {
            if ($displayFee = $order->displayFees) {
                foreach ($displayFee as $item) {
                    $displayFees->push([
                        'month' => $item->month,
                        'used' => $item->used,
                        'time' => (string)$item->created_at
                    ]);
                }

            }
        }
        return $displayFees;
    }

    /**
     * 获取剩余陈列费
     *
     * @param \App\Models\SalesmanCustomer $customer
     * @param $month
     * @return mixed
     */
    public function surplusDisplayFee(SalesmanCustomer $customer, $month)
    {
        $display = $customer->displaySurplus()->where([
            'month' => $month,
            'mortgage_goods_id' => 0
        ])->first();

        return is_null($display) ? $customer->display_fee : $display->surplus;
    }

    /**
     * 获取剩余陈列商品
     *
     * @param $customer
     * @param $month
     * @param null $mortgages
     * @return array
     */
    public function surplusMortgageGoods($customer, $month, $mortgages = null)
    {
        $surplus = [];

        $mortgages = is_null($mortgages) ? $customer->mortgageGoods : $mortgages;
        //获取本月剩余陈列商品
        $surplusMortgageGoods = $customer->displaySurplus()->where(['month' => $month])->whereIn('mortgage_goods_id',
            $mortgages->pluck('id'))->orderBy('id', 'desc')->get();
        foreach ($mortgages as $mortgage) {
            $flag = false;
            foreach ($surplusMortgageGoods as $item) {
                if ($item->mortgage_goods_id == $mortgage->id) {
                    $surplus[] = [
                        'id' => $mortgage->id,
                        'goods_id' => $mortgage->goods_id,
                        'img_url' => $mortgage->goods->image_url,
                        'name' => $mortgage->goods_name,
                        'surplus' => (int)$item->surplus,
                        'pieces_name' => $mortgage->pieces_name,
                        'pieces' => $mortgage->pieces
                    ];
                    $flag = true;
                    break;
                }
            }
            if (!$flag) {
                $surplus[] = [
                    'id' => $mortgage->id,
                    'goods_id' => $mortgage->goods_id,
                    'img_url' => $mortgage->goods->image_url,
                    'name' => $mortgage->goods_name,
                    'surplus' => (int)$mortgage->pivot->total,
                    'pieces_name' => $mortgage->pieces_name,
                    'pieces' => $mortgage->pieces
                ];
            }

        }
        return $surplus;
    }


    /**
     * 验证陈列费
     *
     * @param $displayFee
     * @param $orderAmount
     * @param \App\Models\SalesmanCustomer $customer
     * @param \App\Models\SalesmanVisitOrder|null $salesmanVisitOrder
     * @return array|bool
     */
    public function validateDisplayFee(
        $displayFee,
        $orderAmount,
        SalesmanCustomer $customer,
        SalesmanVisitOrder $salesmanVisitOrder = null
    ) {
        $totalCash = 0;
        $displayList = [];

        foreach ($displayFee as $month => $item) {
            $customerSurplus = $this->surplusDisplayFee($customer, $month);
            $orderDisplayFee = 0;
            if (!is_null($salesmanVisitOrder)) {
                $orderDisplayFee = $salesmanVisitOrder->displayList()->where([
                    'month' => $month,
                    'mortgage_goods_id' => 0
                ])->pluck('used');
            }
            if ($item > bcsub($customerSurplus, $orderDisplayFee, 2)) {
                $this->setError('陈列费不能高于选择月份余额');
                return false;
            }
            $totalCash = bcadd($totalCash, $item, 2);
            $displayList[] = new SalesmanCustomerDisplayList([
                'salesman_customer_id' => $customer->id,
                'month' => $month,
                'used' => $item,
            ]);
        }
        if ($totalCash > $orderAmount) {
            $this->setError('陈列费不能高于订单金额');
            return false;
        }
        return $displayList;
    }

    /**
     * 验证陈列商品
     *
     * @param $mortgages
     * @param \App\Models\SalesmanCustomer $customer
     * @param \App\Models\SalesmanVisitOrder|null $salesmanVisitOrder
     * @return array|bool
     */
    public function validateMortgage(
        $mortgages,
        SalesmanCustomer $customer,
        SalesmanVisitOrder $salesmanVisitOrder = null
    ) {
        $displayList = [];

        foreach ($mortgages as $month => $mortgage) {
            $customerSurplus = $this->surplusMortgageGoods($customer, $month);
            $orderMortgageGoodsNum = 0;
            foreach ($mortgage as $detail) {
                $flag = false;
                foreach ($customerSurplus as $item) {
                    if ($detail['id'] == $item['id']) {
                        if (!is_null($salesmanVisitOrder)) {
                            $orderMortgageGoodsNum = $salesmanVisitOrder->displayList()->where([
                                'month' => $month,
                                'mortgage_goods_id' => $detail['id']
                            ])->pluck('used');
                        }

                        if ($detail['num'] > bcadd($item['surplus'], $orderMortgageGoodsNum)) {
                            $this->setError('抵费商品数量不能大于选择月份剩余数量');
                            return false;
                        }
                        $displayList[] = new SalesmanCustomerDisplayList([
                            'salesman_customer_id' => $customer->id,
                            'month' => $month,
                            'used' => $detail['num'],
                            'mortgage_goods_id' => $item['id']
                        ]);
                        $flag = true;
                        break;
                    }
                }
                if (!$flag) {
                    $this->setError('抵费商品不存在');
                    return false;
                }
            }

        }
        return $displayList;

    }

    /**
     * 查询未审核陈列
     *
     * @param \App\Models\SalesmanCustomer $customer
     * @param $month
     * @param $mortgages_id
     * @return  mixed
     */
    public function nonConfirmDisplay(SalesmanCustomer $customer, $month, $mortgages_id)
    {

        $nonConfirm = $customer->displayList()
            ->where([
                'month' => $month,
                'mortgage_goods_id' => $mortgages_id
            ])->whereHas('order', function ($query) {
                $query->where('status', cons('salesman.order.status.not_pass'));
            })->sum('used');

        return $nonConfirm;
    }

    /**
     * 查询可设置陈列费
     *
     * @param \App\Models\SalesmanCustomer $customer
     * @param $month
     * @param $order_id
     * @return array
     */
    public function canSetDisplayFee(SalesmanCustomer $customer, $month, $order_id)
    {
        //剩余数量
        $customerSurplus = $this->surplusDisplayFee($customer, $month);
        //所有未审核数量
        $nonConfirm = $this->nonConfirmDisplay($customer, $month, 0);
        //本订单数量
        $orderUsed = SalesmanVisitOrder::find($order_id)->displayFees()->where('month',
            $month)->first()->used;
        //其他订单未审核数量(所有未审核-本订单数量)
        $nonConfirm = bcsub($nonConfirm, $orderUsed, 2);
        return ['surplus' => $customerSurplus, 'nonConfirm' => $nonConfirm];

    }

    /**
     * 查询可设置陈列商品
     *
     * @param \App\Models\SalesmanVisitOrder $order
     * @param $month
     * @param $goods_id
     * @return array
     */
    public function canSetMortgageGoods(SalesmanVisitOrder $order, $month, $goods_id)
    {
        $customer = $order->salesmanCustomer;
        $surplus = SalesmanCustomerDisplaySurplus::where([
            'salesman_customer_id' => $customer->id,
            'month' => $month,
            'mortgage_goods_id' => $goods_id
        ])->first();
        //剩余数量
        $customerSurplus = is_null($surplus) ? $customer->mortgageGoods()->where('mortgage_goods.id',
            $goods_id)->withTrashed()->first()->pivot->total : $surplus->surplus;
        //所有未审核数量
        $nonConfirm = $this->nonConfirmDisplay($customer, $month,
            $goods_id);
        //本订单数量
        $orderUsed = SalesmanCustomerDisplayList::where([
            'salesman_visit_order_id' => $order->id,
            'mortgage_goods_id' => $goods_id,
            'month' => $month,
            'salesman_customer_id' => $customer->id
        ])->first()->used;
        //其他订单未审核数量(所有未审核-本订单数量)
        $nonConfirm = bcsub($nonConfirm, $orderUsed, 2);
        return ['surplus' => $customerSurplus, 'nonConfirm' => $nonConfirm];

    }
}