<?php

namespace app\admin\model;

use think\Model;
use app\admin\library\Auth;

/**
 * Message
 */
class Message extends Model
{
    // 表名
    protected $name = 'message';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;

    public static function onBeforeInsert($activity){
        $activity->admin_id = Auth::instance()->id;
    }
}