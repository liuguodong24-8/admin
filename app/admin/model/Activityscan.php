<?php

namespace app\admin\model;

use think\Model;

/**
 * Activityscan
 */
class Activityscan extends Model
{
    // 表名
    protected $name = 'activityscan';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;


    public function activity(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(\app\admin\model\Activity::class, 'activity_id', 'id');
    }

    public function dev(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(\app\admin\model\Dev::class, 'dev_id', 'uid');
    }
}