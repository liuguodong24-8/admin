<?php


namespace app\api\controller\sccd;


use think\facade\Db;

class Equipment extends \app\common\controller\Frontend
{
    protected $noNeedLogin = ['sc_index'];

    protected $noNeedPermission = ['sc_index'];

    public function initialize()
    {
        parent::initialize();
    }



}
