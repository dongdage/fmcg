<?php

namespace App\Http\Controllers\Api\V1\ChildUser;

use App\Models\MortgageGoods;
use App\Models\Salesman;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Api\V1\Controller;
use Gate;

class MortgageGoodsController extends Controller
{

    /**
     * 获取所有陈列商品
     *
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function index()
    {
        $shop = salesman_auth()->user() ? salesman_auth()->user()->shop : child_auth()->user()->shop;

        $mortgageGoods = $shop->mortgageGoods()->active()->get()->each(function($mortgage){
            $mortgage->setAppends(['pieces_name']);
        });
        return $this->success(compact('mortgageGoods'));
    }

    /**
     * 商品禁/启用
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\MortgageGoods $mortgageGoods
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function status(Request $request, MortgageGoods $mortgageGoods)
    {
        if (Gate::forUser(child_auth()->user())->denies('validate-mortgage-goods', $mortgageGoods)) {
            return $this->error('商品不存在');
        }
        $status = intval($request->input('status'));
        $statusVal = cons()->valueLang('status', $status);
        if ($mortgageGoods->fill(['status' => $status])->save()) {
            return $status ? $this->success('启用成功') : $this->success(null);
        }
        return $this->error($statusVal . '时出现问题');
    }

    /**
     * 商品批量启/禁用
     *
     * @param \Illuminate\Http\Request $request
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function batchStatus(Request $request)
    {
        $goodsIds = (array)$request->input('goods_id');
        $status = intval($request->input('status'));
        $statusVal = cons()->valueLang('status', $status);
        if (empty($goodsIds)) {
            return $this->error('请选择要' . $statusVal . '的商品');
        }
        return child_auth()->user()->shop->mortgageGoods()->whereIn('id', $goodsIds)->update(['status' => $status])
            ? $this->success('商品' . $statusVal . '成功') : $this->error('商品' . $statusVal . '时出现问题');

    }

    /**
     * 商品更新
     *
     * @param \App\Http\Requests\Api\v1\UpdateMortgageGoodsRequest $request
     * @param \App\Models\MortgageGoods $mortgageGoods
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function update(Requests\Api\v1\UpdateMortgageGoodsRequest $request, MortgageGoods $mortgageGoods)
    {
        if (Gate::forUser(child_auth()->user())->denies('validate-mortgage-goods', $mortgageGoods)) {
            return $this->error('商品不存在');
        }

        $pieces = $request->only('pieces');

        return $mortgageGoods->fill($pieces)->save() ? $this->success('修改成功') : $this->error('修改商品时出现问题');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $mortgageGoods
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function destroy($mortgageGoods)
    {
        if (Gate::forUser(child_auth()->user())->denies('validate-mortgage-goods', $mortgageGoods)) {
            return $this->error('商品不存在');
        }
        return $mortgageGoods->delete() ? $this->success('移除成功') : $this->error('移除商品时出现问题');;
    }

    /**
     * 商品批量启/禁用
     *
     * @param \Illuminate\Http\Request $request
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function batchDestroy(Request $request)
    {
        $goodsIds = (array)$request->input('goods_id');
        return child_auth()->user()->shop->mortgageGoods()->whereIn('id',
            $goodsIds)->delete() ? $this->success('删除商品成功') : $this->success('删除商品时出现问题');

    }
}
