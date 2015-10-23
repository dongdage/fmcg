<?php

return [
    // 通用状态
    'status' => [
        'off' => 0,
        'on' => 1,
    ],
    //支付方式
    'pay_type' => [
        'online' => 1,
        'cod' => 2,
    ],
    'cod_pay_type' => [
        'cash' => 1,  //现金
        'card' => 2   //刷卡
    ],
    //订单表
    'order' => [
        'pay_status' => [//支付状态
            'non_payment' => 0,
            'payment_success' => 1,
            'payment_failed' => 2,
            'refund' => 3,
            'refund_success' => 4,
            'refund_failed' => 5,
        ],
        'status' => [ //订单状态
//            'non_sure' => 0, //未确认
            'non_send' => 1, //未发货
            'send' => 2, //已发货
            'finished' => 3, //完成
        ],
        'is_cancel' => [ //订单是否被取消，默认是未取消
            'off' => 0,
            'on' => 1,
        ]
    ],
    //用户类别
    'user' => [
        'type' => [
            'retailer' => 1,       //零售商
            'wholesaler' => 2,       //批发商
            'supplier' => 3,       //供应商
        ],
    ],
    //广告表类型
    'advert' => [
        'type' => [
            'index' => 1, // 首页(外侧)广告
            'user' => 2, // 用户(内则)广告
            'app' => 3, // app广告(启动页)
        ],
    ],
    // 店铺图片分类
    'shop' => [
        'file_type' => [
            'logo' => 1,         //logo
            'license' => 2,         //营业执照
            'images' => 3,          //店铺图片
            'business_license' => 4, //经营许可证
            'agency_contract' => 5, //代理合同
        ],
        'address_type' => [
            'shop_address' => 1,        //店铺地址
            'delivery_address' => 0,    //配送地址
        ],
        'sort' => [
            'hot',
            'new'
        ]
    ],
    // 交易相关
    'trade' => [
        'type' => [
            'in' => 1,               //入帐
            'out' => 2,              //提现
        ],
        'pay_type' => [
            'yeepay' => 1,          //易宝
            'alipay' => 2           //支付宝
        ],
        'pay_status' => [
            'success' => 1,         //成功
            'failed' => 2           //失败
        ],
        'trade_currency' => [
            'rmb' => 1,             //人民币
            'foreign_currency' => 2 //外币
        ]
    ],
    'feedback' => [               //反馈
        'status' => [
            'untreaded' => 0,      // 未处理
            'treaded' => 1,        //已处理
        ]
    ],
    // 商品
    'goods' => [
        'type' => [
            'yes' => 1,   //是
            'no' => 0    //否
        ],
        'status' => [
            'on' => 1,   //上架
            'off' => 0   //下架
        ],
        'sort' => [
            'name',
            'price',
            'new'
        ]
    ],
    // 银行列表
    'bank' => [
        'type' => [
            'BOC' => 1,   // '中国银行'
            'ICBC' => 2,   // '中国工商银行'
            'ABOC' => 3,   // '中国农业银行'
            'CCB' => 4,   // '中国建设银行'
            'CMB' => 5,   // '中国招商银行'
            'CB' => 6,   // '商业银行'
            //预留
            'HB' => 7,   // '华夏银行'
            'CEB' => 8,   // '中国光大银行'
            'CMBC' => 9,   // '中国民生银行'
            'CITIC' => 10,   // '中信实业银行'
            'CIB' => 11,   // '福建兴业银行'
        ]
    ],
    //收藏
    'like' => [
        'type' => [
            'goods' => 1,
            'shops' => 2
        ],
        'model' => [
            'goods' => App\Models\Goods::class,
            'shop' => App\Models\Shop::class
        ]
    ],
    //标签
    'attr' => [
        'default' => [
            'brand' => 1,                  // 品牌
            'import_and_export' => 20219,  // 进出口
            'packing' => 20235,            //包装
            'place' => 20465,              //产地
        ]
    ],
    //推送设备类型
    'push_device' => [
        'iphone' => 1,
        'android' => 2,
    ],
    'home_column' => [
        'type' => [
            'goods' => 1,
            'shop' => 2
        ]
    ],
    //排序
    'sort' => [
        'goods' => [
            'hot' => 1,
            'price' => 2,
            'new' => 3,
        ],
        'shop' => [
            'hot' => 1,
            'new' => 2
        ]
    ],
    //统计页的两个分页单页显示条数
    'statistics_per' => 5,
    //推送信息生命时间
    'push_time' => [//单位都是秒(s)
        'when_push' => 300,//当存在时间小于该时间时送入到推送队列
        'msg_life' => 600//信息在redis中存在的生命周期
    ],

];
