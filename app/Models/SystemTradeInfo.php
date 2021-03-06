<?php

namespace App\Models;


use Carbon\Carbon;

class SystemTradeInfo extends Model
{
    protected $table = 'system_trade_info';
    public $timestamps = false;
    protected $fillable = [
        'type',
        'pay_type',
        'account',
        'paid_at',
        'order_id',
        'charge_id',
        'bank_card_no',
        'trade_no',
        'pay_status',
        'amount',
        'is_finished',
        'target_fee',
        'trade_currency',
        'callback_type',
        'hmac',
        'finished_at',
        'success_at',
        'notice_at'
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->paid_at = Carbon::now();
            $model->success_at = Carbon::now();
        });
    }

    /**
     * 关联订单信息
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function order()
    {
        return $this->belongsTo('App\Models\Order');
    }

    public function getOperateAttribute()
    {
        return ($this->order->user_id === auth()->user()->id) ? '-' : '+';
    }
}
