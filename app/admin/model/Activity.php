<?php

namespace app\admin\model;

use think\Model;

/**
 * Activity
 */
class Activity extends Model
{
    // 表名
    protected $name = 'activity';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

}