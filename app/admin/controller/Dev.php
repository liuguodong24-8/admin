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
class Dev extends Backend
{
    /**
     * Dev模型对象
     * @var object
     * @phpstan-var \app\admin\model\Dev
     */
    protected object $model;
    protected object $teacherbindModel;

    protected array|string $preExcludeFields = ['id', 'device_id', 'user_name', 'teaching_cycle', 'user_id', 'period_id', 'grade_id', 'class_id', 'class_name', 'graduated_year', 'in_year', 'school_id', 'school_name', 'teacher_id_card_no', 'teacher_mobile', 'teacher_name', 'update_time', 'create_time'];

    protected array $withJoinTable = ['period', 'grade'];

    protected string|array $quickSearchField = ['id'];

    /**
     * 无需鉴权的方法
     * @var array
     */
    protected array $noNeedPermission = ['push_lock'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new \app\admin\model\Dev;
        $this->teacherbindModel = new \app\admin\model\Teacherbind;
    }

    /**
     * 查看
     * @throws Throwable
     */
    public function index(): void
    {
        // 如果是 select 则转发到 select 方法，若未重写该方法，其实还是继续执行 index
        if ($this->request->param('select')) {
            $this->select();
        }

        /**
         * 1. withJoin 不可使用 alias 方法设置表别名，别名将自动使用关联模型名称（小写下划线命名规则）
         * 2. 以下的别名设置了主表别名，同时便于拼接查询参数等
         * 3. paginate 数据集可使用链式操作 each(function($item, $key) {}) 遍历处理
         */
        list($where, $alias, $limit, $order) = $this->queryBuilder();
        $teacher_info = $this->teacherbindModel->where(['admin_id'=>$this->auth->id])->find();
        if($this->auth->mobile == '' || $teacher_info->isEmpty()) {
            //给个不存在的条件限制输出
            $where[] = ['uid', '>', 99999999];
        }else{
            if($this->auth->mobile != '') $where[] = ['teacher_mobile','=',$this->auth->mobile];
            if(!$teacher_info->isEmpty()) {
                $where[] = ['teacher_id_card_no', '=', $teacher_info['teacher_id_card_no']];
            }else{
                //给个不存在的条件限制输出
                $where[] = ['uid', '>', 99999999];
            }
        }

        $res = $this->model
            ->withJoin($this->withJoinTable, $this->withJoinType)
            ->alias($alias)
            ->where($where)
            ->order($order)
            ->paginate($limit);

        $res->visible(['period' => ['phase_name'], 'grade' => ['grade_name']]);

        $this->success('', [
            'list'   => $res->items(),
            'total'  => $res->total(),
            'remark' => get_route_remark(),
        ]);
    }

    /**
     * 查看班级，年级，学生
     * @throws Throwable
     */
    public function list(): void
    {
        // 如果是 select 则转发到 select 方法，若未重写该方法，其实还是继续执行 index
        if ($this->request->param('select')) {
            $this->select();
        }
        $result = [];
        list($where, $alias, $limit, $order) = $this->queryBuilder();
        if (!empty($this->request->param('key'))) {
            $commaSeparatedString = $this->request->param('key');
            // 在控制器或其他地方进行查询
            $res = $this->model::whereIn("grade_id", $commaSeparatedString)->group('class_name')->select();
            //组装数据
            if($res->all()){
                foreach ($res as $key => $item) {
                    $result[$key]["label"] = $item->class_name;
                    $result[$key]["value"] = $item->class_name;
                }
            } else {
                // 在控制器或其他地方进行查询
                $res = $this->model::whereIn('class_name', $commaSeparatedString)->group('user_name')->select();

                //组装数据
                if($res->all()) {
                    foreach ($res as $key => $item) {
                        $result[$key]["label"] = $item->user_name;
                        $result[$key]["value"] = $item->user_name;
                    }
                }
            }

        } else{
            $group = $this->auth->getGroupAccess();
            /**
             * 教体局管理人员
            校管理人员
            班主任
            科任老师
             */
            if ($group->name == "科任老师" || $group->name == "班主任") {
           // var_dump($this->auth->nickname);
                $where[] = ['teacher_name', "=", $this->auth->nickname];
                $res = $this->model::where($where)->group('class_name')->select();
                //组装数据
                if($res) {
                    foreach ($res as $key => $item) {
                        $result[$key]["label"] = $item->class_name;
                        $result[$key]["value"] = $item->class_name;
                    }
                }
            } else if ($group->name == "校管理人员" || $group->name == "教体局管理人员" || $group->name == "超级管理组" ) {

                $res = $this->model::withJoin("grade")->where($where)->group('grade_id')->select();
                //组装数据
                if($res) {
                    foreach ($res as $key => $item) {
                        $result[$key]["label"] = $item->grade->grade_name;
                        $result[$key]["value"] = $item->grade_id;
                    }
                }
            }
        }

        $this->success('',
            $result
        );
    }


