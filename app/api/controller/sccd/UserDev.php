<?php

namespace app\api\controller\sccd;

use app\common\controller\Frontend;
use Throwable;
use ba\Random;
use think\App;
use app\admin\model\Dev;

class UserDev extends Frontend
{
    protected array $noNeedLogin = ['get_user_info', 'get_token'];
    protected $dev_model = '';

    public function initialize(): void
    {
        parent::initialize();

        $this->dev_model = new Dev();
    }

    private function xun_fly($params, $path)
    {
        /*
         * $data = [
            'method' => 'POST',
            'params' => [
                'condition' =>[
                    'isRequired' => true,
                    'paramPosition' => 'FORM',
                    'data' => [
                        'thirdId' => 'a0fb831afabb',
                        'type' => '1218'
                    ]
                ]
            ],
            'path' => '/queryThirdBind1'
        ];
         */
        $url = 'http://132.232.108.101:8082/proxy/iflytek';
        $data = [
            'method' => 'POST',
            'params' => $params,
            'path' => $path
        ];
        addLog($path,'_xun_fly.log');
        addLog(var_export($params,true),'_xun_fly.log');
        $client = new \GuzzleHttp\Client();
        $response = $client->post($url,[
            'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
            'json' => $data
        ]);
        $result = json_decode($response->getBody()->getContents(), true);
        if($result['success']){
            return $result['data'];
        }else{
            return [];
        }
    }

