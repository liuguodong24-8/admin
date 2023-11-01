<?php

namespace app\api\controller\sccd;

use think\db\exception\PDOException;
use think\facade\Db;
use JPush\Client as JPush;
use think\Response;
use Throwable;      
use app\common\controller\Frontend;

class Indexapi extends Frontend
{
    protected array $noNeedLogin = [];
    protected $app_key = '46e5a6b20750b58dd6965499';
    protected $master_secret = '8e3e46fb0b9b831cd2db0a9d';

    public function initialize(): void
    {
        parent::initialize();
    }

    /*
     * 扫码打卡API
     *  API编号 AD0001
     */
    public function scan_add()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post(); //actid
            if (isset($post['activity_id']) && !empty($post['activity_id'])) {
                $data = ['dev_id' => $this->auth->id, 'activity_id' => $post['activity_id'], 'create_time' => time()];
                $activityscanModel = new \app\admin\model\Activityscan;
                $re = $activityscanModel->where(['dev_id' => $this->auth->id, 'activity_id' => $post['activity_id']])->findOrEmpty();
                if ($re->isEmpty()) {
                    $result = false;
                    $activityscanModel->startTrans();
                    try {
                        $result = $activityscanModel->insertGetId($data);
                        $activityscanModel->commit();
                    } catch (Throwable $e) {
                        $activityscanModel->rollback();
                        $this->error($e->getMessage());
                    }
                    if ($result !== false) {
                        $this->success('扫码成功', [], 200);
                    } else {
                        $this->error('扫码失败');
                    }
                } else {
                    $this->success('扫码成功', [], 200);
                }
            } else {
                $this->error('请求参数异常');
            }
        } else {
            $this->error('请求异常');
        }

    }


    /*
     *   查询设备是否锁定
     *   API编号 AD0003
     */
    public function check_lock()
    {
        if ($this->request->isGet()) {
            $data = Db::name('dev')->where('uid', '=', $this->auth->id)->field('device_id id,status')->find();
            $this->success('数据返回成功', $data, 200);
        } else {
            $this->error('请求异常');
        }
    }

    /*
     *   获取消息详情
     *   API编号 AD0005
     */
    public function get_msg_info()
    {
        if ($this->request->isGet()) {
            $id = $this->request->get("id/d", 0);
            if ($id > 0) {
                $modelMessagesendlist = new \app\admin\model\Messagesendlist;
                $modelTable = strtolower($modelMessagesendlist->getTable());
                $alias[$modelTable] = parse_name(basename(str_replace('\\', '/', get_class($modelMessagesendlist))));
                $where = [$modelTable . '.id' => $id];
                $info = $modelMessagesendlist->withJoin(['message', 'dev'], 'LEFT')
                    ->alias($alias)
                    ->where($where)
                    ->findOrEmpty();
                if ($info->isEmpty()) {
                    $res = null;
                } else {
                    $info = $info->toArray();
                    $res = [
                        'mes_important' => $info['message']['mes_important'],
                        'mes_important_text' => $info['message']['mes_important'] == 0 ? '普通' : '重要',
                        'mes_content' => $info['message']['mes_content'],
                        'msg_status' => $info['msg_status'],
                        'msg_status_text' => $info['msg_status'] == 0 ? '未读' : '已读',
                        'create_time' => $info['message']['create_time'],
                    ];
                }
                $this->success('请求成功', $res, 200);
            } else {
                $this->error('请求参数异常');
            }
        } else {
            $this->error('请求异常');
        }
    }

    /*
    *   获取消息分页数据
    *   API编号 AD0006
    *
    */

    public function get_msg_list()
    {
        if ($this->request->isGet()) {
            $limit = $this->request->get("limit/d", 10);
            if ($limit > 50) $limit = 50;
            $modelMessagesendlist = new \app\admin\model\Messagesendlist();
            $modelMessagesendlistTable = strtolower($modelMessagesendlist->getTable());
            $alias[$modelMessagesendlistTable] = parse_name(basename(str_replace('\\', '/', get_class($modelMessagesendlist))));
            $where = [$modelMessagesendlistTable . '.dev_id' => $this->auth->id];
            $res = $modelMessagesendlist->withJoin(['message'], 'LEFT')
                ->alias($alias)
                ->where($where)
                ->order('id desc')
                ->paginate($limit);
            $this->success('请求成功', ['list' => $res->items(), 'total' => $res->total()], 200);
        } else {
            $this->error('请求异常');
        }
    }


    /*
     *   设置消息已读
     *   API编号 AD0007
     *
     */
    public function get_msg_status()
    {
        if ($this->request->isPost()) {
            $PostData = request()->post();
            if (!empty($PostData['msg_id']) && $PostData['msg_id'] > 0) {
                $modelMessagesendlist = new \app\admin\model\Messagesendlist;
                $result = false;
                $modelMessagesendlist->startTrans();
                try {
                    $result = $modelMessagesendlist->where(['dev_id' => $this->auth->id, 'id' => $PostData['msg_id']])->update(['msg_status' => 1]);
                    $modelMessagesendlist->commit();
                } catch (PDOException $e) {
                    $modelMessagesendlist->rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    $this->success('请求成功', ['status' => 1, 'msg_id' => $PostData['msg_id']], 200);
                } else {
                    $this->error('请求参数异常');
                }
            } else {
                $this->error('请求参数异常');
            }
        } else {
            $this->error('请求异常');
        }
    }

    /*
     *  将录制的视频上传至oss
     *   API编号AD0008
     */

    public function video_upload_oss()
    {


    }


    /*
     *
     *   触发服务器警报系统
     *   API编号AD0009
     */
    public function sos_danger()
    {


    }


    /*
     *    获取oss token并返回
     *    API编号 AD0010
     *
     */

    public function get_oss_token()
    {


    }


    /*
 *    接收设备传输的数据写入地理位置并且实时更新地位置
 *    API编号 AD0011
 *
 */
    public function save_map_device()
    {
        if ($this->request->isPost()) {
            $PostData = $this->request->post();
            if (!isset($PostData['lat']) || empty($PostData['lat']) || !isset($PostData['lng']) || empty($PostData['lng'])) $this->error('访问参数异常');
            //实时更新设备地理位置
            $modelDev = new \app\admin\model\Dev;
            $modelDev->startTrans();
            $result = false;
            $result2 = false;
            $tm = time();
            try {
                $result = $modelDev->where('uid', $this->auth->id)->update(['lng' => $PostData['lng'], 'lat' => $PostData['lat'], 'map_update_time' => $tm]);
                //实时写入设备数据进行存储
                $result2 = Db::name('save_map')->insert(['dev_id' => $this->auth->id, 'lng' => $PostData['lng'], 'lat' => $PostData['lat'], 'create_time' => $tm]);
                $modelDev->commit();
            } catch (Throwable $e) {
                $modelDev->rollback();
                $this->error($e->getMessage());
            }
            if ($result !== false && $result2 !== false) {
                $this->success('数据更新成功', [], 200);
            } else {
                $this->error('数据更新失败');
            }
        } else {
            $this->error('请求异常');
        }
    }

    /*
     *    软件列表接口
     *    API编号 AD0012
     *
     */
    public function software_list()
    {

    }

    /*
     *    软件列表接口
     *    API编号 AD0013
     *
     */
    public function get_userinfo()
    {

    }

    /*
     *    返回锁屏界面图片
     *    API编号 AD0014
     *
     */
    public function lock_img()
    {
//        $get = $this->request->get();
//        $uuid = $get['sccd_id'];
        $uuid = 'b9a757700ec59997b394208d514aa5922c0020f1f74e43d6537d3295899f839827ccab9b97fdd74124505a4a90ec08574c656e7427241850';
        $bg_img = realpath(app()->getRootPath()) . '/public/static/poster/bg_lock.png';
        $dir = realpath(app()->getRootPath()) . '/public';
        $filePath = '/upload/lock/' . date('Y-m-d');
        $realpath = $dir . $filePath;
        $file_name = md5($uuid) . '.png';
        file_exists($realpath) && is_dir($realpath) || mkdir($realpath, 0755, true);
        //读取底图，开始打水印
        $imageClass = \Intervention\Image\ImageManagerStatic::class;
        $image = $imageClass::make($dir . '/static/poster/bg_lock.png');
        $fontPath = $dir . '/static/fonts/zhttfs/SourceHanSansCN-Normal.ttf';
        $fontPath = $dir . '/static/fonts/zhttfs/1.ttf';
        // 添加文本水印
        $image->text('姓名：小明', 20, 100, function ($font) use ($fontPath) {
            $font->file($fontPath);
            $font->size(30);
            $font->color('#000000');
            $font->align('left');
            $font->valign('top');
        });
        $image->text('班级：2021级26班', 300, 100, function ($font) use ($fontPath) {
            $font->file($fontPath);
            $font->size(30);
            $font->color('#000000');
            $font->align('left');
            $font->valign('top');
        });

        // 保存处理后的图像
        $image->save($realpath . '/' . $file_name);
        $this->success('获取成功', ['img_url' => $filePath . '/' . $file_name], 200);
    }

    /*
     *   推送设备ID到服务器
     *   API编号AD0015
     */
    public function push_id()
    {


    }


    /*
     *  推送消息
     *  这里是推所有人
     */

    public function push()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            if (!isset($post['msg']) || empty($post['msg'])) $this->error('请求异常');
            $client = new JPush($this->app_key, $this->master_secret);
            $client->push()
                ->setPlatform('android')
                ->addAllAudience()
                ->setNotificationAlert($post['msg'])
                ->send();
            $this->success('操作成功', [], 200);
        } else {
            $this->error('请求异常');
        }
    }


    /**
     * 2023-09-10
     * 获取设备绑定的学员信息：姓名+班级
     * @return \think\Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function get_student_info()
    {
        if ($this->request->isGet()) {
            //利用token登录机制拿到父类计算的用户信息，直取 uid
            $modelDev = new \app\admin\model\Dev;
            $modelDevTable = strtolower($modelDev->getTable());
            $alias[$modelDevTable] = parse_name(basename(str_replace('\\', '/', get_class($modelDev))));
            $where = [$modelDevTable . '.uid' => $this->auth->id];
            $info = $modelDev->withJoin(['grade', 'period'], 'LEFT')
                ->alias($alias)
                ->where($where)
                ->find();
            if (empty($info)) {
                $res = null;
            } else {
                $info = $info->toArray();
                $res = [
                    'id' => $info['id'],
                    'student_name' => $info['user_name'],
                    'user_avatar' => $info['user_avatar'],
                    'class_name' => '',
                    'school_name' => $info['school_name'],
                    'teacher_name' => $info['teacher_name'],
                    'guardian_name' => $info['guardian_name'],
                    'guardian_mobile' => $info['guardian_mobile'],
                ];
                $res['class_name'] .= !empty($info['in_year']) ? ($info['in_year'] . '年') : '';
                $res['class_name'] .= isset($info['period']['phase_name']) && !empty($info['period']['phase_name']) ? $info['period']['phase_name'] : '';
                $res['class_name'] .= isset($info['grade']['grade_name']) && !empty($info['grade']['grade_name']) ? $info['grade']['grade_name'] : '';
                $res['class_name'] .= isset($info['class_name']) && !empty($info['class_name']) ? $info['class_name'] : '';
                $res['md5'] = md5(implode(',',$res));
            }
            $this->success('获取成功', $res, 200);
        } else {
            $this->error('请求异常');
        }
    }

    /*
     * 读取最新一次历史上的今天内容并返回
     *   API编号 AD0004
     */
    public function history_info()
    {
        if ($this->request->isGet()) {
            $res = Db::name('todayinhistory')->order('id DESC')->limit(1)->find();
            $this->success('获取成功', $res, 200);
        } else {
            $this->error('请求异常');
        }
    }

    /**
     * @return Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function upgrade_info()
    {
        if ($this->request->isGet()) {
            $softwareModel = new \app\admin\model\Software;
            $softwareModelTable = strtolower($softwareModel->getTable());
            $res = Db::name('software')->alias('a')
                ->leftJoin($softwareModelTable.' b','a.type=b.type AND a.id<b.id')
                ->whereRaw('b.id IS NULL')
                ->field('a.*')
                ->select();
            $this->success('获取成功', $res, 200);
        } else {
            $this->error('请求异常');
        }
    }

    /**
     * post 更新写入报警数据
     */
    public function set_sos()
    {
        if ($this->request->isPost()) {
            $post = $this->request->post();
            if (!isset($post['file_url']) || empty($post['file_url']) || !isset($post['sos_id']) || empty($post['sos_id'])) $this->error('请求参数异常');
            $sosModel = new \app\admin\model\Sos;
            $insert_data = [
                'video_url' => $post['file_url']
            ];
            $result = false;
            $sosModel->startTrans();
            try {
                $result = $sosModel->where(['id'=>$post['sos_id']])->save($insert_data);
                $sosModel->commit();
            } catch (Throwable $e) {
                $sosModel->rollback();
                $this->error($e->getMessage());
            }
            if ($result !== false) {
                $this->success('操作成功', [], 200);
            } else {
                $this->error('记录失败，请稍后重试');
            }
        } else {
            $this->error('请求异常');
        }
    }

    /**
     * 发短信
     */
    public function send_sms()
    {
        if ($this->request->isPost()){
            $PostData = $this->request->post();
            if (!isset($PostData['lat']) || empty($PostData['lat']) || !isset($PostData['lng']) || empty($PostData['lng'])) $this->error('访问参数异常');
            //根据token得到uid，然后查询出dev表的主键，写入insert_data
            $devModel = new \app\admin\model\Dev;
            $dev_obj = $devModel->where(['uid'=>$this->auth->id])->field('id,user_name,teacher_mobile')->findOrEmpty();
            if($dev_obj->isEmpty()) $this->error('未找到该名学生，无法发送短信');

            $dev_arr = $dev_obj->toArray();
            $sosModel = new \app\admin\model\Sos;
            $insert_data = [
                'dev_id' => $dev_arr['id'],
                'dev_addr' => $PostData['lng'] . ',' . $PostData['lat'],
                'create_time' => time()
            ];
            $resultId = false;
            $sosModel->startTrans();
            try {
                $resultId = $sosModel->insertGetId($insert_data);
                $sosModel->commit();
            } catch (Throwable $e) {
                $sosModel->rollback();
                $this->error($e->getMessage());
            }
            if ($resultId !== false) {
                //SOS经纬度存储成功,开始发短信
                $url = $this->request->domain() . '/index.php/api/sms/send';
                $client = new \GuzzleHttp\Client();
                $response = $client->post($url,[
                    'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
                    'json' => [
                        'mobile' => $dev_arr['teacher_mobile'],
                        'template_code' => 'user_sos_teacher',//对应数据库的code，sms是模版扩展自己封装的方法
                        'template_data' => [
                            'username' => $dev_arr['user_name']
                        ]
                    ]
                ]);
                $result = json_decode($response->getBody()->getContents(), true);
                /*测试数据*/$result['code'] = 200;
                addLog(var_export($result,true),'_sp_send_sms.log');
                if($result['code']>0){
                    $this->success('操作成功', ['sos_id' => $resultId], 200);
                }else{
                    $this->error($result['msg']);
                }
            } else {
                $this->error('发送失败，请稍后重试');
            }
        } else {
            $this->error('请求异常');
        }
    }

    /**
     * cos配置获取
     * @throws Throwable
     */
    public function get_cos_config(){
        if ($this->request->isGet()){
            $uploadConfig = get_sys_config('', 'upload');
            $res['cos_path'] = '/storage/default/';
            $res['upload_bucket'] = $uploadConfig['upload_bucket'];
            $res['upload_url'] = $uploadConfig['upload_url'];
            $res['upload_cdn_url'] = $uploadConfig['upload_cdn_url'];
            $this->success('获取成功',$res,200);
        } else {
            $this->error('请求异常');
        }
    }


}