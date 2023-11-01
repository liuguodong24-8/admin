<?php

namespace app\admin\model;

use think\Model;

/**
 * Todayinhistory
 */
class Todayinhistory extends Model
{
    // 表名
    protected $name = 'todayinhistory';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;


    public function getContentAttr($value): string
    {
        return !$value ? '' : htmlspecialchars_decode($value);
    }
}