<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */
//declare(ticks=1);

use \GatewayWorker\Lib\Gateway;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events
{
    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     * 
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id)
    {
        // 向当前client_id发送数据
        Gateway::sendToClient($client_id, static::success(['sid'=>$client_id],'bind','sockte建立成功'));
        // 向所有人发送
        // Gateway::sendToAll("$client_id login\r\n");
    }
    
   /**
    * 当客户端发来消息时触发
    * @param int $client_id 连接id
    * @param mixed $message 具体消息
    */
   public static function onMessage($client_id, $message)
   {
      $operation = json_decode($message,true);
      if (empty($message) || empty($operation['type'])) $operation['type'] = '';
      $type = $operation['type'];
      switch ($type) {
        case 'wechat_applet_kefu'://微信小程序客服
          Gateway::sendToClient($client_id,static::success([],$type));
          break;
        case '1'://
          Gateway::sendToClient($client_id,static::success([],$type));
          break;
        case '2'://
          Gateway::sendToClient($client_id,static::success([],$type));
          break;
        
        default:
          Gateway::sendToClient($client_id,static::success([]));
          break;
      }
   }
   
   /**
    * 当用户断开连接时触发
    * @param int $client_id 连接id
    */
   public static function onClose($client_id)
   {
       // 向所有人发送 
       // GateWay::sendToAll("$client_id logout\r\n");
   }

   public static function success($data=[],$type='',$msg='success')
   {
     return json_encode(['status'=>1,'type'=>$type,'data'=>$data,'message'=>$msg],JSON_UNESCAPED_UNICODE);
   }

   public static function fail($msg='fail',$type='',$data=[])
   {
     return json_encode(['status'=>0,'type'=>$type,'data'=>$data,'message'=>$msg],JSON_UNESCAPED_UNICODE);
   }
}
