"use strict";
layui.use(["okUtils", "table", "okCountUp", "okMock"], function () {
    var countUp = layui.okCountUp;
    var table = layui.table;
    var okUtils = layui.okUtils;
    var okMock = layui.okMock;
    var $ = layui.jquery;
    var user_id = getQueryParam("id");

    function getUserAgent() {
        okUtils.ajax("/user/info","post",{id:user_id},true).done(function(res){
            if(res.data.is_agent == 1){
                getTeamInfoAgent();//代理团队信息
            }else{
                getTeamInfoNormal();//正常三级团队信息
            }
        })
    }

    /**
     * 代理团队信息
     */
    function getTeamInfoAgent() {
    	okUtils.ajax("/user/getTeamInfoAgent","post",{user_id:user_id},true).done(function(res){
            $('.recharge_total_agent').html(res.data.rechargeTotalAgent);
            $('.withdraw_total_agent').html(res.data.withdrawTotalAgent);
            $('.cash_today_agent').html(res.data.cashTodayAgent);
            $('.withdraw_today_agent').html(res.data.withdrawTodayAgent);
            $('.recharge_count_agent').html(res.data.rechargeCountAgent);
            $('.withdraw_count_agent').html(res.data.withdrawCountAgent);
            $('.team_count_agent').html(res.data.teamCountAgent);
            $('.team_balance_agent').html(res.data.teamBalanceAgent);

            $('.total_agent').show();
    	})
    }

    /**
     * 正常三级团队信息
     */
    function getTeamInfoNormal() {
        okUtils.ajax("/user/getTeamInfoNormal","post",{user_id:user_id},true).done(function(res){
            $('.recharge_total_normal').html(res.data.rechargeTotalNormal);
            $('.withdraw_total_normal').html(res.data.withdrawTotalNormal);
            $('.team_count_normal').html(res.data.teamCountNormal);
            $('.team_balance_normal').html(res.data.teamBalanceNormal);

            $('.total_normal').show();
        })
    }

    getUserAgent();//获取用户类型
});


