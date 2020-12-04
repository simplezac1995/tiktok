
/**
 * 获取URL请求参数值
 * @param param
 * @returns
 */
function getQueryParam(param){
       var query = window.location.search.substring(1);
       var vars = query.split("&");
       for (var i=0;i<vars.length;i++) {
               var pair = vars[i].split("=");
               if(pair[0] == param){return pair[1];}
       }
       return(false);
}

/**
 * 生成系统唯一编码
 * @returns
 */
function uuid() {
	return "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g, function(c) {
		var r = (Math.random() * 16) | 0, v = c == "x" ? r : (r & 0x3) | 0x8
		return v.toString(16)
	})
}

function createImgsHtml(url,imgsName,showtxt,txt){
	if(!txt) txt="";
	var html = '<div class="layui-upload-album">';
		html += '<div class="layui-upload-img"><img src="'+url+'" style="max-width:100%;max-height:100%"><input type="hidden" name="'+imgsName+'[]" value="'+url+'"></div>';
		if(showtxt){
			html += '<div><input type="text" class="layui-input" name="'+imgsName+'Txt[]" value="'+txt+'"></div>';
		}
		html += '<div class="layui-btn-group">';
		html += '<a class="layui-btn layui-btn-sm layui-btn-primary" onclick="imgsMove(this,3)"><i class="layui-icon">&#xe619;</i></a>';
		html += '<a class="layui-btn layui-btn-sm layui-btn-primary" onclick="imgsMove(this,1)"><i class="layui-icon">&#xe603;</i></a>';
		html += '<a class="layui-btn layui-btn-sm layui-btn-primary" onclick="imgsMove(this,2)"><i class="layui-icon">&#xe602;</i></a>';
		html += '<a class="layui-btn layui-btn-sm layui-btn-primary" onclick="$(this).parents(\'.layui-upload-album:first\').remove()"><i class="layui-icon">&#xe640;</i></a>';
		html += '</div>';
		html += '</div>';

		return html;
}

function imgsMove(_this,type){
	var c = $(_this).parents(".layui-upload-album:first");
	if(type==1){//左移
		if(c.prev().length!=0){
			c.prev().before(c.clone());
			c.remove();
		}
	}else if(type==2){//右移
		if(c.next().length!=0){
			c.next().after(c.clone());
			c.remove();
		}
	}else if(type==3){//置顶
		c.parents(".layui-upload-list:first").find(".layui-upload-album:first").before(c.clone());
		c.remove();
	}
}

/**
 * 分片上传
 * @returns  上传对象 需要时直接调用 .upload()  方法上传文件
 */
