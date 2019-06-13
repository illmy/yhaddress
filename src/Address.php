<?php
/**
 * 返回地址分词 拼音
 */
namespace illmy\Yhaddress;
use Fukuball\Jieba\Jieba;
use Fukuball\Jieba\Finalseg;
use Yurun\Util\Chinese;
use Yurun\Util\Chinese\Pinyin;
use illmy\Yhaddress\Traits\HasHttpRequest;

class Address
{
    use HasHttpRequest;

    protected function init()
    {
        Jieba::init();
        Finalseg::init();
    }

    public function toPinyin($list)
    {
        $data = [];
        if (!is_array($list)) {
            $yin = Chinese::toPinyin($list, Pinyin::CONVERT_MODE_PINYIN,'');
            //只取第一个
            if (is_array($yin)) {
                $data[] = $yin['pinyin'][0];
            } else {
                $data[] = '';
            }
        } else {
            foreach ($list as $value) {
                $yin = Chinese::toPinyin($value, Pinyin::CONVERT_MODE_PINYIN,'');
                if (is_array($yin)) {
                    $data[] = $yin['pinyin'][0];
                } else {
                    $data[] = '';
                }
            }
        }

        return $data;
    }

    public function participle($string)
    {
        $this->init();
        #搜索引擎模式
        $seg_list = Jieba::cutForSearch($string);
        return $seg_list;
    }
    
    public function baiduLexer($string)
    {
        $token = $this->getBaiduAccessToken();

        if (empty($token)) return false;

        $api = "https://aip.baidubce.com/rpc/2.0/nlp/v1/lexer?access_token={$token['access_token']}&charset=UTF-8";

        $text = [
            'text' => $string
        ];

        $data = $this->postJson($api,$text,['Content-Type' => 'application/json']);

        if (isset($data['error_code'])) {
            return false;
        }

        return array_column($data['items'], 'item');
    }

    public function getBaiduAccessToken()
    {
        $name = str_replace('\\',DIRECTORY_SEPARATOR,__DIR__.'/Config.php');
        $data = require_once $name;
        if (!empty($data)) {
            if ($data['time'] + $data['expires_in'] > time()) {
                return $data;
            }
        }
        $api = "https://aip.baidubce.com/oauth/2.0/token?grant_type=client_credentials&client_id=oF4kvNuMIAzSSiqzWiWk2i2F&client_secret=oRk7HE9oS8pHW9Egp6HcmIncqPEDU2Kf";
        $data = $this->post($api);
        if (isset($data['error'])) {
            return false;
        }
        $data = json_decode($data,true);
        $data['time'] = time();
        file_put_contents($name,'<?php'.PHP_EOL.'return '.var_export($data,true).';');
        return $data;
    }

    public function partAndPinyin($string,$mode = 'baidu')
    {
        if ($mode == 'baidu') {
            $seg_list = $this->baiduLexer($string);
        } else {
            $seg_list = $this->participle($string);
        }
        
        foreach ($seg_list as $key => $value) {
            if (mb_strlen($value) <= 1) {
                unset($seg_list[$key]);
            }
        }

        if (!empty($seg_list)) {
            $pin_list = $this->toPinyin($seg_list);
        } else {
            $pin_list = [];
        }

        return $list = [
            'seg' => $seg_list,
            'pin' => $pin_list
        ];
    }

    public function checkAddress($address)
    {
        $api = "https://restapi.amap.com/v3/place/text";
        $data = $this->get($api,['key' => '7f6f6d5eebc862bdaea5748f02fb6755','keywords' => $address]);
        if ($data['status'] != '1') {
            return false;
        }
        return $data;
    }
}