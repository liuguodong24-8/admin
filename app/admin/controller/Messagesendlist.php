<?php

namespace app\admin\controller;

use Throwable;
use app\common\controller\Backend;

/**
 * 消息推送清单
 */
class Messagesendlist extends Backend
{
    /**
     * Messagesendlist模型对象
     * @var object
     * @phpstan-var \app\admin\model\Messagesendlist
     */
    protected object $model;

    protected array|string $preExcludeFields = ['id', 'update_time', 'create_time'];

    protected array $withJoinTable = ['message', 'dev'];

    protected string|array $quickSearchField = ['id'];

    /**
     * 无需鉴权的方法
     * @var array
     */
    protected array $noNeedPermission = ['total'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new \app\admin\model\Messagesendlist;
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
        if($this->request->param('message_id')) $where[] = ['message_id','=',$this->request->param('message_id')];
        $res = $this->model
            ->withJoin($this->withJoinTable, $this->withJoinType)
            ->alias($alias)
            ->where($where)
            ->order($order)
            ->paginate($limit);
        $res->visible(['message' => ['mes_important','mes_concent'], 'dev' => ['user_name']]);

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
     * 获取未读已读数量统计
     */
    public function total()
    {
        $getData = $this->request->get();
        if(!isset($getData['id']) || (int)$getData['id']<=0) $this->error('条件异常，查询失败！');
        $data = $this->model->where(['message_id'=>$getData['id']])->group('msg_status')->field('msg_status,COUNT(*) num')->select();
        $result = [0,0];
        if (!$data->isEmpty()){
            $lin = array_column($data->toArray(),null,'msg_status');
            $result = [isset($lin[0]) ? $lin[0]['num'] : 0, isset($lin[1]) ? $lin[1]['num'] : 0];
        }
        $this->success('查询成功',$result);
    }
}