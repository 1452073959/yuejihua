define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'admin.user/index',
        add_url: 'admin.user/add',
        edit_url: 'admin.user/edit',
        delete_url: 'admin.user/delete',
        export_url: 'admin.user/export',
        modify_url: 'admin.user/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: 'checkbox'},
                    {field: 'id', title: 'id'},
                    {field: 'name', title: '姓名'},
                    {field: 'phone', title: '电话'},
                    {field: 'password', title: '密码'},
                    {field: 'user_img', title: '头像'},
                    {field: 'vip_label', title: 'vip'},
                    {field: 'user_code', title: '权益码'},
                    {field: 'pid', title: 'pid'},
                    {field: 'create_time', title: '创建时间'},
                    {width: 250, title: '操作', templet: ea.table.tool},
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