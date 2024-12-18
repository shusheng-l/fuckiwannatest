define(["jquery", "easy-admin"], function ($, ea) {


    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.user/index',
        modify_url: 'system.user/modify',
        showinfo_url: 'system.user/showinfo',
        money_url: 'system.user/money',
        repaypw_url: 'system.user/repaypw',
    };

    var Controller = {
        index: function () {
            var util = layui.util;
            ea.table.render({
                init: init,
                toolbar: ['refresh'],
                cols: [[
                    {field: 'id', width: 80, title: 'id', search: false},
                    {field: 'telnum', minWidth: 80, title: '手机号'},
                    {field: 'status', title: '用户状态', width: 100, search: 'select', selectList: {0: '禁用', 1: '启用'}, templet: ea.table.switch},
                    /*{field: 'account_status', title: '账户状态', width: 100, search: 'select', selectList: {0: '冻结', 1: '正常'}, templet: ea.table.switch},*/
                    {field: 'device', minWidth: 80, title: '登录设备'},
                    {field: 'reg_ip', minWidth: 80, title: '注册IP地址'},
                    {field: 'reg_time', minWidth: 80, title: '注册时间', search: 'range'},
                    {field: 'last_ip', minWidth: 80, title: '最后登录IP地址'},
                    {field: 'last_time', minWidth: 80, title: '最后登录时间', search: 'range'},
                    {
                        width: 250,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            [{
                                text: '查看资料',
                                url: init.showinfo_url,
                                auth:"showinfo",
                                method: 'open',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                            }],
                            'money',
                            [{
                                text: '用户账户',
                                url: init.money_url,
                                method: 'open', //打开页面编辑
                                auth: 'money',
                                class: 'layui-btn layui-btn-danger layui-btn-xs',
                            }],
                            'repaypw',
                            [{
                                text: '重置密码',
                                url: init.repaypw_url,
                                method: 'get', //get方法传送
                                auth: 'repaypw',
                                class: 'layui-btn layui-btn-danger layui-btn-xs',
                            }],
                        ]
                    }
                ]]
                ,limits: [25,50,100,250]
            });
            ea.listen();
        },
        showinfo: function () {

        },
        repaypw: function () {
            ea.listen();
        },
        money: function () {
            ea.listen();
            $("#toSure").click(function(){ //手动充值
                if($("#amount").val() == "")
                {
                    layer.msg("请输入充值数量!");
                    return;
                }
                layer.confirm('您是进行充值吗？', {
                    btn: ['确定','取消'] //按钮
                }, function(){
                    $.ajax({
                        url: '/admin/system.order/savemoney',
                        type: 'post',
                        // 设置的是请求参数
                        data: { uid: $("#uid").val(), amount: $("#amount").val() },
                        dataType: 'json',
                        success: function (res) {
                            if(res.code == 1)
                            {
                                window.location.reload();
                            } else {
                                layer.msg("充值失败!");
                            }
                        }
                    })
                });
            });
            $(".recharegeSure").click(function(){ //充值确认
                let id = $(this).attr("data-id");
                let type = $(this).attr("data-type");
                let status = $(this).attr("data-status");
                layer.confirm('您是进行此操作吗？', {
                    btn: ['确定','取消'] //按钮
                }, function(){
                    $.ajax({
                        url: '/admin/system.order/czqxs',
                        type: 'post',
                        // 设置的是请求参数
                        data: {id:id,bz:type,sts:status},
                        dataType: 'json',
                        success: function (res) {
                            if(res.code == 1)
                            {
                                window.location.reload();
                            } else {
                                layer.msg("充值失败!");
                            }
                        }
                    })
                });
            });
            $(".recharegeDelete").click(function(){ //充值删除
                let id = $(this).attr("data-id");
                let type = $(this).attr("data-type");
                layer.confirm('您是进行删除吗？', {
                    btn: ['确定','取消'] //按钮
                }, function(){
                    $.ajax({
                        url: '/admin/system.order/dellog',
                        type: 'post',
                        // 设置的是请求参数
                        data: {id:id,bz:type},
                        dataType: 'json',
                        success: function (res) {
                            if(res.code == 1)
                            {
                                window.location.reload();
                            } else {
                                layer.msg("删除失败!");
                            }
                        }
                    })
                });
            });
        }
    };

    return Controller;
});