function chunkUpload(config){
	var def = {
		url:'',//上传路径
		pick:'',//文件选择按钮   ID或class
		fileSizeLimit:15*1024*1024,//文件大小  15m
		extensions:'jpg,png,mp4,xls,xlsx,doc,docx',//允许的文件后缀，不带点，多个用逗号分割
		
		formData:{},//文件上传请求的参数表，每次发送都会发送此对象中的参数
		
		beforeFileQueued:null,//当文件被加入队列之前触发方法
		fileQueued:null,//添加文件到队列触发方法
		uploadProgress:null,//文件上传过程中创建进度条显示方法  返回 进度
		uploadSuccess:null,//所有分片文件上传成功回调方法
		uploadError:null,//文件上传失败回调方法
		uploadComplete:null,//文件上传完成回调方法（不管成功或失败都会回调）
		beforeUploadSuccess:null,//所有分片文件上传成功准备合并分片文件前回调方法
	};
	
	config  = Object.assign(def, config);
	
	var uploader = WebUploader.create({
		server:config.url,
	    swf: '/admin/js/webuploader/Uploader.swf',
	    pick:config.pick,
	    fileSizeLimit:config.fileSizeLimit,//文件大小  15m
	    extensions:config.extensions,
	    formData:config.formData,
	    
	    // 开起分片上传。
	    chunked: true,
	    chunkSize:1024*1024,//1m
	    fileNumLimit:1
	});

	uploader.on( 'beforeFileQueued', function( file ) {
		console.log("当文件被加入队列之前触发");
		if(config.beforeFileQueued){
			config.beforeFileQueued(file);
		}
		uploader.reset();
	});

	uploader.on( 'fileQueued', function( file ) {
		console.log("添加文件到队列");
		if(config.fileQueued){
			config.fileQueued(file);
		}
	});

	uploader.on( 'uploadProgress', function( file, percentage ) {
		console.log("文件上传过程中创建进度条实时显示");
	    console.log(percentage * 100 + '%');
	    if(config.uploadProgress){
			config.uploadProgress(percentage.toFixed(2));
		}
	});

	uploader.on( 'uploadSuccess', function( file,response ) {
		console.log("上传成功");
		console.log(file,response);
		
		if(response.code!=0){
			layui.layer.msg(response.msg);
			return;
		}
		
		if(response.status=='loading'){
			if(config.beforeUploadSuccess){
				config.beforeUploadSuccess(response);
			}else{
				layui.layer.load(1, {content:'数据处理中...'});
				setTimeout(() => {
					$('.layui-layer-loading1').attr("style","width:120px;padding-left: 50px;padding-top: 5px");
				}, 300);
			}
			
			$.ajax({
				url:config.url,
				data:{id:file.id,name:file.name,type:file.type,size:file.size,chunks:1,ext:file.ext,status:'complete'},
				type:'POST',
				success:function(res){
					layui.layer.closeAll();
					if(config.uploadSuccess){
						config.uploadSuccess(res);
					}
				},
				error:function(res){
					layui.layer.closeAll();
					layui.layer.msg("服务器异常");
				}
			})
		}else{
			if(config.uploadSuccess){
				config.uploadSuccess(response);
			}
		}
	});

	uploader.on( 'uploadError', function( file ) {
		console.log("上传失败时触发的");
		if(config.uploadError){
			config.uploadError(file);
		}
	});

	uploader.on( 'uploadComplete', function( file ) {
		console.log("完成上传");
		if(config.uploadError){
			config.uploadError(file);
		}
		uploader.reset();
	});
	
	
	return uploader;
}


/**
 * 列表页初始化
 */
