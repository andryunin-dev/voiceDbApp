/**
 * ВСЕ селекторы в плагине - id attribute
 * главный селектор - который передается при вызове init. Хранится в jqTable.workSet.mainSelector
 * селекторы хранятся и передаются в методы с # в начале, например #tab1, #tab1-bd-wrap и т.д.
 * @type {{}}
 */
var jqTable = {};
/**
 * настройки:
 * варианты задания размеров:
 *     25 - проценты, 300px - пикселы
 *    размеры в пиксела не изменяются при resize
 * Пример:
 * header: {
 *     fixed: true, //фиксировать или нет заголовок таблицы
 *     units: '%',
 *     columns: [
 *         {name: 'column 1', width: 25, class: 'class_1 class_2'},
 *         {name: 'column 2', width: 25},
 *         {name: 'column 3', width: 25},
 *         {name: 'column 4', width: 25}
 *     ]
 * }

 */
jqTable.defaults = {
    width: "auto", //относительно контейнера таблицы
    height: 100, //От верха заголовка, включая заголовок и футер(если он есть).
    marginBottom: '10px',
    dataUrl: "", //URL для запроса данных
    header: {
        tableClasses: "",
        rowClasses: "",
        fixed: true, //фиксировать или нет заголовок таблицы
        columns: {} // модель хедера (см. описание структуры)
    },
    body: {
        tableClasses: "",
        rowClasses: "",
        rowId: "",
        cellClasses: "",
        cellStyles: {},
        colModel: {}
    },
    footer: {
        tableClasses: "ui-state-default",
        infoCell: '-info_cell',
        firstRow: '-ft_1st_row'
    },
    templates: {
        table: '', //селектор шаблона для всей таблицы
        header: '', //селектор темплейта для header (i.e. #tab1-hd-tmpl)
        body: '', //селектор темплейта для body (i.e. #tab1-bd-tmpl)
        footer: '' //селектор темплейта для footer (i.e. #tab1-ft-tmpl)
    },
    /*селекторы
    *     Структура:
    * обертка - тег div с id = main_selector(jqTable.workSet.mainSelector) + '-' + tb|hd|bd|ft + '-wrap'
    * например main selector - #tab1
    * table wrapper selector - #tab1-tb-wrap
    * header wrapper selector - #tab1-hd-wrap
    *
    * Селектроы формируются в методе init из mainSelector + начальное значение селектора
    */
    headerSelectors: {
        table: '-hd',
        scrollCell: '_scrollCell'
    },
    bodySelectors: {
        table: '-bd',
        firstRow: '-bd-fr'
    },
    footerSelectors: {
        footer: '-ft'
    },

    /*
    селекторы оберток таблицы
     */
    wrappers: {
        table: '-tb-wrap', //селектор обертки всей таблицы
        header: '-hd-wrap',
        body: '-bd-wrap',
        footer: '-ft-wrap',
        headerBody: '-bd-hd-wrap'
    },
    pager: {
        selector: "-pager", //селектор пагинатора
        width: '300px',
        inputSelector: "-pg-input", //селектор input поля пейджинатора
        pageCountSelector: "-pg-page-count", //селектор input поля пейджинатора
        rowsOnPage: 50, //значение строк на страницу
        rowsOnPageSelector: "-pg-select", //значение строк на страницу
        rowList: [10, 20, 30, 50, 100, 200], //дефолтные значения списка для установки rows
        startPage: 1 //стартовый номер страницы
    }
};
jqTable.tables = [];
/**
 * ВСЕ селекторы(header, body, footer) - id attribute
 * mainSelector - main селектор (с # в начале, например #tab_1) для текущей таблицы(совпадает с ключом массива tables[]).
 * header selector: #main_selector-hd
 * body: #main_selector-bd
 * footer: #main_selector-ft
 */
jqTable.workSetTmpl = {
    mainSelector: '', //#selector
    scrollWidth: '', //scroll width in px
    scrollWidth_X: '', //scroll X width in px
    scrollMargin: 1,

    tableScroll_X: false,
    tableScroll_Y: true,

    width: '', //width in px
    fixWidth: '', //true/false
    height: '', //height in px
    fixHeight: '', //true/false
    marginBottom: '',

    headerObj: $($.parseHTML('<table><thead></thead></table>')), //jQuery object of header ($(#main_selector-hd))
    bodyObj: $($.parseHTML('<table><tbody></tbody></table>')), //jQuery object of body ($(#main_selector-bd))
    footerObj: $($.parseHTML('<table><tbody></tbody></table>')), //jQuery object of footer ($(#main_selector-ft))
    footerInfoObj: $(),
    footerPagerObj: '', //объект пейджинатора
    containerObj: '', //для хранения объекта общего контейнера $(tabModel.wrappers.table)
    headerBodyObj: '', //контейнер в котором лежат контейнеры body и header
    containerObjParentWidth: '', //ширина родительского элемента главного контейнера в px
    fixedColWidth: 0, //суммарная ширина колонок которые заданы в px. Заполняется в ходе расчетов
    header: {}, //сюда копируется header.columns из  model.header.columns с пересчетом всех размеров в px
    tableData: {
        header: "",
        body: ""
    },
    body: {
        data: {} //данные для body которые получаем по AJAX
    },
    pager: {
        rowsOnPage: "", //текущее значение rows для пагинатора
        page: "", //номер текущей страницы
        pages: 0, //общее кол-во страниц
        records: 0 //общее кол-во записей в выборке
    },
    sort: {
        order: "", //текущий порядок сортировки
        direction: "" //текущее направление сортировки
    },
    window: {
        width: 0,
        height: 0
    },
    params: {
        filters: '' //текущий фильтр контента
    }
};

