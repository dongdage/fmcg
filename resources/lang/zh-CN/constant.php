<?php

return [
    // 通用状态
    'status' => [
        'off' => '禁用',
        'on' => '正常',
    ],
    //支付方式
    'pay_type' => [
        'online' => '在线支付',
        'cod' => '货到付款',
    ],
    'cod_pay_type' => [
        'cash' => '现金',
        'card' => '刷卡'
    ],
    //订单表
    'order' => [
        'pay_status' => [//支付状态
            'non_payment' => '未付款',
            'payment_success' => '已付款',
            'payment_failed' => '付款失败',
            'refund' => '退款中',
            'refund_success' => '退款成功',
            'refund_failed' => '退款失败',
        ],
        'status' => [ //订单状态
            'non_confirm' => '未确认',
            'non_send' => '未发货',
            'send' => '已发货',
            'finished' => '已完成',
        ],
        'is_cancel' => [//订单是否被取消
            'off' => '未取消',
            'on' => '已取消',
        ]
    ],
    //用户类别
    'user' => [
        'type' => [
            'wholesaler' => '批发商',
            'retailer' => '终端商',
            'supplier' => '供应商',
        ],
        // 审核状态
        'audit_status' => [
            'not_audit' => '未审核',
            'pass'      => '通过',
            'not_pass'  => '未通过'
        ]
    ],
    //广告表类型
    'advert' => [
        'type' => [
            'index' => '首页', // 首页(外侧)广告
            'user' => '用户', // 用户(内则)广告
            'app' => 'APP', // app广告(启动页)
        ],
    ],
    // 交易相关
    'trade' => [
        'type' => [
            'in' => '入帐',
            'out' => '提现'
        ],
        'pay_type' => [
            'yeepay' => '易宝',
            'pingxx' => 'pingxx',
        ],
        'pay_info' => [
            'success' => '成功',
            'failed' => '失败'
        ],
        'trade_currency' => [
            'rmb' => '人民币',
            'foreign_currency' => '外币'
        ]
    ],
    'feedback' => [               //反馈
        'status' => [
            'untreaded' => '未处理',
            'treaded' => '已处理'
        ]
    ],
    // 商品
    'goods' => [
        'type' => [
            'yes' => '是',
            'no' => '否',
        ],
        'status' => [
            'on' => '上架',
            'off' => '下架'
        ]
    ],
    // 银行列表
    'bank' => [
        'type' => [
            'ICBC' => '工商银行',
            'CMBCHINA' => '招商银行',
            'CCB' => '建设银行',
            'BOCO' => '交通银行',
            'CIB' => '兴业银行',
            'CMBC' => '中国民生银行',
            'CEB' => '光大银行',
            'BOC' => '中国银行',
            'PINGANBANK' => '平安银行',
            'ECITIC' => '中信银行',
            'SDB' => '深圳发展银行',
            'GDB' => '广发银行',
            'SHB' => '上海银行',
            'SPDB' => '上海浦东发展银行',
            'HXB' => '华夏银行「借」',
            'BCCB' => '北京银行「借」',
            'ABC' => '中国农业银行「借」',
            'POST' => '中国邮政储蓄银行「借」'
        ]
    ],
    // 收藏
    'like' => [
        'type' => [
            'goods' => '商品',
            'shop' => '店铺'
        ]
    ],
    //标签
    'attr' => [
        'default' => [
            'brand' => '品牌',
            'import_and_export' => '进口/国产',
            'packing' => '包装',
            'place' => '产地'
        ]
    ],
    // 前台栏目
    'home_column' => [
        'type' => [
            'goods' => '商品',
            'shop' => '店铺'
        ]
    ],
    //排序
    'sort' => [
        'goods' => [
            'hot' => '热门',
            'price' => '价格',
            'new' => '最新'
        ],
        'shop' => [
            'hot' => '热门',
            'new' => '最新'
        ]
    ],
    //推送信息
    'push_msg' => [
        'non_send' => [
            'online' => '买家已完成在线支付,请发货',
            'cod' => '买家已提交货到付款订单,请发货'
        ],
        'finished' => '买家已确认收货',
        'send' => '卖家已发货,请注意查收',
        'cancel_by_buyer' => '被买家取消了~~',
        'cancel_by_seller' => '被卖家取消了~~',
        'price_changed' => '价格发生了变化',
        'review_failed' => '审核未通过',
        'review_payment' => '已打款,请查收',
        'refund' => '买家已退款',
    ],
    //提现状态
    'withdraw' => [
        'failed' => '审核未通过',
        'review' => '审核中',
        'pass' => '审核通过',
        'payment' => '已打款',
    ],
];