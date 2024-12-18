define(["jquery", "easy-admin"], function ($, ea) {


    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.withdraw/index',
        passmoney_url: 'system.withdraw/passmoney',
        rejectmoney_url: 'system.withdraw/rejectmoney',
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
                    {field: 'qbmoney', minWidth: 80, title: '余额', search: false},
                    {field: 'money', minWidth: 80, title: '金额', search: false},
                    {field: 'status', title: '提现状态', width: 100, search: 'select', selectList: {0: '未审核', 1: '已通过', 2: "已驳回"}, templet: function (d) {
                        if (d.status == 0) {
                            return '未审核';
                        } else if(d.status == 1) {
                            return '已通过';
                        } else {
                            return '已驳回';
                        }
                    }},
                    {field: 'add_time', minWidth: 80, title: '申请时间', search: 'range'},
                    {
                        width: 250,
                        title: '操作',
                        templet: ea.table.tool,
                        operat: [
                            'passmoney',
                            [{
                                text: '通过',
                                url: init.passmoney_url,
                                method: 'get', //打开页面编辑
                                checkFiled: 'status', //需要验证的字段
                                checkType: 1, //等于
                                checkCondition: 0, //需要验证的条件
                                auth: 'passmoney',
                                class: 'layui-btn layui-btn-success layui-btn-xs',
                            }],
                            'rejectmoney',
                            [{
                                text: '驳回',
                                url: init.rejectmoney_url,
                                method: 'get', //打开页面编辑
                                checkFiled: 'status', //需要验证的字段
                                checkType: 1, //等于
                                checkCondition: 0, //需要验证的条件
                                auth: 'rejectmoney',
                                class: 'layui-btn layui-btn-success layui-btn-xs',
                            }],
                        ]
                    }
                ]],
            });

            ea.listen();
        },
        passmoney: function () {
            ea.listen();
        },
        rejectmoney: function () {
            ea.listen();
        }

    };

    return Controller;
});