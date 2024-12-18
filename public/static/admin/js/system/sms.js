define(["jquery", "easy-admin"], function ($, ea) {


    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.sms/index',
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
                    {field: 'code', minWidth: 80, title: '验证码'},
                    {field: 'content', minWidth: 80, title: '短信内容'},
                    {field: 'send_time', minWidth: 80, title: '发送时间', search: 'range'},
                    {field: 'status', title: '短信状态', width: 100, search: 'select', selectList: {0: '未使用', 1: '已使用'}},
                ]],
            });

            ea.listen();
        },
    };

    return Controller;
});