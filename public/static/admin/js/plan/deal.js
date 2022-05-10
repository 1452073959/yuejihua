define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'plan.deal/index',
        add_url: 'plan.deal/add',
        edit_url: 'plan.deal/edit',
        delete_url: 'plan.deal/delete',
        export_url: 'plan.deal/export',
        modify_url: 'plan.deal/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: 'checkbox'},                    {field: 'id', title: 'id'},                    {field: 'trade_amount', title: '交易金额'},                    {field: 'trade_time', title: '交易时间'},                    {field: 'trade_status', title: '交易状态1待消费2已消费3取消'},                    {field: 'actual_amount', title: '实际金额'},                    {field: 'trade_fee', title: '手续费'},                    {field: 'trade_type', title: '1消费2还款'},                    {field: 'plan_details_id', title: 'plan_details_id'},                    {field: 'card_id', title: 'card_id'},                    {width: 250, title: '操作', templet: ea.table.tool},
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