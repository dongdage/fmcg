<?php
/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2015/9/10
 * Time: 10:56
 */
namespace App\Http\Controllers\Index;

use App\Models\Goods;
use App\Services\GoodsService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $gets = $request->all();
        $data = array_filter($this->_formatGet($gets));
        $goods = Goods::with('images')->select([
            'id',
            'name',
            'sales_volume',
            'price_retailer',
            'price_wholesaler',
            'is_new',
            'is_promotion',
            'cate_level_1',
            'cate_level_2'
        ])->where('user_type', '>', auth()->user()->type);

        $result = GoodsService::getGoodsBySearch($data, $goods);
        return view('index.search.index',
            [
                'goods' => $result['goods']->paginate(),
                'categories' => $result['categories'],
                'attrs' => $result['attrs'],
                'searched' => $result['searched'],
                'moreAttr' => $result['moreAttr'],
                'get' => $gets,
                'data' => $data
            ]);
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