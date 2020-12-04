<?php
namespace app\admin\controller;

use app\admin\DbController;
use think\facade\Config;
use think\facade\Db;
use think\helper\Str;

class Power extends DbController{
    
    /**
     * 生成模块代码
     */
    public function createCode(){
        
        $id = $this->request->post("id");
        
        $power = Db::name("power")->where('id',$id)->value("power");
        
        $temp = explode(".", $power);
        
        $controller = $temp[0];
        $table = Str::snake($controller);
        $controller = Str::studly($table);

        $prefix = Config::get('database.connections.mysql.prefix');
        //判断表是否存在
        if(!Db::query("show tables like '{$prefix}{$table}'")){
            return action_error($table." 表存不存在");
        }
        
        //生成控制器
        $this->createController($controller,$table);
        
        //生成HTML页面
        $this->createHtml($table);
        
        return json([
            'code'=>0,
            'msg'=>'代码成功生成',
            'prefix'=>$prefix,
            'controller'=>$controller,
            'table'=>$table
        ]);
    }
    
    private function createController($controller,$table){
        
        $appPath = $this->app->getAppPath();
        $controllerPath = $appPath."controller/";
        $modelPath = $appPath."model/";
        
        
        //生成控制类
        $filename = $controllerPath.$controller.".php";
        if(!file_exists($filename)){
            
            if(config("app.multi_company")){//多企业版
                $fields = Db::name($table)->getTableFields();
            }else{
                $fields=[];
            }
            
            $txt = '<?php'.PHP_EOL;
            $txt .= 'namespace app\admin\controller;'.PHP_EOL;
            if(in_array("company_id", $fields)){
                $txt .= 'use app\admin\ControllerController;'.PHP_EOL;
            }else{
                $txt .= 'use app\admin\DbController;'.PHP_EOL;
            }
            
            $txt .= 'class '.$controller.' extends DbController{'.PHP_EOL;
            $txt .= '}'.PHP_EOL;
            
            $myfile = fopen($filename, "w");
            fwrite($myfile, $txt);
            fclose($myfile);
        }
        
        //生成模型类
        $filename = $modelPath.$controller.".php";
        if(!file_exists($filename)){
            $txt = '<?php'.PHP_EOL;
            $txt .= 'namespace app\admin\model;'.PHP_EOL;
            $txt .= 'use think\Model;'.PHP_EOL;
            $txt .= 'class '.$controller.' extends Model{'.PHP_EOL;
            $txt .= '}'.PHP_EOL;
            
            $myfile = fopen($filename, "w");
            fwrite($myfile, $txt);
            fclose($myfile);
        }
        
    }
    
    private function createHtml($table){
        
        $appPath = $this->app->getAppPath();
        $viewPath = $appPath."view/";
        
        $dir = Str::camel($table);//文件目录
        
        if(!is_dir($viewPath.$dir)){
            mkdir($viewPath.$dir,0777,true);
        }
        
        $prefix = Config::get('database.connections.mysql.prefix');
        $db = Config::get('database.connections.mysql.database');
        $tableFields = Db::query("select column_name as column_name,column_comment as column_comment,data_type as data_type,column_type as column_type from information_schema.columns where table_name='{$prefix}{$table}' and table_schema='$db'");
        
        
        //创建列表页
        $this->createListHtml($viewPath.$dir."/", $tableFields);
        
        //创建表单页
        $this->createFormHtml($viewPath.$dir."/", $tableFields);
    }
    
