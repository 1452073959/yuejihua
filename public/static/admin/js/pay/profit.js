define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'pay.profit/index',
        add_url: 'pay.profit/add',
        edit_url: 'pay.profit/edit',
        delete_url: 'pay.profit/delete',
        export_url: 'pay.profit/export',
        modify_url: 'pay.profit/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: 'checkbox'},
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