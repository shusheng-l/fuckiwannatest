<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/4
 * Time: 15:09
 */
namespace Index;
class Net{

    static $isHeader=false;
    static function send($url, $params=null, $method = 'GET', $header = array(), $multi = false){
        $opts = array(
            CURLOPT_CONNECTTIMEOUT=>10,
            CURLOPT_TIMEOUT    => 30,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER   => $header
        );
        switch(strtoupper($method)){
            case 'GET':
                $opts[CURLOPT_URL] = $url . ($params?('?' . http_build_query($params)):'');
                break;
            case 'POST':
                $params = $params!==null&&$multi ? $params : http_build_query($params);
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = 1;
                $opts[CURLOPT_POSTFIELDS] = $params;
                break;
            default:
                throw new Exception('不支持的请求方式！');
        }
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        if(self::$isHeader)curl_setopt($ch, CURLOPT_HEADER, true);
        $data = curl_exec($ch);
        curl_close($ch);
        self::clear();
        return $data;
    }
    static function clear(){
        self::$isHeader=false;
    }

    public static $url='http://221.231.109.86:8123/ycpolice';
    public static function get($path,$data=null)
    {
        $url=self::$url.$path;
        self::$isHeader=true;
        $content = self::send($url,$data,'POST');
        $line="\r\n\r\n";
        $headPos=strpos($content,$line);
        $headStr=substr($content,0,$headPos);
        $bodytr=substr($content,$headPos+strlen($line));
        if(strpos($headStr,'application/json')!==false){
            $bodytr=json_decode($bodytr,true);
            return  $bodytr;
        }
        return $bodytr;
    }

    public static function zget($path,$data=null)
    {
        $url=self::$url.$path;
        self::$isHeader=true;
        $content = self::send($url,$data,'GET');
        $line="\r\n\r\n";
        $headPos=strpos($content,$line);
        $headStr=substr($content,0,$headPos);
        $bodytr=substr($content,$headPos+strlen($line));
        if(strpos($headStr,'application/json')!==false){
            $bodytr=json_decode($bodytr,true);
            return  $bodytr;
        }
        return $bodytr;
    }
    public static function zpost($path,$data=null)
    {
        self::get($path,$data);
    }
}