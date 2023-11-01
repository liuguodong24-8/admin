<?php

namespace app\admin\controller;

use app\admin\model\AdminLog;
use JPush\Client as JPush;
use think\Exception;
use think\facade\Config;
use think\facade\Db;
use think\Model;
use Throwable;
use app\common\controller\Backend;

/**
 * 学员设备管理
 */
class DevTime extends Backend
{
    /**
     * Dev模型对象
     * @var object
     * @phpstan-var \app\admin\model\Dev
     */
    protected object $model;

    /**
     * 无需鉴权的方法
     * @var array
     */
    protected array $noNeedPermission = ['push_lock'];

    public function initialize(): void
    {
        parent::initialize();
    }


    /**
     * 添加
     */
    public function edit(): void
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $id = $this->request->param("id");

            $data['operator'] = $this->auth->id;
            $data['start_time'] = $this->request->param("start_time");
            $data['end_time'] = $this->request->param("end_time");
            $data['class_name'] = $this->request->param("class_name");
            $data['dev_id'] = $this->request->param("dev_id");

            $result = [];
            $devTime =  Db::table("dev")->where("id", "=", $id)->find();
            if ($devTime){
                $result =  Db::table("dev")->where("id", "=", $id)->update($data);
            }

            if ($result) {
                $this->success();
            }

        }/* else {
            $id = $this->request->param("id");
            if ($id) {
                $devTime =  Db::table("dev_time")->where("id", "=", $id)->find();
                $this->success($devTime);
            }
            $this->success([]);

        }*/

    }


}