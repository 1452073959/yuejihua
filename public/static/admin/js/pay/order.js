define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'pay.order/index',
        add_url: 'pay.order/add',
        edit_url: 'pay.order/edit',
        delete_url: 'pay.order/delete',
        export_url: 'pay.order/export',
        modify_url: 'pay.order/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: 'checkbox'},                    {field: 'id', title: 'id'},                    {field: 'card_id', title: 'card_id'},                    {field: 'user_id', title: 'user_id'},                    {field: 'orderNo', title: 'orderNo'},                    {field: 'rate', title: 'rate'},                    {field: 'pay_status', title: '1成功2失败'},                    {field: 'create_time', title: 'create_time'},                    {width: 250, title: '操作', templet: ea.table.tool},
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