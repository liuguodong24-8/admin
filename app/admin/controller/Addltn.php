<?php

namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 学校关联管理
 */
class Addltn extends Backend
{
    /**
     * Addltn模型对象
     * @var object
     * @phpstan-var \app\admin\model\Addltn
     */
    protected object $model;

    protected array|string $preExcludeFields = ['id'];

    protected string|array $quickSearchField = ['addr'];

    public function initialize(): void
    {
        parent::initialize();
        $this->model = new \app\admin\model\Addltn;
    }


    /**
     * 若需重写查看、编辑、删除等方法，请复制 @see \app\admin\library\traits\Backend 中对应的方法至此进行重写
     */
}