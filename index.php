<?php

use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Nano\Factory\AppFactory;

require_once __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();
!defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', SWOOLE_HOOK_ALL | SWOOLE_HOOK_CURL);
$app->get('/', function () {

    $url = $this->request->input('url', '');
    $preg = "/http[s]?:\/\/[\w.]+[\w\/]*[\w.]*\??[\w=&\+\%]*/is";
    if (!preg_match($preg, $url)) {
        return [
            'code' => 400,
            'msg' => '你传入的 URL 不合法'
        ];
    }
    $headers = get_headers('http://mp.weixinbridge.com/mp/wapredirect?url=' . $url, 1);

    if (empty($headers)) {
        return [
            'code' => 400,
            'msg' => '异常'
        ];
    }
    array_shift($headers['Location']);
    if (current($headers['Location']) != $url) {
        return [
            'code' => 400,
            'msg' => '域名被拦截'
        ];
    }
    return [
        'code' => 200,
        'msg' => '域名正常'
    ];

});
$app->addExceptionHandler(function ($throwable, $response) {
    return $response->withStatus(200)
        ->withBody(new SwooleStream(json_encode([
            'code' => 500,
            'msg' => $throwable->getMessage()
        ])));
});
$app->run();
