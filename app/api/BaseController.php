<?php
declare (strict_types = 1);

namespace app\api;

use app\api\middleware\ActionBefore;
use think\App;
use think\exception\ValidateException;
use think\facade\Db;
use think\Validate;

/**
 * @author jastem
 * 
 * @name 控制器基础类
 * 
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;
    
    protected $userId;
    protected $user;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [ActionBefore::class];
    
    
    

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;
        $this->userId = $this->request->post("user");
        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {}
    
    
    protected function getLoginUser(){
        if($this->user){
            return $this->user;
        }
        
        $this->user = Db::name("user")->find($this->userId);
        
        return $this->user;
    }

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }
    
    
    /**
     * 文件上传方法  支持使用 JS webUploader 控件 分片传，也可以直接上传，返回服务端保存的路径
     * @throws FileException
     * @return Json|View
     */
    protected function upload(){
        set_time_limit(0);
        ini_set('max_execution_time', '0');
        
        //文件根目录
        if($this->request->post("open")){
            $fileRoot = config("filesystem.disks.public.root")."/";
        }else{//存本地，禁止外网访问
            $fileRoot = config("filesystem.disks.local.root")."/";
        }
        
        if($this->request->post("chunks")){//分片上传
            
            $fileRootTmp = $fileRoot."chunks/".date("Ymd")."/";//分片文件临时根目录
            
            //分片文件临时目录
            $dir = $fileRootTmp.md5($this->request->post("name").$this->request->post("size"));
            
            if($this->request->post("status")=="complete"){//分片上传结束,合并文件
                
                $dirFiles = scandir($dir);//获取所有分片文件
                
                $chunkFiles = array();
                foreach ($dirFiles as $v) {
                    if ($v == "." || $v == "..") {
                        continue;
                    }
                    
                    $chunkFiles[explode(".", $v)[0]] = $v;
                }
                
                if(!$chunkFiles) return ['status'=>'error','msg'=>'文件不存在'];
                
                //排序
                ksort($chunkFiles);
                
                $fileName = $dir.".".$this->request->post("ext");
                $handle = fopen($fileName, "a+");
                foreach ($chunkFiles as $chunkFile) {
                    $chunkFilePath = $dir."/".$chunkFile;
                    fwrite($handle, file_get_contents($chunkFilePath));
                    unlink($chunkFilePath);
                }
                fclose($handle);
                
                rmdir($dir);
                
                if(!$this->request->post("local")){
                    $fileName = str_replace(config("filesystem.disks.public.root"), config("filesystem.disks.public.url"), $fileName);
                }
                
                return ['status'=>'complete','file'=>$fileName];
            }
            
            $files = $this->request->file();
            
            foreach($files as $file){
                
                //文件
                $filename = $this->request->post("chunk").".".$file->extension();
                
                if (!is_dir($dir)) {
                    if (false === @mkdir($dir, 0777, true) && !is_dir($dir)) {
                        throw new FileException(sprintf('Unable to create the "%s" directory', $dir));
                    }
                } elseif (!is_writable($dir)) {
                    throw new FileException(sprintf('Unable to write in the "%s" directory', $dir));
                }
                
                $newFilePath = $dir."/".$filename;
                
                $moved = move_uploaded_file($file->getPathname(), $newFilePath);
                restore_error_handler();
                if (!$moved) {
                    throw new FileException(sprintf('Could not move the file "%s" to "%s" (%s)', $this->getPathname(), $target, strip_tags($error)));
                }
                
                @chmod($newFilePath, 0666 & ~umask());
                
            }
            
            return ['status'=>'loading','code'=>0];
            
        }else{//普通上传  默认公开
            
            $files = $this->request->file();
            $paths = array();
            foreach($files as $file){
                if($this->request->post("open")){
                    $savename = \think\facade\Filesystem::disk('public')->putFile( 'upload', $file);
                    $savename = "/storage/".$savename;
                }else{//默认不公开
                    $savename = \think\facade\Filesystem::putFile( 'upload', $file);
                    $savename = $fileRoot.$savename;
                    
                }
                $paths[]=$savename;
            }
            
            foreach ($paths as $key=>$path){
                $path = str_replace("\\", "/", $path);
                $paths[$key] = $path;
            }
            
            
            
            
            
            return ['status'=>'complete','files'=>$paths,'urls'=>$paths,'code'=>0];
        }
    }

}
