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
class DevList extends Backend
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
        list($where, $alias, $limit, $order) = $this->queryBuilder();
        // $where[] = ['admin_id','=',$this->auth->id];

        if ($this->request->get("school")) {
            $where[] = ['school_name', 'in', $this->request->get("school")];
        }

        if ($this->request->get("grade")) {
            $where[] = ['grade_id', 'in', $this->request->get("grade")];
        }

        if ($this->request->get("class")) {
            $where[] = ['class_name', 'in', $this->request->get("class")];
        }

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


}