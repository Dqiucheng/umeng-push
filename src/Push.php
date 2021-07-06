<?php

/**
 * Created by PhpStorm.
 * User: qiucheng
 * Date: 2021/7/1
 * Time: 17:39
 */

namespace Dumeng;

use Dumeng\android\Android;
use Dumeng\ios\Ios;

class Push
{
    const PTF = ['ios', 'android'];

    private $client;
    private $platform = [];

    private $data = [
        'android' => [],
        'ios' => [],
    ];

    public function __construct(client $client)
    {
        $this->client = $client;
    }

    /**
     * @param array $platform
     * @return $this
     * @throws \Exception
     */
    public function set_platform(array $platform)
    {
        # $required_keys = array('android', 'ios');
        $ptf = array_map('strtolower', $platform);
        $this->platform = array_intersect($ptf, self::PTF);
        if (empty($this->platform)) {
            throw new \Exception('Invalid platform value');
        }
        return $this;
    }

    /**
     * 消息发送类型
     * @param $type
     * unicast-单播
     * listcast-列播，要求不超过500个device_token
     * filecast-文件播，多个device_token可通过文件形式批量发送
     * broadcast-广播
     * groupcast-组播，按照filter字段筛选用户群,目前支持的参数如下
     *      -“app_version”(应用版本)
     *      -“channel”(渠道)
     *       -“device_model”(设备型号)
     *      -“province”(省)
     *      -“tag”(用户标签)
     *      -“country”(国家和地区) //“country”和”province”的类型定义请参照 文档示例
     *      -“language”(语言)
     *      -“launch_from”(一段时间内活跃)
     *      -“not_launch_from”(一段时间内不活跃)
     *      -“install_in”(设备注册时间在最近)
     *      -“install_before”(设备注册时间在之前)
     *      -“push_switch”(通知开关状态)
     * customizedcast，通过alias字段进行推送，包括以下两种case:
     *      -“alias”:对单个或者多个alias进行推送
     *      -“file_id”:将alias存放到文件后，根据file_id来推送
     * @return $this
     */
    public function set_type(string $type)
    {
        if (!in_array($type, ['unicast', 'listcast', 'filecast', 'broadcast', 'groupcast', 'customizedcast'])) {
            throw new \Exception('Invalid type value');
        }

        foreach ($this->platform as $k => $v) {
            $this->data[$v]['type'] = $type;
        }
        return $this;
    }

    /**
     * @param $device_tokens
     *          当type=unicast时,必填,表示指定的单个设备。
     *          当type=listcast时,必填,要求不超过500个,以英文逗号分隔
     * @return $this
     */
    public function set_device_tokens(string $device_tokens)
    {
        foreach ($this->platform as $k => $v) {
            $this->data[$v]['device_tokens'] = $device_tokens;
        }
        return $this;
    }

    /**
     * @param $alias_type
     *          当type=customizedcast时,必填。
     *          alias的类型, alias_type可由开发者自定义
     * @return $this
     */
    public function set_alias_type($alias_type)
    {
        foreach ($this->platform as $k => $v) {
            $this->data[$v]['alias_type'] = $alias_type;
        }
        return $this;
    }

    /**
     * @param $alias
     *          当type=customizedcast时,选填(此参数和file_id二选一)
     *          当type=customizedcast时,选填(此参数和file_id二选一)
     * @return $this
     */
    public function set_alias(string $alias)
    {
        foreach ($this->platform as $k => $v) {
            $this->data[$v]['alias'] = $alias;
        }
        return $this;
    }

    /**
     * @param $file_id
     *          当type=filecast时，必填，file内容为多条device_token，以回车符分割
     *          当type=customizedcast时，选填(此参数和alias二选一)
     *          file内容为多条alias，以回车符分隔。注意同一个文件内的alias所对应的alias_type必须和接口参数alias_type一致
     * @return $this
     */
    public function set_file_id(string $file_id)
    {
        foreach ($this->platform as $k => $v) {
            $this->data[$v]['file_id'] = $file_id;
        }
        return $this;
    }

