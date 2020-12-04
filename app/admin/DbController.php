<?php
declare (strict_types = 1);

namespace app\admin;

use think\Exception;
use think\Model;
use think\facade\Db;
use think\facade\View;
use think\helper\Str;
use think\response\Json;

/**
 * @author jastem
 * 
 * @name 数据库控制类
 * 
 * 本类是所有控制类的数据处理类，继承类后，控制类将拥有常用的方法：list,del,add,edit
 * 仅用于查询或统计数据的控制器尽量不继承此类
 * 当然你也可以完成放弃此类
 * 
 * 
 * list方法    此方法获取对应表 数据 ，可以分类获取，如果传入参数 nopage 有值，则获取全部数据
 * list方法  会获实例化 model类 如有创建对应表的model则使用对应表的model，如果没有，则使用 Db类
 * list方法 可使用 getModel 获取当前 model 类 ，可在子类里 model类 进行相关操作，也可直接放弃父类的获取model的方法，使用 setModel 设置自定义的model实例
 * list方法   可在子类重写 getWhere 方法，设置列表过滤条件
 * list方法   可在子类重写 getOrder 方法，设置排序 一般不重写，默认就够用了
 * list方法   可在子类重写showFields 方法，设置 列表或详情查询的字段 ，默认为 *
 * 
 * 
 * 
 * add方法   新增数据   model 逻辑与list方法一致   此方法会调用  saveBefore 方法，可在子类重写此方法，在保存前处理数据
 * 
 * edit方法   修改数据   model 逻辑与list方法一致 此方法会调用  saveBefore 方法，可在子类重写此方法，在保存前处理数据
 * 
 * del方法    删除数据  支持批量删除  参数为id  多个用逗号隔开。  model 逻辑与list方法一致
 *
 * 
 */
abstract class DbController extends BaseController
{
    /**
     * 控制器对应的模型对象
     * @var Model
     */
    private $model;
    
    /**
     * 编辑前的数据信息
     * @var array
     */
    protected $editInfo;
    
    
    /**
     * 控制器对应的表格  表名头字母大写
     * @var string
     */
    protected $table = false;
    
    
     
    /**
     * 数据列表
     * @return Json
     */
    public function list(){
        return $this->listData('Json');
    }
    
    /**
     * 大数据分页方法  （千万行数据级别，无特殊情况请使用 list方法 ）
     * @param string $backType 返回类型
     * 默认返回 array 含数据
     * 若直接返回响应类 则  $backType=Json
     * 若子类需要对数据进行处理 则  $backType=data
     * 若子类需要更复杂的SQL拼接，则$backType=sql
     * 无论返回类型是什么，都会返回当前查询的总记录数
     * @return array|Json[]
     */
    protected function listPage($backType='data'){
        $post = $this->request->post();
        
        $p = empty($post['page'])?1:$post['page'];
        $p = intval($p);
        $l = empty($post['limit'])?20:$post['limit'];
        $l = intval($l);
        $sort = $this->getOrder();
        
        //获取过滤条件
        $wheres = $this->getWhere();
        
        //相关表格
        if($this->table){
            $table = $this->table;
        }else{
            $table = class_basename($this);
            $table = Str::snake($table);
        }
        
        
        //使用估计计算总行数
        $sql = Db::name($table)->where($wheres)->fetchSql(true)->select();
        $count=0;
        $explain = Db::query("EXPLAIN $sql");
        if($explain[0]['rows']){
            $count = $explain[0]['rows'];
        }
        
        $b = ($p-1)*$l;
        $sql = Db::name($table)->field("id")->where($wheres)->order($sort)->limit($b,$l)->fetchSql(true)->select();
        $sql = "select a.* from `t_{$table}` a INNER JOIN ($sql) b on a.id=b.id";
        $sql.= $this->getOrderSql($sort);
        
        if($backType=="sql"){
            return [
                'count'=>$count,
                'sql'=>$sql,
            ];
        }else{
            $list=Db::query($sql);
            $data = array(
                'code'=>0,
                'msg'=>'',
                'count'=>$count,
                'data'=>$list,
                'sql'=>$sql
            );
            if($backType=="data"){
                return $data;
            }else{
                return json($data);
            }
        }
    }
    /**
     * 数据列表实际数据操作
     * @param boolean|string $backType 返回类型，默认返回 响应JSON类，若子类需要对数据进行处理 则  $backType=data
     * @return mixed
     */
    protected function listData($backType='data'){
        $ary = [];//请求参数
        if($this->request->isAjax()){
            $ary = $this->request->post();
        }else{
            $ary = $this->request->get();
        }
        
        //获取过滤条件
        $wheres = $this->getWhere();
        
        //排序
        $sorts = $this->getOrder();
        
        $model = $this->getModel();
        
        if($wheres){
            $model->where($wheres);
        }
        
        $model->order($sorts);
        
        //每页显示数量
        $l = isset($ary['limit'])?intval($ary['limit']):20;
        $l = intval($l);
        
        if(isset($ary['nopage'])){
            
            $list = $model->limit($l)->select();
            $data=[
                'code'=>0,
                'data'=>$list
            ];
        }else{
            //当前页面
            $p = isset($ary['page'])?$ary['page']:1;
            $p = intval($p);
            
            //查询总记录数
            $list = $model->paginate([
                'list_rows'=> $l,
                'page' => $p,
            ])->toArray();
            
            $data = array(
                'code'=>0,
                'msg'=>'',
                'count'=>$list['total'],
                'data'=>$list['data']
            );
            
            $data['sql']=$model->getLastSql();
            
        }
        
        if($backType=="data"){
            return $data;
        }else{
            return json($data);
        }
    }
    
    
    /**
     * 根据id获取信息
     * @return Json|View
     */
    public function info(){
        return $this->infoData("Json");
    }
    
