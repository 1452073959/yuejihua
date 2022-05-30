define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'order.plan/index',
        add_url: 'order.plan/add',
        edit_url: 'order.plan/edit',
        delete_url: 'order.plan/delete',
        export_url: 'order.plan/export',
        modify_url: 'order.plan/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: 'checkbox'},                    {field: 'id', title: 'id'},                    {field: 'user_id', title: 'user_id'},                    {field: 'card_id', title: 'card_id'},                    {field: 'repayment_mode', title: '1消1还1,2消2换二'},                    {field: 'bill_amount', title: '账单金额'},                    {field: 'card_balance', title: '卡余额'},                    {field: 'plan_number', title: '还款笔数'},                    {field: 'pending_amount', title: '待还金额'},                    {field: 'create_time', title: 'create_time'},                    {field: 'plan_no', title: '计划订单号'},                    {field: 'plan_status', title: '计划状态1未开始2取消3执行中4已完成'},                    {width: 250, title: '操作', templet: ea.table.tool},
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