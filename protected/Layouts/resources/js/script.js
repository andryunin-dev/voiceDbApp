var APP = APP || {};
// APP.settings = APP.settings || {};
// APP.popups = APP.popups || [];
APP.getPopup = function (href) {
    $.ajax({
        url: href,
        type: "GET",
        dataType: "html"
    })
        .done(function (html) {
            $("body").append(html);
            APP.currentPopup.index = APP.popups.length - 1;
            APP.currentPopup.obj = $(APP.popups[APP.currentPopup.index].popupSelector);
            APP.currentPopup.obj.dialog({
                autoOpen: true,
                height: "auto",
                width: APP.popups[APP.currentPopup.index].width || "auto",
                modal: true,
                close: function (event, ui) {
                    APP.currentPopup.obj.dialog("destroy");
                    APP.currentPopup.obj.remove();
                    APP.popups.pop();
                    APP.currentPopup.index = (APP.popups.length > 0) ? APP.popups.length - 1 : undefined;
                    APP.currentPopup.obj = (undefined !== APP.currentPopup.index) ? $(APP.popups[APP.currentPopup.index].popupSelector) : undefined;
                }
            })
        })
};

APP.submitForm = function (href, data) {
    $.ajax({
        url: href,
        type: "POST",
        data: data,
        dataType: "json"
    })
        .done(function (data, textStatus, jqXHR) {
            console.log("done");
            console.log(data);
            console.log(textStatus);
            console.log(jqXHR);
            console.log(jqXHR.status);
        })
        .fail(function ( jqXHR, textStatus, errorThrown) {
            console.log("fail");
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        })
};
jQuery(function ($) {
    APP.currentPopup = APP.currentPopup || {};
    APP.body = APP.body || $('body');
    APP.body.on(
        "click",
        "a[role='button']", //открытие диалогового окна
        function (event) {
            event.stopPropagation();
            event.preventDefault();
            urlPopup = $(this).attr("href");
            APP.getPopup(urlPopup);
        }
    );
    
    APP.body.on(
        "click",
        "button[data-action]",
        function (event) {
            var action = $(this).data("action");
            switch (action) {
                //закрытие диалогового окна
                case "close":
                    APP.currentPopup.obj.dialog("close");
            }
        }
    );
    
    APP.body.on(
        "submit",
        "form",
        function (event) {
            event.stopPropagation();
            event.preventDefault();
            data = $(this).serialize()
            console.log(data);
            // console.log($(this).attr('action'));
            APP.submitForm($(this).attr('action'), data);
        }
    )

});

