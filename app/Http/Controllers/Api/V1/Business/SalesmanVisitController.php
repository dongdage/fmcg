<?php

namespace App\Http\Controllers\Api\V1\Business;

use App\Models\SalesmanCustomer;
use App\Models\SalesmanVisit;
use App\Models\SalesmanVisitGoodsRecord;
use App\Models\SalesmanVisitOrder;
use App\Models\SalesmanVisitOrderGoods;
use App\Services\BusinessService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SalesmanVisitController extends Controller
{
    public function __construct()
    {
        $this->middleware('salesman.auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function index(Request $request)
    {
        $carbon = new Carbon();

        $salesman = salesman_auth()->user();
        $startDate = $request->input('start_date', $carbon->copy()->startOfMonth());
        $endDate = $request->input('end_date');
        $endDate = $endDate ? (new Carbon($endDate))->endOfDay() : $carbon->copy();
        $name = $request->input('name');

        $visits = SalesmanCustomer::ofName($name)->with([
            'orders' => function ($query) use ($startDate, $endDate, $salesman) {
                $query->where('salesman_id', $salesman->id)->where('shop_id',
                    $salesman->shop_id)->whereBetween('created_at',
                    [$startDate, $endDate])->with('order', 'displayList');
            },
            'visits' => function ($q) use ($startDate, $endDate, $salesman) {
                $q->where('salesman_id', $salesman->id)->where('shop_id',
                    $salesman->shop_id)->whereBetween('created_at', [$startDate, $endDate]);
            },
            'businessAddress',
        ])->get();
        $visit = $visits->reject(function ($v) {
            return count($v->orders) == 0 && count($v->visits) == 0;
        })->values();
        return $this->success(compact('visit'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function store(Request $request)
    {
        $data = $request->all();

        $salesman = salesman_auth()->user();

        $customer = $salesman->customers()->find($data['salesman_customer_id']);

        if (is_null($customer)) {
            return $this->error('客户不存在');
        }
        $visit = $salesman->visits()->create([
                'salesman_customer_id' => $data['salesman_customer_id'],
                'x_lng' => isset($data['x_lng']) ? $data['x_lng'] : '',
                'y_lat' => isset($data['y_lat']) ? $data['y_lat'] : '',
                'address' => isset($data['address']) ? $data['address'] : '',
                'shop_id' => $salesman->shop_id,
            ]
        );
        $result = DB::transaction(function () use ($salesman, $data, $customer, $visit) {
            $result = $this->_formatData($data);

            $orderConf = cons('salesman.order');
            $businessService = new BusinessService();

            if ($visit->exists) {
                if (isset($result['order']['order_form']) && ($orderForms = array_filter($result['order']['order_form']))) {
                    //验证陈列费或抵费商品是否合法并返回结果
                    if ($customer->display_type == cons('salesman.customer.display_type.cash') && isset($data['display_fee'])) {
                        //验证陈列费
                        $validate = $businessService->validateDisplayFee($data['display_fee'], $orderForms['amount'],
                            $customer);
                        if (!$validate) {
                            $visit->delete();
                            return $businessService->getError();
                        }

                    } elseif ($customer->display_type == cons('salesman.customer.display_type.mortgage') && isset($data['mortgage'])) {
                        //验证陈列费抵费商品
                        $validate = $businessService->validateMortgage($data['mortgage'], $customer);
                        if (!$validate) {
                            $visit->delete();
                            return $businessService->getError();
                        }
                    }
                    $orderForms['salesman_visit_id'] = $visit->id;
                    $orderForms['salesman_customer_id'] = $data['salesman_customer_id'];
                    $orderForms['order_remark'] = isset($data['order_remark']) ? $data['order_remark'] : '';
                    $orderForms['display_remark'] = isset($data['display_remark']) ? $data['display_remark'] : '';
                    $orderForms['type'] = $orderConf['type']['order'];
                    $orderForms['shop_id'] = $salesman->shop_id;
                    //促销活动
                    $apply_promo_id = array_get($data, 'apply_promo_id', null);
                    if (!is_null($apply_promo_id)) {
                        $salesmanApplyPromo = $salesman->applyPromo()->find($data['apply_promo_id']);
                        if (!$salesmanApplyPromo || !empty($salesmanApplyPromo->use_date)) {
                            return '促销活动无效!';
                        }
                        if (!is_null($salesmanApplyPromo->order)) {
                            return '促销活动已在其他订单使用!';
                        }
                        $orderForms['apply_promo_id'] = $salesmanApplyPromo->id;
                    }
                    $orderForm = $salesman->orders()->create($orderForms);
                    if ($orderForm->exists) {
                        $orderGoodsArr = [];
                        foreach ($orderForms['goods'] as $orderGoods) {
                            $orderGoods['salesman_visit_id'] = $visit->id;
                            $orderGoods['type'] = $orderConf['goods']['type']['order'];

                            $orderGoodsArr[] = new SalesmanVisitOrderGoods($orderGoods);
                        }
                        /*$promo = $orderForm->applyPromo->promo ?? false;
                        if ($promo && in_array($promo->type,
                                [cons('promo.type.money-goods'), cons('promo.type.goods-goods')])
                        ) {
                            $orderPromoGoods = [];
                            foreach ($promo->rebate as $promoGoods) {
                                $orderPromoGoods[$promoGoods->goods_id] = [
                                    'quantity' => $promoGoods->quantity,
                                    'piece' => $promoGoods->unit
                                ];
                            }
                            $orderForm->promoGoods()->sync($orderPromoGoods);
                        }*/
                        //订单商品
                        $orderForm->orderGoods()->saveMany($orderGoodsArr);
                        //礼物
                        if ($gifts = array_get($data, 'gifts')) {
                            $giftList = [];
                            foreach ($gifts as $gift) {
                                $giftList[$gift['id']] = [
                                    'num' => $gift['num'],
                                    'pieces' => $gift['pieces'],
                                ];
                            }
                            $orderForm->gifts()->sync($giftList);
                        }

                        //陈列费
                        if (isset($validate) && $customer->display_type != cons('salesman.customer.display_type.no')) {
                            $this->_addDisplayList($validate, $orderForm);
                        }
                    }
                }
                if (isset($result['order']['return_order'])) {
                    $result['order']['return_order']['salesman_visit_id'] = $visit->id;
                    $result['order']['return_order']['type'] = $orderConf['type']['return_order'];
                    $result['order']['return_order']['salesman_customer_id'] = $data['salesman_customer_id'];
                    $result['order']['return_order']['shop_id'] = $salesman->shop_id;

                    $returnOrder = $salesman->orders()->create($result['order']['return_order']);
                    if ($returnOrder->exists) {
                        $orderGoodsArr = [];
                        foreach ($result['order']['return_order']['goods'] as $orderGoods) {
                            $orderGoods['salesman_visit_id'] = $visit->id;
                            $orderGoods['type'] = $orderConf['goods']['type']['return'];
                            $orderGoodsArr[] = new SalesmanVisitOrderGoods($orderGoods);
                        }
                        $returnOrder->orderGoods()->saveMany($orderGoodsArr);
                    }
                }
                if (!empty($result['goodsRecode'])) {
                    $goodsRecodes = $result['goodsRecode'];
                    $goodsRecodeArr = [];
                    foreach ($goodsRecodes as $goodsRecode) {
                        $goodsRecodeArr[] = new SalesmanVisitGoodsRecord($goodsRecode);
                    }
                    $visit->goodsRecord()->saveMany($goodsRecodeArr);
                }
                return 'success';
            }
        });
        if ($result === 'success') {
            return $this->success(['id' => $visit->id]);
        } else {
            $visit->delete();
            return $this->error(is_string($result) ? $result : '添加拜访记录时出现错误');
        }
    }

    /**
     * 添加拜访照片
     *
     * @param \Illuminate\Http\Request $request
     * @param $visit
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function addPhotos(Request $request, $visit)
    {
        try {
            if (!$visit || $visit->salesman_id != salesman_auth()->id()) {
                return $this->error('拜访信息出错!');
            }
            if (!$visit) {
                return $this->error('没有拜访记录!');
            }
            return $visit->fill($request->only('photos'))->save() ? $this->success() : $this->error('图片上传失败!');
        } catch (\Exception $e) {
            return $this->error('图片上传失败!');
        }
    }

    /**
     * 拜访详情
     *
     * @param \App\Models\SalesmanVisit $visit
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function show(SalesmanVisit $visit)
    {
        if (!$visit || $visit->salesman_id != salesman_auth()->id()) {
            return $this->error('拜访信息出错');
        }
        $visit->load([
            'orders.orderGoods',
            'orders.mortgageGoods',
            'orders.gifts',
            'goodsRecord',
            'salesmanCustomer.shippingAddress',
            'orders.orderGoods.goods',
            'orders.applyPromo.promo',
            'photos'
        ]);
        $visitData = head((new BusinessService())->formatVisit([$visit], true)['visitData']);
        $visitData['mortgage'] = isset($visitData['mortgage']) ? $visitData['mortgage'] : [];
        $visitData['statistics'] = isset($visitData['statistics']) ? array_values($visitData['statistics']) : [];

        return $this->success(compact('visitData'));
    }

    /**
     * 是否可添加拜访
     *
     * @param $customer_id
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function canAdd($customer_id)
    {
//        $salesman_id = salesman_auth()->id();
//        $start = Carbon::now()->startOfDay();
//        $end = Carbon::now()->endOfDay();
//        $visit = SalesmanVisit::where(['salesman_customer_id' => $customer_id, 'salesman_id' => $salesman_id])
//            ->whereBetween('created_at', [$start, $end])->lists('id');
        $visit = [];
        return $this->success(compact('visit'));
    }


    /**
     * 根据月份获取剩余陈列费
     *
     * @param \Illuminate\Http\Request $request
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function surplusDisplayFee(Request $request)
    {
        $customerId = $request->input('customer_id');
        $month = $request->input('month');
        //$customer = salesman_auth()->user()->customers()->find($customerId);
        $customer = SalesmanCustomer::find($customerId);
        if (is_null($customer)) {
            return $this->error('客户不存在');
        }
        if (auth()->user() && Gate::denies('validate-customer', $customer)) {
            return $this->error('身份错误');
        }
        if ($customer->display_type != cons('salesman.customer.display_type.cash') || $month > $customer->display_end_month || $month < $customer->display_start_month) {
            return $this->error('本月不可发放');
        }

        //获取当月陈列费未审核订单
        $noConfirm = $customer->displayList()
            ->where([
                'month' => $month,
                'mortgage_goods_id' => 0
            ])->whereHas('order', function ($query) {
                $query->where('status', cons('salesman.order.status.not_pass'));
            })->get();
        return $this->success([
            'surplus' => (new BusinessService())->surplusDisplayFee($customer, $month),
            'noConfirm' => $noConfirm,
            'noConfirmSum' => $noConfirm->sum('used'),
        ]);
    }

    /**
     * 根据月份获取剩余陈列商品
     *
     * @param \Illuminate\Http\Request $request
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function surplusMortgageGoods(Request $request)
    {
        $customerId = $request->input('customer_id');
        $month = $request->input('month');

        //$customer = salesman_auth()->user()->customers()->find($customerId);
        $customer = SalesmanCustomer::find($customerId);
        if (is_null($customer)) {
            return $this->error('客户不存在');
        }

        if (auth()->user() && Gate::denies('validate-customer', $customer)) {
            return $this->error('身份错误');
        }

        if ($customer->display_type != cons('salesman.customer.display_type.mortgage') || $month > $customer->display_end_month || $month < $customer->display_start_month) {
            return $this->error('本月不可发放');
        }

        $mortgages = $customer->mortgageGoods;

        if (is_null($mortgages)) {
            return $this->success([]);
        }

        //获取当月陈列费未审核订单

        $noConfirm = $customer->displayList()
            ->with('mortgageGoods')
            ->where(['month' => $month])
            ->whereIn('mortgage_goods_id', $mortgages->pluck('id'))
            ->whereHas('order', function ($query) {
                $query->where('status', cons('salesman.order.status.not_pass'));
            })->orderBy('id', 'desc')->get();

        $noConfirms = [];
        foreach ($noConfirm as $item) {
            $orderId = $item->salesman_visit_order_id;
            $mortgage = [
                'id' => $item->mortgage_goods_id,
                'name' => $item->mortgageGoods->goods_name,
                'num' => $item->used,
                'piecesName' => $item->mortgageGoods->pieces_name
            ];
            $noConfirms[$orderId] = [
                'id' => $orderId,
                'time' => (string)$item->created_at,
                'mortgageGoods' => isset($noConfirms[$orderId]['mortgageGoods']) ? $noConfirms[$orderId]['mortgageGoods']->push($mortgage)
                    : collect([$mortgage])
            ];
        }
        return $this->success([
            'surplus' => (new BusinessService())->surplusMortgageGoods($customer, $month, $mortgages),
            'noConfirms' => array_values($noConfirms)
        ]);
    }

    /**
     * 添加陈列费
     *
     * @param $data
     * @param \App\Models\SalesmanVisitOrder $orderForm
     * @return array|\Illuminate\Database\Eloquent\Collection
     */
    private function _addDisplayList($data, SalesmanVisitOrder $orderForm)
    {
        return $orderForm->displayList()->saveMany($data);
    }

    /**
     * 格式化拜访记录
     *
     * @param $data
     * @return array
     */
    private function _formatData($data)
    {
        $order = [];
        $goodsRecode = [];

        if (!isset($data['goods'])) {
            return compact('order', 'goodsRecode');
        }

        foreach ($data['goods'] as $goods) {
            if (isset($goods['order_form'])) {
                //$order['order_form']['display_fee'] = isset($data['display_fee']) ? $data['display_fee'] : 0;
                $order['order_form']['amount'] = isset($order['order_form']['amount']) ?
                    bcadd($order['order_form']['amount'],
                        bcmul($goods['order_form']['price'], $goods['order_form']['num'], 2), 2) :
                    bcmul($goods['order_form']['price'], $goods['order_form']['num'], 2);
                $order['order_form']['goods'][] = [
                    'goods_id' => $goods['id'],
                    'price' => $goods['order_form']['price'],
                    'num' => $goods['order_form']['num'],
                    'pieces' => $goods['order_form']['pieces'],
                    'amount' => bcmul($goods['order_form']['price'], $goods['order_form']['num'], 2)
                ];
            }

            if (isset($goods['return_order'])) {
                $order['return_order']['amount'] = isset($order['return_order']['amount']) ?
                    bcadd($order['return_order']['amount'], $goods['return_order']['amount'],
                        2) : $goods['return_order']['amount'];
                $order['return_order']['goods'][] = [
                    'goods_id' => $goods['id'],
                    'num' => $goods['return_order']['num'],
                    'pieces' => array_get($goods['return_order'], 'pieces', 0),
                    'amount' => $goods['return_order']['amount']
                ];
            }
            if ($goods['stock'] || $goods['production_date']) {
                $goodsRecode[] = [
                    'goods_id' => $goods['id'],
                    'stock' => $goods['stock'],
                    'production_date' => $goods['production_date']
                ];
            }

        }
        return compact('order', 'goodsRecode');
    }


}