    /**
     * 查看学校，年级， 班级
     * @throws Throwable
     */
    public function schoolList(): void
    {
        // 如果是 select 则转发到 select 方法，若未重写该方法，其实还是继续执行 index
        if ($this->request->param('select')) {
            $this->select();
        }
        $result = [];
        list($where, $alias, $limit, $order) = $this->queryBuilder();
        if (!empty($this->request->param('key'))) {
            $commaSeparatedString = $this->request->param('key');
            // 在控制器或其他地方进行查询
            $res = $this->model::withJoin("grade")->whereIn("school_name", $commaSeparatedString)->group('grade_id')->select();

            if($res->all()){
                foreach ($res as $key => $item) {
                    $result[$key]["label"] = $item->grade->grade_name;
                    $result[$key]["value"] = $item->grade_id;
                }
            } else {

                $res = $this->model::whereIn("grade_id", $commaSeparatedString)->group('class_name')->select();
                //组装数据
                if($res->all()){
                    foreach ($res as $key => $item) {
                        $result[$key]["label"] = $item->class_name;
                        $result[$key]["value"] = $item->class_name;
                    }
                } /*else {
                    // 在控制器或其他地方进行查询
                    $res = $this->model::whereIn('class_name', $commaSeparatedString)->group('user_name')->select();

                    //组装数据
                    if($res->all()) {
                        foreach ($res as $key => $item) {
                            $result[$key]["label"] = $item->user_name;
                            $result[$key]["value"] = $item->user_name;
                        }
                    }
                }*/
            }

        } else{
            $group = $this->auth->getGroupAccess();
            /**
             * 教体局管理人员
            校管理人员
            班主任
            科任老师
             */
      /*      if ($group->name == "科任老师" || $group->name == "班主任") {
                // var_dump($this->auth->nickname);
                $where[] = ['teacher_name', "=", $this->auth->nickname];
                $res = $this->model::where($where)->group('class_name')->select();
                //组装数据
                if($res) {
                    foreach ($res as $key => $item) {
                        $result[$key]["label"] = $item->class_name;
                        $result[$key]["value"] = $item->class_name;
                    }
                }
            } else if ($group->name == "校管理人员" || $group->name == "教体局管理人员" || $group->name == "超级管理组" ) {*/

                $res = $this->model::where($where)->group('school_name')->select();
                //组装数据
                if($res) {
                    foreach ($res as $key => $item) {
                        $result[$key]["label"] = $item->school_name;
                        $result[$key]["value"] = $item->school_name;
                    }
                }

              /*  $res = $this->model::withJoin("grade")->where($where)->group('grade_id')->select();
                //组装数据
                if($res) {
                    foreach ($res as $key => $item) {
                        $result[$key]["label"] = $item->grade->grade_name;
                        $result[$key]["value"] = $item->grade_id;
                    }
                }*/
          /*  }*/
        }

        $this->success('',
            $result
        );
    }


