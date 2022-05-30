define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'admin.card/index',
        add_url: 'admin.card/add',
        edit_url: 'admin.card/edit',
        delete_url: 'admin.card/delete',
        export_url: 'admin.card/export',
        modify_url: 'admin.card/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: 'checkbox'},                    {field: 'id', title: 'id'},                    {field: 'user_id', title: '用户id'},                    {field: 'card_type', title: '1信用卡2储蓄卡'},                    {field: 'card_no', title: '卡号'},                    {field: 'bank_logo', title: 'bank_logo'},                    {field: 'bank', title: '银行'},                    {field: 'tel', title: '预留手机'},                    {field: 'bill_date', title: '账单日期'},                    {field: 'repayment_date', title: '还款日期'},                    {field: 'cvn2', title: 'cvn2'},                    {field: 'expiration_date', title: '有效期'},                    {field: 'create_time', title: 'create_time'},                    {width: 250, title: '操作', templet: ea.table.tool},
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