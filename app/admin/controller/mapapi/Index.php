<?php

namespace app\admin\controller\mapapi;

use ba\Random;
use Throwable;
use think\facade\Db;
use app\common\controller\Backend;
use app\admin\model\Admin as AdminModel;

class Index extends  Backend{

    public function initialize(): void
    {
        parent::initialize();
    }


    /*
      *  设备在线数量
      *  API编号DT0001
      *
      */
    public function device_online_n()
    {


    }

    /*
     * 设备离线数量
     * API编号DT0002
     */
    public function device_exit_n()
    {


    }

    /*
     * 在线设备信息
     * API编号DT0003
     *
     */
    public function device_online_info()
    {


    }

    /*
     *  离线设备信息
     *  API编号 DT0004
     */
    public function device_exit_info()
    {


    }

    /*
     *   所有设备状态信息
     *   API编号  DT0005
     *
     */
    public function device_status_info()
    {


    }

    /*
     *  班级信息
     *  API编号DT0006
     *
     */
    public function class_info()
    {


    }

    /*
     *  get_address_ltn
     *  API编号DT0007
     *
     */
    public function get_address_ltn()
    {
        $ReturnData = Db::table('sccd_add_ltn')->where('id', 1)->field('addr_ltn,add_id')->find();
        return $this->create($ReturnData, 200, '数据请求成功');
    }


    /*
     *  地理围栏获取
     *  API编号 DT0008
     *
     */
    public function get_wl_map()
    {

        $PostId = request()->param();
        $ReturnData = Db::table('sccd_add_wl')->where('add_id', $PostId['add_id'])->field('add_wl')->select();
        return $this->create($ReturnData, 200, '数据返回成功');


    }


    /*
     *    获取当前地理位置设备进行显示
     *     API编号DT0009
     */
    public function get_device_map()
    {

        $ReturnData = Db::table('sccd_map')->field('sccd_id,lng,lat,device_name')->select();
        return $this->create($ReturnData, 200, '数据返回成功');

    }








}