<?php
/**
 * 返回地址分词 拼音
 */
namespace illmy\Yhaddress;
use Fukuball\Jieba\Jieba;
use Fukuball\Jieba\Finalseg;
use Yurun\Util\Chinese;
use Yurun\Util\Chinese\Pinyin;

class Address
{
    protected static function init()
    {
        Jieba::init();
        Finalseg::init();
    }

    public static function toPinyin($list)
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

    public static function participle($string)
    {
        self::init();
        #搜索引擎模式
        $seg_list = Jieba::cutForSearch($string);
        return $seg_list;
    }
    
    public static function partAndPinyin($string)
    {
        $seg_list = self::participle($string);

        if (!empty($seg_list)) {
            $pin_list = self::toPinyin($seg_list);
        } else {
            $pin_list = [];
        }

        return $list = [
            'seg' => $seg_list,
            'pin' => $pin_list
        ];
    }
}