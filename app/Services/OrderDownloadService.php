<?php
namespace App\Services;

use App\Models\Order;
use PhpOffice\PhpWord\PhpWord;

/**
 * Created by PhpStorm.
 * User: Colin
 * Date: 2016/9/6
 * Time: 15:22
 */
class OrderDownloadService
{

    /**
     * 下载
     *
     * @param $orders
     */
    public function download($orders)
    {

        $shopId = $orders->first()->shop_id;

        $modelId = app('order.download')->getTemplete($shopId);

        if (method_exists($this, 'model' . $modelId)) {
            // Creating the new document...
            $phpWord = new PhpWord();

            $phpWord->setDefaultFontName('仿宋');
            if ($modelId == cons('order.templete.third') || $modelId == cons('order.templete.fourth')) {
                $phpWord->setDefaultFontSize(10.5);
                $phpWord->addTableStyle('table', ['borderSize' => 1, 'alignment' => 'center'],
                    ['name' => '仿宋', 'size' => 15, 'align ' => 'center']);
                // Adding an empty Section to the document...

                $phpWord->addParagraphStyle('Normal', [
                    'spaceBefore' => 0,
                    'spaceAfter' => 0,
                    'lineHeight' => 1,  // 行间距
                ]);
            } else {
                $phpWord->setDefaultFontSize(12);
                $phpWord->addTableStyle('table', ['borderSize' => 1, 'alignment' => 'center'],
                    ['name' => '仿宋', 'size' => 20, 'align ' => 'center']);
                // Adding an empty Section to the document...

                $phpWord->addParagraphStyle('Normal', [
                    'spaceBefore' => 0,
                    'spaceAfter' => 0,
                    'lineHeight' => 1.2,  // 行间距
                ]);
            }


            call_user_func_array([$this, 'model' . $modelId], [$orders, $phpWord, $modelId]);
        }

    }

    /**
     * 模板1
     *
     * @param $orders
     * @param \PhpOffice\PhpWord\PhpWord $phpWord
     */
    public function model1($orders, PhpWord $phpWord, $modelId)
    {
        $this->model1AndModel3($orders, $phpWord, $modelId);
    }

    /**
     *
     * 模板2
     *
     * @param $orders
     * @param \PhpOffice\PhpWord\PhpWord $phpWord
     */
    public function model2($orders, PhpWord $phpWord, $modelId)
    {
        $this->model2AndModel4($orders, $phpWord, $modelId);

    }

    /**
     * 模版3
     *
     * @param $orders
     * @param \PhpOffice\PhpWord\PhpWord $phpWord
     */
    public function model3($orders, PhpWord $phpWord, $modelId)
    {
        $this->model1AndModel3($orders, $phpWord, $modelId);
    }

    /**
     * 模版4
     *
     * @param $orders
     * @param \PhpOffice\PhpWord\PhpWord $phpWord
     */
    public function model4($orders, PhpWord $phpWord, $modelId)
    {
        $this->model2AndModel4($orders, $phpWord, $modelId);

    }

