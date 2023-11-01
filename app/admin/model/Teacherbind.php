<?php

namespace app\admin\model;

use think\Model;

/**
 * Teacherbind
 */
class Teacherbind extends Model
{
    // 表名
    protected $name = 'teacherbind';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;


    public function admin(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(\app\admin\model\Admin::class, 'admin_id', 'id');
    }

    public function addltn(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(\app\admin\model\Addltn::class, 'addltn_id', 'id');
    }
}