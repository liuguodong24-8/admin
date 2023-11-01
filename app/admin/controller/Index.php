<?php
declare (strict_types=1);

namespace app\admin\controller;

use Throwable;
use ba\ClickCaptcha;
use think\facade\Config;
use think\facade\Validate;
use app\common\facade\Token;
use app\admin\model\AdminLog;
use app\common\controller\Backend;

class Index extends Backend
{
    protected array $noNeedLogin      = ['logout', 'login', 'upload'];
    protected array $noNeedPermission = ['index'];

    /**
     * 后台初始化请求
     * @return void
     * @throws Throwable
     */
    public function index(): void
    {
        $adminInfo          = $this->auth->getInfo();
        $adminInfo['super'] = $this->auth->isSuperAdmin();
        unset($adminInfo['token'], $adminInfo['refresh_token']);

        $menus = $this->auth->getMenus();
        if (!$menus) {
            $this->error(__('No background menu, please contact super administrator!'));
        }
        $this->success('', [
            'adminInfo'  => $adminInfo,
            'menus'      => $menus,
            'siteConfig' => [
                'siteName' => get_sys_config('site_name'),
                'version'  => get_sys_config('version'),
                'cdnUrl'   => full_url(),
                'apiUrl'   => Config::get('buildadmin.api_url'),
                'upload'   => get_upload_config(),
            ],
            'terminal'   => [
                'installServicePort' => Config::get('terminal.install_service_port'),
                'npmPackageManager'  => Config::get('terminal.npm_package_manager'),
            ]
        ]);
    }

    /**
     * 管理员登录
     * @return void
     * @throws Throwable
     */
    public function login(): void
    {
        // 检查登录态
        if ($this->auth->isLogin()) {
            $this->success(__('You have already logged in. There is no need to log in again~'), [
                'routePath' => '/admin'
            ], 302);
        }

        $captchaSwitch = Config::get('buildadmin.admin_login_captcha');

        // 检查提交
        if ($this->request->isPost()) {
            $username = $this->request->post('username');
            $password = $this->request->post('password');
            $keep     = $this->request->post('keep');
            $test    = $this->request->post('test');

            $rule = [
                'username|' . __('Username') => 'require|length:3,30',
                'password|' . __('Password') => 'require|regex:^(?!.*[&<>"\'\n\r]).{6,32}$',
            ];
            $data = [
                'username' => $username,
                'password' => $password,
            ];

            if (!$test) {
                if ($captchaSwitch) {
                    $rule['captchaId|' . __('CaptchaId')] = 'require';
                    $rule['captchaInfo|' . __('Captcha')] = 'require';

                    $data['captchaId']   = $this->request->post('captchaId');
                    $data['captchaInfo'] = $this->request->post('captchaInfo');
                }
                $validate = Validate::rule($rule);
                if (!$validate->check($data)) {
                    $this->error($validate->getError());
                }

                if ($captchaSwitch) {
                    $captchaObj = new ClickCaptcha();
                    if (!$captchaObj->check($data['captchaId'], $data['captchaInfo'])) {
                        $this->error(__('Captcha error'));
                    }
                }
            }


            AdminLog::setTitle(__('Login'));

            $res = $this->auth->login($username, $password, (bool)$keep);
            if ($res === true) {
                $this->success(__('Login succeeded!'), [
                    'userInfo'  => $this->auth->getInfo(),
                    'routePath' => '/admin'
                ]);
            } else {
                $msg = $this->auth->getError();
                $msg = $msg ?: __('Incorrect user name or password!');
                $this->error($msg);
            }
        }

        $this->success('', [
            'captcha' => $captchaSwitch
        ]);
    }

    /**
     * 管理员注销
     * @return void
     */
    public function logout(): void
    {
        if ($this->request->isPost()) {
            $refreshToken = $this->request->post('refreshToken', '');
            if ($refreshToken) Token::delete((string)$refreshToken);
            $this->auth->logout();
            $this->success();
        }
    }


    /**
     * 上传文件
     */
    public function upload(): void
    {
        // 检查上传的文件是否存在
        if ($this->request->isPost()) {
            $file = $this->request->file('file'); // 获取上传的文件
            if ($file) {
                $originalName = $file->getOriginalName();
                $extension = pathinfo($originalName, PATHINFO_EXTENSION); // 获取文件后缀

                // 验证文件后缀
                if (in_array($extension, ['jpg', 'png', 'jpeg'])) {
                    $savePath = 'uploads';
                    // 文件上传成功，返回文件路径或其他信息
                   // $upload = new \think\File\UploadedFile($file->getRealPath(), $originalName, 'image/jpeg');
                    // 构建保存的文件名
                    $filename = uniqid() . '.' . $extension;
                    $info = $file->move($savePath, $filename);
                    $this->success($info->getRealPath());
                } else {
                    // 文件后缀验证失败，返回错误信息
                    $this->error("文件格式不支持，仅支持jpg, png, jpeg 格式的图片文件");
                }
            } else {
                // 未获取到上传文件
                $this->error("上传失败");
            }
        }
    }

    /**
     * 名称
     */
    public function name(): void
    {
        // 检查上传的文件是否存在
        if ($this->request->isGet()) {
            $this->success($this->auth->nickname);
        }
    }

}
