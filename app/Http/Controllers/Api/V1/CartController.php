<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2015/9/6
 * Time: 17:57
 */

namespace App\Http\Controllers\Api\V1;

use App\Models\Goods;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected $user;

    public function __construct()
    {
        $this->user = auth()->user();
    }

    /**
     * 购物车首页
     */
    public function getIndex()
    {
        $myCarts = $this->user->carts();
        $carts = $myCarts->with('goods.images.image')->get();

        if (!$carts->isEmpty()) {
            // 将所有状态更新为零
            $myCarts->update(['status' => 0]);

            $carts = (new CartService($carts))->formatCarts();
        }

        return $this->success(['shops' => $carts->toArray()]);
    }

    /**
     * 商品添加至购物车
     *
     * @param \Illuminate\Http\Request $request
     * @param $goodsId
     * @return \WeiHeng\Responses\Apiv1Response
     */
    public function postAdd(Request $request, $goodsId)
    {
        $goodsInfo = Goods::active()->where('user_type', '>', $this->user->type)->find($goodsId);
        if (is_null($goodsInfo)) {
            return $this->error('商品不存在');
        }
        $buyNum = intval($request->input('num'));
        if ($goodsInfo->min_num > $buyNum) {
            return $this->error('不能小于购买量');
        }
        if ($goodsInfo->is_out) {
            return $this->error('该商品缺货');
        }
        if ($buyNum > 10000) {
            return $this->error('商品数量不能大于10000');
        }
        //查询是否有相同的商品,存在则合并
        $cart = $this->user->carts()->where('goods_id', $goodsId);
        if (!is_null($cart->pluck('id'))) {
            $cart->increment('num', $buyNum);

            return $this->success('加入购物车成功');
        }
        if ($this->user->carts()->create(['goods_id' => $goodsId, 'num' => $buyNum])->exists) {
            (new CartService)->increment();
            return $this->success('加入购物车成功');
        }

        return $this->error('加入购物车失败');
    }

    /**
     * 删除购物车商品
     *
     * @param $cartId
     * @return \Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function deleteDelete($cartId)
    {
        $cart = $this->user->carts()->find($cartId);

        if (is_null($cart) || !$cart->delete()) {
            return $this->error('删除失败');
        }

        (new CartService)->decrement();
        return $this->success('删除成功');

    }


}