<?php

namespace App\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

class Salesman extends Model implements AuthenticatableContract
{
    use SoftDeletes;
    use Authenticatable;
    protected $table = 'salesman';
    protected $fillable = [
        'account',
        'password',
        'shop_id',
        'name',
        'avatar',
        'contact_information',
        'expire_at',
        'last_login_ip',
        'last_login_time',
        'status'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'updated_at',
        'created_at',
        'deleted_at',
        'last_login_ip',
        'last_login_time'
    ];

    protected $dates = ['expire_at'];

    /**
     * 模型启动事件
     */
    /* public static function boot()
     {
         parent::boot();

         // 注册创建事件
         static::creating(function ($model) {
             $signService = app('sign');
             $signConfig = cons('sign');
             if ($signService->workerCount() >= $signConfig['max_worker']) {
                 $model->attributes['expire_at'] = Carbon::now();
             }
         });
     }*/

    /**
     * 模型启动事件
     */
    /*public static function boot()
    {
        parent::boot();

        // 注册删除事件
        static::deleted(function ($model) {
            $model->customers()->delete();
        });
    }*/

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['avatar_url'];

    /**
     * 关联店铺
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shop()
    {
        return $this->belongsTo('App\Models\Shop');
    }

    /**
     * 关联厂家店铺
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function maker()
    {
        return $this->belongsTo('App\Models\Shop', 'maker_id');
    }

    /**
     * 申请资产
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function applyAsset()
    {
        return $this->hasMany(AssetApply::class);
    }

    /**
     * 申请活动
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function applyPromo()
    {
        return $this->hasMany(PromoApply::class);
    }

    /**
     * 关联客户
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function customers()
    {
        return $this->hasMany('App\Models\SalesmanCustomer');
    }

    /**
     * 关联拜访表
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function visits()
    {
        $user = auth()->user();
        return $this->hasMany('App\Models\SalesmanVisit')->where(function ($query) use ($user) {
            if ($user && $user->type != cons('user.type.maker')) {
                $query->where('shop_id', $user->shop->id);
            }
        });
    }

    /**
     * 关联订单表
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orders()
    {
        return $this->hasMany('App\Models\SalesmanVisitOrder');
    }

    /**
     * 订货单
     *
     * @return mixed
     */
    public function orderForms()
    {
        return $this->orders()->where('type', cons('salesman.order.type.order'))->where('shop_id', $this->shop_id);
    }

    /**
     * 退货单
     *
     * @return mixed
     */
    public function returnOrders()
    {
        return $this->orders()->where('type', cons('salesman.order.type.return_order'))->where('shop_id',
            $this->shop_id);
    }

    /**
     * 车销单
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function dispatchTrucks()
    {
        return $this->hasMany(DispatchTruck::class);
    }


    /**
     * goods target
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function goodsTarget()
    {
        return $this->belongsToMany('App\Models\Goods', 'salesman_goods_target')->withPivot('id', 'num', 'pieces','barcode', 'month');
    }

    /**
     * 设置头像
     *
     * @param mixed $file
     */
    public function setAvatarAttribute($file)
    {
        if (is_string($file)) {
            $file = config('path.upload_temp') . $file;
        } else {
            $result = $this->convertToFile($file, null, false);
            $file = $result ? $result['path'] : null;
            $file = config('path.upload_temp') . $file;
        }

        try {
            $image = \Image::make($file);
        } catch (\Exception $e) {
            return;
        }
        $sizes = array_keys(cons('salesman.avatar'));
        $avatarPath = config('path.upload_salesman_avatar');

        rsort($sizes);
        if ($this->exists) {
            $uid = array_get($this->attributes, $this->primaryKey);
            $pathName = implode('/', divide_uid($uid, '_{size}.jpg'));

            // 创建目录
            $folder = $avatarPath . dirname($pathName);
            if (!is_dir($folder)) {
                mkdir($folder, 0770, true);
            }

            foreach ($sizes as $size) {
                $avatarFile = str_replace('{size}', $size, $pathName);
                $image->widen($size)->save($avatarPath . $avatarFile);
            }
        } else {
            static::created(function ($model) use ($file, $avatarPath, $sizes, $image) {
                $uid = array_get($model->attributes, $model->primaryKey);
                $pathName = implode('/', divide_uid($uid, '_{size}.jpg'));

                // 创建目录
                $folder = $avatarPath . dirname($pathName);
                if (!is_dir($folder)) {
                    mkdir($folder, 0770, true);
                }

                foreach ($sizes as $size) {
                    $avatarFile = str_replace('{size}', $size, $pathName);
                    $image->widen($size)->save($avatarPath . $avatarFile);
                }
            });
        }

    }

    /**
     * 密码自动转换
     *
     * @param $value
     */
    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['password'] = bcrypt($value);
        }
    }

    /**
     * Get user avatar url.
     *
     * @return string
     */
    public function getAvatarUrlAttribute()
    {
        return salesman_avatar_url(array_get($this->attributes, $this->primaryKey), 64);
    }

    /**
     * 获取店铺类型
     *
     * @return mixed
     */
    public function getShopTypeAttribute()
    {
        return $this->shop->user_type;
    }

    /**
     * 获取店铺地址
     *
     * @return mixed
     */
    public function getShopAddressAttribute()
    {
        return $this->shop()->first()->address;
    }

    /**
     * 是否厂家业务员
     *
     * @return int
     */
    public function getIsMakerAttribute()
    {
        if (is_null($this->maker_id)) {
            return 0;
        }
        return 1;
    }

    /**
     * 获取实际过期时间
     *
     * @return mixed
     */
    public function getExpireAttribute()
    {
        return is_null($this->expire_at) ? $this->shop->user->expire_at : $this->expire_at;
    }

    /**
     * 是否过期
     *
     * @return mixed
     */
    public function getIsExpireAttribute()
    {
        return false && $this->expire->isPast();
    }

    /**
     * 获取model名
     *
     * @return string
     */
    public function getModelNameAttribute()
    {

        return '业务员' . $this->attributes['name'];
    }

    /**
     * 按名字检索
     *
     * @param $query
     * @param $name
     * @param bool $includeAccount
     * @return mixed
     */
    public function scopeOfName($query, $name, $includeAccount = false)
    {
        if ($name) {
            return $includeAccount ? $query->where(function ($query) use ($name) {
                $query->where('name', 'LIKE', '%' . $name . '%')
                    ->orWhere('account', 'LIKE', '%' . $name . '%');
            }) : $query->where('name', 'LIKE', '%' . $name . '%');
        }
        return $query;
    }


    /**
     * 按参数检索
     *
     * @param $query
     * @param $options
     * @return mixed
     */
    public function scopeOfOptions($query, $options)
    {
        return $query->where(array_filter($options));
    }

}
