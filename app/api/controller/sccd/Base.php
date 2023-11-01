<?php

namespace app\api\controller\sccd;

use think\Response;
use think\App;

abstract class Base
{

    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var App
     */
    protected $app;

    /**
     * 构造方法
     * @access public
     * @param App $app 应用对象
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {

    }

    protected function create($data, $code = 200, $msg = '', $type = 'json')
    {
        //标准API生成
        $result = [
            'code' => $code,
            'data' => $data,
            'msg' => $msg
        ];
        return Response::create($result, $type);
    }

}