    /**
     * 模板1和模板3通用生成方法
     *
     * @param $orders
     * @param \PhpOffice\PhpWord\PhpWord $phpWord
     * @param $modelId
     */
    public function model1AndModel3($orders, PhpWord $phpWord, $modelId)
    {
        $orderPayTypes = cons('pay_type');

        $vAlign = ['valign' => 'center'];
        $cellAlignCenter = ['align' => 'center'];
        $gridSpan2 = ['valign' => 'center', 'gridSpan' => 2];
        $gridSpan3 = ['valign' => 'center', 'gridSpan' => 3];
        $gridSpan5 = ['valign' => 'center', 'gridSpan' => 5];


        foreach ($orders as $item) {
            if ($modelId == cons('order.templete.first')) {
                $section = $phpWord->addSection(['orientation' => 'landscape']);
            } else {
                $section = $phpWord->addSection();
            }


            $section->addText($item->shop_name . ' 送货单',
                ['bold' => true, 'size' => $modelId == cons('order.templete.first') ? 18 : 14],
                ['alignment' => 'center']);

            $shopContact = '联系电话：' . $item->shop->contact_info;
            $shopContact .= '   ' . $item->shop->contact_person;
            $shopContact .= '   地址：' . $item->shop->address;
            $section->addText($shopContact, null, ['alignment' => 'center']);

            $section->addText('');

            $firstRow = '录单日期 ：' . $item->created_at->toDateString();
            $firstRow .= '        平台订单号 ： ' . $item->id;
            $firstRow .= '             单据编号 ：' . $item->numbers;
            $section->addText($firstRow);
            $userContact = '购买单位 ：' . $item->user_shop_name;
            if ($item->pay_type == $orderPayTypes['pick_up']) {
                $userContact .= '自提';
            } else {
                $userContact .= '  ' . ($item->shippingAddress ? $item->shippingAddress->consigner . '-' . $item->shippingAddress->phone . '  ' . $item->shippingAddress->address->address_name : '');
            }
            $userContact .= '      制单人 ： 管理员';

            $section->addText($userContact);

            $table = $section->addTable('table');
            $table->addRow(20);
            $table->addCell($modelId == cons('order.templete.first') ? 2200 : 1540, $vAlign)->addText('商品条码', null,
                $cellAlignCenter);
            $table->addCell($modelId == cons('order.templete.first') ? 4100 : 2870, $vAlign)->addText('商品名称', null,
                $cellAlignCenter);
            $table->addCell($modelId == cons('order.templete.first') ? 1800 : 1260, $vAlign)->addText('商品规格', null,
                $cellAlignCenter);
            $table->addCell($modelId == cons('order.templete.first') ? 1500 : 1050, $vAlign)->addText('单价', null,
                $cellAlignCenter);
            $table->addCell($modelId == cons('order.templete.first') ? 700 : 490, $vAlign)->addText('单位', null,
                $cellAlignCenter);
            $table->addCell($modelId == cons('order.templete.first') ? 1000 : 700, $vAlign)->addText('数量', null,
                $cellAlignCenter);
            $table->addCell($modelId == cons('order.templete.first') ? 1000 : 700, $vAlign)->addText('金额', null,
                $cellAlignCenter);
            $table->addCell($modelId == cons('order.templete.first') ? 2500 : 1750, $vAlign)->addText('促销信息', null,
                $cellAlignCenter);

            $orderGoodsNum = 0;

            $userType = $item->user_type_name;

            foreach ($item->goods as $goods) {
                $table->addRow(20);
                $table->addCell($modelId == cons('order.templete.first') ? 2200 : 1540,
                    $vAlign)->addText($goods->bar_code, null, $cellAlignCenter);
                $table->addCell($modelId == cons('order.templete.first') ? 4100 : 2870, $vAlign)->addText($goods->name,
                    null, $cellAlignCenter);
                $table->addCell($modelId == cons('order.templete.first') ? 1800 : 1260,
                    $vAlign)->addText($goods->{'specification_' . $userType}, null, $cellAlignCenter);
                $table->addCell($modelId == cons('order.templete.first') ? 1500 : 1050,
                    $vAlign)->addText($goods->pivot->price, null, $cellAlignCenter);
                $table->addCell($modelId == cons('order.templete.first') ? 700 : 490,
                    $vAlign)->addText(cons()->valueLang('goods.pieces',
                    $goods->pivot->pieces), null, $cellAlignCenter);
                $table->addCell($modelId == cons('order.templete.first') ? 1000 : 700,
                    $vAlign)->addText($goods->pivot->num, null, $cellAlignCenter);
                $table->addCell($modelId == cons('order.templete.first') ? 1000 : 700,
                    $vAlign)->addText($goods->pivot->total_price, null, $cellAlignCenter);
                $table->addCell($modelId == cons('order.templete.first') ? 2500 : 1750,
                    $vAlign)->addText(mb_substr($goods->promotion_info, 0, 20));

                $orderGoodsNum = $orderGoodsNum + $goods->pivot->num;
            }
            $table->addRow(16);
            $table->addCell(0, $gridSpan5)->addText('总计', null, $cellAlignCenter);
            $table->addCell(0, $vAlign)->addText($orderGoodsNum, null, $cellAlignCenter);

            $price = $item->price . ($item->coupon_id ? '  优惠：' . bcsub($item->price, $item->after_rebates_price,
                        2) . '  应付：' . $item->after_rebates_price : '');
            $table->addCell(0, $gridSpan2)->addText($price, null, $cellAlignCenter);

            $table->addRow();
            $table->addCell(0, $gridSpan5)->addText('备注：' . $item->remark);
            $qrcodeCell = $table->addCell(0, $gridSpan3);
            $qrcodeCell->addText($item->shop_name . '店铺首页地址：');
            $modelId == cons('order.templete.first') ? $qrcodeCell->addImage((new ShopService())->qrcode($item->shop_id)) : $qrcodeCell->addImage((new ShopService())->qrcode($item->shop_id,
                70));
            $qrcodeCell->addText('一站式零售服务平台- -订百达');


            $text = '制单:';
            $text .= '        业务员：' . ($item->salesmanVisitOrder ? $item->salesmanVisitOrder->salesman_name : '');
            $text .= '        送货人：' . ($item->deliveryMan ? $item->deliveryMan->name : '');
            $text .= '        仓管：';
            $text .= '        收款人：' . ($item->systemTradeInfo ? cons()->valueLang('trade.pay_type',
                    $item->systemTradeInfo->pay_type) : '');

            $section->addText($text, ['size' => $modelId == cons('order.templete.first') ? 12 : 10],
                ['width' => $modelId == cons('order.templete.first') ? 4000 : 2800]);

            $this->addDownloadCount($item);
        }
        $name = date('Ymd') . strtotime('now') . '.doc';


        $phpWord->save($name, 'Word2007', true);
    }

