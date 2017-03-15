var APP = APP || {};
// APP.settings = APP.settings || {};
// APP.popups = APP.popups || [];
APP.openPopup = function (html) {
    $("body").append(html);
    APP.currentPopup.index = APP.popups.length - 1;
    APP.currentPopup.obj = APP.popups[APP.currentPopup.index];
    APP.currentPopup.JQobj = $(APP.popups[APP.currentPopup.index].popupSelector);
    APP.currentPopup.JQobj.dialog({
        autoOpen: true,
        height: "auto",
        width: APP.popups[APP.currentPopup.index].width || "auto",
        modal: true,
        close: function (event, ui) {
            var reloadParent = APP.currentPopup.reloadParent;
            APP.currentPopup.JQobj.dialog("destroy");
            APP.currentPopup.JQobj.remove();
            APP.popups.pop();
            APP.currentPopup.index = (APP.popups.length > 0) ? APP.popups.length - 1 : undefined;
            APP.currentPopup.JQobj = (undefined !== APP.currentPopup.index) ? $(APP.popups[APP.currentPopup.index].popupSelector) : undefined;
            if (true === reloadParent) {
                APP.refresh();
            }
        }
    })
};

APP.closePopup = function ()
{
    APP.currentPopup.JQobj.dialog("close");
};

APP.refresh = function () {
    //если текущее окно не popup, то reload страницы
    //если popup - запрашиваем текущее окно заново (при наличии APP.currentPopup.path у текущего окна )
    if (undefined === APP.currentPopup.index) {
        location.reload();
    } else if (undefined !== APP.currentPopup.path) {
        var path = APP.currentPopup.path;
        APP.closePopup();
        APP.getPopup(path);
    }
};

APP.getPopup = function (href) {
    $.ajax({
        url: href,
        type: "GET",
        dataType: "html"
    })
        .done(function (html) {
            APP.openPopup(html);
        })
};

APP.request = function (href, data) {
    var requestType = (undefined === data) ? "GET" : "POST";
    $.ajax({
        url: href,
        type: requestType,
        data: data,
        dataType: "html"
    })
        .done(function (data, textStatus, jqXHR) {
            console.log("done");
            //если POST запрос, то закрываем форму и открываем окно результат
            //если GET запрос, значит окна формы нет и сразу открываем окно результата
            if ("POST" == this.type) {
                APP.closePopup();
            }
            APP.openPopup(data); //вывод результата submit
            APP.currentPopup.reloadParent = true; //нужно перегрузить родителя для отоображения изменений
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
        "a[role='button'][data-action='get-popup']", //открытие диалогового окна
        function (event) {
            event.stopPropagation();
            event.preventDefault();
            urlPopup = $(this).attr("href");
            APP.getPopup(urlPopup);
        }
    );
    APP.body.on(
        "click",
        "a[role='button'][data-action!='get-popup']", //запрос
        function (event) {
            event.stopPropagation();
            event.preventDefault();
            url = $(this).attr("href");
            APP.request(url);
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
                    APP.currentPopup.JQobj.dialog("close");
            }
        }
    );
    
    APP.body.on(
        "submit",
        "form",
        function (event) {
            event.stopPropagation();
            event.preventDefault();
            data = $(this).serialize();
            //console.log(data);
            // console.log($(this).attr('action'));
            APP.request($(this).attr('action'), data);
        }
    )

});