    /**
     * @param $filter
     *          当type=groupcast时，必填，用户筛选条件，如用户标签、渠道等
     *          filter的内容长度最大为3000B
     * @return $this
     */
    public function set_filter(array $filter)
    {
        foreach ($this->platform as $k => $v) {
            $this->data[$v]['filter'] = $filter;
        }
        return $this;
    }

    /**
     * 具体消息内容
     * @param $payload  包含：ios、android
     *          ios:ios的消息内容
     *          android:android的消息内容
     * @return $this
     */
    public function set_payload(array $payload)
    {
        if (empty($payload['ios']) && empty($payload['android'])) {
            throw new \Exception('Invalid payload value');
        }

        if (!empty($payload['ios']) && in_array('ios', $this->platform)) {
            $this->data["ios"]['payload'] = (new Ios())->set_payload($payload['ios']);
        }

        if (!empty($payload['android']) && in_array('android', $this->platform)) {
            $this->data["android"]['payload'] = (new Android())->set_payload($payload['android']);
        }
        return $this;
    }

    /**
     * 可选，发送策略
     * @param $policy
     * @return $this
     */
    public function set_policy(array $policy)
    {
        $policy = array_intersect($policy, ['start_time', 'expire_time', 'out_biz_no', 'apns_collapse_id']);
        if (empty($policy)) {
            throw new \Exception('Invalid policy value');
        }
        foreach ($this->platform as $k => $v) {
            $this->data[$v]['policy'] = $policy;
        }
        return $this;
    }

    /**
     * 可选，true正式模式，false测试模式。默认为true
     * @param $production_mode
     *          测试模式只对“广播”、“组播”类消息生效，其他类型的消息任务（如“文件播”）不会走测试模式
     *          测试模式只会将消息发给测试设备。测试设备需要到web上添加
     *          Android:测试设备属于正式设备的一个子集
     * @return $this
     */
    public function set_production_mode(bool $production_mode = true)
    {
        foreach ($this->platform as $k => $v) {
            $this->data[$v]['production_mode'] = $production_mode;
        }
        return $this;
    }

    /**
     * 可选，发送消息描述，建议填写
     * @param $description
     * @return $this
     */
    public function set_description(string $description)
    {
        foreach ($this->platform as $k => $v) {
            $this->data[$v]['description'] = $description;
        }
        return $this;
    }

    /**
     * 可选，厂商通道相关的特殊配置（只适用于Android）
     * @param $channel_properties
     * @return $this
     */
    public function set_channel_properties(array $channel_properties)
    {
        foreach ($this->platform as $k => $v) {
            if ($v == 'android') {
                $this->data[$v]['channel_properties'] = $channel_properties;
            }
        }
        return $this;
    }

    /**
     * 生成请求体
     * @link https://developer.umeng.com/docs/67966/detail/68343
     *
     * @param array $data 设置指定请求体，设置后将直接绕过set_XXX系列函数操作
     * @return array
     * @throws \Exception
     */
    public function build(array $data = [])
    {
        if (!empty($data)) {
            $this->data = $data;
            return $data;
        }

        foreach ($this->data as $ptf => $ptfNotificationArr) {
            if (empty($ptfNotificationArr) || !is_array($ptfNotificationArr) || !in_array($ptf, self::PTF)) {
                unset($this->data[$ptf]);
                continue;
            }
            foreach ($ptfNotificationArr as $key => $val) {
                $this->data[$ptf]['appkey'] = $this->client->getConfig($ptf)['appkey'];
                $this->data[$ptf]['timestamp'] = time();
            }
        }

        if (empty($this->data)) {
            throw new \Exception('Invalid value');
        }
        return $this->data;
    }

    public function toJSON()
    {
        return json_encode($this->build(), JSON_UNESCAPED_UNICODE);
    }



    /*****************************************************开始触达友盟*****************************************************/

