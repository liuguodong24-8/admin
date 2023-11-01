<?php
namespace app\api\controller\sccd;

use think\db\exception\PDOException;
use think\facade\Db;
use think\Response;
use app\common\controller\Backend;
class Mapapi extends Backend{

    /**
     * 无需鉴权的方法
     * @var array
     */
    protected array $noNeedPermission = ['get_address_ltn','get_wl','device_online_total','device_offline_total','device_online_info','device_offline_info','device_all_info','class_info','get_sos_info','sos_situation','sos_situation_status'];

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

    //获取地理围栏编号
    public  function  get_address_ltn(){

         $Address_id=Db::name('admin')->where('id',$this->auth->id)->field('bind_map')->find();

         $Result=Db::name('addltn')->where('add_id',$Address_id['bind_map'])->find();

          return   $this->create(['ltn_data'=>$Result],'200','请求成功');

    }

    public function get_wl(){

       $Address_id=Db::name('admin')->where('id',$this->auth->id)->field('bind_map')->find();

       $WlResult=Db::name('add_wl')->where('add_id',$Address_id['bind_map'])->select();

       return $this->create(['data'=>$WlResult],'200','请求成功');


   }

    /*
    *  在线设备数量
    */
    public function device_online_total()
    {
        $bind_no=Db::name('teacherbind')->where('admin_id',$this->auth->id)->field('teacher_id_card_no')->find();


        $total = Db::name('dev')->where('teacher_id_card_no',$bind_no['teacher_id_card_no'])->where('update_time', '>' ,time() - 900)->count();



        return $this->create(['online_count'=>$total], 200, '数据返回成功');
    }

    /*
    *  离线设备数量
    */
    public function device_offline_total()
    {
        $bind_no=Db::name('teacherbind')->where('admin_id',$this->auth->id)->field('teacher_id_card_no')->find();

        $total = Db::name('dev')->where('teacher_id_card_no',$bind_no['teacher_id_card_no'])->where('update_time', '<' ,time() - 900)->count();

        return $this->create(['offline_count'=>$total], 200, '数据返回成功');
    }

    /*
    *  在线设备信息
    */
    public function device_online_info()
    {
        $bind_no=Db::name('teacherbind')->where('admin_id',$this->auth->id)->field('teacher_id_card_no')->find();

        $list = Db::name('dev')->where('teacher_id_card_no',$bind_no['teacher_id_card_no'])->where('update_time', '>' ,time() - 900)->select();

        return $this->create($list, 200, '数据返回成功');
    }

    /*
    *  离线设备信息
    */
    public function device_offline_info()
    {
        $bind_no=Db::name('teacherbind')->where('admin_id',$this->auth->id)->field('teacher_id_card_no')->find();

        $list = Db::name('dev')->where('teacher_id_card_no',$bind_no['teacher_id_card_no'])->where('update_time', '<' ,time() - 900)->select();

        return $this->create($list, 200, '数据返回成功');
    }

    /*
    *  全部设备信息
    */
    public function device_all_info()
    {
        $bind_no=Db::name('teacherbind')->where('admin_id',$this->auth->id)->field('teacher_id_card_no')->find();

        $list = Db::name('dev')->where('teacher_id_card_no',$bind_no['teacher_id_card_no'])->select();
        $total = ['online'=>0,'offline'=>0,'online_rate'=>0];//在线，离线，在线率百分比
        $time = time();
        $lin = [];
        //addLog(var_export($list->toArray(),true),'_sp_1024-device_all_info2.log');
        if(!empty($list) || (is_array($list) && count($list) > 0 ) || (!is_array($list) && !$list->isEmpty())) {
            $lin = is_array($list) ? $list : $list->toArray();
            foreach ($lin as &$v){
                if(($time - $v['map_update_time']) < 1800){
                    $total['online'] ++;
                    $v['online_status'] = 1;
                }else{
                    $total['offline'] ++;
                    $v['online_status'] = 0;
                }
            }
            //addLog(var_export($lin,true),'_sp_1024-device_all_info.log');
            $total['online_rate'] = bcdiv((string)$total['online'],(string)count($lin),2);
        }
        return $this->create(['list'=>$lin,'total'=>$total], 200, '数据返回成功');
    }

    /*
    *  班级信息
    */
    public function class_info()
    {
        $school = $this->auth->bind_school;
        $class = $this->auth->bind_class;
        $teacher_name=$this->auth->nickname;
        $data = [
            'school'=>$school,
            'class'=>$class,
            'teacher_name'=>$teacher_name
        ];

        return $this->create($data, 200, '数据返回成功');
    }


    /**
     * 获取sos表详情
     */
    public function get_sos_info()
    {
        if ($this->request->isGet()){
            $get = $this->request->get();
            $devModel = new \app\admin\model\Dev;
            $devModelTable = strtolower($devModel->getTable());
            $result = Db::name('sos')->alias('a')
                ->leftJoin($devModelTable.' b','a.dev_id = b.id')
                ->where(['a.id'=>$get['id']])
                ->field('a.dev_addr,a.create_time,b.guardian_name,b.guardian_mobile,b.user_name')
                ->find();

            if(empty($result)){
                $this->error('获取失败，未查询到数据');
            }
            return $this->create( $result, 200, '数据返回成功');
        } else {
            $this->error('请求异常');
        }
    }


    /**
     * sos情况轮询接口
     */
    public function sos_situation()
    {
        //根据token关联
        $teacher_info = Db::name('teacherbind')->where(['admin_id'=>$this->auth->id])->find();
        $where = [['a.status','=',0]];//默认查询未处理的
        if($this->auth->mobile == '' || empty($teacher_info) || (!is_array($teacher_info) && $teacher_info->isEmpty()) || count($teacher_info)==0) {
            //给个不存在的条件限制输出
            $where[] = ['b.uid', '>', 99999999];
        }else{
            if($this->auth->mobile != '') $where[] = ['b.teacher_mobile','=',$this->auth->mobile];
            if(!empty($teacher_info) && ((!is_array($teacher_info) && !$teacher_info->isEmpty()) || count($teacher_info)>0)) {
                $where[] = ['b.teacher_id_card_no', '=', $teacher_info['teacher_id_card_no']];
            }else{
                //给个不存在的条件限制输出
                $where[] = ['b.uid', '>', 99999999];
            }
        }
        //获取sos表
        $devModel = new \app\admin\model\Dev;
        $devModelTable = strtolower($devModel->getTable());
        $sos_list = Db::name('sos')->alias('a')
            ->leftJoin($devModelTable.' b','a.dev_id = b.id')
            ->where($where)
            ->column('a.id,a.dev_addr,b.user_name,b.guardian_name,b.guardian_mobile');
        if (is_array($sos_list) && count($sos_list)>0){
            $this->success('报警数据查询成功',$sos_list,200);
        }else{
            $this->error('暂无报警数据');
        }
    }

    /**
     * 处理状态变更
     */
    public function sos_situation_status(){
        $id = $this->request->get('id');
        if((int)$id >0){
            $result = Db::name('sos')->where(['id'=>$id])->update(['status'=>1]);
            if($result !== false){
                $this->success('处理成功',['id'=>$id],200);
            }else{
                $this->error('处理失败，稍后再试');
            }
        }else{
            $this->error('操作失败，参数异常');
        }
    }








}