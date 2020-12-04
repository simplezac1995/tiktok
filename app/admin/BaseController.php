<?php
declare (strict_types = 1);

namespace app\admin;

use think\App;
use think\exception\ValidateException;
use think\Validate;
use app\admin\middleware\ActionBefore;
use think\exception\FileException;
use think\facade\View;

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
        
        View::assign('controller',$this->request->controller());
        View::assign('action',$this->request->action());

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {}

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
    public function upload(){
        set_time_limit(0);
        ini_set('max_execution_time', '0');
        
        //文件根目录
        if($this->request->param("local")){//存本地，禁止外网访问
            $fileRoot = config("filesystem.disks.local.root")."/";
        }else{
            $fileRoot = config("filesystem.disks.public.root")."/";
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
                
                if($this->request->param("local")){
                    $fileName = str_replace($fileRoot,'', $fileName);
                }else{
                    $fileName = str_replace(config("filesystem.disks.public.root"), config("filesystem.disks.public.url"), $fileName);
                }
                
                return json(['status'=>'complete','file'=>$fileName]);
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
            
            return json(['status'=>'loading','code'=>0]);
            
        }else{//普通上传  默认公开
            
            $files = $this->request->file();
            
            $paths = array();
            foreach($files as $file){
                if($this->request->param("local")){
                    $savename = \think\facade\Filesystem::disk('local')->putFile('system', $file);
                }else{
                    $savename = \think\facade\Filesystem::disk('public')->putFile('system', $file);
                    $savename ="/upload/".$savename;
                }
                $paths[]=$savename;
            }
            
            $path = str_replace("\\", "/", $paths[0]);
           
            return json(['status'=>'complete','file'=>$path,'url'=>$path,'code'=>0]);
        }
    }
    
}