    /**
     * 发送消息
     * @return array
     * @throws \Exception
     */
    public function send()
    {
        $res = [];
        foreach ($this->build() as $ptf => $val) {
            $res[$ptf] = $this->client->http_post(
                $this->client,
                $this->client::SEND_URL,
                $val,
                $this->client->getConfig($ptf)['appMasterSecret']
            );
        }
        return $res;
    }

    /**
     * 消息状态查询
     *
     * 任务类消息(type为broadcast、groupcast、filecast、customizedcast且file_id不为空)，可以通过task_id来查询当前的消息状态
     * todo 注意：非任务类消息，该接口会不生效。
     *
     * @param array $task_id_arr 消息发送时, 从返回消息中获取的task_id
     *      ios:task_id
     *      android:task_id
     * @return mixed
     */
    public function status(array $task_id_arr)
    {
        if (empty(array_intersect(array_keys($task_id_arr), self::PTF))) {
            throw new \Exception('Invalid task_id_arr key value');
        }
        $res = [];
        foreach ($task_id_arr as $ptf => $task_id) {
            $data['appkey'] = $this->client->getConfig($ptf)['appkey'];
            $data['timestamp'] = time();
            $data['task_id'] = $task_id;
            $res[$ptf] = $this->client->http_post(
                $this->client,
                $this->client::STATUS_URL,
                $data,
                $this->client->getConfig($ptf)['appMasterSecret']
            );
        }
        return $res;
    }

    /**
     * 消息撤销
     *
     * 任务类消息(type为broadcast、groupcast、filecast、customizedcast且file_id不为空)，可以进行撤销操作。
     * todo 注意：撤销操作首先会从服务端尝试撤销（Android消息，排队中/发送中状态可以服务端撤销；iOS消息，排队中状态可以服务端撤销）；其次，针对组件版SDK（Android SDK 4.0及以上和iOS sdk 3.0及以上），会尝试从设备端撤销已展示但未被点击的消息。
     *
     * @param array $task_id_arr 消息发送时, 从返回消息中获取的task_id
     *      ios:task_id
     *      android:task_id
     * @return mixed
     */
    public function cancel(array $task_id_arr)
    {
        if (empty(array_intersect(array_keys($task_id_arr), self::PTF))) {
            throw new \Exception('Invalid task_id_arr key value');
        }
        $res = [];
        foreach ($task_id_arr as $ptf => $task_id) {
            $data['appkey'] = $this->client->getConfig($ptf)['appkey'];
            $data['timestamp'] = time();
            $data['task_id'] = $task_id;
            $res[$ptf] = $this->client->http_post(
                $this->client,
                $this->client::CANCEL_URL,
                $data,
                $this->client->getConfig($ptf)['appMasterSecret']
            );
        }
        return $res;
    }

    /**
     * 文件上传
     *
     * 文件上传接口支持两种应用场景
     *  1、发送类型为”filecast”的时候, 开发者批量上传device_token;
     *  2、发送类型为”customizedcast”时, 开发者批量上传alias。
     * 上传文件后获取file_id, 从而可以实现通过文件id来进行消息批量推送的目的。
     * 文件自创建起，服务器会保存两个月。开发者可以在有效期内重复使用该file-id进行消息发送。
     * todo 注意：上传的文件不超过10M。
     *
     * @param array $content 文件内容, 多个device_token/alias请用回车符"\n"分隔
     *      ios:content
     *      android:content
     * @return mixed
     */
    public function upload(array $content_arr)
    {
        if (empty(array_intersect_key($content_arr, self::PTF))) {
            throw new \Exception('Invalid content_arr key value');
        }
        $res = [];
        foreach ($content_arr as $ptf => $content) {
            $data['appkey'] = $this->client->getConfig($ptf)['appkey'];
            $data['timestamp'] = time();
            $data['content'] = $content;
            $res[$ptf] = $this->client->http_post(
                $this->client,
                $this->client::UPLOAD_URL,
                $data,
                $this->client->getConfig($ptf)['appMasterSecret']
            );
        }
        return $res;
    }
}
