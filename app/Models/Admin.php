<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

class Admin extends Model implements AuthenticatableContract
{
    use Authenticatable;

    protected $table = 'admin';
    protected $fillable = [
        'role_id',
        'name',
        'real_name',
        'password',
        'last_login_ip',
        'last_login_time',
        'email',
        'remark',
        'status',
    ];
    protected $hidden = ['password'];

    /**
     * role表
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role()
    {
        return $this->belongsTo('App\Models\Role');
    }

    /**
     * 设置密码时候进行哈希处理
     *
     * @param $value
     */
    public function setPasswordAttribute($value)
    {
        if ($value) {

            $this->attributes['password'] = bcrypt($value);

        }
    }

    public function getNodeIdsAttribute()
    {
        $admin = admin_auth()->user();
        $nodeIds = $admin->name == cons('admin.super_admin_name') ? Node::all()->pluck('id') : $admin->role->nodes->pluck('id');
        return $nodeIds;
    }
}