    /**
     * 若需重写查看、编辑、删除等方法，请复制 @see \app\admin\library\traits\Backend 中对应的方法至此进行重写
     */
    /**
     * 一件锁定解锁
     * @return void
     */
    public function push_lock()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            $state = (string)$post['state'];//比较字符串
            if (!isset($post['state']) || !in_array($state,['0','1'],true)) $this->error('请求异常',[],0);
            $alert = '';
            $title = '';
            switch ($state) {
                case '0':
                    //解锁
                    $alert = '您的设备已启用！';
                    $title = '一键启用';
                    break;
                case '1':
                    //锁定
                    $alert = '您的设备已锁定！';
                    $title = '一键锁定';
                    break;

            }
            if (empty($alert) || empty($title)) $this->error('操作失败',[],0);
            $result = false;
            $err_state = '';
            $student = [];
            $this->model->startTrans();
            try {
                $teacher_info = $this->teacherbindModel->where(['admin_id'=>$this->auth->id])->find();
                if(!empty($teacher_info)){
                    $result = $this->model->where(['teacher_id_card_no'=>$teacher_info['teacher_id_card_no'],'teacher_mobile'=>$this->auth->mobile])->update(['status' => (int)$post['state']]);
                }else{
                    $err_state = 'no bind';
                }
                $this->model->commit();
            } catch (Throwable $e) {
                $this->model->rollback();
                $this->error($e->getMessage(), [], 0);
            }
            if($result !== false){
                $student = $this->model->where(['teacher_id_card_no'=>$teacher_info['teacher_id_card_no'],'teacher_mobile'=>$this->auth->mobile])->column('device_id');
                if(count($student) > 0){
                    $client = new JPush(Config::get('sccd.app_key'), Config::get('sccd.master_secret'));
                    try {
                        $client->push()
                            ->setPlatform('all')
                            ->addRegistrationId($student)
                            ->setNotificationAlert($alert)
                            ->message($title, [
                                'title' => $title,
                                'content_type' => 'json',
                                'extras' => ['status' => (int)$state, 'type' => 'lock']
                            ])
                            ->send();
                    }catch (Throwable $e){
                        //$e->getMessage() 真实反馈
                        addLog(var_export($e->getMessage(),true),'_jpush_err.log');
                        $this->error('消息推送失败，设备号格式错误！', [], 0);
                    }
                }
                $this->success('操作成功',[],1);
            }else{
                $this->error('请求异常',[],0);
            }

        } else {
            $this->error('请求异常',[],0);
        }
    }


    /**
     * 更新设备
     * @throws Throwable
     */
    public function update()
    {
        AdminLog::setTitle(__('Update mac'));
        if ($this->request->isPost()) {
            $mac = $this->request->post("mac_id");
            $id = $this->request->post("id");
            try {
                \app\admin\model\Dev::where("id", "=", $id)->update(["mac_id" => $mac]);

            } catch (Exception $e) {
                $this->error(__($e->getMessage()), $e->getData(), $e->getCode());
            } catch (Throwable $e) {
                $this->error(__($e->getMessage()));
            }
            $this->success();
        }
    }


    /**
     * 若需重写查看、编辑、删除等方法，请复制 @see \app\admin\library\traits\Backend 中对应的方法至此进行重写
     */
    /**
     * 一件锁定解锁
     * @return void
     */
    public function lockOrUnlock()
    {
        if ($this->request->isPost()) {
            $state = $this->request->param("state");
            $ids = $this->request->param("ids");

           // $state = (string)$post['state'];//比较字符串

            if (!isset($state) || !in_array($state,['0','1'],true)) $this->error('请求异常',[],0);

            $result = false;
            $this->model->startTrans();
            try {
                $result = $this->model->where('id', "in" ,$ids ?? [])->update(['status' => (int)$state]);

                /* $teacher_info = $this->teacherbindModel->where(['admin_id'=>$this->auth->id])->find();
                 if(!empty($teacher_info)){
                     $result = $this->model->where(['teacher_id_card_no'=>$teacher_info['teacher_id_card_no'],'teacher_mobile'=>$this->auth->mobile])->update(['status' => (int)$post['state']]);
                 }else{
                     $err_state = 'no bind';
                 }*/
                $this->model->commit();
            } catch (Throwable $e) {
                $this->model->rollback();
                $this->error($e->getMessage(), [], 0);
            }

            if($result !== false){
                $this->success('操作成功',[],1);
            }else{
                $this->error('请求异常',[],0);
            }

        } else {
            $this->error('请求异常',[],0);
        }
    }


    /**
     * 设置情景模式
     * @return void
     */
    public function setDevModel()
    {
        if ($this->request->isPost()) {
            $startTime = $this->request->param("start_time");
            $endTime = $this->request->param("end_time");
            $id = $this->request->param("id");

            // $state = (string)$post['state'];//比较字符串


            $result = false;
            $this->model->startTrans();
            try {
                $result =  Db::table("dev_time")->insertGetId(
                    [
                        "start_time" => $startTime,
                        "end_time" => $endTime,
                        "dev_id" => $id,
                    ]
                );

                $this->model->commit();
            } catch (Throwable $e) {
                $this->model->rollback();
                $this->error($e->getMessage(), [], 0);
            }

            if($result !== false){
                $this->success('操作成功',[],1);
            }else{
                $this->error('请求异常',[],0);
            }

        } else {
            $this->error('请求异常',[],0);
        }
    }
}