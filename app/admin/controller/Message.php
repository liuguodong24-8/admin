<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use JPush\Client as JPush;
use think\facade\Config;
use think\facade\Db;

/**
 * 通知消息
 */
class Message extends Backend
{
    /**
     * Message模型对象
     * @var object
     * @phpstan-var \app\admin\model\Message
     */
    protected object $model;

    protected array|string $preExcludeFields = ['id', 'update_time', 'create_time'];

    protected string|array $quickSearchField = ['id'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new \app\admin\model\Message;
    }


    /**
     * 若需重写查看、编辑、删除等方法，请复制 @see \app\admin\library\traits\Backend 中对应的方法至此进行重写
     */

    /**
     * 查看
     * @throws Throwable
     */
    public function index(): void
    {
        if ($this->request->param('select')) {
            $this->select();
        }

        list($where, $alias, $limit, $order) = $this->queryBuilder();
        $where[] = ['admin_id','=',$this->auth->id];
        $res = $this->model
            ->field($this->indexField)
            ->withJoin($this->withJoinTable, $this->withJoinType)
            ->alias($alias)
            ->where($where)
            ->order($order)
            ->paginate($limit);

        $this->success('', [
            'list'   => $res->items(),
            'total'  => $res->total(),
            'remark' => get_route_remark(),
        ]);
    }

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

            if (empty($data['selectedProvince'] ?? "") || empty($data['selectedCity'] ?? "") ) {
                $this->error(__('Parameter %s can not be empty', ['']));
            }


            $data = $this->excludeFields($data);
            if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                $data[$this->dataLimitField] = $this->auth->id;
            }

            $result = false;
            $result2 = '';
            $tm = time();
            $this->model->startTrans();
            $all_data = [];//写入数据的二维表格
            $jg_push_data = []; //极光消息推送的rid
            $message_id = 0;
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
                /****************************************************/
                $teacher_info = Db::name('teacherbind')->where(['admin_id'=>$this->auth->id])->find();
                if(!empty($teacher_info)){
                    $data['admin_id'] = $this->auth->id;
                    $data['update_time'] = $tm;
                    $data['create_time'] = $tm;
                    $data['send_object'] = json_encode($data['selectedCity'] ?? "", true);

                    $data['image_url'] = "";

                    // 查询需要发送的用户
                    $sccd_ids = Db::name('dev')->whereIn("user_name",  $data['selectedCity'])->column('uid,device_id');
                    if (empty($sccd_ids)) {
                        $sccd_ids = Db::name('dev')->whereIn("class_name",  $data['selectedCity'])->column('uid,device_id');
                    }

                    unset($data['selectedProvince']);
                    unset($data['selectedCity']);

                    $result = $this->model->insertGetId($data);
                    $message_id = $result;

                    //$sccd_ids = Db::name('dev')->where(['teacher_id_card_no'=>$teacher_info['teacher_id_card_no'],'teacher_mobile'=>$this->auth->mobile])->column('uid,device_id');
                    if (count($sccd_ids) > 0) {
                        foreach ($sccd_ids as $v){
                            $all_data[] = [
                                'dev_id'=>$v['uid'],
                                'message_id'=>$result
                            ];
                            $jg_push_data[] = $v['device_id'];
                        }
                    }
                    $messagelistModel = new \app\admin\model\Messagesendlist;
                    if(count($all_data)>0) $result = $messagelistModel->saveAll($all_data);//具体推送的消息明细
                    $this->model->commit();
                }else{
                    $result2 = 'no bind';
                }
                /****************************************************/
            } catch (Throwable $e) {
                $this->model->rollback();
                $this->error($e->getMessage());
            }
            if ($result2 === 'no bind') $this->error('账号还未绑定，无法添加消息');
            if ($result !== false) {
                /*消息推送*/
                if(count($jg_push_data) > 0){
                    $client = new JPush(Config::get('sccd.app_key'), Config::get('sccd.master_secret'));
                    $title = '通知消息';
                    $alert = $data['mes_content'];
                    try {
                        $client->push()
                            ->setPlatform('all')
                            ->addRegistrationId($jg_push_data)
                            ->setNotificationAlert($alert)
                            ->message($title, [
                                'title' => $title,
                                'content_type' => 'json',
                                'extras' => ['type' => 'message', 'id'=>(int)$message_id]
                            ])
                            ->send();
                    }catch (Throwable $e){
                        //$e->getMessage() 真实反馈
                        addLog(var_export($e->getMessage(),true),'_jpush_err.log');
                        $this->error('消息推送失败，设备号格式错误！', [], 0);
                    }
                }
                $this->success(__('Added successfully'));
            } else {
                $this->error(__('No rows were added'));
            }
        }

        $this->error(__('Parameter error'));
    }

}