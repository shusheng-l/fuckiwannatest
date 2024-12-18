define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.noticelist/index',
        add_url: 'system.notice/add',
        edit_url: 'system.notice/edit',
        delete_url: 'system.notice/delete',
        export_url: 'system.notice/export',
        modify_url: 'system.notice/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'id'},
                    {field: 'telnum', minWidth: 80, title: '用户'},
                    {field: 'title', minWidth: 80, title: '标题'},
                    {field: 'content', minWidth: 80, title: '内容'},
                    {field: 'is_read', title: '状态', width: 85, search: 'is_read', selectList: {1: '未读', 2: '已读'}},
                    {field: 'create_time', minWidth: 80, title: '创建时间', search: 'range'},
                    {field: 'read_time', minWidth: 80, title: '阅读时间', search: 'range'},
                ]],
            });

            ea.listen();
        },
        add: function () {
            ea.listen();
        },
        edit: function () {
            ea.listen();
        },
    };
    return Controller;
});