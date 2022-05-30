define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'member.order/index',
        add_url: 'member.order/add',
        edit_url: 'member.order/edit',
        delete_url: 'member.order/delete',
        export_url: 'member.order/export',
        modify_url: 'member.order/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: 'checkbox'},                    {field: 'id', title: 'id'},                    {field: 'no', title: '订单号'},                    {field: 'user_id', title: '用户'},                    {field: 'paid_at', title: '支付时间'},                    {field: 'merber_code', title: '会员码'},                    {field: 'order_pay', title: 'order_pay'},                    {field: 'order_status', title: '订单状态未支付1成功2关闭4.已使用'},                    {field: 'create_time', title: 'create_time'},                    {width: 250, title: '操作', templet: ea.table.tool},
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