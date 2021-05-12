<?php

namespace fast;

/**
 * 字符串类
 */
class Http
{

    /**
     * 发送一个POST请求
     * @param string $url     请求URL
     * @param array  $params  请求参数
     * @param array  $options 扩展参数
     * @return mixed|string
     */
    public static function post($url, $params = [], $options = [])
    {
        $req = self::sendRequest($url, $params, 'POST', $options);
        return $req['ret'] ? $req['msg'] : '';
    }

    public static function patch($url, $params = [], $options = [],$heads=[])
    {
        $req = self::sendRequest($url, $params, 'PATCH', $options);
        return $req['ret'] ? $req : '';
    }

    /**
     * 发送一个GET请求
     * @param string $url     请求URL
     * @param array  $params  请求参数
     * @param array  $options 扩展参数
     * @return mixed|string
     */
    public static function get($url, $params = [], $options = [])
    {
        $req = self::sendRequest($url, $params, 'GET', $options);
        return $req['ret'] ? $req['msg'] : '';
    }

    /**
     * 发送一个GET请求
     * @param string $url     请求URL
     * @param string  $ip  请求参数
     * @param string  $protocol 扩展参数
     * @return mixed|string
     */
    public static function check($url, $ip = '', $protocol = '')
    {
        $req = self::checkHttp($url,'GET', $ip, $protocol);
        return $req['ret'] ? $req['msg'] : '';
    }

