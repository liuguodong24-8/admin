<?php

namespace app\api\controller;

use Throwable;
use app\common\controller\Frontend;
use app\common\model\Attachment;

class Cos extends Frontend
{
    /**
     * 细目
     * @var string
     */
    protected string $topic = 'default';

    public function initialize(): void
    {
        parent::initialize();
    }

    /**
     * @throws Throwable
     */
    public function refreshToken()
    {
        $cos = \modules\cos\Cos::getSts();
        $this->success('', $cos,200);
    }

    public function callback()
    {
        $data       = $this->request->post();
        $params     = [
            'topic'    => $this->topic,
            'admin_id' => 0,
            'user_id'  => $this->auth->id,
            'url'      => $data['url'],
            'width'    => $data['width'] ?? 0,
            'height'   => $data['height'] ?? 0,
            'name'     => substr(htmlspecialchars(strip_tags($data['name'])), 0, 100),
            'size'     => $data['size'],
            'mimetype' => $data['type'],
            'storage'  => 'cos',
            'sha1'     => $data['sha1']
        ];
        $attachment = new Attachment();
        $attachment->data(array_filter($params));
        $attachment->save();
    }
}