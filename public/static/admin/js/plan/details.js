define(["jquery", "easy-admin"], function ($, ea) {

    var init = {
        table_elem: '#currentTable',
        table_render_id: 'currentTableRenderId',
        index_url: 'plan.details/index',
        add_url: 'plan.details/add',
        edit_url: 'plan.details/edit',
        delete_url: 'plan.details/delete',
        export_url: 'plan.details/export',
        modify_url: 'plan.details/modify',
    };

    var Controller = {

        index: function () {
            ea.table.render({
                init: init,
                cols: [[
                    {type: 'checkbox'},                    {field: 'id', title: 'id'},                    {field: 'plan_id', title: 'plan_id'},                    {field: 'to_consume', title: '消费'},                    {field: 'plan_name', title: '计划名称'},                    {field: 'Repay', title: '偿还'},                    {field: 'card_id', title: 'card_id'},                    {width: 250, title: '操作', templet: ea.table.tool},
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