    /**
     * CURL发送Request请求,含POST和REQUEST
     * @param string $url     请求的链接
     * @param mixed  $params  传递的参数
     * @param string $method  请求的方法
     * @param mixed  $options CURL的参数
     * @return array
     */
    public static function sendRequest($url, $params = [], $method = 'POST', $options = [])
    {
        $method = strtoupper($method);
        $protocol = substr($url, 0, 5);
        $query_string = is_array($params) ? http_build_query($params) : $params;

        $ch = curl_init();
        $defaults = [];
        if ('GET' == $method) {
            $geturl = $query_string ? $url . (stripos($url, "?") !== false ? "&" : "?") . $query_string : $url;
            $defaults[CURLOPT_URL] = $geturl;
        } else {
            $defaults[CURLOPT_URL] = $url;
            if ($method == 'POST') {
                $defaults[CURLOPT_POST] = 1;
            } else {
                $defaults[CURLOPT_CUSTOMREQUEST] = $method;
            }
            $defaults[CURLOPT_POSTFIELDS] = $query_string;
        }

        $defaults[CURLOPT_HEADER] = false;
        $defaults[CURLOPT_USERAGENT] = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.98 Safari/537.36";
        $defaults[CURLOPT_FOLLOWLOCATION] = true;
        $defaults[CURLOPT_RETURNTRANSFER] = true;
        $defaults[CURLOPT_CONNECTTIMEOUT] = 8;
        $defaults[CURLOPT_TIMEOUT] = 8;


        // disable 100-continue

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Except:'));
        if ('https' == $protocol) {
            $defaults[CURLOPT_SSL_VERIFYPEER] = false;
            $defaults[CURLOPT_SSL_VERIFYHOST] = false;
        }

        curl_setopt_array($ch, (array)$options + $defaults);

        $ret = curl_exec($ch);
        $err = curl_error($ch);
        $code=curl_getinfo($ch, CURLINFO_HTTP_CODE);


        if (false === $ret || !empty($err)) {
            $errno = curl_errno($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            return [
                'ret'   => false,
                'errno' => $errno,
                'msg'   => $err,
                'info'  => $info,
            ];
        }
        curl_close($ch);
        return [
            'ret' => true,
            'msg' => $ret,
            'code'=> $code,
        ];
    }

    /**
     * CURL发送Request请求,含POST和REQUEST
     * @param string $url     请求的链接
     * @param string $method  请求的方法
     * @param string  $ip  传递的参数
     * @param string  $protocol CURL的参数
     * @return array
     */
    public static function checkHttp($url, $method = 'GET', $ip = '', $protocol = '')
    {
        $method = strtoupper($method);

        $ch = curl_init();
        $defaults = [];
        if ('GET' == $method) {
            $defaults[CURLOPT_URL] = $url;
        } else {
            $defaults[CURLOPT_URL] = $url;
            if ($method == 'POST') {
                $defaults[CURLOPT_POST] = 1;
            } else {
                $defaults[CURLOPT_CUSTOMREQUEST] = $method;
            }
        }

        $defaults[CURLOPT_HEADER] = false;
        $defaults[CURLOPT_USERAGENT] = "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.98 Safari/537.36";
        $defaults[CURLOPT_FOLLOWLOCATION] = true;
        $defaults[CURLOPT_RETURNTRANSFER] = true;
        $defaults[CURLOPT_CONNECTTIMEOUT] = 30;
        $defaults[CURLOPT_TIMEOUT] = 30;

        $defaults[CURLOPT_HEADER] = 0;

        $defaults[CURLOPT_SSLVERSION] = 3;
        $defaults[CURLOPT_SSL_VERIFYPEER] = false;
        $defaults[CURLOPT_SSL_VERIFYHOST] = false;

        $proxy=explode(':',$ip);
        $defaults[CURLOPT_PROXY] = $ip;
//        $defaults[CURLOPT_PROXY] = $proxy[0];
//        $defaults[CURLOPT_PROXYPORT] = $proxy[1];
//        $defaults[CURLOPT_PROXYTYPE] = CURLPROXY_HTTP;

        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Except:'));

        curl_setopt_array($ch, $defaults);

        $ret = curl_exec($ch);
        $err = curl_error($ch);

        if (false === $ret || !empty($err)) {
            $errno = curl_errno($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            return [
                'ret'   => false,
                'errno' => $errno,
                'msg'   => $err,
                'info'  => $info,
            ];
        }
        curl_close($ch);
        return [
            'ret' => true,
            'msg' => $ret,
        ];
    }

    /**
     * 异步发送一个请求
     * @param string $url    请求的链接
     * @param mixed  $params 请求的参数
     * @param string $method 请求的方法
     * @return boolean TRUE
     */
    public static function sendAsyncRequest($url, $params = [], $method = 'POST')
    {
        $method = strtoupper($method);
        $method = $method == 'POST' ? 'POST' : 'GET';
        //构造传递的参数
        if (is_array($params)) {
            $post_params = [];
            foreach ($params as $k => &$v) {
                if (is_array($v)) {
                    $v = implode(',', $v);
                }
                $post_params[] = $k . '=' . urlencode($v);
            }
            $post_string = implode('&', $post_params);
        } else {
            $post_string = $params;
        }
        $parts = parse_url($url);
        //构造查询的参数
        if ($method == 'GET' && $post_string) {
            $parts['query'] = isset($parts['query']) ? $parts['query'] . '&' . $post_string : $post_string;
            $post_string = '';
        }
        $parts['query'] = isset($parts['query']) && $parts['query'] ? '?' . $parts['query'] : '';
        //发送socket请求,获得连接句柄
        $fp = fsockopen($parts['host'], isset($parts['port']) ? $parts['port'] : 80, $errno, $errstr, 3);
        if (!$fp) {
            return false;
        }
        //设置超时时间
        stream_set_timeout($fp, 3);
        $out = "{$method} {$parts['path']}{$parts['query']} HTTP/1.1\r\n";
        $out .= "Host: {$parts['host']}\r\n";
        $out .= "Content-Type: application/x-www-form-urlencoded\r\n";
        $out .= "Content-Length: " . strlen($post_string) . "\r\n";
        $out .= "Connection: Close\r\n\r\n";
        if ($post_string !== '') {
            $out .= $post_string;
        }
        fwrite($fp, $out);
        //不用关心服务器返回结果
        //echo fread($fp, 1024);
        fclose($fp);
        return true;
    }

    /**
     * 发送文件到客户端
     * @param string $file
     * @param bool   $delaftersend
     * @param bool   $exitaftersend
     */
    public static function sendToBrowser($file, $delaftersend = true, $exitaftersend = true)
    {
        if (file_exists($file) && is_readable($file)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment;filename = ' . basename($file));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check = 0, pre-check = 0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            ob_clean();
            flush();
            readfile($file);
            if ($delaftersend) {
                unlink($file);
            }
            if ($exitaftersend) {
                exit;
            }
        }
    }

    /**
     * $header //请求头
     * $cookie //存储cookie
     * $arrip //代理IP的地址及端口
     * $params //参数  你要提交的
     * $method //请求方式（GET,POST）
     */
    public function dorequest($arrip = array(),$url,$header,$timeout = 20000,$method='',$cookie){

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC); //代理认证模式

        curl_setopt($ch, CURLOPT_PROXY, "$arrip[0]"); //代理服务器地址

        curl_setopt($ch, CURLOPT_PROXYPORT,$arrip[1]); //代理服务器端口

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($ch, CURLOPT_URL, $url);//设置链接

        //curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/4.0");
        if(!defined('CURLOPT_TIMEOUT_MS')){

            $res = curl_setopt($ch, CURLOPT_TIMEOUT,30); //设置1秒超时
        }

        else {

            curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout);

        }

 if ($cookie) {

     curl_setopt($ch, CURLOPT_COOKIEJAR,  $cookie);//存储cookies

     curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);   }

      if(!defined('CURLOPT_CONNECTTIMEOUT_MS')){

          curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);

      } else {
          curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $timeout);
      }
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//设置是否返回信息

    $method = strtoupper($method);

     if($method == 'POST'){

         curl_setopt($ch, CURLOPT_POST, 1);//设置为POST方式

         curl_setopt($ch, CURLOPT_POSTFIELDS, ($params));

     }

      if($header)

      {
          curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

      }   //设置跳转location 最多3次

     curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
      $response = curl_exec($ch);//接收返回信息

    }
}
