__**依赖**__

php | >= 7.4.0
dcat/laravel-admin | >= ~2.0

__**安装**__


composer 安装
composer require sudongxu/dcat-operation-log

__**启用插件**__

开发工具 -> 扩展 -> sudongxu.dcat-operation-log -> 升级 -> 启用
发布配置 operation-log.php
php artisan vendor:publish --provider="Sudongxu\OperationLog\OperationLogServiceProvider"

__**方法使用**__

1、添加日志菜单，路径为auth/operation-logs即可

2、相关配置，查看 operation-log.php

3、如需关闭日志的路由，close_routes 的值为 true。如需控制总后台的日志路由，可以将如下代码放入后台配置文件中（admin.php）

    'extensions' => [
        'dcat_operation_log' => [
            'close_routes' => true
        ]
    ]
4、多后台，需要配置管理员用户的映射关系，记得把模型补上

    'users_map' => [
        'admin_users' => Dcat\Admin\Models\Administrator::class,
    ]
安装问题
发布文件时可能存在权限问题，记得给足权限。可在项目根目录执行 chmod -R 755 public/vendor
读取不到已经发布的配置，可清空一下缓存 php artisan config:clear
升级程序的流程，与启用插件的一样
