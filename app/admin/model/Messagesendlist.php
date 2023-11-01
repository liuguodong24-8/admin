<?php

namespace app\admin\model;

use think\Model;

/**
 * Messagesendlist
 */
class Messagesendlist extends Model
{
    // 表名
    protected $name = 'messagesendlist';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = true;


    public function message(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(\app\admin\model\Message::class, 'message_id', 'id');
    }

    public function dev(): \think\model\relation\BelongsTo
    {
        return $this->belongsTo(\app\admin\model\Dev::class, 'dev_id', 'uid');
    }
}