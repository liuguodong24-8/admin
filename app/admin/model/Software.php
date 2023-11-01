<?php

namespace app\admin\model;

use think\Model;

/**
 * Software
 */
class Software extends Model
{
    // 表名
    protected $name = 'software';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;
    protected $updateTime = false;

}