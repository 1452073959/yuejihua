<?php
// 应用公共文件

use app\common\service\AuthService;
use think\facade\Cache;

if (!function_exists('__url')) {

    /**
     * 构建URL地址
     * @param string $url
     * @param array $vars
     * @param bool $suffix
     * @param bool $domain
     * @return string
     */
    function __url(string $url = '', array $vars = [], $suffix = true, $domain = false)
    {
        return url($url, $vars, $suffix, $domain)->build();
    }
}

if (!function_exists('password')) {

    /**
     * 密码加密算法
     * @param $value 需要加密的值
     * @param $type  加密类型，默认为md5 （md5, hash）
     * @return mixed
     */
    function password($value)
    {
        $value = sha1('blog_') . md5($value) . md5('_encrypt') . sha1($value);
        return sha1($value);
    }

}

if (!function_exists('xdebug')) {

    /**
     * debug调试
     * @deprecated 不建议使用，建议直接使用框架自带的log组件
     * @param string|array $data 打印信息
     * @param string $type 类型
     * @param string $suffix 文件后缀名
     * @param bool $force
     * @param null $file
     */
    function xdebug($data, $type = 'xdebug', $suffix = null, $force = false, $file = null)
    {
        !is_dir(runtime_path() . 'xdebug/') && mkdir(runtime_path() . 'xdebug/');
        if (is_null($file)) {
            $file = is_null($suffix) ? runtime_path() . 'xdebug/' . date('Ymd') . '.txt' : runtime_path() . 'xdebug/' . date('Ymd') . "_{$suffix}" . '.txt';
        }
        file_put_contents($file, "[" . date('Y-m-d H:i:s') . "] " . "========================= {$type} ===========================" . PHP_EOL, FILE_APPEND);
        $str = (is_string($data) ? $data : (is_array($data) || is_object($data)) ? print_r($data, true) : var_export($data, true)) . PHP_EOL;
        $force ? file_put_contents($file, $str) : file_put_contents($file, $str, FILE_APPEND);
    }
}

if (!function_exists('sysconfig')) {

    /**
     * 获取系统配置信息
     * @param $group
     * @param null $name
     * @return array|mixed
     */
    function sysconfig($group, $name = null)
    {
        $where = ['group' => $group];
        $value = empty($name) ? Cache::get("sysconfig_{$group}") : Cache::get("sysconfig_{$group}_{$name}");
        if (empty($value)) {
            if (!empty($name)) {
                $where['name'] = $name;
                $value = \app\admin\model\SystemConfig::where($where)->value('value');
                Cache::tag('sysconfig')->set("sysconfig_{$group}_{$name}", $value, 3600);
            } else {
                $value = \app\admin\model\SystemConfig::where($where)->column('value', 'name');
                Cache::tag('sysconfig')->set("sysconfig_{$group}", $value, 3600);
            }
        }
        return $value;
    }
}

if (!function_exists('array_format_key')) {

    /**
     * 二位数组重新组合数据
     * @param $array
     * @param $key
     * @return array
     */
    function array_format_key($array, $key)
    {
        $newArray = [];
        foreach ($array as $vo) {
            $newArray[$vo[$key]] = $vo;
        }
        return $newArray;
    }

}

if (!function_exists('auth')) {

    /**
     * auth权限验证
     * @param $node
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    function auth($node = null)
    {
        $authService = new AuthService(session('admin.id'));
        $check = $authService->checkNode($node);
        return $check;
    }

}

function tudincode($url = "http://www.baidu.com")
{
    require '../app/home/help/phpqrcode/phpqrcode.php';
//        $qrcode = new \QRcode();
    $value = $url;                    //二维码内容
    $errorCorrectionLevel = 'H';    //容错级别
    $matrixPointSize = 6;           //生成图片大小
    ob_start();
    \QRcode::png($value, false, $errorCorrectionLevel, $matrixPointSize, 2);
    // $object->png($url, false, $errorCorrectionLevel, $matrixPointSize, 2); //这里就是把生成的图片流从缓冲区保存到内存对象上，使用base64_encode变成编码字符串，通过json返回给页面。
    $imageString = base64_encode(ob_get_contents()); //关闭缓冲区
    ob_end_clean(); //把生成的base64字符串返回给前端
    $data = array('code' => 200, 'data' => $imageString);
//        return '<img src="data:image/png;base64,'.$imageString.'" >';
    return $imageString;

}


function http_post_data($url, $data_string)
{
    $data_string = json_encode($data_string);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

    $ssl = preg_match('/^https:\/\//i', $url) ? TRUE : FALSE;

    curl_setopt($ch, CURLOPT_URL, $url);

    if ($ssl) {

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts

        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在

    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length: ' . strlen($data_string))
    );

    //curl_setopt($ci, CURLOPT_HEADER, true); /*启用时会将头文件的信息作为数据流输出*/

    //curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);

    curl_setopt($ch, CURLOPT_MAXREDIRS, 2);/*指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的*/


    curl_setopt($ch, CURLINFO_HEADER_OUT, true);

    ob_start();
    curl_exec($ch);
    $return_content = ob_get_contents();
    ob_end_clean();
    $return_content = json_decode($return_content, true);
    $return_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    return array($return_code, $return_content);
}