function listPageInit(config){
	var def = {
		name:'',//列表名称
		cols:[],//列表表头
		
		
		url:'/'+__controller+'/list',//列表数据请求路径
		delurl:'/'+__controller+'/del',//删除数据提交路径，此路径需要支持批量删除  参数  id 或 id[]
		importurl:'/pages/'+__controller+"/import",//导入操作页
		formPage:'/pages/'+__controller+"/form",//表单页 新增或修
		infoPage:'/pages/'+__controller+"/info",//详情页
		
		size: "sm", //用于设定表格尺寸，若使用默认尺寸不设置该属性即可
		height:'full-80',//表格默认高度
		defaultToolbar: [],//默认工具['filter', 'print', 'exports']
		exportTypes:['page','checked','all'], //导出类型   当前页，选中行，全部数据（分页时使用）
		
		dataTable:'dataTable',//数据表ID  不要加  # 或 .   与 lay-filter名称保持一样
		toolbar:'#toolbarTpl',//表头工具模板  如果需要导入功能请使用  #toolbarTpl2
		searchForm:'.ok-search',//搜索表单 form
		
		limit:100,//默认显示几条数据
		limits:[20,50,100,200,300],
		totalRow:false,//是否开启合计行区域
		page:true,//是否分页
		models:[],//追加加载layui控件
		
		afterPageInit:null,//初始化完成后执行的方法  传入 toolFunction  工具栏 事件方法  可在方法新增修改事件
		afterDel:null,//删除后执行方法  传入行数据
		searchAfter:null,//搜索表单完成后执行的方法,传入表单数据
		searchBefore:null,//搜索表单前执行的方法,传入表单数据，主要用于表单验证
		done:null //数据加载完成后执行的方法
	};
	
	config  = Object.assign(def, config);
	
	var models = ["element", "jquery", "table", "form", "laydate", "okLayer", "okUtils"];
	config.models.forEach(function(row){
		models.push(row);
	});
	
	
	layui.use(models, function () {
		let table = layui.table;
		let form = layui.form;
		let laydate = layui.laydate;
		let okLayer = layui.okLayer;
		let okUtils = layui.okUtils;
		let okMock = layui.okMock;
		let $ = layui.jquery;
		let toolFunction = {
			batchDel:function(){
				okLayer.confirm("确定要批量删除吗？", function (index) {
					layer.close(index);
					
					var checkStatus = table.checkStatus(config.dataTable);
		            var rows = checkStatus.data.length;
		            if (rows > 0) {
		                var ids = [];
		                for (var i = 0; i < checkStatus.data.length; i++) {
		                    ids.push(checkStatus.data[i].id);
		                }
		                
		                okUtils.ajax(config.delurl, "post", {id:ids}, true).done(function (response) {
							console.log(response);
							dataTable.reload();
						}).fail(function (error) {
							console.log(error)
						});
		            } else {
		                layer.msg("未选择有效数据", {offset: "t", anim: 6});
		            }
				});
			},
			add:function(){
				okLayer.open("添加"+config.name, config.formPage, "90%", "90%", null, function () {
					dataTable.reload();
				})
			},
			edit:function(data){
				okLayer.open("更新"+config.name, config.formPage+"?id="+data.id, "90%", "90%", null, function () {
					dataTable.reload();
				})
			},
			copy:function(data){
				okLayer.open("复制"+config.name, config.formPage+"?copy=1&id="+data.id, "90%", "90%", null, function () {
					dataTable.reload();
				})
			},
			info:function(data){
				okLayer.open(config.name+" 详情", config.infoPage+"?id="+data.id, "90%", "90%", null, function () {
					dataTable.reload();
				})
			},
			del:function(data){
				okLayer.confirm("确定要删除吗？", function (index) {
					layer.close(index);
					okUtils.ajax(config.delurl, "post", {id: data.id}, true).done(function (response) {
						console.log(response);
						dataTable.reload();
						if(config.afterDel){
							config.afterDel(data);
						}
					}).fail(function (error) {
						console.log(error)
					});
				})
			},
			showimg:function(data){
				okLayer.open("图片", data.imgurl, "60%", "60%", null, function () {
				})
			},
			importExcel:function(){
				okLayer.open("导入"+config.name, config.importurl, "60%", "60%", null, function () {
					dataTable.reload();
				})
			},
			//重写导出事件
			LAYTABLE_EXPORT:function(pages){
				console.log("导出事件总页数："+pages);
				var container = $("[lay-event=LAYTABLE_EXPORT] .layui-table-tool-panel");
				var html = '';
				if(config.exportTypes.indexOf("page")!=-1){
					html += '<li data-type="page">导出当前页</li>';
				}
				
				if(config.exportTypes.indexOf("checked")!=-1){
					html += '<li data-type="checked">导出选择中行</li>';
				}
				
				if(config.exportTypes.indexOf("all")!=-1){
					html += '<li data-type="all">导出所有数据</li>';
				}
				
				container.html(html);
				
				$("li",container).click(function(){
					var type = $(this).data("type");
					console.log("导出事件："+type);
					switch (type) {
					case 'page':
						table.exportFile(config.dataTable,null,"xls");
						break;
						
					case 'checked':
						var checkStatus = table.checkStatus(config.dataTable);
			            if (checkStatus.data.length > 0) {
			            	table.exportFile(config.dataTable,checkStatus.data,"xls");
			            } else {
			                layer.msg("未选择有效数据");
			            }
						
			            break;
					case 'all':
						allData=[];
						importExcelPage(1,pages);
						break;
					
					}
					
					container.html("");
					container.hide();
					
				});
			}
		}

		//日期处理
		$(".date").each(function(){
			$(this).attr("readonly","readonly");
			laydate.render({elem: this, type: "date"});
		});
		
		$(".datetime").each(function(){
			$(this).attr("readonly","readonly");
			laydate.render({elem: this, type: "datetime"});
		});
		
		$(".date-range").each(function(){
			$(this).attr("readonly","readonly");
			laydate.render({elem: this, type: "date",range:'~'});
		});
		
		$(".datetime-range").each(function(){
			$(this).attr("readonly","readonly");
			laydate.render({elem: this, type: "datetime",range:'~'});
		});
		
		
		let dataTable = table.render({
			title:config.name,
			elem: "#"+config.dataTable,
			url: config.url,
			method:"post",
			limit: config.limit,
			limits: config.limits,
			page: config.page,
			toolbar: config.toolbar,
			defaultToolbar:config.defaultToolbar,
			size: config.size,
			height:config.height,
			cols: config.cols,
			totalRow:config.totalRow,
			done: function (res, curr, count) {
				if(config.done){
					config.done(res, curr, count)
				}
			}
		});
		
		//下拉事件
		form.on('select', function(data){
		  $(data.elem).change();
		  layui.form.render("select");
		});

		//搜索
		form.on("submit(search)", function (data) {
			
			if(config.searchBefore){
				var check = config.searchBefore(data.field);
				if(!check){
					return false;
				}
			}
			
			var param = {where: data.field}
			
			if(config.page) param.page={curr:1};
			dataTable.reload(param);
			
			if(config.searchAfter){
				config.searchAfter(data.field)
			}
			
			return false;
		});

		table.on("toolbar("+config.dataTable+")", function (obj) {
			console.log(obj);
			//表头工具方法传入总页数
			try{
				eval("toolFunction."+obj.event+"("+obj.config.page.pages+")");
			}catch(e){
				console.error(e);
			}
			
		});

		table.on("tool("+config.dataTable+")", function (obj) {
			let data = obj.data;
			//行工具方法传入数据
			try{
				eval("toolFunction."+obj.event+"("+JSON.stringify(data)+")");
			}catch(e){
				
			}
		});
		
		table.on("sort("+config.dataTable+")", function(obj){ //注：sort 是工具条事件名，test 是 table 原始容器的属性 lay-filter="对应的值"
			  var data={};
			  $(config.searchForm).serializeArray().forEach(function(row){
				  data[row.name]=row.value;
			  });
			  data.o=obj.field+"|"+obj.type;
			  dataTable.reload({
			    initSort: obj //记录初始排序，如果不设的话，将无法标记表头的排序状态。
			    ,where: data
			  });
			  
		});
		
		
		
		var allData=[];
		function importExcelPage(page,total){
			if(page>total){
				table.exportFile(dataTable.config.id,allData,"xls");
				allData=[];
				return;
			}
			
			var data={};
			$(config.searchForm).serializeArray().forEach(function(row){
				data[row.name]=row.value;
			});
			data.page=page+"";
			data.limit = dataTable.config.limit+"";
			console.log(data);
			okUtils.ajax(config.url,"post",data,true).done(function(res){
				res.data.forEach(function(row){
					allData.push(row);
				});
				
				page+=1;
				importExcelPage(page,total);
			});
		}
		
		if(config.afterPageInit){
			config.afterPageInit(toolFunction);
		}
	})
}


