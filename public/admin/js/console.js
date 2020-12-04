"use strict";
layui.use(["okUtils", "table", "okCountUp", "okMock"], function () {
    var countUp = layui.okCountUp;
    var table = layui.table;
    var okUtils = layui.okUtils;
    var okMock = layui.okMock;
    var $ = layui.jquery;

    /**
     * 统计
     */
    function statText() {
    	okUtils.ajax("/index/totalReport","post",{},true).done(function(res){
    		for(var i in res.data){
    			$(".total ."+i).text(res.data[i]);
    		}
    	})
        
    }

    function registReport(){
    	okUtils.ajax("/index/registReport","post",{},true).done(function(res){
    		var option = {
			    xAxis: {
			        type: 'category',
			        data: res.data.x
			    },
			    yAxis: {
			        type: 'value'
			    },
			    series: [{
			        data: res.data.y,
			        type: 'bar'
			    }]
			};
    		var registMap = echarts.init($("#registMap")[0], "theme");
    		registMap.setOption(option);
            okUtils.echartsResize([registMap]);
    	})
        
    }
    
    function loginReport(){
    	okUtils.ajax("/index/loginReport","post",{},true).done(function(res){
    		var option = {
			    xAxis: {
			        type: 'category',
			        data: res.data.x
			    },
			    yAxis: {
			        type: 'value'
			    },
			    series: [{
			        data: res.data.y,
			        type: 'bar'
			    }]
			};
    		var loginMap = echarts.init($("#loginMap")[0], "theme");
    		loginMap.setOption(option);
            okUtils.echartsResize([loginMap]);
    	})
        
    }
    
    function rechargeMoneyReport(){
    	okUtils.ajax("/index/rechargeMoneyReport","post",{},true).done(function(res){
    		var option = {
			    xAxis: {
			        type: 'category',
			        data: res.data.x
			    },
			    yAxis: {
			        type: 'value'
			    },
			    series: [{
			        data: res.data.y,
			        type: 'bar'
			    }]
			};
    		var map = echarts.init($("#rechargeMoneyMap")[0], "theme");
    		map.setOption(option);
            okUtils.echartsResize([map]);
    	})
        
    }
    
    function rechargeCountReport(){
    	okUtils.ajax("/index/rechargeCountReport","post",{},true).done(function(res){
    		var option = {
			    xAxis: {
			        type: 'category',
			        data: res.data.x
			    },
			    yAxis: {
			        type: 'value'
			    },
			    series: [{
			        data: res.data.y,
			        type: 'bar'
			    }]
			};
    		var map = echarts.init($("#rechargeCountMap")[0], "theme");
    		map.setOption(option);
            okUtils.echartsResize([map]);
    	})
        
    }


    statText();
    registReport();
    loginReport();
    rechargeMoneyReport();
    rechargeCountReport();
});


