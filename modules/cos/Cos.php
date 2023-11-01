<?php

namespace modules\cos;

use think\App;
use QCloud\COSSTS\Sts;
use Qcloud\Cos\Client;
use Throwable;
use think\facade\Cache;
use think\facade\Event;
use app\common\model\Config;
use app\common\model\Attachment;
use app\admin\library\module\Server;

class Cos
{
    private string $uid = 'cos';

    public function AppInit(): void
    {
        // 上传配置初始化
        Event::listen('uploadConfigInit', function (App $app) {
            $uploadConfig = get_sys_config('', 'upload');
            if ($uploadConfig['upload_mode'] == 'cos' && empty($app->request->upload)) {
                $cdn                  = $uploadConfig['upload_cdn_url'] == '' ? 'https://' . $uploadConfig['upload_bucket'] . '.cos.' . $uploadConfig['upload_url'] . '.myqcloud.com' : $uploadConfig['upload_cdn_url'];
                $app->request->upload = [
                    'cdn'    => $cdn,
                    'mode'   => $uploadConfig['upload_mode'],
                    'params' => [
                        'bucket' => $uploadConfig['upload_bucket'],
                        'region' => $uploadConfig['upload_url']
                    ]
                ];
            }
        });

        // 附件管理中删除了文件
        Event::listen('AttachmentDel', function (Attachment $attachment) {
            if ($attachment->storage != 'cos') {
                return true;
            }
            $uploadConfig = get_sys_config('', 'upload');
            if (!$uploadConfig['upload_access_id'] || !$uploadConfig['upload_secret_key'] || !$uploadConfig['upload_bucket']) {
                return true;
            }
            $cosClient = new Client([
                'region'      => $uploadConfig['upload_url'],
                'credentials' => [
                    'secretId'  => $uploadConfig['upload_access_id'],
                    'secretKey' => $uploadConfig['upload_secret_key']
                ]
            ]);
            $key       = str_replace(full_url(), '', ltrim($attachment->url, '/'));
            $cosClient->deleteObject([
                'Bucket' => $uploadConfig['upload_bucket'],
                'Key'    => $key
            ]);
            return true;
        });
    }

    /**
     * 使用 secretId & secretKey 颁发临时凭证
     * @return array
     * @throws Throwable
     */
    public static function getSts(): array
    {
        $uploadConfig = get_sys_config('', 'upload');
        $sts          = new Sts();
        $config       = [
            'secretId'        => $uploadConfig['upload_access_id'],
            'secretKey'       => $uploadConfig['upload_secret_key'],
            'bucket'          => $uploadConfig['upload_bucket'],
            'region'          => $uploadConfig['upload_url'],
            'durationSeconds' => 3600,
            'allowPrefix'     => ['*'],
            'allowActions'    => [
                'name/cos:PutObject',
                'name/cos:PostObject',
                'name/cos:InitiateMultipartUpload',
                'name/cos:ListMultipartUploads',
                'name/cos:ListParts',
                'name/cos:UploadPart',
                'name/cos:CompleteMultipartUpload'
            ],
            'condition'       => [],
        ];
        try {
            $data = $sts->getTempKeys($config);
            return [
                'tmpSecretId'  => $data['credentials']['tmpSecretId'],
                'tmpSecretKey' => $data['credentials']['tmpSecretKey'],
                'sessionToken' => $data['credentials']['sessionToken'],
                'startTime'    => time(),
                'expiredTime'  => $data['expiredTime']
            ];
        } catch (Throwable $e) {
            $error = json_decode($e->getMessage(), true);
            return [
                'error'   => 1,
                'code'    => $error['Error']['Code'],
                'message' => $error['Error']['Message']
            ];
        }
    }

    /**
     * @throws Throwable
     */
    public function enable(): void
    {
        Config::addConfigGroup('upload', '上传配置');

        if (!Config::where('name', 'upload_mode')->value('id')) {
            // 配置数据曾在禁用时被删除
            Server::importSql(root_path() . 'modules' . DIRECTORY_SEPARATOR . $this->uid . DIRECTORY_SEPARATOR);
        }

        // 恢复缓存中的配置数据
        $config = Cache::pull($this->uid . '-module-config');
        if ($config) {
            $config = json_decode($config, true);
            foreach ($config as $item) {
                Config::where('name', $item['name'])->update([
                    'value' => $item['value']
                ]);
            }
        }
    }

    /**
     * @throws Throwable
     */
    public function disable(): void
    {
        $config = Config::whereIn('name', ['upload_mode', 'upload_bucket', 'upload_access_id', 'upload_secret_key', 'upload_url', 'upload_cdn_url'])->select();

        // 备份配置到缓存
        if (!$config->isEmpty()) {
            $configData = $config->toArray();
            Cache::set($this->uid . '-module-config', json_encode($configData), 3600);
        }

        foreach ($config as $item) {
            $item->delete();
        }
        Config::removeConfigGroup('upload');
    }

}