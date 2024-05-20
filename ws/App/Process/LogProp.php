<?php 
namespace App\Process;
use Swoole\Process;
// use EasySwoole\EasySwoole\Logger;
use App\Api\Utils\Request;
use EasySwoole\Component\Process\AbstractProcess;

class LogProp extends AbstractProcess {

    protected function run($arg)
    {
        //当进程启动后，会执行的回调
        file_put_contents(EASYSWOOLE_TEMP_DIR.'/logGoodsPid.file',$this->getPid(),LOCK_EX);
        while (1) 
        {
            \Swoole\Coroutine::sleep(10);
            Request::getInstance()->http("https://wydzg-log.shenzhenyuren.com/log_ysjdftz",'get',[]);
        }

    }

    /*
     * 该回调可选
     * 当有主进程对子进程发送消息的时候，会触发的回调，触发后，务必使用
     * $process->read()来读取消息
     */
    protected function onPipeReadable(Process $process){}

    /*
     * 该回调可选
     * 当该进程退出的时候，会执行该回调
     */
    protected function onShutDown()
    {

    }

    /*
     * 该回调可选
     * 当该进程出现异常的时候，会执行该回调
     */
    protected function onException(\Throwable $throwable, ...$args){}

    public function reloadProcess()
    {
        \Swoole\Process::kill(file_get_contents(EASYSWOOLE_TEMP_DIR.'/logGoodsPid.file'),SIGTERM);
    }

}