    /**
     * 模板2和模板4通用生成方法
     *
     * @param $orders
     * @param \PhpOffice\PhpWord\PhpWord $phpWord
     * @param $modelId
     */
    public function model2AndModel4($orders, PhpWord $phpWord, $modelId)
    {
        $orderPayTypes = cons('pay_type');

        $vAlign = ['valign' => 'center'];
        $cellAlignCenter = ['align' => 'center'];
        $gridSpan2 = ['valign' => 'center', 'gridSpan' => 2];
        $gridSpan3 = ['valign' => 'center', 'gridSpan' => 3];
        $gridSpan5 = ['valign' => 'center', 'gridSpan' => 5];
        $gridSpan8 = ['valign' => 'center', 'gridSpan' => 8];


        foreach ($orders as $item) {
            if ($modelId == cons('order.templete.second')) {
                $section = $phpWord->addSection(['orientation' => 'landscape']);
            } else {
                $section = $phpWord->addSection();
            }

            $section->addText($item->shop_name . ' 送货单', ['bold' => true, 'size' => 15], ['alignment' => 'center']);
            $table = $section->addTable('table');

            $table->addRow();
            $table->addCell($modelId == cons('order.templete.second') ? 6300 : 4410,
                $gridSpan2)->addText('录单日期 ：' . $item->created_at->toDateString());
            $table->addCell($modelId == cons('order.templete.second') ? 4000 : 2800,
                $gridSpan3)->addText('平台订单号 ：' . $item->id);
            $table->addCell($modelId == cons('order.templete.second') ? 4000 : 2800,
                $gridSpan3)->addText('单据编号 ：' . $item->numbers);

            $userInfo = '购买单位 ：' . $item->user_shop_name;
            $userInfo .= $item->pay_type == $orderPayTypes['pick_up'] ? '   自提' : '     联系电话 ：' . ($item->shippingAddress ? $item->shippingAddress->consigner . '-' . $item->shippingAddress->phone : '');
            $userInfo .= '  地址 ：' . (is_null($item->shippingAddress->address) ? '' : $item->shippingAddress->address->address_name);

            $table->addRow();
            $table->addCell(0, $gridSpan8)->addText($userInfo);

            $table->addRow(20);
            $table->addCell($modelId == cons('order.templete.second') ? 2400 : 1680, $vAlign)->addText('商品条码', null,
                $cellAlignCenter);
            $table->addCell($modelId == cons('order.templete.second') ? 3900 : 2730, $vAlign)->addText('商品名称', null,
                $cellAlignCenter);
            $table->addCell($modelId == cons('order.templete.second') ? 1800 : 1260, $vAlign)->addText('商品规格', null,
                $cellAlignCenter);
            $table->addCell($modelId == cons('order.templete.second') ? 1500 : 1050, $vAlign)->addText('单价', null,
                $cellAlignCenter);
            $table->addCell($modelId == cons('order.templete.second') ? 700 : 490, $vAlign)->addText('单位', null,
                $cellAlignCenter);
            $table->addCell($modelId == cons('order.templete.second') ? 1000 : 700, $vAlign)->addText('数量', null,
                $cellAlignCenter);
            $table->addCell($modelId == cons('order.templete.second') ? 1000 : 700, $vAlign)->addText('金额', null,
                $cellAlignCenter);
            $table->addCell($modelId == cons('order.templete.second') ? 2500 : 1750, $vAlign)->addText('促销信息', null,
                $cellAlignCenter);

            $orderGoodsNum = 0;

            $userType = $item->user_type_name;
            foreach ($item->goods as $goods) {
                $table->addRow(20);
                $table->addCell($modelId == cons('order.templete.second') ? 2200 : 1540,
                    $vAlign)->addText($goods->bar_code, null, $cellAlignCenter);
                $table->addCell($modelId == cons('order.templete.second') ? 3100 : 2170, $vAlign)->addText($goods->name,
                    null, $cellAlignCenter);
                $table->addCell($modelId == cons('order.templete.second') ? 1800 : 1260,
                    $vAlign)->addText($goods->{'specification_' . $userType}, null, $cellAlignCenter);
                $table->addCell($modelId == cons('order.templete.second') ? 1500 : 1050,
                    $vAlign)->addText($goods->pivot->price, null, $cellAlignCenter);
                $table->addCell($modelId == cons('order.templete.second') ? 700 : 490,
                    $vAlign)->addText(cons()->valueLang('goods.pieces',
                    $goods->pivot->pieces), null, $cellAlignCenter);
                $table->addCell($modelId == cons('order.templete.second') ? 1000 : 700,
                    $vAlign)->addText($goods->pivot->num, null, $cellAlignCenter);
                $table->addCell($modelId == cons('order.templete.second') ? 1000 : 700,
                    $vAlign)->addText($goods->pivot->total_price, null, $cellAlignCenter);
                $table->addCell($modelId == cons('order.templete.second') ? 2500 : 1750,
                    $vAlign)->addText(mb_substr($goods->promotion_info, 0, 20));

                $orderGoodsNum = $orderGoodsNum + $goods->pivot->num;
            }
            $table->addRow(16);
            $table->addCell(0, $gridSpan5)->addText('总计', null, $cellAlignCenter);
            $table->addCell(0, $vAlign)->addText($orderGoodsNum, null, $cellAlignCenter);

            $price = $item->price . ($item->coupon_id ? '  优惠：' . bcsub($item->price, $item->after_rebates_price,
                        2) . '  应付：' . $item->after_rebates_price : '');
            $table->addCell(0, $gridSpan2)->addText($price, null, $cellAlignCenter);

            $table->addRow();
            $table->addCell(0, $gridSpan5)->addText('备注：' . $item->remark);
            $qrcodeCell = $table->addCell(0, $gridSpan3);
            $qrcodeCell->addText($item->shop_name . '店铺首页地址：');
            $modelId == cons('order.templete.second') ? $qrcodeCell->addImage((new ShopService())->qrcode($item->shop_id)) : $qrcodeCell->addImage((new ShopService())->qrcode($item->shop_id,
                70));
            $qrcodeCell->addText('一站式零售服务平台- -订百达');


            $text = '   业务员：' . ($item->salesmanVisitOrder ? $item->salesmanVisitOrder->salesman_name : '') .
                '       送货人：' . ($item->deliveryMan ? $item->deliveryMan->name : '') .
                '       收款人：' . ($item->systemTradeInfo ? cons()->valueLang('trade.pay_type',
                    $item->systemTradeInfo->pay_type) : '');

            $section->addText($text, ['size' => $modelId == cons('order.templete.second') ? 12 : 10],
                ['width' => $modelId == cons('order.templete.second') ? 4000 : 2800]);

            $this->addDownloadCount($item);
        }
        $name = date('Ymd') . strtotime('now') . '.doc';


        $phpWord->save($name, 'Word2007', true);
    }

    /**
     * 添加下载次数
     *
     * @param \App\Models\Order $order
     * @return int
     */
    public function addDownloadCount(Order $order)
    {
        return $order->increment('download_count');
    }
}