jQuery(function ($) {

    var body = $('body');
    //console.log($("[role='button'][data-action]"));
    body.on(
        "click",
        "[role='button'][data-action]",
        function (event) {

            event.stopPropagation();
            //console.log($(this).attr("href"));
            switch ($(this).attr("data-action")) {
                case "add":
                case "edit":
                    event.preventDefault();
                    $.ajax({
                        url: $(this).attr("href"),
                        type: "GET",
                        dataType: "html"
                    })
                        .done(function (html) {
                            body.append(html);
                            var modalWindow = $(APP.settings.modalWindowSelector);
                            modalWindow.dialog({
                                autoOpen: true,
                                height: "auto",
                                width: APP.settings.modalWidth || "auto",
                                modal: true,
                                close: function (event, ui) {
                                    modalWindow.dialog("destroy");
                                    $(APP.settings.modalWindowSelector).remove();
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
                            body.append(html);
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