/**
 * 表单页初始化
 */

var _formData = {};
function formPageInit(config){
	var def = {
		infourl:'/'+__controller+'/info',//表单数据请求路径
		addurl:'/'+__controller+'/add',//新增数据请求路径
		editurl:'/'+__controller+'/edit',//修改数据请求路径
		verify:{},//表单验证
		afterPageInit:null,//初始化完成后执行的方法  传入 
		afterSave:null,//保存成功后执行的方法
		beforeSave:null,//保存之前执行方法 传入表单数据  主要用于验证表单  返回true 继续执行,否则中断执行
		
		form:'formFilter',//表单过滤名称  form 属性 lay-filter="formFilter"
		saveBtn:'save',//表单内的保存按钮  按钮属性  lay-filter="save"
		models:[],//追加加载layui控件
	};
	
	config  = Object.assign(def, config);
	
	var models = ["element", "form", "laydate", "okLayer", "okUtils", "jquery","xmSelect",'upload','croppers'];
	config.models.forEach(function(row){
		models.push(row);
	});
	//表单验证
	var verify={
			required:[/[\S]+/,"必填项不能为空"],
			phone:[/(^$)|^1\d{10}$/,"请输入正确的手机号"],
			email:[/(^$)|^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/,"邮箱格式不正确"],
			url:[/(^$)|(^#)|(^http(s*):\/\/[^\s]+\.[^\s]+)/,"链接格式不正确"],
			number:function(e){if(e && isNaN(e))return"只能填写数字"},
			date:[/(^$)|^(\d{4})[-\/](\d{1}|0\d{1}|1[0-2])([-\/](\d{1}|0\d{1}|[1-2][0-9]|3[0-1]))*$/,"日期格式不正确"],
			identity:[/(^$)|(^\d{15}$)|(^\d{17}(x|X|\d)$)/,"请输入正确的身份证号"]
	};
	
	config.verify = Object.assign(verify, config.verify);
	
	layui.config({
        base: '/admin/js/cropper/' //layui自定义layui组件目录
    }).use(models, function () {
		let form = layui.form;
		let laydate = layui.laydate;
		let okLayer = layui.okLayer;
		let okUtils = layui.okUtils;
		let croppers = layui.croppers;
		let upload = layui.upload;
		let $ = layui.jquery;
		
		var id = getQueryParam("id");
		
		//初始化页面渲染
		function afterPageInit(){
			//日期处理
			$(".date").each(function(){
				$(this).attr("readonly","readonly");
				laydate.render({elem: this, type: "date",trigger:'click'});
			});
			
			$(".datetime").each(function(){
				$(this).attr("readonly","readonly");
				laydate.render({elem: this, type: "datetime",trigger:'click'});
			});
			
			$(".date-range").each(function(){
				$(this).attr("readonly","readonly");
				laydate.render({elem: this, type: "date",range:'~'});
			});
			
			$(".datetime-range").each(function(){
				$(this).attr("readonly","readonly");
				laydate.render({elem: this, type: "datetime",range:'~'});
			});
			
			//下拉事件
			form.on('select', function(data){
			  $(data.elem).change();
			  layui.form.render("select");
			});
			
			//富文本
			$("textarea.html").each(function() {
				//$(this).css("width","720px");
				var myid = $(this).attr("id")
				if (!myid) {
					myid = uuid()
					$(this).attr("id", myid)
				}
				UE.getEditor(myid)
			})
			
			//图片上传（带裁剪）
			$(".cutUpload").each(function(){
		    	var width = $(this).attr("width");
		    	var height = $(this).attr("height");
		    	var _this = this;
		    	
		    	
		    	//创建一个头像上传组件
		        croppers.render({
		            elem: _this
		            ,saveW:width     //保存宽度
		            ,saveH:height
		            ,mark:width/height    //选取比例
		            ,area:'900px'  //弹窗宽度
		            ,url: "/"+__controller+"/upload"  //图片上传接口返回和（layui 的upload 模块）返回的JOSN一样
		            ,done: function(res){ //上传完毕回调
		            	$('.thumbImg',_this).attr('src',res.url);
		          	 	$(_this).css("background","#fff");
		          	 	var name = $(_this).attr("name");
		          	 	if(!name) name = "imgurl";
		            	$(":hidden[name='"+name+"']").remove();
		            	$(_this).after('<input type="hidden" name="'+name+'" value="'+res.url+'" />');
		            }
		        });
		    });

			//图片上传 （普通）
		    upload.render({
		        elem: '.imgUpload',
		        url: "/"+__controller+"/upload",
		        before: function(obj){
		        	loadIndex=layer.load(2);
		        },
		        done: function(res, index, upload){
		        	layer.close(loadIndex);
		          	  //上传完毕
		              if(res.url){
		                  var o = $(this.item);
		            	  $('.thumbImg',o).attr('src',res.url);
		            	  o.css("background","#fff");
		              	  var name = o.attr("name");
		              	  if(!name) name = "imgurl";
		              	  $(":hidden[name='"+name+"']").remove();
		                  o.after('<input type="hidden" name="'+name+'" value="'+res.url+'" />');
		              }
		        }
		    });
		    
		    //图集上传 （普通）
		    upload.render({
			      elem: '.imgs-upload-btn',
			      url: "/"+__controller+"/upload",
			      multiple: true,
			      before: function(obj){
			      	loadIndex=layer.load(2);
			      },
			      done: function(res){
			    	  layer.close(loadIndex);
			    	  //上传完毕
			          if(res.url){
			        	  var item = this.item;
			        	  var name = $(item).attr("name");
			        	  var html = createImgsHtml(res.url,name);
			        	  var o = $(this.item).parents(".layui-upload:first");
			        	  $('.layui-upload-list',o).append(html);
			          }
			      }
			});
		    
		    if(_formData){
		    	//下拉默认值
		    	$('select[def]').each(function(){
		    		var name = $(this).attr("name");
		    		$(this).attr('def',_formData[name]);
		    	});
		    	
			    //编辑时图片处理
				$(":hidden[name=logo],:hidden[name=imgurl],:hidden[name=img]").each(function(){
					var name = $(this).attr("name");
					var content = $(this).parents(".thumbBox:first");
					$("img",content).attr("src",_formData[name]);
				});
				
				//编辑时图集处理
				$(".imgs-upload-btn").each(function(){
					var name = $(this).attr("name");
					var o = $(this).parents(".layui-upload:first");
					var imgs = _formData[name];
					
					if(imgs){
						if(typeof(imgs)=='string'){
							imgs = JSON.parse(imgs);
						}
						
						imgs.forEach(function(row){
							var html = createImgsHtml(row,name);
				        	$('.layui-upload-list',o).append(html);
						});
						
					}
				});
		    }
		    
		    
		    if(config.afterPageInit){
				config.afterPageInit();
			}
		}
		
		if(id && config.infourl){
			//获取页面数据
			okUtils.ajax(config.infourl, "post", {id:id}, true).done(function (res) {
				var copy = getQueryParam("copy");
				if(copy){//复制
					res.data.id="";
				}else{
					_formData = res.data;
				}
				
				form.val(config.form, res.data);
				afterPageInit()
			})
		}else{
			afterPageInit()
		}

		form.verify(config.verify);

		form.on("submit("+config.saveBtn+")", function (data) {
			
			var check = true;
			if(config.beforeSave){
				check = config.beforeSave(data.field);
			}
			
			if(!check) return false;
			
			var url = config.addurl;
			if(data.field.id) url = config.editurl;
			okUtils.ajax(url, "post", data.field, true).done(function (response) {
				console.log(response);
				okLayer.greenTickMsg("保存成功", function () {
					if(config.afterSave){
						config.afterSave();
					}else{
						parent.layer.close(parent.layer.getFrameIndex(window.name));
					}
				});
			}).fail(function (error) {
				console.log(error)
			});
			return false;
		});
		
	})
	
}


/**
 * 省市区三级联动
 * @param province  表单下拉省份 name
 * @param city	表单下拉城市 name
 * @param area	表单下拉区县 name
 * @returns
 */
function provinceCityArea(province,city,area){
	$ = layui.jquery;
	var provinceObj = $("select[name="+province+"]");
	var cityObj = $("select[name="+city+"]");
	var areaObj = $("select[name="+area+"]");
	//初始化省份下拉数据
	loadSelectData(provinceObj);
	
	//省份数据改变事件
	provinceObj.change(function(){
		cityObj.find('option:not([value=""])').remove();
		areaObj.find('option:not([value=""])').remove();
		loadSelectData(cityObj,{area_index:this.value});
	});

	//城市数据改变事件
	cityObj.change(function(){
		areaObj.find('option:not([value=""])').remove();
		
		var area_index = provinceObj.val()+","+this.value;
		
		loadSelectData(areaObj,{area_index:area_index});
	});
	
	//如果表单有值 加载默认值
	if(_formData){
		if(_formData.province_code){
			loadSelectData(cityObj,{area_index:_formData.province_code});
			
			if(_formData.city_code){
				loadSelectData(areaObj,{area_index:_formData.province_code+","+_formData.city_code});
			}
		}
	}
}

/**
 * 加载下拉框选项数据 （select 标签必要属性 url=数据请求地址  def=默认值）
 * @param obj 下拉框 jquery 对象
 * @param param 请求数据时的参数  非必须
 * @returns
 */
function loadSelectData(obj,param){
	if(!param) param={};
	
	var url = obj.attr("url");
	var def = obj.attr("def");
	
	layui.okUtils.ajax(url,"post",param,true).done(function(res){
		  var html = obj.children("option:first");
		  res.data.forEach(function(row){
			  html+='<option value="'+row.value+'">'+row.name+'</option>';
		  });
		  
		  obj.find('option[value!="0"]:not([value=""])').remove();
		  obj.append(html);
		  obj.val(def);
		  layui.form.render('select');
	})
}


function openTab(id,name,url){
	var $ = layui.jquery;
	var html = '<a lay-id="0_'+id+'" data-url="'+url+'" is-close="true"><i class="ok-icon">&#xe6b7;</i><cite>'+name+'</cite></a>';
	var okTab = layui.okTab();
	okTab.tabAdd($(html));
}