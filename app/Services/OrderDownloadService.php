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
                $phpWord->setDefaultFontSize(8);
                $phpWord->addTableStyle('table', ['borderSize' => 1, 'alignment' => 'center'],
                    ['name' => '仿宋', 'size' => 15, 'align ' => 'center']);

                $phpWord->addParagraphStyle('Normal', [
                    'spaceBefore' => 0,
                    'spaceAfter' => 0,
                    'lineHeight' => 1,  // 行间距
                ]);
            } else {
                $phpWord->setDefaultFontSize(8);
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
        $gridSpan4 = ['valign' => 'center', 'gridSpan' => 4];
        $gridSpan5 = ['valign' => 'center', 'gridSpan' => 5];
        $gridSpan6 = ['valign' => 'center', 'gridSpan' => 6];
        $gridSpan8 = ['valign' => 'center', 'gridSpan' => 8];
        $cellRowSpan = array('vMerge' => 'restart', 'valign' => 'center');
        $cellRowContinue = array('vMerge' => 'continue');


        $isLandscape = $modelId == cons('order.templete.first');

        $style = [
            'marginTop' => 0,
            'marginRight' => 0,
            'marginLeft' => 0,
            'marginBottom' => 0
        ];

        $isLandscape && $style['orientation'] = 'landscape';

        foreach ($orders as $item) {
            $section = $phpWord->addSection($style);
            $section->addText($item->shop_name . ' 送货单',
                ['bold' => true, 'size' => $isLandscape ? 18 : 14],
                ['alignment' => 'center']);

            $shopContact = '联系电话：' . $item->shop->contact_info;
            $shopContact .= '   ' . $item->shop->contact_person;
            $shopContact .= '   地址：' . $item->shop->address;
            $section->addText($shopContact, null, ['alignment' => 'center']);

            $section->addText('');

            $table = $section->addTable(['alignment' => 'center']);

            $table->addRow(20);
            $table->addCell($isLandscape ? 8000 : 5600, $vAlign)->addText('录单日期 ：' . $item->created_at->toDateString());
            $table->addCell($isLandscape ? 5000 : 3500, $vAlign)->addText('平台订单号 ： ' . $item->id);
            $table->addCell($isLandscape ? 3500 : 2450, $vAlign)->addText('单据编号 ：' . $item->numbers);

            $table->addRow(20);
            $userContact = '购买单位 ：' . $item->user_shop_name;
            if ($item->pay_type == $orderPayTypes['pick_up']) {
                $userContact .= '自提';
            } else {
                $userContact .= '  ' . ($item->shippingAddress ? $item->shippingAddress->consigner . '-' . $item->shippingAddress->phone . '  ' . $item->shippingAddress->address->address_name : '');
            }
            $table->addCell($isLandscape ? 13000 : 9100, $gridSpan2)->addText($userContact);
            $table->addCell($isLandscape ? 3500 : 2450, $vAlign)->addText('制单人 ： 管理员');


            $table = $section->addTable('table');


            $table->addRow(20);
            $table->addCell($isLandscape ? 3000 : 2400, $vAlign)->addText('商品条码', null,
                $cellAlignCenter);
            $table->addCell($isLandscape ? 5000 : 3200, $vAlign)->addText('商品名称', null,
                $cellAlignCenter);
            $table->addCell($isLandscape ? 1800 : 1260, $vAlign)->addText('商品规格', null,
                $cellAlignCenter);
            $table->addCell($isLandscape ? 1500 : 1050, $vAlign)->addText('单价', null,
                $cellAlignCenter);
            $table->addCell($isLandscape ? 700 : 490, $vAlign)->addText('单位', null,
                $cellAlignCenter);
            $table->addCell($isLandscape ? 1000 : 700, $vAlign)->addText('数量', null,
                $cellAlignCenter);
            $table->addCell($isLandscape ? 1000 : 700, $vAlign)->addText('金额', null,
                $cellAlignCenter);
            $table->addCell($isLandscape ? 2500 : 1750, $vAlign)->addText('促销信息', null,
                $cellAlignCenter);

            $orderGoodsNum = 0;

            $userType = $item->user_type_name;

            $orderGoods = (new OrderService())->explodeOrderGoods($item);

            foreach ($orderGoods['orderGoods'] as $goods) {
                $table->addRow(20);
                $table->addCell($isLandscape ? 2200 : 1540,
                    $vAlign)->addText($goods->bar_code, null, $cellAlignCenter);
                $table->addCell($isLandscape ? 4100 : 2870, $vAlign)->addText($goods->name,
                    null, $cellAlignCenter);
                $table->addCell($isLandscape ? 1800 : 1260,
                    $vAlign)->addText($goods->{'specification_' . $userType}, null, $cellAlignCenter);
                $table->addCell($isLandscape ? 1500 : 1050,
                    $vAlign)->addText($goods->pivot->price, null, $cellAlignCenter);
                $table->addCell($isLandscape ? 700 : 490,
                    $vAlign)->addText(cons()->valueLang('goods.pieces',
                    $goods->pivot->pieces), null, $cellAlignCenter);
                $table->addCell($isLandscape ? 1000 : 700,
                    $vAlign)->addText($goods->pivot->num, null, $cellAlignCenter);
                $table->addCell($isLandscape ? 1000 : 700,
                    $vAlign)->addText($goods->pivot->total_price, null, $cellAlignCenter);
                $table->addCell($isLandscape ? 2500 : 1750,
                    $vAlign)->addText(mb_substr($goods->promotion_info, 0, 20));

                $orderGoodsNum = $orderGoodsNum + $goods->pivot->num;
            }
            foreach ($item->gifts as $gift) {
                $table->addRow(20);
                $table->addCell($isLandscape ? 2200 : 1540,
                    $vAlign)->addText($gift->bar_code, null, $cellAlignCenter);
                $table->addCell($isLandscape ? 3100 : 2170, $vAlign)->addText($gift->name,
                    null, $cellAlignCenter);
                $table->addCell($isLandscape ? 1800 : 1260,
                    $vAlign)->addText($gift->{'specification_' . $userType}, null, $cellAlignCenter);
                $table->addCell($isLandscape ? 1500 : 1050,
                    $vAlign)->addText($gift->pivot->price, null, $cellAlignCenter);
                $table->addCell($isLandscape ? 700 : 490,
                    $vAlign)->addText(cons()->valueLang('goods.pieces',
                    $gift->pivot->pieces), null, $cellAlignCenter);
                $table->addCell($isLandscape ? 1000 : 700,
                    $vAlign)->addText($gift->pivot->num, null, $cellAlignCenter);
                $table->addCell($isLandscape ? 1000 : 700,
                    $vAlign)->addText($gift->pivot->total_price, null, $cellAlignCenter);
                $table->addCell($isLandscape ? 2500 : 1750,
                    $vAlign)->addText('赠品');

                $orderGoodsNum = $orderGoodsNum + $gift->pivot->num;
            }
            $table->addRow(16);
            $table->addCell(0, $gridSpan5)->addText('总计', null, $cellAlignCenter);
            $table->addCell(0, $vAlign)->addText($orderGoodsNum, null, $cellAlignCenter);

            $price = $item->price;
            if ($item->coupon_id) {
                $price .= ' - ' . bcsub($item->price, $item->after_rebates_price,
                        2) . ' = ' . $item->after_rebates_price;
            } elseif ($item->display_fee > 0) {
                $price .= ' - ' . $item->display_fee . ' = ' . $item->after_rebates_price;
            }
            $table->addCell(0, $gridSpan2)->addText($price, null, $cellAlignCenter);

            if (!$orderGoods['mortgageGoods']->isEmpty()) {
                $table->addRow();
                $table->addCell(0, $gridSpan8)->addText('抵费商品', ['size' => 10], $cellAlignCenter);
                foreach ($orderGoods['mortgageGoods'] as $goods) {
                    $table->addRow(20);
                    $table->addCell($isLandscape ? 2200 : 1540,
                        $vAlign)->addText($goods->bar_code, null, $cellAlignCenter);
                    $table->addCell($isLandscape ? 3100 : 2170, $vAlign)->addText($goods->name,
                        null, $cellAlignCenter);
                    $table->addCell($isLandscape ? 1800 : 1260,
                        $vAlign)->addText($goods->{'specification_' . $userType}, null, $cellAlignCenter);

                    $table->addCell($isLandscape ? 3200 : 2250,
                        $gridSpan4)->addText($goods->pivot->num . cons()->valueLang('goods.pieces',
                            $goods->pivot->pieces), null, $cellAlignCenter);

                    if ($goods == $orderGoods['mortgageGoods']->first()) {
                        $table->addCell($isLandscape ? 2500 : 1750,
                            $cellRowSpan)->addText($item->remarkGroup['display'], 0, 20);
                    } else {
                        $table->addCell(0, $cellRowContinue);
                    }
                }
            }
            if ($item->display_fee > 0) {
                $table->addRow();
                $table->addCell(0, $gridSpan8)->addText('陈列费', ['size' => 10], $cellAlignCenter);
                $table->addRow(20);
                $table->addCell($isLandscape ? 2200 : 1540,
                    $vAlign)->addText('', null, $cellAlignCenter);
                $table->addCell($isLandscape ? 3100 : 2170, $vAlign)->addText('现金',
                    null, $cellAlignCenter);
                $table->addCell($isLandscape ? 3200 : 2250,
                    $gridSpan5)->addText($item->display_fee, null, $cellAlignCenter);

                if ($orderGoods['mortgageGoods']->isEmpty()) {
                    $table->addCell($isLandscape ? 2500 : 1750,
                        $vAlign)->addText($item->remarkGroup['display']);
                } else {
                    $table->addCell(0, $cellRowContinue);
                }
            }

            if ($item->promo) {
                $table->addRow();
                $table->addCell(0, $gridSpan8)->addText('促销活动', ['size' => 10], $cellAlignCenter);
                //返利商品
                if (in_array($item->promo->type, [cons('promo.type.goods-goods'), cons('promo.type.money-goods')])) {
                    foreach ($item->promo->rebate as $rebate) {
                        $table->addRow(20);
                        if ($rebate == $item->promo->rebate->first()) {
                            $table->addCell($isLandscape ? 2200 : 1540,
                                array_merge($cellRowSpan, $gridSpan2))->addText($item->promo->name, null,
                                $cellAlignCenter);
                        } else {
                            $table->addCell($isLandscape ? 2200 : 1540, array_merge($cellRowContinue, $gridSpan2));
                        }
                        $table->addCell($isLandscape ? 2200 : 1540,
                            $gridSpan4)->addText($rebate->goods->name, null, $cellAlignCenter);
                        $table->addCell($isLandscape ? 2200 : 1540,
                            $gridSpan2)->addText($rebate->quantity . cons()->valueLang('goods.pieces', $rebate->unit),
                            null, $cellAlignCenter);
                    }
                } else {
                    $table->addRow(20);
                    $table->addCell($isLandscape ? 2200 : 1540,
                        $gridSpan2)->addText($item->promo->name, null, $cellAlignCenter);
                    $rebateText = '(' . ($item->promo->type == cons('promo.type.custom') ? '自定义' : '现金') . ')';
                    if (in_array($item->promo->type,
                        [cons('promo.type.goods-money'), cons('promo.type.money-money')])) {
                        $rebateText .= $item->promo->rebate[0]->money;
                    } else {
                        $rebateText .= $item->promo->rebate[0]->custom;
                    }
                    $table->addCell($isLandscape ? 2200 : 1540,
                        $gridSpan6)->addText($rebateText, null, $cellAlignCenter);
                }
            }

            $table->addRow();
            $table->addCell(0, $gridSpan5)->addText('备注：' . $item->remarkGroup['remark'], ['size' => 10]);
            $qrcodeCell = $table->addCell(0, $gridSpan3);
            $qrcodeCell->addText($item->shop_name . '店铺首页地址：');
            $isLandscape ? $qrcodeCell->addImage((new ShopService())->qrcode($item->shop_id)) : $qrcodeCell->addImage((new ShopService())->qrcode($item->shop_id,
                60));
            $qrcodeCell->addText('一站式零售服务平台- -订百达');

            $deliveryManText = '';

            foreach ($item->deliveryMan as $deliveryMan) {
                $deliveryManText .= $deliveryMan->name . '  ';
            }

            $text = '   制单:';
            $text .= '        业务员：' . ($item->salesmanVisitOrder ? $item->salesmanVisitOrder->salesman_name : '');
            $text .= '        送货人：' . $deliveryManText;
            $text .= '        仓管：';
            $text .= '        收款人：' . ($item->systemTradeInfo ? cons()->valueLang('trade.pay_type',
                    $item->systemTradeInfo->pay_type) : '');

            $section->addText($text, ['size' => $isLandscape ? 12 : 10],
                ['width' => $isLandscape ? 4000 : 2800]);

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
        $gridSpan4 = ['valign' => 'center', 'gridSpan' => 4];
        $gridSpan5 = ['valign' => 'center', 'gridSpan' => 5];
        $gridSpan6 = ['valign' => 'center', 'gridSpan' => 6];
        $gridSpan8 = ['valign' => 'center', 'gridSpan' => 8];
        $cellRowSpan = array('vMerge' => 'restart', 'valign' => 'center');
        $cellRowContinue = array('vMerge' => 'continue');

        $isLandscape = $modelId == cons('order.templete.second');

        $style = [
            'marginTop' => 0,
            'marginRight' => 0,
            'marginLeft' => 0,
            'marginBottom' => 0
        ];
        $isLandscape && $style['orientation'] = 'landscape';


        foreach ($orders as $item) {
            $section = $phpWord->addSection($style);
            $section->addText($item->shop_name . ' 送货单', ['bold' => true, 'size' => 15], ['alignment' => 'center']);
            $table = $section->addTable('table');

            $table->addRow();
            $table->addCell($isLandscape ? 7400 : 5500,
                $gridSpan2)->addText('录单日期 ：' . $item->created_at->toDateString());
            $table->addCell($isLandscape ? 4000 : 2800,
                $gridSpan3)->addText('平台订单号 ：' . $item->id);
            $table->addCell($isLandscape ? 4000 : 2800,
                $gridSpan3)->addText('单据编号 ：' . $item->numbers);

            $userInfo = '购买单位 ：' . $item->user_shop_name;
            $userInfo .= $item->pay_type == $orderPayTypes['pick_up'] ? '   自提' : '     联系电话 ：' . ($item->shippingAddress ? $item->shippingAddress->consigner . '-' . $item->shippingAddress->phone : '');
            if ($item->pay_type == $orderPayTypes['pick_up']) {
                $userInfo .= '  提货地址 ：' . $item->shop->address;
            } else {
                $userInfo .= '  地址 ：' . (is_null($item->shippingAddress->address) ? '' : $item->shippingAddress->address->address_name);
            }


            $table->addRow();
            $table->addCell(0, $gridSpan8)->addText($userInfo);

            $table->addRow(20);
            $table->addCell($isLandscape ? 2400 : 2500, $vAlign)->addText('商品条码', null,
                $cellAlignCenter);
            $table->addCell($isLandscape ? 5000 : 3000, $vAlign)->addText('商品名称', null,
                $cellAlignCenter);
            $table->addCell($isLandscape ? 1800 : 1260, $vAlign)->addText('商品规格', null,
                $cellAlignCenter);
            $table->addCell($isLandscape ? 1500 : 1050, $vAlign)->addText('单价', null,
                $cellAlignCenter);
            $table->addCell($isLandscape ? 700 : 490, $vAlign)->addText('单位', null,
                $cellAlignCenter);
            $table->addCell($isLandscape ? 1000 : 700, $vAlign)->addText('数量', null,
                $cellAlignCenter);
            $table->addCell($isLandscape ? 1000 : 700, $vAlign)->addText('金额', null,
                $cellAlignCenter);
            $table->addCell($isLandscape ? 2500 : 1750, $vAlign)->addText('促销信息', null,
                $cellAlignCenter);

            $orderGoodsNum = 0;

            $userType = $item->user_type_name;

            $orderGoods = (new OrderService())->explodeOrderGoods($item);

            foreach ($orderGoods['orderGoods'] as $goods) {
                $table->addRow(20);
                $table->addCell($isLandscape ? 2200 : 1540,
                    $vAlign)->addText($goods->bar_code, null, $cellAlignCenter);
                $table->addCell($isLandscape ? 3100 : 2170, $vAlign)->addText($goods->name,
                    null, $cellAlignCenter);
                $table->addCell($isLandscape ? 1800 : 1260,
                    $vAlign)->addText($goods->{'specification_' . $userType}, null, $cellAlignCenter);
                $table->addCell($isLandscape ? 1500 : 1050,
                    $vAlign)->addText($goods->pivot->price, null, $cellAlignCenter);
                $table->addCell($isLandscape ? 700 : 490,
                    $vAlign)->addText(cons()->valueLang('goods.pieces',
                    $goods->pivot->pieces), null, $cellAlignCenter);
                $table->addCell($isLandscape ? 1000 : 700,
                    $vAlign)->addText($goods->pivot->num, null, $cellAlignCenter);
                $table->addCell($isLandscape ? 1000 : 700,
                    $vAlign)->addText($goods->pivot->total_price, null, $cellAlignCenter);
                $table->addCell($isLandscape ? 2500 : 1750,
                    $vAlign)->addText(mb_substr($goods->promotion_info, 0, 20));

                $orderGoodsNum = $orderGoodsNum + $goods->pivot->num;
            }
            foreach ($item->gifts as $gift) {
                $table->addRow(20);
                $table->addCell($isLandscape ? 2200 : 1540,
                    $vAlign)->addText($gift->bar_code, null, $cellAlignCenter);
                $table->addCell($isLandscape ? 3100 : 2170, $vAlign)->addText($gift->name,
                    null, $cellAlignCenter);
                $table->addCell($isLandscape ? 1800 : 1260,
                    $vAlign)->addText($gift->{'specification_' . $userType}, null, $cellAlignCenter);
                $table->addCell($isLandscape ? 1500 : 1050,
                    $vAlign)->addText($gift->pivot->price, null, $cellAlignCenter);
                $table->addCell($isLandscape ? 700 : 490,
                    $vAlign)->addText(cons()->valueLang('goods.pieces',
                    $gift->pivot->pieces), null, $cellAlignCenter);
                $table->addCell($isLandscape ? 1000 : 700,
                    $vAlign)->addText($gift->pivot->num, null, $cellAlignCenter);
                $table->addCell($isLandscape ? 1000 : 700,
                    $vAlign)->addText($gift->pivot->total_price, null, $cellAlignCenter);
                $table->addCell($isLandscape ? 2500 : 1750,
                    $vAlign)->addText('赠品');

                $orderGoodsNum = $orderGoodsNum + $gift->pivot->num;
            }
            $table->addRow(16);
            $table->addCell(0, $gridSpan5)->addText('总计', null, $cellAlignCenter);
            $table->addCell(0, $vAlign)->addText($orderGoodsNum, null, $cellAlignCenter);

            $price = $item->price;
            if ($item->coupon_id) {
                $price .= ' - ' . bcsub($item->price, $item->after_rebates_price,
                        2) . ' = ' . $item->after_rebates_price;
            } elseif ($item->display_fee > 0) {
                $price .= ' - ' . $item->display_fee . ' = ' . $item->after_rebates_price;
            }
            $table->addCell(0, $gridSpan2)->addText($price, null, $cellAlignCenter);

            if (!$orderGoods['mortgageGoods']->isEmpty()) {
                $table->addRow();
                $table->addCell(0, $gridSpan8)->addText('抵费商品', ['size' => 10], $cellAlignCenter);
                foreach ($orderGoods['mortgageGoods'] as $goods) {
                    $table->addRow(20);
                    $table->addCell($isLandscape ? 2200 : 1540,
                        $vAlign)->addText($goods->bar_code, null, $cellAlignCenter);
                    $table->addCell($isLandscape ? 3100 : 2170, $vAlign)->addText($goods->name,
                        null, $cellAlignCenter);
                    $table->addCell($isLandscape ? 1800 : 1260,
                        $vAlign)->addText($goods->{'specification_' . $userType}, null, $cellAlignCenter);

                    $table->addCell($isLandscape ? 3200 : 2250,
                        $gridSpan4)->addText($goods->pivot->num . cons()->valueLang('goods.pieces',
                            $goods->pivot->pieces), null, $cellAlignCenter);

                    if ($goods == $orderGoods['mortgageGoods']->first()) {
                        $table->addCell($isLandscape ? 2500 : 1750,
                            $cellRowSpan)->addText($item->remarkGroup['display'], 0, 20);
                    } else {
                        $table->addCell(0, $cellRowContinue);
                    }
                }
            }
            if ($item->display_fee > 0) {
                $table->addRow();
                $table->addCell(0, $gridSpan8)->addText('陈列费', ['size' => 10], $cellAlignCenter);
                $table->addRow(20);
                $table->addCell($isLandscape ? 2200 : 1540,
                    $vAlign)->addText('', null, $cellAlignCenter);
                $table->addCell($isLandscape ? 3100 : 2170, $vAlign)->addText('现金',
                    null, $cellAlignCenter);
                $table->addCell($isLandscape ? 3200 : 2250,
                    $gridSpan5)->addText($item->display_fee, null, $cellAlignCenter);

                if ($orderGoods['mortgageGoods']->isEmpty()) {
                    $table->addCell($isLandscape ? 2500 : 1750,
                        $vAlign)->addText($item->remarkGroup['display']);
                } else {
                    $table->addCell(0, $cellRowContinue);
                }
            }


            if ($item->promo) {
                $table->addRow();
                $table->addCell(0, $gridSpan8)->addText('促销活动', ['size' => 10], $cellAlignCenter);
                //返利商品
                if (in_array($item->promo->type, [cons('promo.type.goods-goods'), cons('promo.type.money-goods')])) {
                    foreach ($item->promo->rebate as $rebate) {
                        $table->addRow(20);
                        if ($rebate == $item->promo->rebate->first()) {
                            $table->addCell($isLandscape ? 2200 : 1540,
                                array_merge($cellRowSpan, $gridSpan2))->addText($item->promo->name, null,
                                $cellAlignCenter);
                        } else {
                            $table->addCell($isLandscape ? 2200 : 1540, array_merge($cellRowContinue, $gridSpan2));
                        }
                        $table->addCell($isLandscape ? 2200 : 1540,
                            $gridSpan4)->addText($rebate->goods->name, null, $cellAlignCenter);
                        $table->addCell($isLandscape ? 2200 : 1540,
                            $gridSpan2)->addText($rebate->quantity . cons()->valueLang('goods.pieces', $rebate->unit),
                            null, $cellAlignCenter);
                    }
                } else {
                    $table->addRow(20);
                    $table->addCell($isLandscape ? 2200 : 1540,
                        $gridSpan2)->addText($item->promo->name, null, $cellAlignCenter);
                    $rebateText = '(' . ($item->promo->type == cons('promo.type.custom') ? '自定义' : '现金') . ')';
                    if (in_array($item->promo->type,
                        [cons('promo.type.goods-money'), cons('promo.type.money-money')])) {
                        $rebateText .= $item->promo->rebate[0]->money;
                    } else {
                        $rebateText .= $item->promo->rebate[0]->custom;
                    }
                    $table->addCell($isLandscape ? 2200 : 1540,
                        $gridSpan6)->addText($rebateText, null, $cellAlignCenter);
                }
            }

            $table->addRow();
            $table->addCell(0, $gridSpan5)->addText('备注：' . $item->remarkGroup['remark'], ['size' => 10]);
            $qrcodeCell = $table->addCell(0, $gridSpan3);
            $qrcodeCell->addText($item->shop_name . '店铺首页地址：');
            $isLandscape ? $qrcodeCell->addImage((new ShopService())->qrcode($item->shop_id)) : $qrcodeCell->addImage((new ShopService())->qrcode($item->shop_id,
                60));
            $qrcodeCell->addText('一站式零售服务平台- -订百达');

            $deliveryManText = '';

            foreach ($item->deliveryMan as $deliveryMan) {
                $deliveryManText .= $deliveryMan->name . '  ';
            }


            $text = '   业务员：' . ($item->salesmanVisitOrder ? $item->salesmanVisitOrder->salesman_name : '') .
                '       送货人：' . $deliveryManText .
                '       收款人：' . ($item->systemTradeInfo ? cons()->valueLang('trade.pay_type',
                    $item->systemTradeInfo->pay_type) : '');

            $section->addText($text, ['size' => $isLandscape ? 12 : 10],
                ['width' => $isLandscape ? 4000 : 2800]);

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