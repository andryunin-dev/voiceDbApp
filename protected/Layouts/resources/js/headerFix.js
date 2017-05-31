(function ($) {
    $.fn.headerFix = function (method) {
        //начальные настройки и опции
        var options = {
            'height': 600,
            'class': {
                'tableWrapper': 'tab-wrap',
                'bodyWrapper': 'body-wrap'
            }
        };

        options.selector = {
            'tableWrapper': "." + options.class.tableWrapper,
            'bodyWrapper': "." + options.class.bodyWrapper
        };

        //для хранения выбранных объектов
        var env = {
            'table': this,
            'header': '',
            'body': this.find("tbody")
        };        // немного магии

        //вспомогательные методы
        var helpers = {
            alignHeader: function () {
                //если есть скролл - добавляем выравнивающую ячейку
                scrollWidth = $(options.selector.bodyWrapper).width() - $(env.table).outerWidth();
                if (scrollWidth > 0) {
                    $("<th>", {'width': scrollWidth, 'class': 'scroll-th'}).appendTo(env.header.find("thead tr"));
                } else $(".scroll-th").remove();
            },
            resize: function () {
                headerHeight = env.header.outerHeight();
                if (options.height == '100%') {
                    env.bodyWrapper.height(helpers.getHeightToBottom() - headerHeight);
                } else {
                    env.bodyWrapper.height(options.height - headerHeight);
                }
                helpers.alignHeader();
            },
            getHeightToBottom: function () {
                return $(window).height() - env.header.offset().top;
            }
        };
        //методы публичные
        var methods = {

            init: function (params) {
                options = $.extend(
                    options, params
                );
                //создаем заголовок
                env.header = (function () {
                    var header = env.table.clone().removeAttr("id").css("margin", 0);
                    header.find("tbody").remove();
                    return header;
                })(this);
                //создаем контейнеры
                env.tableWrapper = $("<div>", {"class": options.class.tableWrapper});
                env.bodyWrapper = $("<div>", {"class": options.class.bodyWrapper}).css("overflow-y", "auto");

                env.table.wrap(env.tableWrapper); //обертываем таблицу во внешний контейнер
                env.table.wrap(env.bodyWrapper); //обертываем во внутренний контейнер
                // выбираем внешний контейнер, контейнер body.
                // сохраняем их для будущего использования
                env.tableWrapper = $(options.selector.tableWrapper);
                env.bodyWrapper = $(options.selector.bodyWrapper);

                env.tableWrapper.prepend(env.header); // добавляем header
                env.table.find("thead").hide(); //прячем заголовок основной таблицы
                helpers.alignHeader();
                helpers.resize();
            }
        };
        function alignHeader() {
            //если есть скролл - добавляем выравнивающую ячейку
            scrollWidth = $(options.selector.bodyWrapper).width() - $(env.table).outerWidth();
            if (scrollWidth > 0) {
                $("<th>", {'width': scrollWidth}).appendTo(env.header.find("thead tr"));
            }
        }


        if ( methods[method] ) {
            // если запрашиваемый метод существует, мы его вызываем
            // все параметры, кроме имени метода прийдут в метод
            // this так же перекочует в метод
            return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
        } else if ( typeof method === 'object' || ! method ) {
            // если первым параметром идет объект, либо совсем пусто
            // выполняем метод init
            return methods.init.apply( this, arguments );
        } else {
            // если ничего не получилось
            $.error( 'Метод "' +  method + '" не найден в плагине jQuery.mySimplePlugin' );
        }

    }
})(jQuery);