    private function createFormHtml($dir,$tableFields){
        if(file_exists($dir."form.html")){
            return;
        }
        
        $html='{extend name="base" /}{block name="body"}'.PHP_EOL;
        $html.='<script type="text/javascript" charset="utf-8" src="/admin/js/ueditor/ueditor.config.js"></script>'.PHP_EOL;
        $html.='<script type="text/javascript" charset="utf-8" src="/admin/js/ueditor/ueditor.all.js"></script>'.PHP_EOL;
        $html.='<body>'.PHP_EOL;
        $html.='<div class="ok-body">'.PHP_EOL;
        $html.='	<form class="layui-form ok-form layui-form-pane" lay-filter="formFilter">'.PHP_EOL;
        $html.='		<input type="hidden" name="id" />'.PHP_EOL;
        foreach ($tableFields as $val){ 
            if(in_array($val['column_name'], ['id','create_time','create_user','update_time','update_user']) 
                || !$val['column_comment']
            ) continue;
            
            if($val['column_name']=="imgs" || $val['column_name']=="album"){//相册
                $html.='		<div class="layui-form-item">'.PHP_EOL;
                $html.='			<label class="layui-form-label">'.$val['column_comment'].'</label>'.PHP_EOL;
                $html.='			<div class="layui-input-block">'.PHP_EOL;
                $html.='		          <div class="layui-upload imgs-layui-upload">'.PHP_EOL;
                $html.='		              <button type="button" class="layui-btn imgs-upload-btn" name="'.$val['column_name'].'">多图上传</button>'.PHP_EOL;
                $html.='		              <blockquote class="layui-elem-quote layui-quote-nm" style="margin-top: 10px;">'.PHP_EOL;
                $html.='		                  <div>预览图：</div>'.PHP_EOL;
                $html.='		                  <div class="layui-upload-list" style="display:inline-block;">'.PHP_EOL;
                $html.='		                  </div>'.PHP_EOL;
                $html.='		              </blockquote>'.PHP_EOL;
                $html.='		          </div>'.PHP_EOL;
                $html.='			</div>'.PHP_EOL;
                $html.='		</div>'.PHP_EOL;
            }else if($val['column_name']=="imgurl" || $val['column_name']=="img" || $val['column_name']=="logo"){//图片，封面
                $html.='		<div class="layui-form-item">'.PHP_EOL;
                $html.='			<label class="layui-form-label">'.$val['column_comment'].'</label>'.PHP_EOL;
                $html.='			<div class="layui-input-block">'.PHP_EOL;
                $html.='			     <div class="layui-col-md3 layui-col-xs5">'.PHP_EOL;
                $html.='			         <div class="layui-upload-list thumbBox mag0 magt3 cutUpload" style="margin: 0" width="300" height="200">'.PHP_EOL;
                $html.='			             <img class="layui-upload-img thumbImg" src="">'.PHP_EOL;
                $html.='			             <input type="hidden" name="imgurl" value="">'.PHP_EOL;
                $html.='			         </div>'.PHP_EOL;
                $html.='			     </div>'.PHP_EOL;
                $html.='			</div>'.PHP_EOL;
                $html.='		</div>'.PHP_EOL;
            }else{
                $html.='		<div class="layui-form-item'.(in_array($val['data_type'],['text','longtext'])?' layui-form-text':'').'">'.PHP_EOL;
                $html.='			<label class="layui-form-label">'.$val['column_comment'].'</label>'.PHP_EOL;
                $html.='			<div class="layui-input-block">'.PHP_EOL;
                if($val['data_type']=="tinyint"){//下拉
                    $html.='				<select name="'.$val['column_name'].'" lay-verify="required"><option value="">请选择</option></select>'.PHP_EOL;
                }else if($val['data_type']=="datetime"){//时间
                    $html.='				<input type="text" name="'.$val['column_name'].'" placeholder="请选择'.$val['column_comment'].'" autocomplete="off" class="layui-input datetime" lay-verify="required">'.PHP_EOL;
                }else if($val['data_type']=="date"){//日期
                    $html.='				<input type="text" name="'.$val['column_name'].'" placeholder="请选择'.$val['column_comment'].'" autocomplete="off" class="layui-input date" lay-verify="required">'.PHP_EOL;
                }else if($val['data_type']=="decimal"){//金额
                    $html.='				<div class="layui-inline">'.PHP_EOL;
                    $html.='				<input type="text" name="'.$val['column_name'].'" placeholder="请选择'.$val['column_comment'].'" autocomplete="off" class="layui-input" lay-verify="required">'.PHP_EOL;
                    $html.='				</div>'.PHP_EOL;
                    $html.='				<div class="layui-inline">元</div>'.PHP_EOL;
                }else if($val['data_type']=="int" || $val['data_type']=="float"){//数字
                    $html.='				<div class="layui-inline">'.PHP_EOL;
                    $html.='				<input type="text" name="'.$val['column_name'].'" placeholder="请输入'.$val['column_comment'].'" autocomplete="off" class="layui-input" lay-verify="required">'.PHP_EOL;
                    $html.='				</div>'.PHP_EOL;
                    $html.='				<div class="layui-inline"></div>'.PHP_EOL;
                }else if($val['data_type']=="text"){//多行文本输入框
                    $html.='				<textarea id="'.$val['column_name'].'" class="layui-textarea" name="'.$val['column_name'].'"></textarea>'.PHP_EOL;
                }else if($val['data_type']=="longtext"){ //富文本框
                    $html.='				<textarea id="'.$val['column_name'].'" class="html" name="'.$val['column_name'].'" style="height: 500px;width:100%"></textarea>'.PHP_EOL;
                }else{
                    $html.='				<input type="text" name="'.$val['column_name'].'" placeholder="请输入'.$val['column_comment'].'" autocomplete="off" class="layui-input" lay-verify="required">'.PHP_EOL;
                }
                $html.='			</div>'.PHP_EOL;
                $html.='		</div>'.PHP_EOL;
            }
            
        }
        
        $html.='		<div class="layui-form-item">'.PHP_EOL;
        $html.='			<div class="layui-input-block">'.PHP_EOL;
        $html.='				<button class="layui-btn" lay-submit lay-filter="save">立即提交</button>'.PHP_EOL;
        $html.='				<button type="reset" class="layui-btn layui-btn-primary">重置</button>'.PHP_EOL;
        $html.='			</div>'.PHP_EOL;
        $html.='		</div>'.PHP_EOL;
        $html.=''.PHP_EOL;
        $html.=''.PHP_EOL;
        $html.=''.PHP_EOL;
        $html.=''.PHP_EOL;
        $html.='	</form>'.PHP_EOL;
        $html.='</div>'.PHP_EOL;
        $html.='<script>formPageInit()</script>'.PHP_EOL;
        $html.='</body>'.PHP_EOL;
        $html.='{/block}'.PHP_EOL;
        
        $myfile = fopen($dir."form.html", "w");
        fwrite($myfile, $html);
        fclose($myfile);
    }
    
