<?php

namespace app\admin\controller;

use Throwable;
use app\common\controller\Backend;
use app\common\model\Attachment;

class Cos extends Backend
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
        $this->success('', $cos);
    }

    public function callback()
    {
        $data       = $this->request->post();
        $params     = [
            'topic'    => $this->topic,
            'admin_id' => $this->auth->id,
            'user_id'  => 0,
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