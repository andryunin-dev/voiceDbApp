jQuery(function ($) {
    var modalWindows = $("div[role='dialog']"); //модальные окна
    var controlButtons = $("*[role='button'][aria-controls]"); //control-buttons управления модальными окнами
    var controlRows = $("tr.office-row");
    //задаем параметры модальных окон
    modalWindows.each(function () {
        var result = $(this).dialog(
            {
                autoOpen: false,
                height: "auto",
                width: "auto",
                modal: true
            }
        );
    });

    //если надо поменять параметры окна
    // modalWindows.filter("[id='add-region']").dialog(
    //     {width: 400}
    // );

    //задаем обработчик событий на кнопки
    controlButtons.each(function () {
        $(this).on("click", function (event) {
            //event.stopPropagation()
            var window = {
                id: $(this).attr("aria-controls"),
                dataId: $(this).data("id")
            };
            var action = $(this).attr("value");

            switch(action) {
                case 'open':
                    modalWindows.filter("[id=" + window.id + "]").dialog("open");
                    break;
                case 'close':
                    modalWindows.filter("[id=" + window.id + "]").dialog("close");
                    break;
            }
            // console.log($(this).attr("value"));
            // console.log(window.dataId);
        })
    });

    //задаем обработчик событий на клики по строкам таблицы
    // controlRows.each(function () {
    //     $(this).on("click", function (event) {
    //         console.log(this);
    //
    //         var window = {
    //             id: $(this).attr("aria-controls"),
    //             dataId: $(this).data("id")
    //         };
    //         modalWindows.filter("[id=" + window.id + "]").dialog("open");
    //         // console.log($(this).attr("value"));
    //         // console.log(window.dataId);
    //     })
    // });
    $("table").on("click", "tr", function (event) {
        console.log(event.target);
        console.log(this);
    });
//Tabs
    var tabs = $(".nav-tabs[role='tablist'] li[role='tab']");
    var panels = $(".tab-content div[role=tabpanel]");
    //console.log(tabs);

    //задаем обработчик событий на клики по табам
    tabs.each(function() {
        $(this).on( "click", function () {
            tabs.filter("[aria-selected='true']").removeClass("active").attr('aria-selected','false');
            $(this).addClass("active").attr('aria-selected','true');

            panels.filter(".active").removeClass("active");
            var panelId = $(this).attr("aria-controls");
            panels.filter("[id=" + panelId + "]").addClass("active");
            // console.log("[id=" + panelId + "]");
        })
    });
});


