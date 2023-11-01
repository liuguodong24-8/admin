<?php

namespace app\admin\model;

use think\Model;

/**
 * Sos
 */
class Sos extends Model
{
    // 表名
    protected $name = 'sos';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;


    public function dev(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(\app\admin\model\Dev::class, 'dev_id', 'id');
    }
}