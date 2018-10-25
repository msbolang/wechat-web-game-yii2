<?php

namespace msbolang\WechatTools;

use Yii;


class WechatTools  {

    public $_wechatAuthUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?'; //网页授权API
    public $_debug = true;
    public function __construct() {}

    /**
     * 
     * @param type $authUrl 网页授权
     */
    public function auth($authUrl){
          // 第一步：微信网页授权
         $this->_wechatAuthUrl = $this->_wechatAuthUrl.'appid=' .WECHATAPPID. '&redirect_uri=' . urlencode($authUrl) . '&response_type=code&scope=snsapi_base&state=3#wechat_redirect';
         header("location: $this->_wechatAuthUrl");
         exit;
    }
    
    /**
     * 
     * @param type $code
     * @param type $authUrl
     * @return type
     * 获取用户openID
     */
    public  function handleCode($code,$authUrl) {

             $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . WECHATAPPID . '&secret=' . WECHATAPPSECRET . '&code=' . $code . '&grant_type=authorization_code';
             $res = $this->curl_get_contents($url);
             $_objres = json_decode($res);

           if (!isset($_objres->openid)) {
               
               if($this->_debug){
                   self::EC('handleCode 43 ERROR',$_objres);//self 自己如果定義了EC 訪問自己的 如沒有 訪問父
               }
               header("Location:$authUrl");
              exit;
           }
           
             if (strlen($_objres->openid) == 28) {
                 return $_objres->openid;
             }else{
                 var_dump($_objres);
                 
              if($this->_debug){
                   self::EC('handleCode 55 ERROR ',$_objres);//self 自己如果定義了EC 訪問自己的 如沒有 訪問父
               }
               
                  header("Location:$authUrl");
             }
             
    }
    
     //获取微信用戶詳細信息
     public function get_wechat_user_info($token, $openid)
    {
        $userinfo = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $token . '&openid=' . $openid . '&lang=zh_CN';
        $json = $this->curl_get_contents($userinfo);
        $wechat_user = json_decode($json);
        return $wechat_user;
    }
    
    
    public function get_ticket()
    {
        $token = $this->tokenBase();
        $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=$token&type=jsapi";
        $obj = $this->curl_get_contents($url);
        return json_decode($obj);
    }
    
     public function get_tokenBase()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".WECHATAPPID."&secret=".WECHATAPPSECRET;
        $obj = $this->curl_get_contents($url);
        return json_decode($obj);
    }
    
    
     //獲取基礎token
    public function tokenBase()
    {
        // $model = LmConfig::find()->where(['wc_appid'=>$this->wc_appid])->one();
        $mealdb = NEW \yii\db\Ebaytest();
        $model = $mealdb->find()->where(['wc_appid' => WECHATAPPID])->one();

        if ($model->access_token) {
            if ((time() - $model->token_time) >= 7200) {
                $token = $this->get_tokenBase();
                if(isset($token->errmsg) && !isset($token->access_token)){
                    echo $token->errmsg;exit;
                }
                $model->access_token = $token->access_token;
                $model->expires_in = $token->expires_in;
                $model->token_time = time();
                if ($model->save()) {
                    return $token->access_token;
                }
            } else {
                return $model->access_token;
            }
        } else {
            $token = $this->get_tokenBase();
            $model->access_token = $token->access_token;
            $model->expires_in = $token->expires_in;
            $model->token_time = time();
            if ($model->save()) {
                return $token->access_token;
            }
        }
    }
    
    
    
    
      // old name token_jc_ag   rename tokenBaseAgain
        public function tokenBaseAgain()
    {
        $mealdb = NEW \yii\db\Ebaytest();
        $model = $mealdb->find()->where(['wc_appid' => WECHATAPPID])->one();

        if ($model->access_token) {
            $token = $this->get_tokenBase();
            $model->access_token = $token->access_token;
            $model->expires_in = $token->expires_in;
            $model->token_time = time();
            if ($model->save()) {
                return $token->access_token;
            }
        } else {
            $token = $this->get_tokenBase();
            $model->access_token = $token->access_token;
            $model->expires_in = $token->expires_in;
            $model->token_time = time();
            if ($model->save()) {
                return $token->access_token;
            }
        }
    }
    
    
    
    
    
    
    public function signatureticket($time_, $str_,$url)
    {
        // $model = LmConfig::find()->where(['wc_appid'=>$this->wc_appid])->one();
        $mealdb = NEW \yii\db\Ebaytest();

        $model = $mealdb->find()->where(['wc_appid' => WECHATAPPID])->one();
  
        if ($model->ticket) {
            if ((time() - $model->ticket_time) >= 7200) {
                $ticketID = $this->get_ticket();
                $model->ticket = $ticketID->ticket;
                $model->ticket_time = time();
                if ($model->save()) {
                    $signat = $this->signatureticket_end($time_, $str_, $ticketID->ticket,$url);
                    return $signat;
                }
            } else {
                $signat = $this->signatureticket_end($time_, $str_, $model->ticket,$url);
                return $signat;
            }
        } else {
            $ticketID = $this->get_ticket();
            $model->ticket = $ticketID->ticket;
            $model->ticket_time = time();
            if ($model->save()) {
                $signat = $this->signatureticket_end($time_, $str_, $ticketID->ticket,$url);
                return $signat;
            }
        }
    }

    public function signatureticket_end($time, $str, $ticket,$url)
    {
        $wxOri = sprintf("jsapi_ticket=%s&noncestr=%s&timestamp=%s&url=%s", $ticket, $str, $time, $url);
        $wxSha1 = sha1($wxOri);
        return $wxSha1;
    }


    
    public function getrandstr($length)
    {
        $str = null;
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol) - 1;
        for ($i = 0; $i < $length; $i++) {
            $str .= $strPol[rand(0, $max)]; //rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }
        return $str;
    }

    
    
    
    
    
     private function curl_get_contents($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        @curl_setopt($ch, CURLOPT_USERAGENT, _USERAGENT_);
        @curl_setopt($ch, CURLOPT_REFERER, _REFERER_);
        @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        @curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
        @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $r = curl_exec($ch);
        curl_close($ch);
        return $r;
    }
    
    

}