(function ($) {
    $.fn.jqTable = function (method) {
        /*внутренние методы*/
        var inner = {
            /**
             * метод вернет либо workSet(если он валидный), или workSet из объекта jQuery, если он содержит его. Метод нужен только для генерации ошибки в консоле
             * @param jqObj
             * @param workSet
             * @param methodName
             * @returns {*}
             */
            getWorkSet: function () {
                //сначала пытаемся найти plain object workSet
                var res = {};
                $.each(arguments, function (key, arg) {
                    if ($.isPlainObject(arg) && arg.hasOwnProperty("mainSelector")) {
                        res = arg;
                        return false;
                    }
                });
                if (! $.isEmptyObject(res)) {
                    return res;
                }
                //если не нашли, пытаемся найти workSet завернутый в jQuery обертку
                $.each(arguments, function (key, arg) {
                    if ((arg instanceof jQuery) && arg[0] && arg[0].hasOwnProperty("mainSelector")) {
                        res = arg[0];
                        return false;
                    }
                });
                if (! $.isEmptyObject(res)) {
                    return res;
                }
                //если не нашли, пытаемся найти по селектору(предварительно отрезав #)
                $.each(arguments, function (key, arg) {
                    if ($.type(arg) === "string") {
                        res = jqTable.tables[arg.slice(1)];
                        if ($.isPlainObject(res) && res.hasOwnProperty("mainSelector")) {
                            return false;
                        }
                    }
                });
                if (! $.isEmptyObject(res)) {
                    return res;
                }
                //если не нашли пытаемся вытащить селектор из this
                $.each(arguments, function (key, arg) {
                    if (arg instanceof jQuery && arg.hasOwnProperty('selector')) {
                        res = jqTable.tables[arg.selector.slice(1)];
                        if ($.isPlainObject(res) && res.hasOwnProperty("mainSelector")) {
                            return false;
                        }
                    }
                });
                if (! $.isEmptyObject(res)) {
                    return res;
                }

                console.log(arguments.callee.caller.name + ": неверный вызов функции getWorkSet");
            },
            baseLayout: function (workSet) {
                workSet = inner.getWorkSet(this, workSet);
                //ищем элемент table с id=mainSelector, делаем на базе него header и запоминаем объект JQ в workSet
                var srcTable = $(workSet.mainSelector);
                //если получили undefined - значит не нашли исходную таблицу в разметке
                if (srcTable === undefined) {
                    console.log('По селектору ' + workSet.mainSelector + 'не найдена исходная таблица');
                    return
                }
                //если нашли,
                //формируем селекторы
                workSet.model.headerSelectors.table = workSet.mainSelector + workSet.model.headerSelectors.table;
                workSet.model.headerSelectors.scrollCell = workSet.mainSelector + workSet.model.headerSelectors.scrollCell;

                workSet.model.bodySelectors.table = workSet.mainSelector + workSet.model.bodySelectors.table;
                workSet.model.bodySelectors.firstRow = workSet.mainSelector + workSet.model.bodySelectors.firstRow;

                workSet.model.footerSelectors.footer = workSet.mainSelector + workSet.model.footerSelectors.footer;

                workSet.model.wrappers.table = workSet.mainSelector + workSet.model.wrappers.table;
                workSet.model.wrappers.header = workSet.mainSelector + workSet.model.wrappers.header;
                workSet.model.wrappers.body = workSet.mainSelector + workSet.model.wrappers.body;
                workSet.model.wrappers.footer = workSet.mainSelector + workSet.model.wrappers.footer;
                workSet.model.wrappers.headerBody = workSet.mainSelector + workSet.model.wrappers.headerBody;
                //селектор первой строки футера
                workSet.model.footer.firstRow = workSet.mainSelector + workSet.model.footer.firstRow;
                workSet.model.footer.infoCell = workSet.mainSelector + workSet.model.footer.infoCell;
                //селектор ячейки пейджинатора
                workSet.model.pager.selector = workSet.mainSelector + workSet.model.pager.selector;
                workSet.model.pager.inputSelector = workSet.mainSelector + workSet.model.pager.inputSelector;
                workSet.model.pager.pageCountSelector = workSet.mainSelector + workSet.model.pager.pageCountSelector;
                workSet.model.pager.rowsOnPageSelector = workSet.mainSelector + workSet.model.pager.rowsOnPageSelector;

                // меняем содержимое на workSet.headerObj и id и сохраняем в workSet.headerObj
                workSet.headerObj = srcTable.empty().html(workSet.headerObj.html()); // header
                workSet.headerObj.attr("id", workSet.model.headerSelectors.table.slice(1));
                //дописываем id к объектам  bodyObj, footerObj
                workSet.bodyObj.attr("id", workSet.model.bodySelectors.table.slice(1)); //body
                workSet.footerObj.attr("id", workSet.model.footerSelectors.footer.slice(1)); //footer
                //создаем обертку table(общий контейнер) и обертываем headerObj в нее, затем headerObj - в обертку header
                //кладем header в контейнеры, чтобы получить
                //получаем: обертка table -> обертка headerBody -> обертка header -> headerObj
                workSet.headerObj.wrap($("<div></div>").attr("id", workSet.model.wrappers.table.slice(1)));
                workSet.headerObj.wrap($("<div></div>").attr("id", workSet.model.wrappers.headerBody.slice(1)));
                workSet.headerObj.wrap($("<div></div>").attr("id", workSet.model.wrappers.header.slice(1)));
                //добавляем в контейнер headerBody обертки для body
                // в контейнер table - обертку футера и добавляем в них объекты bodyObj и footerObj
                workSet.containerObj = $(workSet.model.wrappers.table);
                workSet.headerBodyObj = $(workSet.model.wrappers.headerBody);
                workSet.headerBodyObj.append($("<div></div>").attr("id", workSet.model.wrappers.body.slice(1)));
                workSet.containerObj.append($("<div></div>").attr("id",workSet.model.wrappers.footer.slice(1)));
                workSet.bodyObj.appendTo(workSet.model.wrappers.body);
                workSet.footerObj.appendTo(workSet.model.wrappers.footer);
                return $(workSet);
            },
            /**
             *  определяем ширину таблицы в px
             */
            tableWidthToPx: function (workSet) {
                workSet = inner.getWorkSet(this, workSet);
                var model = workSet.model;
                if (String(model.width).indexOf("px") >= 0) {
                    //ширина таблицы задана в px
                    workSet.width = parseInt(model.width);
                    if (workSet.width > workSet.containerObj - workSet.scrollWidth - workSet.scrollMargin) {
                        workSet.width = workSet.width - workSet.scrollWidth - workSet.scrollMargin;
                        console.log('общая ширина таблицы уменьшена на ширину скролла');
                    }
                    workSet.fixWidth = true;
                } else if (String(model.width).toLowerCase() === 'auto') {
                    //ширина таблицы 100% от контейнера минус scrollWidth и scrollMargin
                    workSet.width = workSet.containerObjParentWidth - workSet.scrollWidth - workSet.scrollMargin;
                    workSet.fixWidth = false;
                } else  if (parseInt(model.width) > 0) {
                    //ширина таблицы задана в процентах от контейнера
                    // вычитаем ширину скролла
                    workSet.width = workSet.containerObjParentWidth * parseInt(model.width) / 100  - workSet.scrollWidth - workSet.scrollMargin;
                    workSet.fixWidth = false;
                } else {
                    console.log('Не задана ширина таблицы ' + workSet.mainSelector);
                    return;
                }
                // /**
                //  * если ширина таблицы посчитана и она больше ширины родительского элемента главного контейнера
                //  * то ширину containerObj ограничиваем шириной его родителя (позже включим у containerObj скрол по горизонтале)
                //  */
                // if (workSet.width > 0 && workSet.width <= workSet.containerObjParentWidth) {
                //     workSet.containerObj.width(workSet.width);
                // } else {
                //     workSet.containerObj.width(workSet.containerObjParentWidth);
                // }
            },
            tableHeightToPx: function (workSet) {
                workSet = inner.getWorkSet(this, workSet);
                var model = workSet.model;
                //margin-bottom
                if (String(model.marginBottom).indexOf("px") >= 0) {
                    //отступ таблицы задана в px
                    workSet.marginBottom = parseInt(model.marginBottom);
                    workSet.fixMarginBottom = true;
                } else if (parseInt(model.marginBottom) > 0) {
                    //отступ таблицы задан в процентах. За 100% принимаем высоту от верха контейнера таблицы до низа окна
                    workSet.marginBottom = parseInt(parseInt(model.height) / 100);
                    workSet.fixMarginBottom = false;
                } else {
                    workSet.marginBottom = 0;
                    workSet.fixMarginBottom = true;
                }
                if (String(model.height).indexOf("px") >= 0) {
                    //высота таблицы задана в px
                    workSet.height = parseInt(model.height) - workSet.marginBottom;
                    workSet.fixHeight = true;
                } else if (parseInt(model.height) > 0) {
                    //высота таблицы задана в процентах. За 100% принимаем высоту от верха контейнера таблицы до низа окна
                    workSet.height = parseInt((workSet.window.height - workSet.containerObj.offset().top) * parseInt(model.height) / 100)  - workSet.marginBottom;
                    workSet.fixHeight = false;
                } else {
                    console.log('Не задана высота таблицы ' + workSet.mainSelector)
                }

            },
            bodyMaxHeight: function (workSet) {
                workSet = inner.getWorkSet(this, workSet);
                var maxHeight = workSet.height - (workSet.headerObj.parent().height() + workSet.footerObj.parent().height());
                if (workSet.tableScroll_X) {
                    maxHeight -= workSet.scrollWidth_X;
                }
                workSet.bodyObj.parent().css('max-height', maxHeight);
            },
            bodyHeight: function (workSet) {
                workSet = inner.getWorkSet(this, workSet);
                var maxHeight = workSet.height - (workSet.headerObj.parent().height() + workSet.footerObj.parent().height());
                workSet.bodyObj.parent().css('height', maxHeight);
            },
            colSizesToPx: function (workSet) {
                workSet = inner.getWorkSet(this, workSet);
                var model = workSet.model;
                /**
                 * копируем параметры заголовка таблицы в 'headPx'.
                 * В процессе суммируем ширину фиксированных ширин столбцов и ставим у них fixed: true
                 * Эти колонки не будут меняться при ресайзе таблицы
                 * У колонок заданных в процентах - ставим fixed: false и считаем их ширину
                 * Если суммарная ширина получившихся колонок > чем ширина таблицы - корректируем самую широкую колонку (maxWidth object)
                 */
                var headerPx = {columns: {}};
                var acc = 0;
                // acc += workSet.scrollWidth + workSet.scrollMargin; //суммарную ширину уменьшаем на ширину скрола и поправки
                var maxWidth = {val: 0, key: ''};
                $.each(model.header.columns, function (key, colModel) {
                    var cell = headerPx.columns[key] = $.extend(true, {}, colModel);
                    /**
                     * ищем ширину колонок заданную в px
                     */
                    if (String(cell.width).indexOf("px") >= 0) {
                        cell.fixed = true;
                        cell.width = parseInt(cell.width);
                        acc += parseInt(cell.width);
                        if (maxWidth.val < cell.width) {
                            maxWidth.val = cell.width;
                            maxWidth.key = key;
                        }
                    } else {
                        cell.fixed = false;
                        cell.width = parseInt(cell.width); //пока ширину оставим в процентах, переведем в px позже
                    }

                });
                workSet.fixedColWidth = acc; //запоминаем фиксированную часть ширины, чтобы каждый раз не пересчитывать
                var freeWidth = workSet.width - acc;
                //ищем колонки с шириной в % и пересчитываем в px
                $.each(headerPx.columns, function (key, colModel) {
                    if (colModel.fixed === false) {
                        colModel.width = Math.round(freeWidth * colModel.width / 100);
                        acc += colModel.width;
                        if (maxWidth.val < colModel.width) {
                            maxWidth.val = colModel.width;
                            maxWidth.key = key;
                        }
                    }
                });
                /**
                 * корректируем расхождение суммарной ширины ячеек и ширины контейнера
                 * путем вычитания разности из ширины самой широкой ячейки
                 */
                headerPx.columns[maxWidth.key].width -= acc - workSet.width;
                // workSet.header = headerPx; //запоминаем получившийся массив колонок в workSet.header.columns
                workSet.header = $.extend(true, workSet.header, headerPx); //запоминаем получившийся массив колонок в workSet.header.columns
                return $(workSet); //возвращаем workSet
            },
            columnsIdCheck: function (workSet) {
                workSet = inner.getWorkSet(this, workSet);
                var columns = workSet.header.columns;
                $.each(workSet.header.columns, function (key,value) {
                    if (value.id && value.id.length > 0) {
                        value.td_id = value.id;
                        value.id = workSet.mainSelector.slice(1) + '_th_' + value.id;
                        value.td_id = workSet.mainSelector.slice(1) + '_td_' + value.td_id;
                    } else {
                        value.id = workSet.mainSelector.slice(1) + '_th_' + key;
                        value.td_id = workSet.mainSelector.slice(1) + '_td_' + key;
                    }
                });
            },
            initPager: function (workSet) {
                workSet = inner.getWorkSet(this, workSet);
                //если в куке хранится текущая страница - берем ее в качестве текущей
                workSet.pager.page = + Cookies(workSet.mainSelector.slice(1) + '_currentPage') || workSet.model.pager.startPage;
                workSet.pager.rowsOnPage = + Cookies(workSet.mainSelector.slice(1) + '_rows') || workSet.model.pager.rowsOnPage;
            },
            scrollSize: function (workSet) {
                workSet = inner.getWorkSet(this, workSet);
                var styles = {
                    width: "50px",
                    height: "50px",
                    "overflow-y": "scroll",
                    "overflow-x": "scroll"
                };
                var element = $("<div></div>").attr("id", workSet.mainSelector.slice(1) + '-scrl-outer').css(styles).append($("<div></div>").attr("id", workSet.mainSelector.slice(1) + '-scrl-inner').css({height: "100%"}));
                $('body').append(element);
                workSet.scrollWidth = $(workSet.mainSelector + '-scrl-outer').width() - $(workSet.mainSelector + '-scrl-inner').width();
                workSet.scrollWidth_X = $(workSet.mainSelector + '-scrl-outer').height() - $(workSet.mainSelector + '-scrl-inner').height();
                $(workSet.mainSelector + '-scrl-outer').remove();
                // console.log("scroll width: " + workSet.scrollWidth);
            },
            /**
             * пересчитывает размеры модели таблицы в px и записывает в workSet
             */
            sizeToPx: function (workSet) {
                workSet = inner.getWorkSet(this, workSet);
                var parentWidth = workSet.containerObj.parent().width();
                workSet.containerObjParentWidth = parentWidth;
                inner.tableWidthToPx(workSet);
                inner.tableHeightToPx(workSet);
                inner.colSizesToPx(workSet);
                return $(workSet); //возвращаем workSet
            },

            buildFirstRowBody: function (workSet) {
                //создаем первую строку с нужной шириной колонок и высотой = 0 и id = #mainSelector-bd-fr (first row)
                workSet = inner.getWorkSet(this, workSet);
                //проверяем чтобы были проставлены id у всех колонок в workSet
                inner.columnsIdCheck(workSet);

                // workSet.bodyObj.width(workSet.width - (workSet.scrollWidth + workSet.scrollMargin));
                var tr = $('<tr></tr>').attr("id", workSet.mainSelector.slice(1) + '-bd-fr');
                $.each(workSet.header.columns, function (key,value) {
                    var tag = $('<td></td>');
                    tag.attr({"id": value.td_id}).appendTo(tr);
                });
                //запоминаем первую строку для обновления контента
                workSet.bodyFirstRowObj = tr;
                // inner.setFirstRowBodyWidth(workSet);
                workSet.bodyObj.find("tbody").html(tr);
                return $(workSet);
            },
            buildHeader: function (workSet) {
                workSet = inner.getWorkSet(this, workSet);
                // workSet.headerObj.width(workSet.width);
                if (! workSet.model.templates.table) {
                    var header = $('<thead></thead>').addClass(workSet.model.header.class);
                    var tr = $('<tr></tr>').appendTo(header);
                    var lastKey = '';
                    $.each(workSet.header.columns, function (key,value) {
                        lastKey = key;
                        var tag = $('<th></th>');
                        tag.width(this.width);
                        tag.attr({"id": value.id, title: value.name})
                            .html(value.name)
                            .appendTo(tr);
                    });
                    //справа добавляем ячейку для компенсации скрола
                    $('<th></th>').attr('id', workSet.model.headerSelectors.scrollCell.slice(1)).appendTo(tr);
                    workSet.headerObj.html(header);
                    return $(workSet);
                } else {
                    //есть template для хедера - используем jsRender
                    workSet.tableData.header = workSet.header.columns;
                    var template = $.templates(workSet.model.templates.table);
                    var thead = $(template.render(workSet.tableData));

                    //справа добавляем ячейку для компенсации скрола
                    $('<th></th>').attr('id', workSet.model.headerSelectors.scrollCell.slice(1)).appendTo(thead.find('tr'));
                    workSet.headerObj.html(thead.find('thead'));
                    return $(workSet);
                }

            },
            buildFooter: function (workSet) {
                workSet = inner.getWorkSet(this, workSet);
                //append first row to footer and info cell
                workSet.footerObj.find('tbody').append(
                    $('<tr/>', {
                        id: workSet.model.footer.firstRow.slice(1)
                    }).append(
                        $('<td/>', {
                            id: workSet.model.footer.infoCell.slice(1)
                        })
                    )
                );
                //add to footerInfoObj info cell
                inner.buildPager(workSet);
                return workSet;
            },
            buildPager: function (workSet) {
                workSet = inner.getWorkSet(this, workSet);
                var footerFirstRow = workSet.footerObj.find(workSet.model.footer.firstRow);
                //собираем пейджинатор
                workSet.pager.firstPageObj = $('<span/>', {
                    class: 'ui-icon ui-icon-seek-first'
                });
                workSet.pager.prevPageObj = $('<span/>', {
                    class: 'ui-icon ui-icon-seek-prev'
                });
                workSet.pager.nextPageObj = $('<span/>', {
                    class: 'ui-icon ui-icon-seek-next'
                });
                 workSet.pager.lastPageObj = $('<span/>', {
                     class: 'ui-icon ui-icon-seek-end'
                 });


                var pagerCell = $('<td/>', {
                    id: workSet.model.pager.selector.slice(1),
                    class: 'jqt-pager',
                    align: 'center'
                }).append(
                    workSet.pager.firstPageObj
                ).append(
                    workSet.pager.prevPageObj
                ).append(
                    $('<span/>', {
                        class: 'ui-separator'
                    })
                ).append(
                    $('<span/>', {
                    }).text('Page')
                ).append(
                    $('<input/>', {
                        id: workSet.model.pager.inputSelector.slice(1),
                        class: 'ui-pg-input',
                        type: 'text',
                        size: 3,
                        maxlength: 7
                    }).width(20)
                ).append(
                    $('<span/>', {
                    }).text('of')
                ).append(
                    $('<span/>', {
                        id: workSet.model.pager.pageCountSelector.slice(1)
                    }).text('')
                ).append(
                    $('<span/>', {
                        class: 'ui-separator'
                    })
                ).append(
                    workSet.pager.nextPageObj
                ).append(
                    workSet.pager.lastPageObj
                );
                var rowListSelector = $('<select/>', { id: workSet.model.pager.rowsOnPageSelector});
                $.each(workSet.model.pager.rowList, function () {
                    $('<option/>', {
                        value: this,
                        text: this
                    }).appendTo(rowListSelector);
                });
                //читаем из кук rowsPerPage
                var rowsOnPage = Cookies(workSet.mainSelector.slice(1) + '_rowsOnPage');
                if ( rowsOnPage ) {
                    if ($.inArray(parseInt(rowsOnPage), workSet.model.pager.rowList) >= 0) {
                        //если выбрано цифровое значение
                        workSet.pager.rowsOnPage = parseInt(rowsOnPage);

                    } else if ($.inArray(rowsOnPage, workSet.model.pager.rowList) >= 0){
                        //если выбрано текстовое значение ("все")
                        workSet.pager.rowsOnPage = -1;
                    }
                    rowListSelector.find('option[value=' + rowsOnPage + ']').attr('selected', 'selected');
                } else {
                    rowListSelector.find('option[value=' + workSet.model.pager.rowList[0] + ']').attr('selected', 'selected');
                }
                rowListSelector.appendTo(pagerCell);

                footerFirstRow.append(pagerCell);
                //запоминаем pager Obj в workSet
                workSet.footerPagerObj = footerFirstRow.find(workSet.model.pager.selector);
                workSet.footerPagerObj.width(workSet.model.pager.width);
                //вешаем обработчики событий
                inner.eventsPager(workSet);
                //обновляем данные пейджинатора
                inner.updatePager(workSet);
            },
            updatePager: function (workSet) {
                //вставить в пейджинатор номер текушей страницы, кол-во страниц
                workSet.footerPagerObj.find(workSet.model.pager.inputSelector).val(workSet.pager.page);
                workSet.footerPagerObj.find(workSet.model.pager.pageCountSelector).text(workSet.pager.pages);
                //если последняя страница - выключаем пейджинацию вправо
                if ( parseInt(workSet.pager.page) < workSet.pager.pages ) {
                    workSet.pager.lastPageObj.removeClass('ui-state-disabled');
                    workSet.pager.nextPageObj.removeClass('ui-state-disabled');
                } else {
                    workSet.pager.lastPageObj.addClass('ui-state-disabled');
                    workSet.pager.nextPageObj.addClass('ui-state-disabled');
                }
                //если первая страница - выключаем пейджинацию влево
                if ( parseInt(workSet.pager.page) > 1 ) {
                    workSet.pager.firstPageObj.removeClass('ui-state-disabled');
                    workSet.pager.prevPageObj.removeClass('ui-state-disabled');
                } else {
                    workSet.pager.firstPageObj.addClass('ui-state-disabled');
                    workSet.pager.prevPageObj.addClass('ui-state-disabled');
                }

            },
            eventsPager: function (workSet) {
                workSet = inner.getWorkSet(this, workSet);
                $(workSet.model.pager.selector).on(
                    'click',
                    workSet,
                    function (event) {
                        if ($(event.target).hasClass('ui-icon-seek-next')) {
                            event.data.pager.page += 1;
                            params = {
                                filters: {
                                    appTypes: 'netDevices'
                                }
                            };
                            methods.updateBodyJSON(event.data, params)
                        } else if ($(event.target).hasClass('ui-icon-seek-end')) {
                            event.data.pager.page = event.data.pager.pages;
                            params = {
                                filters: {
                                    appTypes: 'netDevices'
                                }
                            };
                            methods.updateBodyJSON(event.data, params)
                        } else if ($(event.target).hasClass('ui-icon-seek-prev')) {
                            event.data.pager.page -= 1;
                            params = {
                                filters: {
                                    appTypes: 'netDevices'
                                }
                            };
                            methods.updateBodyJSON(event.data, params)
                        } else if ($(event.target).hasClass('ui-icon-seek-first')) {
                            event.data.pager.page = 1;
                            params = {
                                filters: {
                                    appTypes: 'netDevices'
                                }
                            };
                            methods.updateBodyJSON(event.data, params)
                        }
                    }
                );
                $(workSet.model.pager.selector).change(workSet, function (event) {
                    if ($(event.target).attr('id') == event.data.model.pager.rowsOnPageSelector) {
                        var rowsOnPage = $(event.target).find("option:selected").text();
                        if ($.isNumeric(rowsOnPage)) {
                            event.data.pager.rowsOnPage = parseInt(rowsOnPage);
                        } else {
                            event.data.pager.rowsOnPage = -1;
                        }
                        Cookies.set(workSet.mainSelector.slice(1) + '_rowsOnPage', rowsOnPage);
                        params = {
                            filters: {
                                appTypes: 'netDevices'
                            }
                        };
                        methods.updateBodyJSON(event.data, params);
                    }
                });
            },
            /**
             * инициализирует начальные классы для оформления таблицы
             * @param workSet
             */
            setStyles: function (workSet) {
                workSet = inner.getWorkSet(this, workSet);


                //для отладки задаем стили для first row body
                // var tr = $(workSet.mainSelector + '-bd-fr').find('td').height(0).css('border', '1px solid gray');
                //рабочий вариант
                var tr = $(workSet.mainSelector + '-bd-fr').find('td').height(0);
                //добавляем к общему контейнеру класс для привязки стилей
                workSet.containerObj.addClass("jqtable");

                /*=== Применение стилей к HEADER ===*/
                /* уровень table */
                workSet.headerObj.addClass("jqt-common-table jqt-hd-table" + " " + workSet.model.header.tableClasses);
                /* уровень tr */
                workSet.headerObj.find('tr').addClass(workSet.model.header.rowClasses);
                /* уровень th */
                $.each(workSet.header.columns, function (key,value) {
                    if (value.class) {
                        workSet.headerObj.find('#' + value.id).addClass(value.class);
                    }
                });
                //для ячейки компенсирующей скролл удаляем все классы и добавляем "ui-state-default"
                workSet.headerObj.find('th').last().removeClass().addClass("ui-state-default");

                /*=== Применение стилей к BODY ===*/
                /*уровень контейнера tbody*/
                workSet.bodyObj.parent().css({'background-color': "#dfdfdf"});
                /* у первой строки делаем нулевые паддинги*/
                workSet.bodyObj.find(workSet.model.bodySelectors.firstRow + ' td').css({'padding': 0, border: 'none'});
                /* уровень tbody*/
                workSet.bodyObj.addClass("jqt-common-table jqt-bd-table" + " " + workSet.model.body.tableClasses);
                /* уровень tr (через метод applyBodyStyles, т.к он вызывается при каждом апдейте содержимого таблицы)*/
                inner.applyBodyStyles(workSet);
                /*=== Применение стилей к FOOTER ===*/
                /* уровень table */
                workSet.footerObj.addClass(workSet.model.footer.tableClasses);
                /*=== Устанавливаем высоту для Body ===*/
                // inner.bodyMaxHeight(workSet);
                return $(workSet); //возвращаем workSet
            },
            setBodyScroll: function (workSet) {
                workSet = inner.getWorkSet(this, workSet);
                /*=== уровень контейнеров ===*/
                if (workSet.tableScroll_Y) {
                    $(workSet.model.wrappers.body).css({"overflow-y": "scroll"});
                } else {
                    $(workSet.model.wrappers.body).css({"overflow-y": "hidden"});
                }
                if (workSet.tableScroll_X) {
                    workSet.headerBodyObj.css({"overflow-x": "scroll"});
                } else {
                    workSet.headerBodyObj.css({"overflow-x": "hidden"});
                }


            },
            applyBodyStyles: function (workSet) {
                workSet = inner.getWorkSet(this, workSet);
                /*=== Применение стилей к BODY ===*/
                /* уровень tr */
                workSet.bodyObj.find('tr').addClass(workSet.model.body.rowClasses);
            },
            globalEvents: function (workSet) {
                workSet = inner.getWorkSet(this, workSet);
                $(window).resize(function (event) {
                    workSet.window.width = $(window).width();
                    workSet.window.height = $(window).height();
                    inner.sizeToPx(workSet);
                    inner.columnsIdCheck(workSet);
                    inner.resize(workSet);
                    // inner.setFirstRowBodyWidth(workSet);
                })
            },
            setFirstRowBodyWidth: function (workSet) {
                workSet = inner.getWorkSet(this, workSet);
                $.each(workSet.header.columns, function (key,value) {
                    workSet.bodyFirstRowObj.find('#' + value.td_id).width(value.width);
                });
            },
            resize: function (workSet) {
                workSet = inner.getWorkSet(this, workSet);
                //ширина общего контейнера
                /**
                 * если ширина таблицы посчитана и она больше ширины родительского элемента главного контейнера
                 * то ширину containerObj ограничиваем шириной его родителя (позже включим у containerObj скрол по горизонтале)
                 */

                if (workSet.width > 0 && workSet.width + workSet.scrollWidth + workSet.scrollMargin > workSet.containerObjParentWidth) {
                    workSet.containerObj.width(workSet.containerObjParentWidth);
                    workSet.tableScroll_X = true;
                } else {
                    workSet.containerObj.width(workSet.width + workSet.scrollWidth + workSet.scrollMargin);
                    workSet.tableScroll_X = false;
                }
                //ширина заголовка
                workSet.headerObj.outerWidth(workSet.width + workSet.scrollWidth + workSet.scrollMargin);
                //ширину контейнера body
                workSet.bodyObj.parent().width(workSet.width + workSet.scrollWidth + workSet.scrollMargin);
                //ширина таблицы body
                workSet.bodyObj.width(workSet.width);
                //ширина колонок перввой строки body
                inner.setFirstRowBodyWidth(workSet);
                //устанавливаем размеры ячеек заголовка
                $.each(workSet.header.columns, function (key,value) {
                    workSet.headerObj.find('#' + value.id).outerWidth(this.width);
                });
                //размер для scrollCell
                workSet.headerObj.find(workSet.model.headerSelectors.scrollCell).outerWidth(workSet.scrollWidth + workSet.scrollMargin);
                /*=== Устанавливаем высоту для Body ===*/
                inner.setBodyScroll(workSet);
                inner.bodyHeight(workSet);
            }
        };
        var methods = {
            /**
             *
             * @param userOptions пользовательские установки таблицы
             */
            init: function (userOptions) {
                userOptions = userOptions || {};
                //создаем эл-т массива  с селектором в качестве ключа, в который пишем workSet из workSetTmpl(в корень)
                jqTable.tables[$(this).attr("id")] = $.extend(true, {}, jqTable.workSetTmpl);
                //в .model пишем все дефолтные значения
                jqTable.tables[$(this).attr("id")].model = $.extend(true, jqTable.defaults, userOptions);

                var workSet = jqTable.tables[$(this).attr("id")]; //альяс для workSet
                var tabModel = jqTable.tables[$(this).attr("id")].model; //альяс для модели таблицы
                workSet.mainSelector = "#" + $(this).attr("id"); //запоминаем селектор текущей таблицы в workSet
                workSet.window.width = $(window).width();
                workSet.window.height = $(window).height();
                inner.scrollSize(workSet);
                inner.baseLayout(workSet);

                //заполняем в workSet начальные данные для пагинатора
                inner.initPager(workSet);

                inner.sizeToPx(workSet);
                inner.buildFirstRowBody(workSet);
                inner.buildHeader(workSet);
                inner.buildFooter(workSet);
                inner.setStyles(workSet);
                inner.resize(workSet);
                inner.globalEvents(workSet);
                return $(workSet);
            },
            /**
             *
             * @param workSet
             * @param params
             */
            updateBodyJSON2: function (workSet, params) {
                workSet = inner.getWorkSet(this, workSet);

                var requestParams = {
                    columnList: '', //либо массив с именами полей для выгрузки, либо пусто (undefined)
                    rowsOnPage: workSet.pager.rowsOnPage, //если -1 то все строки
                    page: workSet.pager.page,
                    order: 'default',
                    filters: {},
                    search: {}
                };
                requestParams = $.extend(true, requestParams, params);
                function renderBody(bodyData) {
                    workSet.tableData.body = bodyData.data;
                    var template = $.templates(workSet.model.templates.table);
                    var html = $(template.render(workSet.tableData));
                    workSet.bodyObj.children('tbody').first().append(html.find('tbody').html());
                    inner.applyBodyStyles(workSet);
                }
                $.ajax({
                    url: workSet.model.dataUrl,
                    data: requestParams,
                    success: renderBody,
                    error: function () {
                        console.log('Error in Ajax');
                    }
                });
            },
            updateBodyJSON: function (workSet, params) {

                workSet = inner.getWorkSet(this, workSet);
                //все данные для пейджинатора передаем через куки main_selector_pagesCount, _recordsCount, _rowsOnPage
                //для этого передаем на сервер главный селектор
                var requestParams = {
                    columnList: '', //либо массив с именами полей для выгрузки, либо пусто (undefined)
                    tableId: workSet.mainSelector.slice(1),
                    rowsOnPage: workSet.pager.rowsOnPage, //если -1 то все строки
                    page: workSet.pager.page,
                    order: 'default',
                    filters: {},
                    search: {}
                };
                requestParams = $.extend(true, requestParams, params);
                function renderBody(bodyData) {
                    var body = workSet.bodyObj.children('tbody').first();
                    body.html(workSet.bodyFirstRowObj);
                    body.append(bodyData.renderResult);
                    workSet.pager.page = parseInt(bodyData.params.currentPage);
                    workSet.pager.pages = parseInt(bodyData.params.pagesCount);
                    workSet.pager.records = parseInt(bodyData.params.recordsCount);
                    // //из Cookies читаем currentPage, pagesCount, recordsCount и пишем в workSet
                    // workSet.pager.page = +Cookies(workSet.mainSelector.slice(1) + '_currentPage');
                    // workSet.pager.pages = +Cookies(workSet.mainSelector.slice(1) + '_pagesCount');
                    // workSet.pager.records = +Cookies(workSet.mainSelector.slice(1) + '_recordsCount');
                    inner.updatePager(workSet);
                    inner.applyBodyStyles(workSet);
                    $(".toggler").on(
                        "click",
                        function ($e) {
                            $(this).closest("table").find("tbody tr td").slideToggle("fast");
                            $(this).find(".js-caret").toggleClass("caret-down");
                        }
                    );
                }
                $.ajax({
                    url: workSet.model.dataUrl,
                    data: requestParams,
                    success: renderBody,
                    error: function () {
                        console.log('Error in Ajax');
                    }
                });

            },
            updateBodyHTML: function (workSet, params) {

                workSet = inner.getWorkSet(this, workSet);
                //все данные для пейджинатора передаем через куки main_selector_pagesCount, _recordsCount, _rowsOnPage
                //для этого передаем на сервер главный селектор
                var requestParams = {
                    columnList: '', //либо массив с именами полей для выгрузки, либо пусто (undefined)
                    tableId: workSet.mainSelector.slice(1), //все данные передаем через куки main_selector_pagesCount ...
                    rowsOnPage: workSet.pager.rowsOnPage, //если -1 то все строки
                    page: workSet.pager.page,
                    order: 'default',
                    filters: {},
                    search: {}
                };
                requestParams = $.extend(true, requestParams, params);
                function renderBody(bodyData) {
                    var body = workSet.bodyObj.children('tbody').first();
                    body.html(workSet.bodyFirstRowObj);
                    body.append(bodyData);
                    //из Cookies читаем currentPage, pagesCount, recordsCount и пишем в workSet
                    workSet.pager.page = +Cookies(workSet.mainSelector.slice(1) + '_currentPage');
                    workSet.pager.pages = +Cookies(workSet.mainSelector.slice(1) + '_pagesCount');
                    workSet.pager.records = +Cookies(workSet.mainSelector.slice(1) + '_recordsCount');
                    inner.updatePager(workSet);
                    inner.applyBodyStyles(workSet);
                    $(".toggler").on(
                        "click",
                        function ($e) {
                            $(this).closest("table").find("tbody tr td").slideToggle("fast");
                            $(this).find(".js-caret").toggleClass("caret-down");
                        }
                    );
                }
                $.ajax({
                    url: workSet.model.dataUrl,
                    data: requestParams,
                    success: renderBody,
                    error: function () {
                        console.log('Error in Ajax');
                    }
                });

            },
            getWorkSet: function () {
                var workSet = inner.getWorkSet(this);
                return workSet;
            }
        };
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
            $.error( 'Метод "' +  method + '" не найден в плагине jQuery.jqTable' );
        }
        if ( inner[method] ) {
            // если запрашиваемый метод существует, мы его вызываем
            // все параметры, кроме имени метода прийдут в метод
            // this так же перекочует в метод
            return inner[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
        } else {
            // если ничего не получилось
            $.error( 'Метод "' +  method + '" не найден в плагине jQuery.jqTable' );
        }
    }
})(jQuery);