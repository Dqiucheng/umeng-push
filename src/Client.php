<?php
/**
 * Created by PhpStorm.
 * User: qiucheng
 * Date: 2021/7/1
 * Time: 15:29
 */

namespace DUmeng;


class Client extends Base
{
    private $config = [
        'ios' => [
            'appkey' => '',
            'appMasterSecret' => '',
        ],
        'android' => [
            'appkey' => '',
            'appMasterSecret' => '',
        ],
    ];

    private $logFile = null;


    /**
     * Client constructor.
     * @param array $config     友盟配置信息
     * @param string $logFile   默认日志路径为 ./upush.log,即保存在当前运行目录，如果想关闭日志，可以指定为 null。
     * @throws \Exception
     */
    public function __construct(array $config, $logFile = self::LOG_FILE)
    {
        if (empty($config['ios']) && empty($config['android'])) {
            throw new \Exception("Invalid ios or android");
        }

        if (!empty($config['ios'])) {
            if (empty($config['ios']['appkey']) || empty($config['ios']['appMasterSecret'])) {
                throw new \Exception("Invalid ios.appkey or ios.appMasterSecret");
            }
            $this->config['ios'] = $config['ios'];
        }

        if (!empty($config['android'])) {
            if (empty($config['android']['appkey']) || empty($config['android']['appMasterSecret'])) {
                throw new \Exception("Invalid android.appkey or android.appMasterSecret");
            }
            $this->config['android'] = $config['android'];
        }

        $this->logFile = $logFile;
    }

    public function getConfig($platform = null)
    {
        if ($platform != null) {
            return $this->config[$platform];
        }
        return $this->config;
    }

    public function getLogFile()
    {
        return $this->logFile;
    }


    public function push()
    {
        return new Push($this);
    }
}