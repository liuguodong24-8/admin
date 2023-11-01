<?php
// +----------------------------------------------------------------------
// | 短信配置
// +----------------------------------------------------------------------

return [
    // 发送短信HTTP请求的超时时间（秒）
    'timeout'  => '5',
    // 默认发送配置
    'default'  => [
        // 网关调用策略，默认：顺序调用
        'strategy' => 'Overtrue\EasySms\Strategies\OrderStrategy',
        // 默认可用的发送网关
        'gateways' => ['qcloud'],
    ],
    // 可用的网关配置
    'gateways' => [
        // 配置项后面的注释用于动态修改配置时定位
        'aliyun'  => [
            'access_key_id'     => '',#aliyun#
            'access_key_secret' => '',#aliyun#
            'sign_name'         => '',#aliyun#
        ],
        'qcloud'  => [
            'sdk_app_id' => '1400252107',#qcloud#
            'secret_id'  => 'AKID780g7OVTGg1KUXTG6iApLfy9A1jIsGPJ',#qcloud#
            'secret_key' => 'lCe9gEe5UkpidUE3jGpKk5OhyKh6Nhw3',#qcloud#
            'sign_name'  => '虫洞手创',#qcloud#
        ],
        'qiniu'   => [
            'secret_key' => '',#qiniu#
            'access_key' => '',#qiniu#
        ],
        'yunpian' => [
            'api_key'   => '',#yunpian#
            'signature' => '【默认签名】',#yunpian#
        ],
    ],
];