    private function createListHtml($dir,$tableFields){
        if(file_exists($dir."list.html")){
            return;
        }
        $html='{extend name="base" /}{block name="body"}'.PHP_EOL;
        $html.='<body>'.PHP_EOL;
        $html.='<div class="ok-body">'.PHP_EOL;
        $html.='	<div class="layui-row">'.PHP_EOL;
        $html.='		<form class="layui-form layui-col-md12 ok-search">'.PHP_EOL;
        $html.='			<div class="layui-inline"><input class="layui-input" placeholder="关键词" autocomplete="off" name="name"></div>'.PHP_EOL;
        $html.='			<button class="layui-btn" lay-submit="" lay-filter="search"><i class="layui-icon">&#xe615;</i></button>'.PHP_EOL;
        $html.='		</form>'.PHP_EOL;
        $html.='	</div>'.PHP_EOL;
        $html.='	<!--数据表格-->'.PHP_EOL;
        $html.='	<table class="layui-hide" id="dataTable" lay-filter="dataTable"></table>'.PHP_EOL;
        $html.='</div>'.PHP_EOL;
        $html.='<script>'.PHP_EOL;
        $html.='listPageInit({'.PHP_EOL;
        $html.='	name:"数据",'.PHP_EOL;
        $html.='	cols:[['.PHP_EOL;
        $html.='		{type: "checkbox", fixed: "left"},'.PHP_EOL;
        $html.='		{field: "id", title: "ID", width: 80, sort: true},'.PHP_EOL;
        foreach ($tableFields as $val){
            if($val['column_name']!="id" && $val['column_comment']){
                $html.='		{field: "'.$val['column_name'].'", title: "'.$val['column_comment'].'", width: 150},'.PHP_EOL;
            }
        }
        $html.='		{title: "操作", width: 80, align: "center", templet: "#operationTpl", fixed: "right"}'.PHP_EOL;
        $html.='	]]'.PHP_EOL;
        $html.='})'.PHP_EOL;
        $html.='</script>'.PHP_EOL;
        $html.='</body>'.PHP_EOL;
        $html.='{/block}'.PHP_EOL;
        
        $myfile = fopen($dir."list.html", "w");
        fwrite($myfile, $html);
        fclose($myfile);
    }
    
    public function list(){
        $res = parent::list();
        
        
        $responseData = $res->getData();
        
        $datas = $responseData['data'];
        
        foreach ($datas as $key=>$val){
            if($val['top']){
                $datas[$key]['parentName'] = Db::name("power")->where('id',$val['top'])->value("name");
            }
        }
        
        $responseData['data']=$datas;
        return json($responseData);
        
    }
    
    protected function getWhere(){
        $ary = $this->request->post();
        $where = [];
        
        if(!empty($ary['name'])){
            $where[]=['name',"=",$ary['name']];
        }
        
        if(isset($ary['top']) && $ary['top']!=""){
            $where[]=['top',"=",$ary['top']];
        }
        
        return $where;
    }
    
}
