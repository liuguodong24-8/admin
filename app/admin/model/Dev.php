<?php

namespace app\admin\model;

use think\Model;
use think\model\relation\BelongsTo;

/**
 * Dev
 */
class Dev extends Model
{
    // 表名
    protected $name = 'dev';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    public function period(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(\app\admin\model\Period::class, 'period_id', 'phase_code');
    }

    public function grade(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(\app\admin\model\Grade::class, 'grade_id', 'grade_code');
    }

    public function group($field): BelongsTo
    {
        return $this->belongsTo(Dev::class, $field);
    }
}