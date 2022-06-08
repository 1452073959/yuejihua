<?php
// 应用公共文件

use app\common\service\AuthService;
use think\facade\Cache;
use \think\facade\Config;
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

function httpget($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.22 (KHTML, like Gecko)");
    curl_setopt($ch, CURLOPT_ENCODING, "gzip");//加入gzip解析
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $output = curl_exec($ch);
    curl_close($ch);

    return $output;
}


function getCity($ip) {
    // 获取当前位置所在城市
    $content = file_get_contents("http://api.map.baidu.com/location/ip?ak=2TGbi6zzFm5rjYKqPPomh9GBwcgLW5sS&ip={$ip}&coor=bd09ll");
    $json = json_decode($content);
    $address = $json->{'content'}->{'address'};//按层级关系提取address数据
    $data['address'] = $address;
    $res = [];
    $res['province'] = mb_substr($data['address'],0,3,'utf-8');
    $res['city'] = mb_substr($data['address'],3,3,'utf-8');
    return $res;
}

 function ip() {
    //strcasecmp 比较两个字符，不区分大小写。返回0，>0，<0。
    if(getenv('HTTP_CLIENT_IP') && strcasecmp(getenv('HTTP_CLIENT_IP'), 'unknown')) {
        $ip = getenv('HTTP_CLIENT_IP');
    } elseif(getenv('HTTP_X_FORWARDED_FOR') && strcasecmp(getenv('HTTP_X_FORWARDED_FOR'), 'unknown')) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
    } elseif(getenv('REMOTE_ADDR') && strcasecmp(getenv('REMOTE_ADDR'), 'unknown')) {
        $ip = getenv('REMOTE_ADDR');
    } elseif(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], 'unknown')) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    $res =  preg_match ( '/[d.]{7,15}/', $ip, $matches ) ? $matches [0] : '';
    echo $res;
    //dump(phpinfo());//所有PHP配置信息
}






//转账到户
function transfer4($phone,$name,$amount)
{

    $out_trade_no = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);//订单号，自己生成
    //1、execute 使用
    require '../vendor/aop1/AopClient.php';
    require '../vendor/aop1/request/AlipayFundTransToaccountTransferRequest.php';
    $alipayPublicKey = Config::get('alisms.alipayPublicKeyapp');
    $privateKey = Config::get('alisms.privateKeyapp');
    $aop = new \AopClient ();
    $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';//支付宝网关
    $aop->appId = '2021003131608441';
    $aop->rsaPrivateKey = $privateKey;
    $aop->apiVersion = '1.0';
    $aop->signType = 'RSA2';
    $aop->postCharset = 'utf-8';
    $aop->format = 'json';
    $request = new \AlipayFundTransToaccountTransferRequest ();
    $BizContent = array(
        'out_biz_no' => $out_trade_no, //商户转账唯一订单号
        'payee_type' => 'ALIPAY_LOGONID', //收款方账户类型
        'payee_account' =>$phone, //收款方账户
        'payee_real_name' =>$name, //收款方姓名
        'amount' => $amount, //转账金额
        'payer_show_name' => '源信生活', //付款方显示姓名

    );
    $request->setBizContent(json_encode($BizContent));
    $result = $aop->execute($request);
    $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
    $resultCode = $result->$responseNode;

    return $resultCode;

}


function transfer6($amount,$out_trade_no,$subject)
{
    require_once '../vendor/aop1/AopClient.php';
    require_once '../vendor/aop1/AopCertClient.php';
    require_once '../vendor/aop1/AopCertification.php';
    require_once '../vendor/aop1/AlipayConfig.php';
    require_once '../vendor/aop1/request/AlipayTradeAppPayRequest.php';
    $privateKey = Config::get('alisms.privateKeyapp');
    $alipayPublicKey = Config::get('alisms.alipayPublicKeyapp');
    $alipayConfig = new AlipayConfig();
    $alipayConfig->setServerUrl("https://openapi.alipaydev.com/gateway.do");
    $alipayConfig->setAppId("2021003131608441");
    $alipayConfig->setPrivateKey($privateKey);
    $alipayConfig->setFormat("json");
    $alipayConfig->setAlipayPublicKey($alipayPublicKey);
    $alipayConfig->setCharset("UTF8");
    $alipayConfig->setSignType("RSA2");
    $alipayClient = new AopClient($alipayConfig);
    $request = new AlipayTradeAppPayRequest();
//    $request->setBizContent("{".
//        "\"out_trade_no\":\"70501111S00114529\",".
//        "\"total_amount\":\"0.01\",".
//        "\"subject\":\"大乐透\"".
//        "}");

    $bizcontent = array(
        'out_trade_no' => $out_trade_no,// 订单号
        'total_amount' => $amount,   // 提现实际金额
        'subject' => $subject,
        'product_code' => 'QUICK_MSECURITY_PAY',
    );



    $request->setNotifyUrl("http://47.114.116.249:1314/home/index/notice");
    $request->setBizContent(json_encode($bizcontent));
    $responseResult = $alipayClient->sdkExecute($request);

    return json_encode(['orderinfo'=>$responseResult],true);

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
//发送手机
function send_sms($AccessKeyId, $Secret, $params)
{
    require '../vendor/autoload.php';
    if (empty($params['phone'])) {
        return false;
    }
    // 创建客户端
    AlibabaCloud\Client\AlibabaCloud::accessKeyClient($AccessKeyId, $Secret)
        ->regionId('cn-hangzhou')
        ->asDefaultClient();
    try {
        $result = AlibabaCloud\Client\AlibabaCloud::rpc()
            ->product('Dysmsapi')
            ->version('2017-05-25')
            ->action('SendSms')
            ->host('dysmsapi.aliyuncs.com')
            ->options([
                // 这里的参数可以在openAPI Explorer里面查看
                'query' => [
                    'RigionId' => 'cn_hangzhou',
                    'PhoneNumbers' => $params['phone'],    // 输入的手机号
                    'SignName' => $params['sign'],    // 签名信息
                    'TemplateCode' => $params['code'],    // 短信模板id
                    'TemplateParam' => $params['param']    // 可选，模板变量值，json格式
                ]
            ])
            ->request();
//        print_r($result->toArray());
        return $result->toArray();
    } catch (ClientException $e) {
        echo $e->getErrorMessage() . PHP_EOL;
    } catch (ServerException $e) {
        echo $e->getErrorMessage() . PHP_EOL;
    }
}


/**
 * 校验手机号码
 * @param $phone
 * @return bool
 */
function validatePhone($phone)
{
    if (!preg_match("/^1[3456789]\d{9}$/", $phone)) {
        return false;
    }
    return true;
}
//    
function randomDivInt($div,$total){
    $remain=$total;
    $max_sum=($div-1)*$div/2;
    $p=$div; $min=0;
    $a=array();
    for($i=0; $i<$div-1; $i++){
        $max=($remain-$max_sum)/($div-$i);
        $e=rand($min,$max);
        $min=$e+1; $max_sum-=--$p;
        $remain-=$e;
        $a[$e]=true;
    }
    $a=array_keys($a);
    $a[]=$remain;
    return $a;
}

