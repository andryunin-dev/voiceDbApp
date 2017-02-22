jQuery(function ($) {

    var body = $('body');
    //console.log($("[role='button'][data-action]"));
    body.on(
        "click",
        "[role='button'][data-action]",
        function (event) {
            event.preventDefault();
            //console.log($(this).attr("href"));
            switch ($(this).attr("data-action")) {
                case "add":
                case "edit":
                    $.ajax({
                        url: $(this).attr("href"),
                        type: "GET",
                        dataType: "html"
                    })
                        .done(function (html) {
                            var modalWindow = $(html);

                            modalWindow.dialog({
                                autoOpen: true,
                                height: "auto",
                                width: "auto",
                                modal: true,
                                close: function (event, ui) {
                                    modalWindow.dialog("destroy");
                                }
                            });
                            //прикручиваем обработчик кнопок модального окна
                            modalWindow.on(
                                "click",
                                "[role='button'][data-action]",
                                function (event) {
                                    event.preventDefault();
                                    switch ($(this).data("action")) {
                                        case "close":
                                            modalWindow.dialog("close");
                                    }
                                }
                            );
                        });
                    break;
                case "delete":
                    break;
            }
        }
    );

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