    /**
     * @return \think\Response
     * //a0fb831afabb
     */
    public function get_user_info()
    {
        /***************************/
        $tm = time();
        $user = [
            'uid'=>0,
            'device_id'=>'',
            'mac_id'=>'',
            'user_id'=>'',
            'user_name'=>'',
            'class_name'=>'',
            'class_id'=>'',
            'school_id'=>'',
            'grade_id'=>'',
            'period_id'=>'',
            'graduated_year'=>'',
            'in_year'=>'',
            'teaching_cycle' => '',
            'teacher_name'=>'',
            'teacher_mobile'=>'',
            'teacher_id_card_no'=>'',
            'update_time'=>$tm,
            'create_time'=>$tm
        ];
        if ($this->request->isPost()){
            $post = $this->request->post();
            addLog(var_export($post,true),'_get_user_info_post_param.log');
            if(key_exists('code',$post) && !empty($post['code']) && key_exists('dev_id',$post) && !empty($post['dev_id'])){
                $user['device_id'] = $post['dev_id'];//极光id
                $user['mac_id'] = $post['code'];//串号
                //获取user_id
                $third_info = $this->xun_fly(['condition' =>[
                    'isRequired' => true,
                    'paramPosition' => 'FORM',
                    'data' => [
                        'thirdId' => $post['code'],
                        'type' => '1218'
                    ]
                ]],'/queryThirdBind1');
                addLog($post['code'].' | queryThirdBind1','_$third_info.log');
                addLog(var_export($third_info,true),'_$third_info.log');
                $third_info = $third_info[0];//返回值是个多维数组，不是单独json
                if(key_exists('userId',$third_info) && !empty($third_info['userId'])){
                    $user['user_id'] = $third_info['userId'];
                    //1、获取姓名
                    $user_info_part = $this->xun_fly(['userId' =>[
                        'isRequired' => true,
                        'paramPosition' => 'FORM',
                        'data' => $user['user_id']
                    ]],'/getUserByUserId');
                    addLog($post['code'].' | getUserByUserId','_$user_info_part.log');
                    addLog(var_export($user_info_part,true),'_$user_info_part.log');
                    if(key_exists('userName',$user_info_part) && !empty($user_info_part['userName'])) {
                        $user['user_name'] = $user_info_part['userName'];
                    }else{
                        $this->error('学员数据接口异常！数据同步失败，未获取到学生姓名');
                    }
                    //2、班级名称，班级id，学校id，年级，学段，毕业年份，入学年份
                    $user_info_part2 = $this->xun_fly([
                        'studentId' =>[
                            'isRequired' => true,
                            'paramPosition' => 'FORM',
                            'data' => $user['user_id']
                        ],
                        'param'=>[
                            'isRequired' => true,
                            'paramPosition' => 'FORM',
                            'data' => ['type'=> '1218']
                        ]
                    ],'/listOrgClassByStudent1');
                    addLog($post['code'].' | listOrgClassByStudent1','_$user_info_part2.log');
                    addLog(var_export($user_info_part2,true),'_$user_info_part2.log');
                    if (!is_array($user_info_part2) || count($user_info_part2) == 0 || !is_array($user_info_part2[0]) || count($user_info_part2[0]) == 0) {
                        $this->error('学员数据接口异常！数据同步失败，未获取到学生班级信息');
                    }
                    $user_info_part2 = $user_info_part2[0];//返回值是个多维数组，不是单独json
                    if(key_exists('className',$user_info_part2) && !empty($user_info_part2['className'])) $user['class_name'] = $user_info_part2['className'];
                    if(key_exists('id',$user_info_part2) && !empty($user_info_part2['id'])) $user['class_id'] = $user_info_part2['id'];
                    if(key_exists('schoolId',$user_info_part2) && !empty($user_info_part2['schoolId'])) $user['school_id'] = $user_info_part2['schoolId'];
                    if(key_exists('inYear',$user_info_part2) && !empty($user_info_part2['inYear'])) $user['in_year'] = $user_info_part2['inYear'];
                    if(key_exists('graduatedYear',$user_info_part2) && !empty($user_info_part2['graduatedYear'])) $user['graduated_year'] = $user_info_part2['graduatedYear'];
                    if(key_exists('phaseCode',$user_info_part2) && !empty($user_info_part2['phaseCode'])) $user['period_id'] = $user_info_part2['phaseCode'];
                    if(key_exists('gradeCode',$user_info_part2) && !empty($user_info_part2['gradeCode'])) $user['grade_id'] = $user_info_part2['gradeCode'];
                    //schoolName
                    $school_info = $this->xun_fly(['schoolId' =>[
                        'isRequired' => true,
                        'paramPosition' => 'FORM',
                        'data' => $user['school_id']
                    ]],'/getSchool');
                    if(key_exists('schoolName',$school_info) && !empty($school_info['schoolName'])) $user['school_name'] = $school_info['schoolName'];
                    //3、班主任信息
                    //3.1 教学周期id
                    $cycle_info = $this->xun_fly(['schoolId' =>[
                        'isRequired' => true,
                        'paramPosition' => 'FORM',
                        'data' => $user['school_id']
                    ]],'/getCurrentTeachingCycleInSchool1');
                    if(key_exists('id',$cycle_info) && !empty($cycle_info['id'])) {
                        $user['teaching_cycle'] = $cycle_info['id'];
                        $class_teacher_info = $this->xun_fly([
                            'classIds' =>[
                                'isRequired' => true,
                                'paramPosition' => 'FORM',
                                'data' => $user['class_id']
                            ],
                            'teachingCycle' =>[
                                'isRequired' => true,
                                'paramPosition' => 'FORM',
                                'data' => $cycle_info['id']
                            ],
                            'roleEnName' =>[
                                'isRequired' => true,
                                'paramPosition' => 'FORM',
                                'data' => 'headteacher'
                            ]
                        ],'/listHeaderMasterByClassTeachingCycleRole1');
                        addLog($post['code'].' | listHeaderMasterByClassTeachingCycleRole1','_$class_teacher_info.log');
                        addLog(var_export($class_teacher_info,true),'_$class_teacher_info.log');
                        //取班主任的层级很深
                        if(is_array($class_teacher_info) && count($class_teacher_info)>0 && is_array($class_teacher_info[0]) && count($class_teacher_info[0])>0
                            && key_exists('users', $class_teacher_info[0])
                            && is_array($class_teacher_info[0]['users'])
                            && count($class_teacher_info[0]['users'])>0 && is_array($class_teacher_info[0]['users'][0])){
                            $class_teacher_info = $class_teacher_info[0]['users'][0];
                            if(key_exists('userName',$class_teacher_info) && !empty($class_teacher_info['userName'])) $user['teacher_name'] = $class_teacher_info['userName'];
                            if(key_exists('mobile',$class_teacher_info) && !empty($class_teacher_info['mobile'])) $user['teacher_mobile'] = $class_teacher_info['mobile'];
                            if(key_exists('idCardNo',$class_teacher_info) && !empty($class_teacher_info['idCardNo'])) $user['teacher_id_card_no'] = $class_teacher_info['idCardNo'];
                        }else{
                            $this->error('学员数据接口异常！数据同步失败，未获取到班主任信息');
                        }
                    }
                }
            }else{
                addLog('get_user_info 参数code异常，接口访问失败','__userdev.log');
                $this->error('学员数据接口请求异常！');
            }
            addLog($post['code'],'user_info.log');
            addLog(var_export($user,true),'user_info.log');

            //多一步查询：
            $check_where = ['user_id' => $user['user_id']];
            //查询dev表是否有数据，没得才进行，有数据 $student_info
            $student_info = $this->dev_model->where($check_where)->findOrEmpty();

            $result = false;
            $uname = 'ad';
            $passwd = '';
            if($student_info->isEmpty()){
                //注册
                $this->dev_model->startTrans();
                try {
                    //最直接：查询，有就登录，没有就注册+写入
                    //问题： 账户名，密码，salt 三个字段取值？
                    $uname .= mb_substr($user['user_id'],-18,18);
                    $salt = Random::build('alnum', 16);
                    $passwd = mb_substr(md5($uname.$salt),5,15);
                    $reg_result = $this->auth->register($uname, $passwd,'','',1,[],(string)$salt);
                    addLog(var_export($reg_result,true),'_register_step.log');
                    if (isset($reg_result) && $reg_result === true){
                        $user['uid'] = $this->auth->id;//把user表的主键关联过来， 便于查询
                        $result = $this->dev_model->insertGetId($user);
                        $this->dev_model->commit();
                    }
                } catch (Throwable $e) {
                    $this->dev_model->rollback();
                    $this->error($e->getMessage());
                }
            }else{
                //更新
                $this->dev_model->startTrans();
                try {
                    //最直接：查询，有就登录，没有就注册+写入
                    //问题： 账户名，密码，salt 三个字段取值？
                    $uname .= mb_substr($user['user_id'],-18,18);
                    //user模型
                    $ordinary_user_model = new \app\admin\model\User;
                    $salt = $ordinary_user_model->where(['id'=>$student_info['uid']])->value('salt');
                    $passwd = mb_substr(md5($uname.$salt),5,15);
                    $login_result = $this->auth->login($uname, $passwd, true);
                    addLog('$uname='.$uname,'_update_login.log');
                    addLog('$salt='.$salt,'_update_login.log');
                    addLog('$passwd='.$passwd,'_update_login.log');
                    if ($login_result === true){
                        $user['uid'] = $this->auth->id;//把user表的主键关联过来， 便于查询
                        $result = $this->dev_model->where(['id'=>$student_info['id']])->save($user);
                        $this->dev_model->commit();
                    }
                } catch (Throwable $e) {
                    $this->dev_model->rollback();
                    $this->error($e->getMessage());
                }
            }

            if ($result !== false) {
                addLog($post['code'].'，写入成功','user_info.log');
                //写入成功，返回userinfo信息和token数据
                $info = $this->auth->getUserInfo();
                $userInfo['token'] = $info['token'];
                $userInfo['refresh_token'] = $info['refresh_token'];
                $userInfo['user_key'] = $uname;
                $userInfo['user_secret'] = $passwd;
                addLog(var_export($userInfo,true),'_initialization_success.log');
                $this->success('写入成功',$userInfo, 200);
            } else {
                addLog($post['code'].'，写入失败','user_info.log');
                $this->error('写入失败');
            }
        }else{
            $this->error('请求异常');
        }
    }

    /**
     * 未完成
     * @return void
     */
    public function get_token()
    {
       if ($this->request->isPost()){
           $post = $this->request->post();
           addLog(var_export($post,true),'_get_token.log');
           if (empty($post['user_key']) || empty($post['user_secret'])) $this->error('登录失败，令牌不能为空');

           $res = $this->auth->login($post['user_key'], $post['user_secret'], true);
           if (isset($res) && $res === true) {
               $info = $this->auth->getUserInfo();
               $userInfo['token'] = $info['token'];
               $userInfo['refresh_token'] = $info['refresh_token'];
               $this->success(__('Login succeeded!'),$userInfo,200);
           } else {
               addLog($this->auth->getError(),'_get_token.log');
               $this->error('登录失败，请重试');
           }
       }else{
           $this->error('请求异常');
       }
    }

}