<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Coordinate;
use App\Models\DeliveryArea;
use App\Models\Goods;
use App\Http\Requests;
use App\Models\Images;
use App\Services\AddressService;
use App\Services\AttrService;
use App\Services\GoodsService;
use Illuminate\Http\Request;
use Gate;

class MyGoodsController extends Controller
{

    public function index(Request $request)
    {
        $gets = $request->all();

        $goods = auth()->user()->shop->goods()->with('images')->select([
            'id',
            'name',
            'sales_volume',
            'price_retailer',
            'price_wholesaler',
            'min_num_retailer',
            'min_num_wholesaler',
            'user_type',
            'is_new',
            'is_promotion',
            'cate_level_1',
            'cate_level_2'
        ]);

        $result = GoodsService::getGoodsBySearch($gets, $goods, false);
        return $this->success([
            'goods' => $result['goods']->paginate()->toArray(),
            'categories' => $result['categories']
        ]);
    }
    /**
     * tore a newly created resource in storage.
     *
     * @param \App\Http\Requests\Api\v1\CreateGoodsRequest $request
     * @return \Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function store(Requests\Api\v1\CreateGoodsRequest $request)
    {
        $attributes = $request->all();
        $goods = auth()->user()->shop->goods()->create($attributes);
        if ($goods->exists) {
            // 更新配送地址
            $this->updateDeliveryArea($goods, $request->input('area'));

            // 更新标签
            $this->updateAttrs($goods, $attributes['attrs']);
            return $this->created('添加商品成功');
        }
        return $this->error('添加商品出现错误');
    }

    /**
     *
     *
     * @param $goods
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function show($goods)
    {
        $goods->load(['images', 'deliveryArea.coordinate']);
        if (Gate::denies('validate-my-goods', $goods)) {
            return $this->forbidden('权限不足');
        }
        $attrs = (new AttrService())->getAttrByGoods($goods, true);
        $goods->shop_name = $goods->shop()->pluck('name');
        $goods->attrs = $attrs;

        return $this->success(['goods' => $goods]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\Api\v1\UpdateGoodsRequest $request
     * @param $goods
     * @return \Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function update(Requests\Api\v1\UpdateGoodsRequest $request, $goods)
    {
        $attributes = $request->all();
        if ($goods->fill($attributes)->save()) {
            // 更新配送地址
            $this->updateDeliveryArea($goods, $attributes['area']);
            // 更新标签
            $this->updateAttrs($goods, $attributes['attrs']);
            return $this->success('更新商品成功');
        }
        return $this->error('更新商品时遇到问题');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $goods
     * @return Response
     */
    public function destroy($goods)
    {
        if ($goods->delete()) {
            return $this->success('删除商品成功');
        }
        return $this->error('删除商品时遇到问');
    }

    /**
     * 商品上下架
     *
     * @param \Illuminate\Http\Request $request
     * @param $goodsId
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function shelve(Request $request, $goodsId)
    {
        $goods = Goods::find($goodsId);
        $status = intval($request->input('status'));
        $goods->status = $status;
        $statusVal = cons()->valueLang('goods.status', $status);
        if ($goods->save()) {
            return $this->success('商品' . $statusVal . '成功');
        }
        return $this->error('商品' . $statusVal . '失败');
    }

    /**
     * 获取商品图片
     *
     * @param \Illuminate\Http\Request $request
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function getImages(Request $request)
    {
        $cate = array_filter($request->only('cate_level_1', 'cate_level_2', 'cate_level_3'));

        if (empty($cate)) {
            return $this->success(['goodsImage' => []]);
        }

        $attrs = $request->input('attrs');

        $goodsImage = Images::where($cate);
        if ($attrs) {
            $goodsImage = $goodsImage->ofAttr($attrs);
        }
        $goodsImage = $goodsImage->paginate()->toArray();
        return $this->success(['goodsImage' => $goodsImage]);
    }

    /**
     * 更新配送地址处理
     *
     * @param $model
     * @param $area
     * @return bool
     */
    private function updateDeliveryArea($model, $area)
    {
        //配送区域添加
        $areaArr = (new AddressService($area))->formatAddressPost();

        $nowArea = $model->deliveryArea;
        if (count($nowArea) == count(array_filter($area['province_id']))) {
            return true;
        }
        //删除原有配送区域信息
        $model->deliveryArea->each(function ($address) {
            $address->delete();
        });
        if (!empty($areaArr)) {
            foreach ($areaArr as $data) {
                if (isset($data['coordinate'])) {
                    $coordinate = $data['coordinate'];
                    unset($data['coordinate']);
                }
                $areas = new DeliveryArea($data);
                $areaModel = $model->deliveryArea()->save($areas);
                if (isset($coordinate)) {
                    $areaModel->coordinate()->save(new Coordinate($coordinate));
                }
            }
        }
        return true;
    }

    /**
     * 更新标签
     *
     * @param $model
     * @param $attrs
     */
    private function updateAttrs($model, $attrs)
    {
        //删除所有标签
        $model->attr()->detach();

        $attrArr = [];
        foreach ($attrs as $pid => $id) {
            $attrArr[$id] = ['attr_pid' => $pid];
        }
        if (!empty($attrArr)) { 
            $model->attr()->sync($attrArr);
        }
    }
}