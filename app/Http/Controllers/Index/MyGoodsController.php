<?php

namespace App\Http\Controllers\Index;

use App\Models\Attr;
use App\Models\Goods;
use App\Services\CategoryService;
use App\Services\GoodsService;
use App\Http\Requests;
use App\Services\AttrService;
use Illuminate\Http\Request;
use DB;
use Gate;
use Illuminate\Support\Facades\Response;


class MyGoodsController extends Controller
{
    protected $sort = [
        'name',
        'price',
        'new'
    ];

    /**
     * Display a listing of the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $gets = $request->all();
        $data = $this->_formatGet($gets);
        $shop = auth()->user()->shop;

        $result = GoodsService::getShopGoods($shop, $data);
        $myGoods = $result['goods']->orderBy('id', 'DESC')->paginate();

        $cateIds = $myGoods->pluck('category_id')->all();
        $cateName = [];

        foreach (CategoryService::getCategories() as $key => $category) {
            if (in_array($key, $cateIds)) {
                $cateName[$key] = $category['name'];
            }
        }
        $cateId = isset($data['category_id']) ? $data['category_id'] : -1;
        $categories = CategoryService::formatShopGoodsCate($shop, $cateId);

        return view('index.my-goods.index', [
            'goods' => $myGoods,
            'goodsCateName' => $cateName,
            'categories' => $categories,
            'attrs' => $result['attrs'],
            'searched' => $result['searched'],
            'moreAttr' => $result['moreAttr'],
            'get' => $gets,
            'data' => $data
        ]);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //默认加入店铺配送地址
        $shop = auth()->user()->shop()->with(['deliveryArea'])->first();
        $shopDelivery = $shop->deliveryArea->each(function ($area) {
            $area->id = '';
        });
        $goods = new Goods;
        $goods->deliveryArea = $shopDelivery;
        return view('index.my-goods.goods', [
            'goods' => $goods,
            'attrs' => [],
        ]);
    }

    /**
     * Show the form for creating some new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function batchCreate()
    {
        return view('index.my-goods.batch-create');
    }


    /**
     * Display the specified resource.
     *
     * @param $goods
     * @return \Illuminate\View\View
     */
    public function show($goods)
    {
        if (Gate::denies('validate-my-goods', $goods)) {
            return redirect(url('my-goods'));
        }
        $attrs = (new AttrService())->getAttrByGoods($goods, true);
        /* $coordinate = $goods->deliveryArea->each(function ($area) {
             $area->coordinate;
         });*/
        return view('index.my-goods.detail',
            ['goods' => $goods, 'attrs' => $attrs/*, 'coordinates' => $coordinate->toJson()*/]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $goods
     * @return Response
     */
    public function edit($goods)
    {
        if (Gate::denies('validate-my-goods', $goods)) {
            return redirect(url('my-goods'));
        }
        $goodsAttr = $goods->attr;
        //获取所有标签
        $attrGoods = [];
        foreach ($goodsAttr as $attr) {
            $attrGoods[$attr->pid] = $attr->pivot->toArray();
        }

        $attrResults = Attr::select(['attr_id', 'pid', 'name'])->where('category_id',
            $goods->category_id)->get()->toArray();

        $attrResults = (new AttrService($attrResults))->format();
        return view('index.my-goods.goods', [
            'goods' => $goods,
            'attrs' => $attrResults,
            'attrGoods' => $attrGoods,
        ]);
    }

    /**
     * 模板下载
     *
     * @return \Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function downloadTemplate()
    {
        $userType = auth()->user() ? auth()->user()->type : cons('user.type.wholesaler');
        $fileName = array_search($userType, cons('user.type'));
        $file = public_path('images/') . $fileName . '.xls';
        if (is_file($file)) {
            return Response::download($file);
        }
        return $this->error('文件不存在');
    }

    /**
     * 格式化查询每件
     *
     * @param $get
     * @return array
     */
    private function _formatGet($get)
    {
        $data = [];
        foreach ($get as $key => $val) {
            if (starts_with($key, 'attr_')) {
                $pid = explode('_', $key)[1];
                $data['attr'][$pid] = $val;
            } else {
                $data[$key] = $val;
            }
        }

        return $data;
    }

}
