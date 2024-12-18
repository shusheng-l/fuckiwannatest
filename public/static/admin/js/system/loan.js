define(["jquery", "easy-admin"], function ($, ea) {


    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.loan/index',
        showinfo_url: 'system.loan/showinfo',
        resetinfo_url: 'system.loan/resetinfo',
        addqq_url: 'system.loan/addqq',
        pass_url: 'system.loan/secondaudit', //和二审通过方法一致
        reject_url: 'system.loan/reject',
        secondaudit_url: 'system.loan/secondaudit', //和直接通过方法一致
    };

    var Controller = {
        index: function () {
            var util = layui.util;
            ea.table.render({
                init: init,
                toolbar: ['refresh'],
                cols: [[
                    {field: 'id', width: 60, title: 'id', search: false},
                    {field: 'username', minWidth: 60, title: '用户名'},
                    {field: 'telnum', minWidth: 100, title: '手机号'},
                    {field: 'adminusername', minWidth: 60, title: '所属客服'},
                    {field: 'device', minWidth: 100, title: '登录设备', search: false},
                    {field: 'data_status', width: 100, title: '资料状态',align: 'center', search: false, selectList: {0: '未完善', 1: '已完善'}},
                    {
                        field: 'status', width: 120, align: 'center', search: 'select', selectList: {"-1": "未通过审核",0: '等待申请额度', 1: '等待审核',2: "审核通过"}, templet: function (d) {
                            if (d.status == 0) {
                                return '等待申请额度';
                            } else if(d.status == 1) {
                                return '等待审核';
                            } else if(d.status == -1) {
                                return '未通过审核';
                            } else {
                                return '审核通过';
                            }
                        }, title: '审核结果'
                    },
                    {field: 'amount', minWidth: 100, title: '预期金额', search: false},
                    {field: 'quotama', minWidth: 100, title: '审批金额', search: false},
                    {
                        field: 'qbtx', width: 100, align: 'center', search: 'select', selectList: {0: '未提现', 1: '已提现'}, templet: function (d) {
                            if (d.qbtx == 0) {
                                return '未提现';
                            } else {
                                return '已提现';
                            }
                        }, title: '提现状态'
                    },
                    {
                        width: 400,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            'showinfo',
                            [{
                                text: '查看资料',
                                url: init.showinfo_url,
                                auth:"showinfo",
                                method: 'open',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                            }],
                            'resetinfo',
                            [{
                                text: '重置资料',
                                url: init.resetinfo_url,
                                status: 2,
                                method: 'open',
                                checkFiled: 'status', //需要验证的字段
                                checkType: 6, //不等于
                                checkCondition: 2, //需要验证的条件
                                auth: 'resetinfo',
                                class: 'layui-btn layui-btn-success layui-btn-xs',
                            }],
                            /*'addqq',
                            [{
                                text: '添加客服',
                                url: init.addqq_url,
                                method: 'open',
                                auth: 'addqq',
                                class: 'layui-btn layui-btn-success layui-btn-xs',
                            }],*/
                            'pass',
                            [{
                                text: '通过并授额',
                                url: init.pass_url,
                                method: 'open', //打开页面编辑
                                checkFiled: 'status', //需要验证的字段
                                checkType: 1, //等于
                                checkCondition: 1, //需要验证的条件
                                auth: 'secondaudit',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                            }],
                            'reject',
                            [{
                                text: '不符合条件',
                                url: init.reject_url,
                                method: 'get', //get方法传送
                                checkFiled: 'status', //需要验证的字段
                                checkType: 1, //等于
                                checkCondition: 1, //需要验证的条件
                                auth: 'reject',
                                class: 'layui-btn layui-btn-danger layui-btn-xs',
                            }],
                            'secondaudit',
                            [{
                                text: '二审通过并授额',
                                url: init.secondaudit_url,
                                method: 'open', //get方法传送
                                checkFiled: 'status', //需要验证的字段
                                checkType: 1, //等于
                                checkCondition: -1, //需要验证的条件
                                auth: 'secondaudit',
                                class: 'layui-btn layui-btn-normal layui-btn-xs',
                            }],
                        ]
                    }
                ]]
                ,limits: [25,50,100,250]
            });
            ea.listen();
        },
        showinfo: function () {
            ea.listen();
        },
        resetinfo: function () {
            ea.listen();
        },
        addqq: function () {
            ea.listen();
        },
        pass: function () {
            ea.listen();
        },
        reject: function () {
            ea.listen();
        },
        secondaudit: function () {
            ea.listen();
        },
    };

    return Controller;
});