    protected function infoData($backType="data"){
        $ary = [];//请求参数
        if($this->request->isAjax()){
            $ary = $this->request->post();
        }else{
            $ary = $this->request->get();
        }
        
        $model = $this->getModel();
        
        if(!isset($ary['id']) || !$ary['id']){
            return action_error("参数错误");
        }
        
        $info = $model->find($ary['id']);
        if($backType=="data"){
            return $info;
        }else{
            return action_succ($info);
        }
    }
    
    public function add($ary=[]){
        if(!$ary){
            if($this->request->isAjax()){
                $ary = $this->request->post();
            }else{
                $ary = $this->request->get();
            }
        }
        
        try {
            $ary = $this->saveBefore($ary);
        } catch (Exception $e) {
            return action_error($e->getMessage());
        }
        
        if($this->table){
            $table = $this->table;
        }else{
            $table = class_basename($this);
        }
        
        $table = Str::snake($table);
        $model = Db::name($table);
        $this->model = $model;
        //过滤表中不存在的字段
        $fields = $model->getTableFields();
        
        $oldAry = $ary;
        
        foreach ($ary as $key=>$val){
            if(is_array($val)) $ary[$key]=json_encode($val,JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);
            if(!in_array($key, $fields)){
                unset($ary[$key]);
            }
        }
        
        $userInfo = get_login_info();
        if(empty($ary['create_user']) && in_array("create_user", $fields)) $ary['create_user'] = $userInfo['id'];
        if(empty($ary['create_time']) && in_array("create_time", $fields)) $ary['create_time'] = time();
        if(empty($ary['sort']) && in_array("sort", $fields)) $ary['sort'] = 100;
        if(isset($ary['id'])) unset($ary['id']);
        
        
        
        $id = $model->insertGetId($ary);
        
        if($id){
            $oldAry['id']=$id;
            $this->saveAfter($oldAry);
        }
        
        
        //返回新增ID
        return action_succ(['id'=>$id]);
    }
    
