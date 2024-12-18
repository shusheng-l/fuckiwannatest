define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'system.orderstatus/index',
        add_url: 'system.orderstatus/add',
        edit_url: 'system.orderstatus/edit',
        delete_url: 'system.orderstatus/delete',
        export_url: 'system.orderstatus/export',
        modify_url: 'system.orderstatus/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: "checkbox"},
                    {field: 'id', width: 80, title: 'id'},
                    {field: 'sort', width: 80, title: '排序', edit: 'text'},
                    {field: 'title', minWidth: 80, title: '状态名'},
                    {field: 'status_title', minWidth: 80, title: '状态标题'},
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