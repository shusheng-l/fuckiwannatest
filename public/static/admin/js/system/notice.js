define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.notice/index',
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
                    {field: 'sort', width: 80, title: '排序', edit: 'text'},
                    {field: 'title', minWidth: 80, title: '模板名称'},
                    {field: 'remark', minWidth: 80, title: '内容'},
                    {field: 'type', title: '所属状态', width: 100, search: 'type', selectList: {1: '审核中',2: '审核通过',3: '审核通过',4: '失败状态'}},
                    {field: 'status', title: '状态', width: 85, search: 'select', selectList: {0: '禁用', 1: '启用'}, templet: ea.table.switch},
                    {field: 'create_time', minWidth: 80, title: '创建时间', search: 'range'},
                    {width: 250, title: '操作', templet: ea.table.tool}
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