    public function edit($ary=[]){
        if(!$ary){
            if($this->request->isAjax()){
                $ary = $this->request->post();
            }else{
                $ary = $this->request->get();
            }
        }
        
        if(!$this->editInfo){
            $this->editInfo = $this->getModel()->find($ary['id']);
        }
        
        try {
            $ary = $this->saveBefore($ary,false);
        } catch (\Exception $e) {
            return action_error($e->getMessage());
        }
        
        
        if($this->table){
            $table = $this->table;
        }else{
            $table = class_basename($this);
        }
        
        $table = Str::snake($table);
        $model = Db::name($table);
        $this->model = $model;
        //过滤表中不存在的字段
        $fields = $model->getTableFields();
        
        $oldAry = $ary;
        foreach ($ary as $key=>$val){
            if(is_array($val)) $ary[$key]=json_encode($val,JSON_UNESCAPED_UNICODE+JSON_UNESCAPED_SLASHES);
            if(!in_array($key, $fields)){
                unset($ary[$key]);
            }
        }
        
        $userInfo = get_login_info();
        
        if(empty($ary['update_user']) && in_array("update_user", $fields)) $ary['update_user'] = $userInfo['id'];
        if(empty($ary['update_time']) && in_array("update_time", $fields)) $ary['update_time'] = time();
        
        $c = $model->save($ary);
        
        if($c) $this->saveAfter($oldAry,false);
        
        //返回受影响条数
        return action_succ(['count'=>$c]);
    }
    
    /**
     * 删除数据
     * @return Json
     */
    public function del($id=null){
        if(!$id) $id = $this->request->param("id");
        
        if(!$id){
            return action_error("参数错误");
        }
        
        $model = $this->getModel();
        $c = $model->delete($id);
        
        //返回受影响条数
        return action_succ(['count'=>$c]);
    }
    
    /**
     * 保存之前调此方法  add,edit都会调此方法，可在保存前对数据进行处理
     * @param array $ary
     * @param boolean $isadd 是否是添加数据  默认为是
     * @return array
     */
    protected function saveBefore($ary,$isadd=true){
        return $ary;
    }
    
    
    /**
     * 保存之后调此方法  add,edit都会调此方法，可在保存前对数据进行处理
     * @param array $ary
     * @param boolean $isadd 是否是添加数据  默认为是
     */
    protected function saveAfter($ary,$isadd=true){
        
    }
    
    /**
     * 根据控制器名称获取对应的模型对象
     * @param boolean $afresh 是否重新获取模型
     * @return Model
     */
    protected  function getModel($afresh=false){
        
        if($this->model && !$afresh){
            return $this->model;
        }
        
        if($this->table){
            $table = $this->table;
        }else{
            $table = class_basename($this);
        }
        //判断对应的模型是否存在
        if(class_exists('\app\admin\model\\'.$table)){
            $classname = '\app\admin\model\\'.$table;
            //$model = new $classname;
            $model = call_user_func($classname."::field",$this->showFields());
        }else{
            $table = Str::snake($table);
            $model = Db::name($table);
            $fields = $this->showFields();
            //设置显示字段
            if($fields){
                $model->field($fields);
            }
        }
        
        $this->model=$model;
        
        return $model;
    }
    
    protected  function setModel($model){
        $this->model=$model;
    }
    
    /**
     * 根据参数获取过滤条件 （请在继承类里重写此方法  默认无条件）
     * @param array $ary  POST 或  GET 参数
     * @return array 
     */
    protected function getWhere(){
        return [];
    }
    
    
    /**
     * 根据参数获取排序 （请在继承类里重写此方法， 默认ID倒序）
     * @param array $ary  POST 或  GET 参数
     * @return array
     */
    protected function getOrder(){
        
        if($this->request->isAjax()){
            $ary = $this->request->post();
        }else{
            $ary = $this->request->get();
        }
        
        $sort = [];
        
        if(isset($ary['o']) && $ary['o']){
            $temp = explode("|", $ary['o']);
            $sort=[$temp[0]=>$temp[1]];
        }else{
            $sort=['id'=>'desc'];
        }
        
        return $sort;
        
    }
    
    
    /**
     * 获取排序 sql
     * @param array $sorts
     * @return string $sql
     */
    protected function getOrderSql($sorts){
        $sql = "";
        if($sorts){
            $sql=" order by ";
            foreach ($sorts as $key=>$val){
                $sql.=$key." ".$val;
            }
        }
        
        return $sql;
    }
    
    /**
     * 列表或详情显示字段 （请在继承类里重写此方法  默认全部）
     * @return string|array 
     */
    protected function showFields(){
        return false;
    }
}
