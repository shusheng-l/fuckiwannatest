define(["jquery", "easy-admin"], function ($, ea) {

    var form = layui.form;
    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.order/index',
        showinfo_url: 'system.order/showinfo',
        changestatus_url: 'system.order/changestatus',
        changemoney_url: 'system.order/changemoney',
        contract_url: 'system.order/contract',
        changecard_url: 'system.order/changecard',
        deleter_url: 'system.order/deleter',
        remark_url: 'system.order/remark',
        loaninfo_url: 'system.order/loaninfo',
        movescreen_url: '/admin/screen/movescreen',
        saftscreen_url: '/admin/screen/saftscreen',
        doaccount_url: 'system.order/doaccount',
    };

    var Controller = {
        index: function () {
            var util = layui.util;
            ea.table.render({
                init: init,
                toolbar: ['refresh'],
                cols: [[
                    {field: 'id', width: 60, title: 'id', search: false},
                    /*{field: 'oid', minWidth: 220, title: '订单号'},*/
                    {field: 'telnum', minWidth: 120, title: '手机号码',templet: ea.table.tool,
                        operat: [
                            'showinfo',
                            [{
                                change_title: "telnum", //让动态变量可以有操作
                                title: "查看资料",
                                url: init.showinfo_url,
                                method: 'open', //打开页面编辑
                                auth: 'showinfo',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                            }]
                    ]},
                    {field: 'username', minWidth: 100, title: '姓名', search: false},
                    {field: 'adminusername', minWidth: 60, title: '所属客服'},
                    {field: 'dwname', minWidth: 100, title: '贷款用途'},
                    /*{field: 'money', minWidth: 120, title: '金额', search: false},
                    {field: 'allLoanMoney', minWidth: 120, title: '总利息', search: false},
                    {field: 'interest', minWidth: 120, title: '月利率', search: false},
                    {field: 'time', minWidth: 100, title: '期限', search: false},
                    {field: 'quotama', minWidth: 120, title: '每期', search: false},*/
                    /*{field: 'withdraw_time', minWidth: 130, title: '提现时间', search: 'range'},*/
                    {field: 'qbmoney', minWidth: 100, title: '钱包余额', search: false,templet: ea.table.tool,
                        operat: [
                            'changemoney_1',
                            [{
                                change_title: "qbmoney", //让动态变量可以有操作
                                title: "钱包管理",
                                url: init.changemoney_url,
                                method: 'open', //打开页面编辑
                                auth: 'changemoney',
                                class: 'layui-btn layui-btn-danger layui-btn-xs',
                            }]
                        ]},
                    /*{
                        width: 100,
                        title: '钱包管理',
                        templet: ea.table.tool,
                        operat: [
                            'changemoney_1',
                            [{
                                text: '钱包管理',
                                url: init.changemoney_url,
                                method: 'open', //打开页面编辑
                                auth: 'changemoney',
                                class: 'layui-btn layui-btn-danger layui-btn-xs',
                            }]
                        ]
                    },*/
                    /*{field: 'account_status', title: '钱包状态', width: 100, search: 'select', selectList: {0: '冻结', 1: '正常'}, templet: ea.table.switch},*/
                    {
                        width: 100,
                        title: '提现操作',
                        templet: ea.table.tool,
                        operat: [
                            'do_account',
                            [{
                                text: '关闭提现',
                                url: init.doaccount_url,
                                method: 'get', //打开页面编辑
                                checkFiled: 'account_status', //需要验证的字段
                                checkType: 1, //等于
                                checkCondition: 1, //需要验证的条件
                                auth: 'do_account',
                                class: 'layui-btn layui-btn-danger layui-btn-xs',
                            }],
                            'do_account',
                            [{
                                text: '开启提现',
                                url: init.doaccount_url,
                                method: 'get', //打开页面编辑
                                checkFiled: 'account_status', //需要验证的字段
                                checkType: 1, //等于
                                checkCondition: 0, //需要验证的条件
                                auth: 'do_account',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                            }],
                        ]
                    },
                    /*{field: 'sign', minWidth: 100, title: '签名', search: false, templet: ea.table.image},*/
                    {field: 'dbt', minWidth: 130, title: '订单状态', search: false,templet: ea.table.tool,
                        operat: [
                            'changestatus',
                            [{
                                change_title: "dbt", //让动态变量可以有操作
                                title: "订单状态",
                                url: init.changestatus_url,
                                method: 'open', //打开页面编辑
                                auth: 'changestatus',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                            }]
                        ]},
                    {
                        width: 400,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            'loaninfo',
                            [{
                                text: "借款详情",
                                url: init.loaninfo_url,
                                method: 'open',
                                auth: 'contract',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                            }],
                            /*'changestatus',
                            [{
                                text: "订单状态",
                                url: init.changestatus_url,
                                method: 'open',
                                auth: 'changestatus',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                            }],*/
                            'contract',
                            [{
                                text: "合同",
                                url: init.contract_url,
                                method: 'open',
                                auth: 'contract',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                            }],
                            /*[{
                                text: '查看资料',
                                url: init.showinfo_url,
                                auth:"showinfo",
                                method: 'open',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                            }],*/
                            'changecard',
                            [{
                                text: "改卡",
                                url: init.changecard_url,
                                method: 'open',
                                auth: 'changecard',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                            }],
                            'deleter',
                            [{
                                text: "删除",
                                url: init.deleter_url,
                                method: 'get',
                                auth: 'deleter',
                                class: 'layui-btn layui-btn-success layui-btn-xs',
                            }],
                            [{
                                text: "转账截图",
                                url: init.movescreen_url,
                                method: 'openew',
                                auth: 'remark',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                            }],
                            [{
                                text: "保险截图",
                                url: init.saftscreen_url,
                                method: 'openew',
                                auth: 'remark',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                            }],
                            /*'remark',
                            [{
                                text: "备注",
                                url: init.remark_url,
                                method: 'open',
                                auth: 'remark',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                            }],*/
                        ]
                    }
                ]]
                ,limits: [25,50,100,250]
            });
            ea.listen();

        },
        resetinfo: function () {
            ea.listen();
        },
        do_account: function () {
            ea.listen();
        },
        deleter: function () {
            ea.listen();
        },
        remark: function () {
            ea.listen();
        },
        changemoney: function () {
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
        },
        changestatus: function () {
            ea.listen();
            inputContent();//初始化
            form.on('select(status)', function(data){ //监听下拉
                inputContent();
            })
        },
        contract: function () {
            ea.listen();
        },
        loaninfo: function () {
            ea.listen();
        },
        changecard: function () {
            ea.listen();
        },
    };
    //获取选中的下拉，填充相关输入框
    function inputContent() {
        let color = $("#status option:selected").attr("data-color"); //颜色
        let statusTitle = $("#status option:selected").attr("data-status-title"); //状态标题
        let content = $("#status option:selected").attr("data-content"); //内容
        let remark = $("#status option:selected").attr("data-remark"); //备注
        $("#color").val(color);
        $("#idbt").val(statusTitle);
        $("#ixsm").val(content);
        $("#ixbzmark").val(remark);
    }

    return Controller;
});