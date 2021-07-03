<?php
/**
 * Created by PhpStorm.
 * User: qiucheng
 * Date: 2021/7/1
 * Time: 15:29
 */

namespace DUmeng;

class Base
{
    const UMENG_HOST = 'http://msg.umeng.com';
    const UMENG_HOST_HTTPS = 'https://msgapi.umeng.com';
    const SEND_URL = self::UMENG_HOST . '/api/send';
    const STATUS_URL = self::UMENG_HOST . '/api/status';
    const CANCEL_URL = self::UMENG_HOST . '/api/cancel';
    const UPLOAD_URL = self::UMENG_HOST . '/upload';

    const LOG_FILE = "./upush.log";

    /**
     * API通过HTTP Status Code来说明请求是否成功, 200表示成功, 400表示失败, 表示超时。
     * @link https://developer.umeng.com/docs/67966/detail/149296#h1--j-http-status-code-10
     *
     * @param client $client
     * @param $url
     * @param array $postBody
     * @param string|null $appMasterSecret
     * @return mixed
     */
    public function http_post(client $client, $url, array $postBody = [], string $appMasterSecret = null)
    {
        $postBody = json_encode($postBody, JSON_UNESCAPED_UNICODE);
        $url .= "?sign=" . md5("POST" . $url . $postBody . $appMasterSecret);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postBody);


        $data['data'] = json_decode(curl_exec($ch), true);
        $data['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $data['http_total_time'] = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        $data['http_curl_err'] = curl_error($ch);
        curl_close($ch);
        if (!empty($client->getLogFile())) {
            self::log(
                $client->getLogFile(),
                "URL:{$url}, POST_BODY:{$postBody}, RESPONSE:" . json_encode($data, JSON_UNESCAPED_UNICODE)
            );
        }
        return $data;
    }

    /**
     * @param $LogFile
     * @param $content
     */
    public static function log($LogFile, $content)
    {
        error_log($content . "\r\n", 3, $LogFile);
    }
}