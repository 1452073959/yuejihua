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
                    {type: 'checkbox'},                    {field: 'id', title: 'id'},                    {field: 'user_id', title: 'user_id'},                    {field: 'order_id', title: 'order_id'},                    {field: 'up_id', title: 'up_id'},                    {field: 'card_id', title: 'card_id'},                    {field: 'type', title: '1分润2招商'},                    {field: 'amount', title: '金额'},                    {field: 'profit', title: '利润'},                    {field: 'tranTime', title: '交易时间'},                    {field: 'createtime', title: 'createtime'},                    {width: 250, title: '操作', templet: ea.table.tool},
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