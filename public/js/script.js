jQuery(function ($) {
    var body = $('body');
    var modalWindows = $("div[role='dialog']"); //модальные окна
    var controlButtons = $("*[role='button'][aria-controls]"); //control-buttons управления модальными окнами
    var controlRows = $("tr.office-row");
    //задаем параметры модальных окон
    modalWindows.each(function () {
        var winHeight = $(window).height();
        var winWidth = $(window).width();
        var result = $(this).dialog(
            {
                open: function(){
                    $("body").css("overflow", "hidden");
                },
                close: function(){
                    $("body").css("overflow", "auto");
                },
                position: { my: "center top", at: "top+5%", of: window },
                autoOpen: false,
                height: "auto",
                maxHeight: winHeight * 0.9,
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
<<<<<<< HEAD
    controlRows.each(function () {
        $(this).on("click", function (event) {
            var window = {
                id: $(this).attr("aria-controls"),
                dataId: $(this).data("id")
            };
            modalWindows.filter("[id=" + window.id + "]").dialog("open");
            // console.log($(this).attr("value"));
            // console.log(window.dataId);
            //переключаем фокус на элемент у которого [aria-selected='true']
            tabs.filter("[aria-selected='true']").children().get(0).focus();
        })
    });
//Tabs
    var tabs = $(".nav-tabs[role='tablist'] li[role='tab']");
    var panels = $(".tab-content div[role=tabpanel]");

    //задаем обработчик событий на клики по табам
    tabs.each(function() {
        $(this).on( "click", function (event) {
            event.preventDefault();
            tabs.filter("[aria-selected='true']").removeClass("active").attr('aria-selected','false');
            $(this).addClass("active").attr('aria-selected','true');

            panels.filter(".active").removeClass("active");
            var panelId = $(this).attr("aria-controls");
            panels.filter("[id=" + panelId + "]").addClass("active");
            // console.log("[id=" + panelId + "]");
        })
    });
});


