# wechat-web-game-yii2


 配置文件 
     'components' => [
            ............
            ............
            'wechatTool' => [
                'class' => 'msbolang\tool\WechatTools',
                'appid' => 'appid',
                'secret' => 'secret',
                'forceFollow' => 'true',//是否強制關注
                'followUrl' => '',//強制關注true 時未關注的用戶跳轉的地址
            ],
            ............
            ............


调用方法
controller里面直接  Yii::$app->wechatTool调用插件的方法和属性