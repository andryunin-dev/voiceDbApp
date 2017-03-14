jQuery(function ($) {
    APP.selectors.body = $('body');
    //console.log($("[role='button'][data-action]"));
    APP.selectors.body.on(
        "click",
        "[role='button'][data-action]",
        function (event) {

            event.stopPropagation();
            //console.log($(this).attr("href"));
            switch ($(this).attr("data-action")) {
                case "popup-add":
                case "popup-edit":
                    event.preventDefault();
                    $.ajax({
                        url: $(this).attr("href"),
                        type: "GET",
                        dataType: "html"
                    })
                        .done(function (html) {
                            console.log(APP);
                            APP.selectors.body.append(html);
                            var modalWindow = $(APP.popups[APP.popups.length - 1].selector);
                            modalWindow.dialog({
                                autoOpen: true,
                                height: "auto",
                                width: APP.settings.modalWidth || "auto",
                                modal: true,
                                close: function (event, ui) {
                                    modalWindow.dialog("destroy");
                                    modalWindow.remove();
                                    APP.popups.pop();
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
                case "view":
                    event.preventDefault();
                    //console.log(this);
                    console.log($(this).data("id"));
                    $.ajax({
                        url: APP.settings.modalURL,
                        cache: false,
                        type: "GET",
                        dataType: "html",
                        data: {id: $(this).data("id")}
                    })
                        .done(function (html) {
                            APP.selectors.body.append(html);
                            var modalWindow = $(APP.settings.modalWindowSelector);
                            console.log(html);
                            modalWindow.dialog({
                                autoOpen: true,
                                height: "auto",
                                width: "auto",
                                modal: true,
                                close: function (event, ui) {
                                    modalWindow.dialog("destroy");
                                    $(APP.settings.modalWindowSelector).remove()
                                }
                            });
                        });
                    break;
                case "delete":
                    break;
            }
        }
    );
});

