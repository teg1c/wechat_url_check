
# Installing
Cli

```
composer update -vvv  -o
php index.php start
```

Docker
```
docker run --name=wechat_url_check -p 9501:9501 registry.cn-hangzhou.aliyuncs.com/tegic/wechat_url_check
```

# Usage

```
http://127.0.0.1:9501/?url=http://baidu.com
```

Success
```
{
    "code": 200,
    "msg": "域名正常"
}
```
Forbid
```
{
    "code": 400,
    "msg": "域名被拦截"
}
```