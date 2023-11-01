<?php

namespace app\admin\controller;

use think\facade\Db;
use Throwable;
use app\common\controller\Backend;

/**
 * sos报警记录
 */
class Sos extends Backend
{
    /**
     * Sos模型对象
     * @var object
     * @phpstan-var \app\admin\model\Sos
     */
    protected object $model;

    protected array|string $preExcludeFields = ['id', 'create_time'];

    protected array $withJoinTable = ['dev'];

    protected string|array $quickSearchField = ['id'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new \app\admin\model\Sos;
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
        //查询当前token的所有学生id作为条件
        $teacher_info = Db::name('teacherbind')->where(['admin_id'=>$this->auth->id])->find();
        $sccd_ids = Db::name('dev')->where(['teacher_id_card_no'=>$teacher_info['teacher_id_card_no'],'teacher_mobile'=>$this->auth->mobile])->column('id');
        if(count($teacher_info)>0){
            $where[] = ['teacher_id_card_no','=',$teacher_info['teacher_id_card_no']];
            $where[] = ['teacher_mobile','=',$this->auth->mobile];
        }else{
            $this->error('该账号未绑定，无法查询');
        }
        $res = $this->model
            ->withJoin($this->withJoinTable, $this->withJoinType)
            ->alias($alias)
            ->where($where)
            ->order($order)
            ->paginate($limit);
        $res->visible(['dev' => ['user_name','guardian_name','guardian_mobile']]);
        $listData = $res->items();
        if(count($listData)>0){
            foreach ($listData as &$v){
                $v['sos'] = 'http://sosmap.hellocrab.top?token='.$this->auth->getToken().'&id='.$v['id'];
            }
        }
        $this->success('', [
            'list'   => $listData,
            'total'  => $res->total(),
            'remark' => get_route_remark(),
        ]);
    }

    /**
     * 若需重写查看、编辑、删除等方法，请复制 @see \app\admin\library\traits\Backend 中对应的方法至此进行重写
     */
}