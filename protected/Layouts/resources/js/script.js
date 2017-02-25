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
});

