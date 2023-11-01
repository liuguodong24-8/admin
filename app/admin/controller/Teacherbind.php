<?php

namespace app\admin\controller;

use Throwable;
use app\common\controller\Backend;

/**
 * 教师关系绑定
 */
class Teacherbind extends Backend
{
    /**
     * Teacherbind模型对象
     * @var object
     * @phpstan-var \app\admin\model\Teacherbind
     */
    protected object $model;

    protected object $addltnModel;
    protected object $adminModel;

    protected array|string $preExcludeFields = ['id', 'update_time', 'create_time'];

    protected array $withJoinTable = ['admin', 'addltn'];

    protected string|array $quickSearchField = ['id'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new \app\admin\model\Teacherbind;
        $this->addltnModel = new \app\admin\model\Addltn;
        $this->adminModel = new \app\admin\model\Admin;
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
        $res = $this->model
            ->withJoin($this->withJoinTable, $this->withJoinType)
            ->alias($alias)
            ->where($where)
            ->order($order)
            ->paginate($limit);
        $res->visible(['admin' => ['username','nickname'], 'addltn' => ['addr']]);

        $this->success('', [
            'list'   => $res->items(),
            'total'  => $res->total(),
            'remark' => get_route_remark(),
        ]);
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
                //新增
                $ltnInfo = $this->addltnModel->where(['id'=>$data['addltn_id']])->field('addr,add_id')->find();
                if (!$ltnInfo->isEmpty()){
                    $result = $this->model->save($data);
                    /***************/
                    $updateData = [
                        'bind_map' => $ltnInfo['add_id'],
                        'bind_school' => $ltnInfo['addr'],
                        'bind_class' => $data['bind_class'],
                    ];
                    $this->adminModel->where(['id'=>$this->auth->id])->update($updateData);
                    /***************/
                    $this->model->commit();
                }else{
                    $result = false;
                }
                //新增 ↑  ↑  ↑  ↑  ↑  ↑  ↑
            } catch (Throwable $e) {
                $this->model->rollback();
                $this->error($e->getMessage());
            }
            if ($result !== false) {
                $this->success(__('Added successfully'));
            } else {
                $this->error(__('No rows were added'));
            }
        }

        $this->error(__('Parameter error'));
    }

    /**
     * 编辑
     * @throws Throwable
     */
    public function edit(): void
    {
        $id  = $this->request->param($this->model->getPk());
        $row = $this->model->find($id);
        if (!$row) {
            $this->error(__('Record not found'));
        }

        $dataLimitAdminIds = $this->getDataLimitAdminIds();
        if ($dataLimitAdminIds && !in_array($row[$this->dataLimitField], $dataLimitAdminIds)) {
            $this->error(__('You have no permission'));
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (!$data) {
                $this->error(__('Parameter %s can not be empty', ['']));
            }

            $data   = $this->excludeFields($data);
            $result = false;
            $this->model->startTrans();
            try {
                // 模型验证
                if ($this->modelValidate) {
                    $validate = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                    if (class_exists($validate)) {
                        $validate = new $validate;
                        if ($this->modelSceneValidate) $validate->scene('edit');
                        $validate->check($data);
                    }
                }
                //新增
                $ltnInfo = $this->addltnModel->where(['id'=>$data['addltn_id']])->field('addr,add_id')->find();
                if (!$ltnInfo->isEmpty()){
                    $result = $row->save($data);
                    /***************/
                    $updateData = [
                        'bind_map' => $ltnInfo['add_id'],
                        'bind_school' => $ltnInfo['addr'],
                        'bind_class' => $data['bind_class'],
                    ];
                    $this->adminModel->where(['id'=>$this->auth->id])->update($updateData);
                    /***************/
                    $this->model->commit();
                }else{
                    $result = false;
                }
                //新增 ↑  ↑  ↑  ↑  ↑  ↑  ↑

            } catch (Throwable $e) {
                $this->model->rollback();
                $this->error($e->getMessage());
            }
            if ($result !== false) {
                $this->success(__('Update successful'));
            } else {
                $this->error(__('No rows updated'));
            }
        }

        $this->success('', [
            'row' => $row
        ]);
    }
}