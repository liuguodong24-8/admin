<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use JPush\Client as JPush;
use think\facade\Config;

/**
 * 软件升级管理
 */
class Software extends Backend
{
    /**
     * Software模型对象
     * @var object
     * @phpstan-var \app\admin\model\Software
     */
    protected object $model;

    protected array|string $preExcludeFields = ['id', 'create_time'];

    protected string|array $quickSearchField = ['id'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new \app\admin\model\Software;
    }


    /**
     * 若需重写查看、编辑、删除等方法，请复制 @see \app\admin\library\traits\Backend 中对应的方法至此进行重写
     */
    /**
     * 添加
     */
    public function add(): void
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (!$data) {
                $this->error(__('Parameter %s can not be empty', ['']));
            }

            $data = $this->excludeFields($data);
            if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                $data[$this->dataLimitField] = $this->auth->id;
            }

            $result = false;
            $this->model->startTrans();
            try {
                // 模型验证
                if ($this->modelValidate) {
                    $validate = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                    if (class_exists($validate)) {
                        $validate = new $validate;
                        if ($this->modelSceneValidate) $validate->scene('add');
                        $validate->check($data);
                    }
                }
                $package = [
                    1=>'top.hellocrab.sccd.schoolmanager',
                    2=>'top.hellocrab.sccd.launcher2',
                    3=> 'top.hellocrab.sccd.appstore',
                    4=> 'com.iflytek.student.hd'
                ];
                $data['package_name'] = $package[$data['type']];
                $result = $this->model->save($data);
                $this->model->commit();
            } catch (Throwable $e) {
                $this->model->rollback();
                $this->error($e->getMessage());
            }
            if ($result !== false) {
                /*软件升级消息推送*/
                $client = new JPush(Config::get('sccd.app_key'), Config::get('sccd.master_secret'));
                $title = '通知消息';
                try {
                    $client->push()
                        ->setPlatform('all')
                        ->addAllAudience()
                        ->message($title, [
                            'title' => $title,
                            'content_type' => 'json',
                            'extras' => ['type' => 'update_apk']
                        ])
                        ->send();
                    addLog('推送成功了一条','_success_push_upgrade.log');
                }catch (Throwable $e){
                    //$e->getMessage() 真实反馈
                    addLog(var_export($e->getMessage(),true),'_jpush_err.log');
                    $this->error('消息推送失败，设备号格式错误！', [], 0);
                }
                $this->success(__('Added successfully'));
            } else {
                $this->error(__('No rows were added'));
            }
        }

        $this->error(__('Parameter error'));
    }
}