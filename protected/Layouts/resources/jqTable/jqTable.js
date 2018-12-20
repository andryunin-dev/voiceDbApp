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
 *     columns: [
 *         {name: 'column 1', width: 25, class: 'class_1 class_2', sortable: true, filterable: true},
 *         {name: 'column 2', width: 25},
 *         {name: 'column 3', width: 25},
 *         {name: 'column 4', width: 25}
 *     ]
 * }

 */

/*селекторы
 *     Структура:
 * обертка - тег div с id = main_selector(jqTable.workSet.mainSelector) + '-' + tb|hd|bd|ft + '-wrap'
 * например main selector - #tab1
 * table wrapper selector - #tab1-tb-wrap
 * header wrapper selector - #tab1-hd-wrap
 *
 * Селектроы формируются в методе init из mainSelector + начальное значение селектора
 */

jqTable.defaultModel = {
    tableName: '',
    width: 100, //относительно контейнера таблицы
    height: 100, //От верха заголовка, включая заголовок и футер(если он есть).
    marginBottom: '10px',
    dataUrl: "", //URL для запроса данных
    header: {
        fixed: true, //фиксировать или нет заголовок таблицы
        fixedPivColWidth: 0,
        fixedColWidth: 0,
        buildOnServer: true, //если true - заголовок собирается на стороне сервера, false - локально
        columns: {}, // модель хедера (см. описание структуры)
        selectors: {
            table: '_hd',
            scrollCell: '_hd_scrollCell' //ячейка для компенсации скрола
        },
        filters: { //параметры контекстных фильтров в заголовке таблицы.
            url: undefined //URL по которому обращаются фильтры (jQuery UI autocomplete).
        }
    },
    body: {
        selectors: {
            table: '_bd',
            firstRow: '_bd_fr'
        }
    },
    bodyFooter: {
        selectors: {
            table: '_bd_ft',
            firstRow: '_bd_ft_fr',
            scrollCell: '_bd_ft_scrollCell' //ячейка для компенсации скрола
        }
    },
    footer: {
        classes: {
            table: "ui_state_default"
        },
        selectors: {
            table: '_ft',
            infoCell: '_ft_info_cell',
            firstRow: '_ft_1st_row',
            secondRow: '_ft_2st_row'
        }
    },
    wrappers: {
        selectors: {
            table: '_tb_wrap', //селектор обертки всей таблицы
            header: '_hd_wrap',
            body: '_bd_wrap',
            bodyFooter: '_bd_ft_wrap',
            footer: '_ft_wrap',
            headerBody: '_bd_hd_wrap' //селектор обертки хедера и боди
        }
    },

    pager: {
        width: '300px',
        rowsOnPage: 50, //значение строк на страницу
        rowList: [10, 20, 30, 50, 100, 200], //дефолтные значения списка для установки rows
        startPage: 1, //стартовый номер страницы
        preloader: {
            width: '20px',
            height: ''
        },
        selectors: {
            pager: '_pg',//весь пагинатор
            input: '_pg_input',//input поле пейджинатора
            rowsOnPageList: '_pg_row_list',//список значение строк на страницу
            pagesCount: '_pg_pages_count', //общее кол-во страниц
            preloader: '_pg_preload' //общее кол-во страниц
        }
    },
    globalSearch: {
        width: '300px',
        height: '20px'
    },
    hdFilter: {
        selectors: {
            boxPrefix: '_hdf_'
        }
    },
    /*
    * tableFilter - фильтр формируемый на уровне таблицы из фильтров заголовка
    * шаблон объекта фильтра:
    *   tableFilter: {
    *       fieldName1: {
    *           like: '',
    *           eq: ''
    *       },
    *       fieldNameN: {
    *           like: '',
    *           eq: ['москва', 'саратов']
    *       }
    *   }
    * пример:
     *   tableFilter: {
     *       "region": {
     *           like: '',
     *           eq: 'Иркутск'
     *       },
     *       city: {
     *           like: '',
     *           eq: ['москва', 'саратов']
     *       }
     *   }
    *

     */
    // tableFilter: {
    //     "region": {
    //         eq: "Иркутск"
    //     },
    //     "__city": {
    //         eq: "city 1, city2 2"
    //     }
    // },
    tableFilter: {

    },
    sorting: {
        sortBy: 'default', //field name for sort
        direction: undefined //ASC or DESC
    },
    debug: {
        console: true,
        alert: false
    },
    styles: {
        header: {
            table: {
                classes: [],
                defaultClasses: ["jqt-common-table", "jqt-hd-table"],
                css: {},
                defaultCss: {}
            },
            tr: {
                classes: [],
                defaultClasses: [],
                css: {},
                defaultCss: {}
            },
            th: {
                classes: [],
                defaultClasses: [],
                css: {},
                defaultCss: {}
            },
            td: {
                classes: [],
                defaultClasses: [],
                css: {},
                defaultCss: {}
            }
        },
        body: {
            table: {
                classes: [],
                defaultClasses: ["jqt-common-table", "jqt-bd-table"],
                css: {},
                defaultCss: {}
            },
            tr: {
                classes: [],
                defaultClasses: [],
                css: {},
                defaultCss: {}
            },
            th: {
                classes: [],
                defaultClasses: [],
                css: {height: 0, padding: 0, border: "none"}
            },
            td: {
                classes: [],
                defaultClasses: [],
                css: {},
                defaultCss: {}
            }
        },
        bodyFooter: {
            table: {
                classes: [],
                defaultClasses: ["jqt-common-table", "jqt-bd-ft-table", "table"],
                css: {},
                defaultCss: {}
            },
            tr: {
                classes: [],
                defaultClasses: [],
                css: {},
                defaultCss: {}
            },
            th: {
                classes: [],
                defaultClasses: [],
                css: {height: 0, padding: 0, border: "none"}
            },
            td: {
                classes: [],
                defaultClasses: [],
                css: {},
                defaultCss: {}
            }
        },
        footer: {
            table: {
                classes: [],
                defaultClasses: ["ui-state-default"],
                css: {},
                defaultCss: {}
            },
            tr: {
                classes: [],
                defaultClasses: [],
                css: {},
                defaultCss: {}
            },
            th: {
                classes: [],
                defaultClasses: [],
                css: {},
                defaultCss: {}
            },
            td: {
                classes: [],
                defaultClasses: [],
                css: {},
                defaultCss: {}
            }
        },
        tableBox: {
            classes: [],
            defaultClasses: ["jqtable"],
            css: {},
            defaultCss: {"background-color": "#dfdfdf"}
        },
        headerBox: {
            classes: [],
            defaultClasses: ['test1', 'test2'],
            css: {},
            defaultCss: {width: '100%'}
        },
        bodyBox: {
            classes: [],
            defaultClasses: [],
            css: {},
            defaultCss: {}
        },
        headerBodyBox: {
            classes: [],
            defaultClasses: [],
            css: {},
            defaultCss: {}
        },
        footerBox: {
            classes: [],
            defaultClasses: [],
            css: {},
            defaultCss: {}
        }
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
    obj: {
        $header: $($.parseHTML('<table><thead></thead></table>')), //header ($(#main_selector-hd))
        $headerScrollCell: undefined, //ячейка для компенсации скролла
        $bodyFooterScrollCell: undefined, //ячейка для компенсации скролла
        $headerBox: undefined,
        $headerBodyBox: undefined, //контейнер в котором контейнеры хедера и боди
        $body: $($.parseHTML('<table><thead></thead><tbody></tbody></table>')), //body ($(#main_selector-bd))
        $bodyFirstRow: $($.parseHTML('<tr></tr>')), //первая строка body (у нее выставляем ширину колонок)
        $bodyBox: undefined,

        $bodyFooter: $($.parseHTML('<table><thead></thead><tbody></tbody></table>')), // lower part of body
        $bodyFooterFirstRow: $($.parseHTML('<tr></tr>')), //первая строка lower body (у нее выставляем ширину колонок),
        $bodyFooterBox: undefined,

        $footer: $($.parseHTML('<table><tbody></tbody></table>')), //footer ($(#main_selector-ft))
        $footerInfo: undefined,//инфо ячейка футера
        $footerBox: undefined,
        $tableBox: undefined, //контейнер все таблицы
        $tableBoxParent: undefined, //родительский элемент $tableBox
        $pager: undefined, //pager
        $pgFirstPage: undefined,
        $pgPrewPage: undefined,
        $pgNextPage: undefined,
        $pgLastPage: undefined,
        $pgRowsOnPageList: undefined,
        $pgInput: undefined,
        $pgPagesCount: undefined,
        $pgPreloader: undefined,
        $tableFilters: $(),
        headerFilters: {
            items: {}
        }
    },

    table: {
        width: undefined,//width in px
        height: undefined,//height in px
        fixWidth: undefined, //true/false
        fixHeight: undefined, //true/false

        tableBoxParentWidth: undefined, //ширина родительского элемента главного контейнера в px
        marginBottom: '',// margin снизу таблицы
        fixMarginBottom: undefined,
        X_Scroll_enable: false,
        Y_Scroll_enable: true,

        isBuilt: false,
        isPivot: false
    },

    header: {
        columns: {}, //сюда копируется header.columns из  model.header.columns с пересчетом всех размеров в px
        fixedColWidth: 0, //суммарная ширина колонок которые заданы в px. Заполняется в ходе расчетов
        isBuilt: false
    },
    body: {
        data: {} //данные для body которые получаем по AJAX (rendered body template )
    },
    bodyFooter: {
        columns: {}, //сюда копируется bodyFooter.columns из  model.bodyFooter.columns с пересчетом всех размеров в px
        fixedColWidth: 0, //суммарная ширина колонок которые заданы в px. Заполняется в ходе расчетов
        isBuilt: false
    },
    footer: {
    },
    globalSearch: {
        width: 0
    },

    scrolls: {
        Y_scrollWidth: '', //scroll y width in px
        X_scrollWidth: '', //scroll X width in px
        scrollMargin: 1
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
        filters: {} //текущий фильтр контента
    },
    tableFilter: {}, //текущие параметры фильтра
    //hrefFilter - фильтр формируемый из URL (Get параметров)
    hrefFilter: {
        href: '' // ссылка из которой формируется(на стороне сервера href filter). После формирования set to ''. Если ставить в undefined, то не передается в AJAX запросе
    },
    globalFilter: {}, //фильтр для глобального поиска
    sorting: {}, //текущие параметры сортировки
    auxiliary: {
        lastClickTime: 0
    }
};

(function ($) {
    $.fn.jqTable = function (method) {
        /*внутренние методы*/
        var inner = {
            debug: function (ws, data) {
                if (ws.model.debug.console) console.log(data);
                if (ws.model.debug.alert) alert(data);
            },
            getWorkSet: function () {
                var res;
                // если передан jquery объект ищем в jqtable  по селектору
                $.each(arguments, function (key, arg) {
                    if (arg.jquery && this.selector.slice(1) in jqTable.tables) {
                        res = jqTable.tables[this.selector.slice(1)];
                    }
                });
                if (res) { return res; }

                //если передан сам workSet - возвращаем его
                $.each(arguments, function (key, arg) {
                        if ($.isPlainObject(arg) && arg.hasOwnProperty("mainSelector")) {
                            res = arg;
                            return false;
                        }
                });
                if (res) { return res; }

                //если передан main селектор пытаемся найти по селектору(предварительно отрезав #)
                $.each(arguments, function (key, arg) {
                    if ($.type(arg) === "string") {
                        var tmp = jqTable.tables[arg.slice(1)];
                        if ($.isPlainObject(tmp) && tmp.hasOwnProperty("mainSelector")) {
                            res = tmp;
                            return false;
                        }
                    }
                });

                if (res) {
                    return res;
                } else {
                    return undefined;
                }
            },
            baseLayout: function (ws) {
                //ищем элемент table с id=mainSelector, делаем на базе него header и запоминаем объект JQ в workSet
                var srcTable = $(ws.mainSelector);
                //если получили undefined - значит не нашли исходную таблицу в разметке
                if (srcTable === undefined) {
                    console.log('По селектору ' + ws.mainSelector + 'не найдена исходная таблица');
                    throw 'stop in baseLayout';
                }
                //если нашли,
                // меняем содержимое на workSet.headerObj и id и сохраняем в workSet.headerObj
                ws.obj.$header = srcTable.empty().html(ws.obj.$header.html()); // header
                ws.obj.$header.attr("id", ws.model.header.selectors.table.slice(1));
                //формируем объект ячейки для компенсации скролла
                ws.obj.$headerScrollCell = $('<th></th>').attr('id', ws.model.header.selectors.scrollCell.slice(1));
                ws.obj.$bodyFooterScrollCell = $('<th></th>').attr('id', ws.model.bodyFooter.selectors.scrollCell.slice(1));
                //дописываем id к объектам  bodyObj, bodyFooter, footerObj
                ws.obj.$body.attr("id", ws.model.body.selectors.table.slice(1)); //body
                ws.obj.$bodyFooter.attr("id", ws.model.bodyFooter.selectors.table.slice(1)); //bodyFooter
                ws.obj.$footer.attr("id", ws.model.footer.selectors.table.slice(1)); //footer
                //оборачиваем объект body
                //получаем: обертка table -> обертка headerBody -> обертка header -> $header
                ws.obj.$header
                    .wrap($("<div></div>").attr("id", ws.model.wrappers.selectors.table.slice(1)))
                    .wrap($("<div></div>").attr("id", ws.model.wrappers.selectors.headerBody.slice(1)))
                    .wrap($("<div></div>").attr("id", ws.model.wrappers.selectors.header.slice(1)));
                //добавляем в контейнер headerBody обертки для body
                // в контейнер table - обертку футера и добавляем в них объекты $body и $footer
                ws.obj.$tableBox = $(ws.model.wrappers.selectors.table);
                ws.obj.$tableBoxParent = ws.obj.$tableBox.parent();
                ws.obj.$headerBox = $(ws.model.wrappers.selectors.header);
                ws.obj.$headerBodyBox = $(ws.model.wrappers.selectors.headerBody);
                //добавляем объекты body, bodyFooter и footer в таблицу
                ws.obj.$body.appendTo(ws.obj.$headerBodyBox);
                ws.obj.$body.wrap($("<div></div>").attr("id", ws.model.wrappers.selectors.body.slice(1)));
                ws.obj.$bodyFooter.appendTo(ws.obj.$headerBodyBox);
                ws.obj.$bodyFooter.wrap($("<div></div>").attr("id", ws.model.wrappers.selectors.bodyFooter.slice(1)));
                //добавляем firstRow in $body and $bodyFooter
                ws.obj.$body.find('thead').html(ws.obj.$bodyFirstRow);
                ws.obj.$bodyFooter.find('thead').html(ws.obj.$bodyFooterFirstRow);
                ws.obj.$bodyBox = $(ws.model.wrappers.selectors.body);
                ws.obj.$bodyFooterBox = $(ws.model.wrappers.selectors.bodyFooter);
                ws.obj.$footer.appendTo(ws.obj.$tableBox);
                ws.obj.$footer.wrap($("<div></div>").attr("id",ws.model.wrappers.selectors.footer.slice(1)));
                ws.obj.$footerBox = $(ws.model.wrappers.selectors.footer);


            },

            setBodyMaxHeight: function (workSet) {
                workSet = inner.getWorkSet(this, workSet);
                var maxHeight = workSet.height - (workSet.headerObj.parent().height() + workSet.footerObj.parent().height());
                if (workSet.tableScroll_X) {
                    maxHeight -= workSet.scrollWidth_X;
                }
                workSet.bodyObj.parent().css('max-height', maxHeight);
            },
            setBodyHeight: function (ws) {
                var height = ws.table.height - (ws.obj.$headerBox.height() + ws.obj.$footerBox.height() + ws.obj.$bodyFooter.height() + ws.table.marginBottom);
                ws.obj.$bodyBox.css('height', height);
            },

            updateFooterInfo: function (ws, info) {
                ws.obj.$footerInfo.empty();
                $.each(info, function (index, value) {
                    var infoItem = $('<span/>').html(value).append($('<span/>').text(' / '));
                    ws.obj.$footerInfo.append(infoItem);
                });
                ws.obj.$footerInfo.find('span').last().remove();
            },
            setBodyScroll: function (ws) {
                /*=== уровень контейнеров ===*/
                if (ws.table.Y_Scroll_enable) {
                    ws.obj.$bodyBox.css({"overflow-y": "scroll"});
                } else {
                    ws.obj.$bodyBox.css({"overflow-y": "hidden"});
                }
                if (ws.table.X_Scroll_enable) {
                    ws.obj.$headerBodyBox.css({"overflow-x": "scroll"});
                } else {
                    ws.obj.$headerBodyBox.css({"overflow-x": "hidden"});
                }


            },
            applyBodyStyles: function (workSet) {
                workSet = inner.getWorkSet(this, workSet);
                /*=== Применение стилей к BODY ===*/
                /* уровень tr */
                workSet.bodyObj.find('tr').addClass(workSet.model.body.rowClasses);
            },
            sizeToPx: function (ws) {
                //Обновляем внешние размеры
                inner.windowSize(ws);
                inner.tableBoxParentWidth(ws);

                inner.tableWidthToPx(ws);
                inner.tableHeightToPx(ws);
                inner.colSizesToPx(ws);
                //inner.colSizesBodyFooterToPx(ws);
                inner.pagerWidthToPx(ws);
                inner.globalSearchSizeToPx(ws);
            },
            tableWidthToPx: function (ws) {
                var md = ws.model;
                if (String(md.width).indexOf("px") >= 0) {
                    //ширина таблицы задана в px
                    ws.table.width = parseInt(md.width);
                    ws.table.X_Scroll_enable = ws.table.width > ws.table.tableBoxParentWidth;
                    ws.table.fixWidth = true;
                } else  if (parseInt(md.width) > 0) {
                    //ширина таблицы задана в процентах от контейнера
                    ws.table.width = ws.table.tableBoxParentWidth * parseInt(md.width) / 100;
                    ws.table.X_Scroll_enable = ws.table.width > ws.table.tableBoxParentWidth;
                    ws.table.fixWidth = false;
                } else {
                    inner.debug(ws, 'Не задана ширина таблицы ' + ws.mainSelector);
                    throw 'The width of table ' + ws.mainSelector + ' is not set';
                }
            },
            tableHeightToPx: function (ws) {
                var md = ws.model;
                //margin-bottom
                if (String(md.marginBottom).indexOf("px") >= 0) {
                    //отступ таблицы задана в px
                    ws.table.marginBottom = parseInt(md.marginBottom);
                    ws.table.fixMarginBottom = true;
                } else if (parseInt(md.marginBottom) > 0) {
                    //отступ таблицы задан в процентах. За 100% принимаем высоту от верха контейнера таблицы до низа окна
                    ws.table.marginBottom = parseInt(parseInt(md.height) / 100);
                    ws.table.fixMarginBottom = false;
                } else {
                    ws.table.marginBottom = 0;
                    ws.table.fixMarginBottom = true;
                }
                //height
                if (String(md.height).indexOf("px") >= 0) {
                    //высота таблицы задана в px
                    ws.table.height = parseInt(md.height);
                    ws.table.fixHeight = true;
                } else if (parseInt(md.height) > 0) {
                    //высота таблицы задана в процентах. За 100% принимаем высоту от верха контейнера таблицы до низа окна
                    ws.table.height = parseInt((ws.window.height - ws.obj.$tableBox.offset().top) * parseInt(md.height) / 100)  - ws.table.marginBottom;
                    ws.table.fixHeight = false;
                } else {
                    inner.debug(ws, 'Не задана высота таблицы ' + ws.mainSelector);
                    throw 'The height of table ' + ws.mainSelector + ' is not set';
                }
            },
            colSizesToPx: function (ws) {
                var md = ws.model;
                /**
                 * копируем параметры заголовка таблицы в 'headPx'.
                 * В процессе суммируем ширину фиксированных ширин столбцов и ставим у них fixed: true
                 * Эти колонки не будут меняться при ресайзе таблицы
                 * У колонок заданных в процентах - ставим fixed: false и считаем их ширину
                 * Если суммарная ширина получившихся колонок > чем ширина таблицы - корректируем самую широкую колонку (maxWidth object)
                 */
                    //для расчета ширины ячеек берем ширину таблицы - ширину верт. скрола - scrollMargin (защитный маргин)
                var tableWidth = ws.table.width - ws.scrolls.Y_scrollWidth - ws.scrolls.scrollMargin;
                var headerPx = {columns: {}};
                var acc = 0;
                var pivotItemsWidth = 0;
                var maxWidth = {val: 0, key: ''};

                $.each(md.header.columns, function (key, colModel) {
                    var cell = headerPx.columns[key] = $.extend(true, {}, colModel);
                    /**
                     * ищем ширину колонок заданную в px
                     */
                    if (String(cell.width).indexOf("px") >= 0) {
                        cell.fixed = true;
                        cell.width = parseInt(cell.width);
                        if (!cell.isPivot) {
                            acc += parseInt(cell.width);
                            if (maxWidth.val < cell.width) {
                                maxWidth.val = cell.width;
                                maxWidth.key = key;
                            }
                        } else {
                            ws.table.isPivot = true;
                            pivotItemsWidth += cell.width;
                        }

                    } else {
                        cell.fixed = false;
                        cell.width = parseInt(cell.width); //пока ширину оставим в процентах, переведем в px позже
                    }
                });
                ws.header.fixedColWidth = acc; //запоминаем фиксированную часть ширины, чтобы каждый раз не пересчитывать
                ws.header.fixedPivColWidth = pivotItemsWidth;
                var freeWidth = tableWidth - acc;
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

                //calculate bodyFooter cell's sizes
                //for bodyFooter
                var bodyFooterPx = {columns: {}};
                var accBF = 0;
                var pivotItemsWidthBF = 0;
                var maxWidthBF = {val: 0, key: ''};

                $.each(md.bodyFooter.columns, function (key, colModel) {
                    var cell = bodyFooterPx.columns[key] = $.extend(true, {}, colModel);
                    /**
                     * ищем ширину колонок заданную в px
                     */
                    if (String(cell.width).indexOf("px") >= 0) {
                        cell.fixed = true;
                        cell.width = parseInt(cell.width);
                        if (!cell.isPivot) {
                            accBF += parseInt(cell.width);
                            if (maxWidthBF.val < cell.width) {
                                maxWidthBF.val = cell.width;
                                maxWidthBF.key = key;
                            }
                        } else {
                            ws.table.isPivot = true;
                            pivotItemsWidthBF += cell.width;
                        }

                    } else {
                        cell.fixed = false;
                        cell.width = parseInt(cell.width); //пока ширину оставим в процентах, переведем в px позже
                    }
                });
                ws.bodyFooter.fixedColWidth = accBF; //запоминаем фиксированную часть ширины, чтобы каждый раз не пересчитывать
                ws.bodyFooter.fixedPivColWidth = pivotItemsWidthBF;
                var freeWidthBF = tableWidth - accBF;
                //ищем колонки с шириной в % и пересчитываем в px
                $.each(bodyFooterPx.columns, function (key, colModel) {
                    if (colModel.fixed === false) {
                        colModel.width = Math.round(freeWidthBF * colModel.width / 100);
                        accBF += colModel.width;
                        if (maxWidthBF.val < colModel.width) {
                            maxWidthBF.val = colModel.width;
                            maxWidthBF.key = key;
                        }
                    }
                });


                /**
                 * если это не pivot таблица
                 * корректируем расхождение суммарной ширины ячеек и ширины контейнера
                 * путем вычитания разности из ширины самой широкой ячейки
                 */
                if (!ws.table.isPivot) {
                    headerPx.columns[maxWidth.key].width -= acc - tableWidth;
                    if (maxWidthBF.key) {
                        bodyFooterPx.columns[maxWidthBF.key].width -= accBF - tableWidth;
                    }
                } else {
                    /*если pivot table то считаем новую ширину как сумму всех колонок + скролл + scroll margin*/
                    ws.table.width = acc + ws.header.fixedPivColWidth + ws.scrolls.Y_scrollWidth + ws.scrolls.scrollMargin;
                }
                // workSet.header = headerPx; //запоминаем получившийся массив колонок в workSet.header.columns
                ws.header = $.extend(true, ws.header, headerPx); //запоминаем получившийся массив колонок в workSet.header.columns
                ws.bodyFooter = $.extend(true, ws.bodyFooter, bodyFooterPx); //запоминаем получившийся массив колонок в workSet.header.columns
            },
            colSizesBodyFooterToPx: function (ws) {
                if ($.isPlainObject(ws.model.bodyFooter.columns) && $.isEmptyObject(ws.model.bodyFooter.columns)) {
                    return;
                }
                var md = ws.model;
                /**
                 * копируем параметры заголовка таблицы в 'headPx'.
                 * В процессе суммируем ширину фиксированных ширин столбцов и ставим у них fixed: true
                 * Эти колонки не будут меняться при ресайзе таблицы
                 * У колонок заданных в процентах - ставим fixed: false и считаем их ширину
                 * Если суммарная ширина получившихся колонок > чем ширина таблицы - корректируем самую широкую колонку (maxWidth object)
                 */
                    //для расчета ширины ячеек берем ширину таблицы - ширину верт. скрола - scrollMargin (защитный маргин)
                var tableWidth = ws.table.width - ws.scrolls.Y_scrollWidth - ws.scrolls.scrollMargin;
                var bodyFooterPx = {columns: {}};
                var acc = 0;
                var pivotItemsWidth = 0;
                var maxWidth = {val: 0, key: ''};
                $.each(md.bodyFooter.columns, function (key, colModel) {
                    var cell = bodyFooterPx.columns[key] = $.extend(true, {}, colModel);
                    /**
                     * ищем ширину колонок заданную в px
                     */
                    if (String(cell.width).indexOf("px") >= 0) {
                        cell.fixed = true;
                        cell.width = parseInt(cell.width);
                        if (!cell.isPivot) {
                            acc += parseInt(cell.width);
                            if (maxWidth.val < cell.width) {
                                maxWidth.val = cell.width;
                                maxWidth.key = key;
                            }
                        } else {
                            ws.table.isPivot = true;
                            pivotItemsWidth += cell.width;
                        }

                    } else {
                        cell.fixed = false;
                        cell.width = parseInt(cell.width); //пока ширину оставим в процентах, переведем в px позже
                    }
                });
                ws.bodyFooter.fixedColWidth = acc; //запоминаем фиксированную часть ширины, чтобы каждый раз не пересчитывать
                ws.bodyFooter.fixedPivColWidth = pivotItemsWidth;
                var freeWidth = tableWidth - acc;
                //ищем колонки с шириной в % и пересчитываем в px
                $.each(bodyFooterPx.columns, function (key, colModel) {
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
                 * если это не pivot таблица
                 * корректируем расхождение суммарной ширины ячеек и ширины контейнера
                 * путем вычитания разности из ширины самой широкой ячейки
                 */
                if (!ws.table.isPivot) {
                    bodyFooterPx.columns[maxWidth.key].width -= acc - tableWidth;
                } else {
                    /*если pivot table то считаем новую ширину как сумму всех колонок + скролл + scroll margin*/
                    ws.table.width = acc + ws.header.fixedPivColWidth + ws.scrolls.Y_scrollWidth + ws.scrolls.scrollMargin;
                }
                // workSet.header = headerPx; //запоминаем получившийся массив колонок в workSet.header.columns
                ws.bodyFooter = $.extend(true, ws.bodyFooter, bodyFooterPx); //запоминаем получившийся массив колонок в workSet.header.columns
            },
            updateColSizesInPx: function (ws) {
                var md = ws.model;
                var tableWidth = ws.table.width - ws.scrolls.Y_scrollWidth - ws.scrolls.scrollMargin;
                var freeWidth = tableWidth - ws.header.fixedColWidth;
                var freeWidthBF = tableWidth - ws.bodyFooter.fixedColWidth;

                var acc = 0;
                var accBF = 0;
                var maxWidth = {val: 0, key: ''}; //временный объект для хранения самой широкой колонки
                var maxWidthBF = {val: 0, key: ''}; //временный объект для хранения самой широкой колонки
                $.each(ws.header.columns, function (name, column) {
                    if (column.fixed === false) {
                        column.width = Math.round(freeWidth *  md.header.columns[name].width / 100);
                        acc += column.width;

                        if (maxWidth.val < column.width) {
                            maxWidth.val = column.width;
                            maxWidth.key = name;
                        }
                    } else {
                        if (ws.header.isPivot) {
                            return true;
                        }
                        if (maxWidth.val < column.width) {
                            maxWidth.val = column.width;
                            maxWidth.key = name;
                        }
                    }
                });
                $.each(ws.bodyFooter.columns, function (name, column) {
                    if (column.fixed === false) {
                        column.width = Math.round(freeWidthBF *  md.bodyFooter.columns[name].width / 100);
                        accBF += column.width;

                        if (maxWidthBF.val < column.width) {
                            maxWidthBF.val = column.width;
                            maxWidthBF.key = name;
                        }
                    } else {
                        if (ws.bodyFooter.isPivot) {
                            return true;
                        }
                        if (maxWidthBF.val < column.width) {
                            maxWidthBF.val = column.width;
                            maxWidthBF.key = name;
                        }
                    }
                });

                /**
                 * если это не pivot таблица
                 * корректируем расхождение суммарной ширины ячеек и ширины контейнера
                 * путем вычитания разности из ширины самой широкой ячейки
                 */
                if (!ws.table.isPivot) {
                    ws.header.columns[maxWidth.key].width -= (acc + ws.header.fixedColWidth) - tableWidth;
                    if (maxWidthBF.key) {
                        ws.bodyFooter.columns[maxWidthBF.key].width -= (accBF + ws.bodyFooter.fixedColWidth) - tableWidth;
                    }
                } else {
                    /*если pivot table то считаем новую ширину как сумму всех колонок + скролл + scroll margin*/
                    ws.table.width = acc + ws.header.fixedColWidth + ws.header.fixedPivColWidth + ws.scrolls.Y_scrollWidth + ws.scrolls.scrollMargin;
                }

            },
            pagerWidthToPx: function (ws) {
                ws.pager.width = parseInt(ws.model.pager.width)
            },
            globalSearchSizeToPx: function (ws) {
                ws.globalSearch.width = parseInt(ws.model.globalSearch.width);
                ws.globalSearch.height = parseInt(ws.model.globalSearch.height);
            },
            //управление стилями
            setStyles: function (ws, subjectName) {
                if (subjectName === undefined) {
                    $.each(ws.model.styles, function (key, value) {
                        inner.setStyles(ws, key);
                    })
                }else if(ws.model.styles.hasOwnProperty(subjectName)) {
                    var subjectStyles = ws.model.styles[subjectName];
                    // если это стили для box
                    if ($.isPlainObject(subjectStyles) && subjectStyles.hasOwnProperty('classes') && subjectStyles.hasOwnProperty('css')) {
                        if (! $.isEmptyObject(subjectStyles.classes)) {
                            ws.obj['$' + subjectName].addClass(subjectStyles.classes.join(' '));
                        }
                        if (! $.isEmptyObject(subjectStyles.css)) {
                            $.each(subjectStyles.css, function (key, value) {
                                ws.obj['$' + subjectName].css(key, value);
                            });
                        }

                    } else if ($.isPlainObject(subjectStyles) && subjectStyles.hasOwnProperty('table') && subjectStyles.hasOwnProperty('tr') && subjectStyles.hasOwnProperty('th') && subjectStyles.hasOwnProperty('td')) {
                        //если это стили для таблицы - применям последовательно для table, tr, th, td
                        //уровень таблицы
                        var subject = ws.obj['$' + subjectName];
                        if (! $.isEmptyObject(subjectStyles.table.classes)) {
                            subject.addClass(subjectStyles.table.classes.join(' '));
                        }
                        if (! $.isEmptyObject(subjectStyles.table.css)) {
                            $.each(subjectStyles.table.css, function (key, value) {
                                subject.css(key, value);
                            });
                        }
                        //уровень 'tr'
                        subject = ws.obj['$' + subjectName].find('tr');
                        if (! $.isEmptyObject(subjectStyles.tr.classes)) {
                            subject.addClass(subjectStyles.tr.classes.join(' '));
                        }
                        if (! $.isEmptyObject(subjectStyles.tr.css)) {
                            $.each(subjectStyles.tr.css, function (key, value) {
                                subject.css(key, value);
                            });
                        }
                        //уровень 'th'
                        subject = ws.obj['$' + subjectName].find('th');
                        if (! $.isEmptyObject(subjectStyles.th.classes)) {
                            subject.addClass(subjectStyles.th.classes.join(' '));
                        }
                        if (! $.isEmptyObject(subjectStyles.th.css)) {
                            $.each(subjectStyles.th.css, function (key, value) {
                                subject.css(key, value);
                            });
                        }
                        //уровень 'td'
                        if (! $.isEmptyObject(subjectStyles.td.classes)) {
                            subject.addClass(subjectStyles.td.classes.join(' '));
                        }
                        if (! $.isEmptyObject(subjectStyles.td.css)) {
                            $.each(subjectStyles.td.css, function (key, value) {
                                subject.css(key, value);
                            });
                        }
                    }
                } else {
                    inner.debug(ws, 'Property ' + subjectName + " isn't found in 'styles' object");
                }
            },
            setDefaultStyles: function (ws, subjectName) {
                if (subjectName === undefined) {
                    $.each(ws.model.styles, function (key, value) {
                        inner.setDefaultStyles(ws, key);
                    })
                }else if(ws.model.styles.hasOwnProperty(subjectName)) {
                    var subjectStyles = ws.model.styles[subjectName];
                    // если это стили для box
                    if ($.isPlainObject(subjectStyles) && subjectStyles.hasOwnProperty('defaultClasses') && subjectStyles.hasOwnProperty('defaultCss')) {
                        if (! $.isEmptyObject(subjectStyles.defaultClasses)) {
                            ws.obj['$' + subjectName].addClass(subjectStyles.defaultClasses.join(' '));
                        }
                        if (! $.isEmptyObject(subjectStyles.defaultCss)) {
                            $.each(subjectStyles.defaultCss, function (key, value) {
                                ws.obj['$' + subjectName].css(key, value);
                            });
                        }

                    } else if ($.isPlainObject(subjectStyles) && subjectStyles.hasOwnProperty('table') && subjectStyles.hasOwnProperty('tr') && subjectStyles.hasOwnProperty('th') && subjectStyles.hasOwnProperty('td')) {
                        //если это стили для таблицы - применям последовательно для table, tr, th, td
                        //уровень таблицы
                        var subject = ws.obj['$' + subjectName];
                        if (! $.isEmptyObject(subjectStyles.table.defaultClasses)) {
                            subject.addClass(subjectStyles.table.defaultClasses.join(' '));
                        }
                        if (! $.isEmptyObject(subjectStyles.table.defaultCss)) {
                            $.each(subjectStyles.table.defaultCss, function (key, value) {
                                subject.css(key, value);
                            });
                        }
                        //уровень 'tr'
                        subject = ws.obj['$' + subjectName].find('tr');
                        if (! $.isEmptyObject(subjectStyles.tr.defaultClasses)) {
                            subject.addClass(subjectStyles.tr.defaultClasses.join(' '));
                        }
                        if (! $.isEmptyObject(subjectStyles.tr.defaultCss)) {
                            $.each(subjectStyles.tr.defaultCss, function (key, value) {
                                subject.css(key, value);
                            });
                        }
                        //уровень 'th'
                        subject = ws.obj['$' + subjectName].find('th');
                        if (! $.isEmptyObject(subjectStyles.th.defaultClasses)) {
                            subject.addClass(subjectStyles.th.defaultClasses.join(' '));
                        }
                        if (! $.isEmptyObject(subjectStyles.th.defaultCss)) {
                            $.each(subjectStyles.th.defaultCss, function (key, value) {
                                subject.css(key, value);
                            });
                        }
                        //уровень 'td'
                        if (! $.isEmptyObject(subjectStyles.td.defaultClasses)) {
                            subject.addClass(subjectStyles.td.defaultClasses.join(' '));
                        }
                        if (! $.isEmptyObject(subjectStyles.td.defaultCss)) {
                            $.each(subjectStyles.td.defaultCss, function (key, value) {
                                subject.css(key, value);
                            });
                        }
                    }
                } else {
                    inner.debug(ws, 'Property ' + subjectName + " isn't found in 'styles' object");
                }
            },
            setScrollCellStyle: function (ws) {
                ws.obj.$headerScrollCell.removeClass().addClass("ui-state-default");
                ws.obj.$bodyFooterScrollCell.removeClass().addClass("ui-state-default");
            },
            setInitialStyles: function (ws) {
                inner.setDefaultStyles(ws, 'header');
                inner.setDefaultStyles(ws, 'body');
                inner.setDefaultStyles(ws, 'bodyFooter');
                inner.setDefaultStyles(ws, 'footer');
                inner.setDefaultStyles(ws, 'tableBox');
                inner.setDefaultStyles(ws, 'headerBox');
                inner.setDefaultStyles(ws, 'bodyBox');
                inner.setDefaultStyles(ws, 'headerBodyBox');
                inner.setDefaultStyles(ws, 'footerBox');

                inner.setStyles(ws, 'header');
                inner.setStyles(ws, 'body');
                inner.setStyles(ws, 'bodyFooter');
                inner.setStyles(ws, 'footer');
                inner.setStyles(ws, 'tableBox');
                inner.setStyles(ws, 'headerBox');
                inner.setStyles(ws, 'bodyBox');
                inner.setStyles(ws, 'headerBodyBox');
                inner.setStyles(ws, 'footerBox');
            },
            //создание элементов
            fillSelectors: function (ws) {
                var md = ws.model;
                $.each(md.header.selectors, function (index, value) {
                    md.header.selectors[index] = ws.mainSelector + md.header.selectors[index];
                });
                 $.each(md.body.selectors, function (index, value) {
                    md.body.selectors[index] = ws.mainSelector + md.body.selectors[index];
                });
                 $.each(md.bodyFooter.selectors, function (index, value) {
                    md.bodyFooter.selectors[index] = ws.mainSelector + md.bodyFooter.selectors[index];
                });
                 $.each(md.footer.selectors, function (index, value) {
                    md.footer.selectors[index] = ws.mainSelector + md.footer.selectors[index];
                });
                 $.each(md.wrappers.selectors, function (index, value) {
                    md.wrappers.selectors[index] = ws.mainSelector + md.wrappers.selectors[index];
                });
                 $.each(md.pager.selectors, function (index, value) {
                    md.pager.selectors[index] = ws.mainSelector + md.pager.selectors[index];
                });
                 $.each(md.hdFilter.selectors, function (index, value) {
                    md.hdFilter.selectors[index] = ws.mainSelector + md.hdFilter.selectors[index];
                });
            },
            buildFooter: function (ws) {
                inner.buildPager(ws);
                inner.buildInfoCell(ws);
                footer.buildGlobalSearch(ws);
                ws.obj.$pgPreloader = inner.buildPreloader('0.2em');
                var $pgPreloaderCell = $('<td/>');
                $pgPreloaderCell.append(ws.obj.$pgPreloader);

                //append two rows to footer and wraps they into table tags
                var $firstRow = $('<tr/>', {id: ws.model.footer.selectors.firstRow.slice(1)});
                var $secondRow = $('<tr/>', {id: ws.model.footer.selectors.secondRow.slice(1)});
                ws.obj.$footer.find('tbody').append($('<tr/>').append($firstRow));
                ws.obj.$footer.find('tbody').append($('<tr/>').append($secondRow));
                //делаем каждую строку таблицей, чтобы менять ячейки независимо
                $firstRow.wrap($('<table/>'));
                $secondRow.wrap($('<table/>'));

                //add $footerInfo to first row and $globalSearch, $pager
                $firstRow
                    .append($('<td/>').append(ws.obj.$globalSearch))
                    .append($('<td/>').append(ws.obj.$footerInfo))
                    .append($('<td/>').append(ws.obj.$pgPreloader))
                    .append($('<td/>').append(ws.obj.$pager));
                // $secondRow.append(ws.obj.$footerInfo);
                // $secondRow
                //     .append($('<td/>').append(ws.obj.$globalSearch))
                //     .append($('<td/>').append(ws.obj.$pgPreloader))
                //     .append($('<td/>').append(ws.obj.$pager));
            },
            buildInfoCell: function (ws) {
                ws.obj.$footerInfo = $('<div/>', {
                    id: ws.model.footer.selectors.infoCell.slice(1),
                    class: "table-footer-info-cell"
                });
            },
            buildPager: function (ws) {
                //собираем пейджинатор
                ws.obj.$pgFirstPage = $('<span/>', {
                    class: 'ui-icon ui-icon-seek-first'
                });
                ws.obj.$pgPrewPage = $('<span/>', {
                    class: 'ui-icon ui-icon-seek-prev'
                });
                ws.obj.$pgNextPage = $('<span/>', {
                    class: 'ui-icon ui-icon-seek-next'
                });
                ws.obj.$pgLastPage = $('<span/>', {
                    class: 'ui-icon ui-icon-seek-end'
                });
                ws.obj.$pgInput = $('<input/>', {
                    id: ws.model.pager.selectors.input.slice(1),
                    class: 'ui-pg-input',
                    type: 'text',
                    size: 3,
                    maxlength: 7
                });
                ws.obj.$pgPagesCount = $('<span/>', {
                    id: ws.model.pager.selectors.pagesCount.slice(1)
                }).text('');

                ws.obj.$pager = $('<div/>', {
                    id: ws.model.pager.selectors.pager.slice(1),
                    class: 'jqt-pager',
                    'text-align': 'center'
                }).append(
                    ws.obj.$pgPreloader
                ).append(
                    ws.obj.$pgFirstPage
                ).append(
                    ws.obj.$pgPrewPage
                ).append(
                    $('<span/>', {
                        class: 'ui-separator'
                    })
                ).append(
                    $('<span/>', {
                    }).text('Page')
                ).append(
                    ws.obj.$pgInput
                ).append(
                    $('<span/>', {
                    }).text('of')
                ).append(
                    ws.obj.$pgPagesCount
                ).append(
                    $('<span/>', {
                        class: 'ui-separator'
                    })
                ).append(
                    ws.obj.$pgNextPage
                ).append(
                    ws.obj.$pgLastPage
                );
                ws.obj.$rowsOnPageList = $('<select/>', { id: ws.model.pager.selectors.rowsOnPageList.slice(1)});
                $.each(ws.model.pager.rowList, function () {
                    $('<option/>', {
                        value: $.isNumeric(this) ? this : -1,
                        text: this
                    }).appendTo(ws.obj.$rowsOnPageList);
                });
                ws.obj.$pager.append(ws.obj.$rowsOnPageList);
            },
            columnsIdUpdate: function (ws) {
                $.each(ws.header.columns, function (key,value) {
                    if (typeof value.id === 'undefined' ) {
                        value.id = key;
                    }
                    value.th_id = ws.mainSelector + '_th_' + value.id;
                    value.td_id = ws.mainSelector + '_td_' + value.id;
                });
                $.each(ws.model.header.columns, function (key,value) {
                    if (typeof value.id === 'undefined') {
                        value.id = key;
                    }
                    value.th_id = ws.mainSelector + '_th_' + value.id;
                    value.td_id = ws.mainSelector + '_td_' + value.id;
                });
                $.each(ws.bodyFooter.columns, function (key,value) {
                    if (typeof value.id === 'undefined' ) {
                        value.id = key;
                    }
                    value.th_id = ws.mainSelector + '_bd_ft_th_' + value.id;
                    value.td_id = ws.mainSelector + '_bd_ft_td_' + value.id;
                });
                $.each(ws.model.bodyFooter.columns, function (key,value) {
                    if (typeof value.id === 'undefined') {
                        value.id = key;
                    }
                    value.th_id = ws.mainSelector + '_bd_ft_th_' + value.id;
                    value.td_id = ws.mainSelector + '_bd_ft_td_' + value.id;
                });
            },
            buildHeader: function (ws) {
                if (ws.model.header.buildOnServer) {
                    var dataRequest = {
                        header: {
                            columns: ws.model.header.columns
                        }
                    };
                    $.ajax({
                        url: ws.model.dataUrl,
                        data: dataRequest,
                        method: 'post'
                    })
                        .done(function (data, textStatus, jqXHR) {
                            if (data.exception) {
                                console.log(data.exception);
                                return
                            }
                            inner.debug(ws, 'buildHeader: ' + textStatus);
                            ws.obj.$header.html(data.header.html);
                            inner.setStyles(ws, 'header');
                            //справа добавляем ячейку для компенсации скрола
                            ws.obj.$header.find('tr').append(ws.obj.$headerScrollCell);
                            //устанавливаем ее стиль
                            inner.setScrollCellStyle(ws);
                            //устанавливаем ширину колонок заголовка
                            inner.setColumnWidth(ws);
                            //если таблица построена - устанвливаем ее высоту, иначе делаем это в setTableSizes
                            if (ws.table.isBuilt) {
                                inner.setBodyHeight(ws);
                            }
                            headerFilters.addFilters(ws);
                            ws.header.isBuilt = true;
                        })
                        .fail(function (jqXHR, textStatus, errorThrown) {
                            inner.debug(ws, 'buildHeader: ' + jqXHR.responseText);
                        });
                } else {
                    var header = $('<thead></thead>').addClass(ws.model.header.class);
                    var tr = $('<tr></tr>').appendTo(header);
                    var lastKey = '';
                    $.each(ws.header.columns, function (key,value) {
                        lastKey = key;
                        var tag = $('<th></th>');
                        tag.width(this.width);
                        tag.attr({"id": value.th_id.slice(1), title: value.name})
                            .html(value.name)
                            .appendTo(tr);
                    });
                    //справа добавляем ячейку для компенсации скрола
                    $('<th></th>').attr('id', ws.model.header.selectors.scrollCell.slice(1)).appendTo(tr);
                    ws.obj.header.html(header);
                }
            },
            buildFirstRowBody: function (ws) {
                //добавляем id к 1-й строке, наполняем ячейками с нужными id
                ws.obj.$bodyFirstRow.attr("id", ws.model.body.selectors.firstRow.slice(1));
                $.each(ws.model.header.columns, function (key,value) {
                    $('<th></th>').attr({"id": value.td_id.slice(1)}).appendTo(ws.obj.$bodyFirstRow);
                });
            },
            buildFirstRowBodyFooter: function (ws) {
                //if ws.model.bodyFooter.columns is empty - hide $bodyFooter
                if ($.isPlainObject(ws.model.bodyFooter.columns) && $.isEmptyObject(ws.model.bodyFooter.columns)) {
                    ws.obj.$bodyFooterBox.hide();
                }
                //добавляем id к 1-й строке, наполняем ячейками с нужными id
                ws.obj.$bodyFooterFirstRow.attr("id", ws.model.bodyFooter.selectors.firstRow.slice(1));
                $.each(ws.model.bodyFooter.columns, function (key,value) {
                    $('<th></th>').attr({"id": value.th_id.slice(1)}).appendTo(ws.obj.$bodyFooterFirstRow);
                });
                //append scroll cell
                ws.obj.$bodyFooterFirstRow.append(ws.obj.$bodyFooterScrollCell);
            },
            buildPreloader: function (fontSize) {
                fontSize = fontSize || '0.2em';
                return $('<div><div></div></div>').css('font-size', fontSize).addClass('in_progress').hide();
            },
            //управление размерами
            setColumnWidth: function (ws) {
                $.each(ws.header.columns, function (key,value) {
                    ws.obj.$header.find(value.th_id).outerWidth(value.width);
                    ws.obj.$bodyFirstRow.find(value.td_id).outerWidth(value.width);
                });
                $.each(ws.bodyFooter.columns, function (key, value) {
                    ws.obj.$bodyFooter.find(value.th_id).outerWidth(value.width);
                });
                //установка ширины ячейки компенсации скрола
                ws.obj.$headerScrollCell.outerWidth(ws.scrolls.Y_scrollWidth + ws.scrolls.scrollMargin);
                ws.obj.$bodyFooterScrollCell.outerWidth(ws.scrolls.Y_scrollWidth + ws.scrolls.scrollMargin);
            },
            setTableSizes: function (ws) {
                //РАЗМЕРЫ КОЛОНОК ЗДЕСЬ НЕ УСТАНАВЛИВАЮТСЯ!!!
                // (устанавливаются при построении заголовка или вызовом метода inner.setColumnWidth(ws))
                //после изменения размеров окна пользоваться методом resize
                //ширина общего контейнера
                /**
                 * если ширина таблицы посчитана и она больше ширины родительского элемента главного контейнера
                 * то ширину containerObj ограничиваем шириной его родителя (позже включим у containerObj скрол по горизонтале)
                 */
                /*если table.width < table.tableBoxParentWidth, то ширину $tableBox делаем = table.width */
                if (ws.table.width < ws.table.tableBoxParentWidth) {
                    ws.obj.$tableBox.width(ws.table.width);
                } else {
                    ws.obj.$tableBox.width(ws.table.tableBoxParentWidth);
                }
                ws.table.X_Scroll_enable = ws.table.width > 0 && ws.table.width > ws.table.tableBoxParentWidth;
                //ширина заголовка
                ws.obj.$header.outerWidth(ws.table.width);
                //ширину контейнера body
                ws.obj.$bodyBox.width(ws.table.width);
                //ширину контейнера bodyFooter
                ws.obj.$bodyFooterBox.width(ws.table.width);
                //ширина таблицы body
                var bodyWidth = ws.table.width;
                if (ws.table.Y_Scroll_enable) {
                    bodyWidth = bodyWidth - ws.scrolls.Y_scrollWidth - ws.scrolls.scrollMargin;
                }
                ws.obj.$body.width(bodyWidth);
                //set body footer width
                ws.obj.$bodyFooter.width(ws.table.width);
                //ширина ячейки preLoader
                ws.obj.$pgPreloader.parent('td').css('width', ws.model.pager.preloader.width);
                //ширина ячейки пейджинатора
                ws.obj.$pager.parent('td').outerWidth(ws.pager.width);
                //ширина ячейки globalSearch и высота поля поиска
                ws.obj.$globalSearch.find('input').outerHeight(ws.globalSearch.height);
                ws.obj.$globalSearch.parent('td').outerWidth(ws.pager.width);


                /*=== Устанавливаем высоту для Body ===*/
                //только если построен заголовок таблицы
                if (ws.header.isBuilt) {
                    inner.setBodyHeight(ws);
                }
                ws.table.isBuilt = true;
            },
            updateSizes: function (ws) {
                inner.tableWidthToPx(ws);
                inner.tableHeightToPx(ws);
                inner.updateColSizesInPx(ws);

                inner.setTableSizes(ws);
                inner.setColumnWidth(ws);
            },
            scrollSize: function (ws) {
                var styles = {
                    width: "50px",
                    height: "50px",
                    "overflow-y": "scroll",
                    "overflow-x": "scroll"
                };
                var element = $("<div></div>").attr("id", ws.mainSelector.slice(1) + '_scrl_outer').css(styles).append($("<div></div>").attr("id", ws.mainSelector.slice(1) + '_scrl_inner').css({height: "100%"}));
                $('body').append(element);
                ws.scrolls.Y_scrollWidth = $(ws.mainSelector + '_scrl_outer').width() - $(ws.mainSelector + '_scrl_inner').width();
                ws.scrolls.X_scrollWidth = $(ws.mainSelector + '_scrl_outer').height() - $(ws.mainSelector + '_scrl_inner').height();
                $(ws.mainSelector + '_scrl_outer').remove();
                console.log("scroll width: " + ws.scrolls.Y_scrollWidth + '/' + ws.scrolls.X_scrollWidth);
            },
            windowSize: function (ws) {
                ws.window.width = $(window).width();
                ws.window.height = $(window).height();
            },
            tableBoxParentWidth: function (ws) {
                ws.table.tableBoxParentWidth = ws.obj.$tableBoxParent.width();
            },

            //задание обработчиков событий
            windowResizeEvent: function (ws) {
                $(window).resize(function (event) {
                    //пересчитываем ширину, высоту таблицы, обновляем размеры колонок, применяем обновленный данные
                    inner.windowSize(ws);
                    inner.tableBoxParentWidth(ws);

                    inner.updateSizes(ws);
                });
            },
            pagerEvents: function (ws) {
                ws.obj.$pager.on(
                    'click',
                    ws,
                    function (event) {
                        ws = event.data;
                        if (inner.doubleClick(ws, 300)) { return; }
                        if ($(event.target).hasClass('ui-icon-seek-next')) {
                            ws.pager.page += 1;
                            methods.updateBodyContent(ws);
                        } else if ($(event.target).hasClass('ui-icon-seek-end')) {
                            event.data.pager.page = event.data.pager.pages;
                            methods.updateBodyContent(ws);
                        } else if ($(event.target).hasClass('ui-icon-seek-prev')) {
                            event.data.pager.page -= 1;
                            methods.updateBodyContent(ws);
                        } else if ($(event.target).hasClass('ui-icon-seek-first')) {
                            event.data.pager.page = 1;
                            methods.updateBodyContent(ws);
                        }
                    }
                );
                ws.obj.$pager.change(
                    ws,
                    function (event) {
                    if ($(event.target).attr('id') == event.data.model.pager.selectors.rowsOnPageList.slice(1)) {
                        ws = event.data;
                        var rowsOnPage = $(event.target).find("option:selected").val();
                        ws.pager.rowsOnPage = parseInt(rowsOnPage);
                        Cookies.set(ws.mainSelector.slice(1) + '_rowsOnPage', rowsOnPage);
                        methods.updateBodyContent(ws);
                    }
                });
            },
            //обработчик клика по ссылкам в пределах body таблицы
            clickOnBodyLinkHandler: function (ws) {
                ws.obj.$body.on(
                    'click',
                    ws,
                    function (e) {
                        if ($(e.target).attr('href')) {
                            var href = $(e.target).get(0);
                            //если hostname и path равны в урле клика и текущем - значит обновляем контент таблицы
                            //иначе выходим и переход по ссылке
                            if (href.hostname != location.hostname || href.pathname != location.pathname) {
                                return;
                            }
                            e.preventDefault();
                            methods.updateBodyContent(ws, href.href)
                        }
                    }
                );
            },
            eventHandlersSet: function (ws) {
                inner.windowResizeEvent(ws);
                inner.pagerEvents(ws);
                inner.clickOnBodyLinkHandler(ws);
            },
            //методы фильтра и сортировки
            filterInit: function (ws) {
                ws.tableFilter = $.extend(true, {}, ws.model.tableFilter);
            },
            sortingInit: function (ws) {
                ws.sorting = $.extend(true, {}, ws.model.sorting);
            },

            //методы пейджинатора
            initPagerSettings: function (ws) {
                //если в куке хранится текущая страница - берем ее в качестве текущей
                // ws.pager.page = + Cookies(ws.mainSelector.slice(1) + '_page') || ws.model.pager.startPage;
                ws.pager.page = parseInt(ws.model.pager.startPage);
                ws.pager.rowsOnPage = + Cookies(ws.mainSelector.slice(1) + '_rowsOnPage') || ws.model.pager.rowsOnPage;
                inner.updatePager(ws);
            },
            updatePager: function (ws) {
                //вставить в пейджинатор номер текушей страницы, кол-во страниц
                ws.obj.$pgInput.val(ws.pager.page);
                ws.obj.$pgPagesCount.text(ws.pager.pages);
                ws.obj.$rowsOnPageList.children('option').removeProp('selected').each(function () {
                    if (parseInt($(this).val()) == parseInt(ws.pager.rowsOnPage)) {
                        $(this).prop('selected',true);
                        return false;
                    }
                });
                //если последняя страница - выключаем пейджинацию вправо
                if ( parseInt(ws.pager.page) < ws.pager.pages ) {
                    ws.obj.$pgLastPage.removeClass('ui-state-disabled');
                    ws.obj.$pgNextPage.removeClass('ui-state-disabled');
                } else {
                    ws.obj.$pgLastPage.addClass('ui-state-disabled');
                    ws.obj.$pgNextPage.addClass('ui-state-disabled');
                }
                //если первая страница - выключаем пейджинацию влево
                if ( parseInt(ws.pager.page) > 1 ) {
                    ws.obj.$pgFirstPage.removeClass('ui-state-disabled');
                    ws.obj.$pgPrewPage.removeClass('ui-state-disabled');
                } else {
                    ws.obj.$pgFirstPage.addClass('ui-state-disabled');
                    ws.obj.$pgPrewPage.addClass('ui-state-disabled');
                }
            },
            //вспомогательные методы
            doubleClick: function (ws, guardTime) {
                //guardTime - защитный интервал в мс
                var currentTime = $.now();
                if ((currentTime - ws.auxiliary.lastClickTime) < guardTime) {
                    return true; //это двойной клик
                }
                ws.auxiliary.lastClickTime = currentTime;
                return false;
            },
            updateBodyParams: function (ws, requestedURL) {
                //requestedURL - пишем его в hrefFilter.href для формирования на стороне сервера из него href фильтра
                ws.hrefFilter.href = requestedURL || '';
                return {
                    body: {
                        tableName: ws.model.tableName,
                        tableFilter: ws.tableFilter,
                        hrefFilter: ws.hrefFilter,
                        globalFilter: ws.globalFilter,
                        sorting: ws.sorting,
                        columns: ws.header.columns,
                        bodyFooter: ws.bodyFooter.columns,
                        pager: ws.pager,
                        tableId: ws.mainSelector.slice(1)
                    }
                };
            }
        };
        var footer = {
            buildGlobalSearch: function (ws) {
                ws.globalFilter = new prototype.GlobalFilter(ws.header.columns);

                ws.obj.$globalSearch = $('<div/>', {width: '100%', class: "input-group"})
                    .append($('<input/>', {type: "text", class: "form-control global-filter", placeholder: "Search for..."}).css({'border-radius': '5px', padding: '2px 12px'}));
                ws.obj.$globalSearch.find('input')
                    .data('filter', ws.globalFilter)
                    .autocomplete({
                        source: function (request, response) {

                        },
                        minLength: 0,
                        delay: 300,
                        search: function (e, ui) {
                            $(this).data('filter').value = $(this).val();
                            methods.updateBodyContent(ws);
                            console.log('search', this, ui);
                        },
                    });
                return ws.obj.$globalSearch;
            }
        };
        var headerFilters = {
            addFilters: function (ws) {
                /**
                 * поле input каждого фильтра имеет data аттрибуты:
                 * 'column' - имя колонки, для которой создается фильтр
                 * 'statement' - оператор сравнения (текущий). Наверное он лишний!!
                 * структура инстанса фильтра (на примерер колонки 'region'):
                 * $filterBox (div id=(ws.model.hdFilter.selectors.boxPrefix + 'region') class='ui-filter-box', data-box-name='region'
                 *      $filterItemsBox (div.ui-filter-items-box)
                 *      end_$filterItemsBox
                 *
                 *      <input> class='ui-filter-input' data:{column: 'region', statement: 'like)}
                 *
                 * end_$filterBox
                 * - событие select:
                 *      - при селекте из списка вариантов не даем замещать содержимое поля input
                 *      - выбраный элемент списка оформляем в виде кнопки и помещаем в $filterItemsBox(с предварительной проверкой, что такой элемент уже существует)
                 *      - кнопка элемента создается в headerFilters.createFilterItem
                 */

                $.each(ws.header.columns, function (key, item) {
                    if (item.filterable) {
                        var $headerCell = ws.obj.$header.find(item.th_id);
                        //create an icon for filter
                        var $finder = $('<div/>', {class: 'ui-filter-icon-box'})
                            .css({position: 'relative'})
                            .data("column", key)
                            .append($('<span/>', {class: 'glyphicon glyphicon-filter'}))
                            .addClass('pull-right');
                        ws.obj.$header.find(item.th_id).prepend($finder);

                        var $input = $('<input/>', {
                            type: 'text',
                            class: 'ui-filter-input',
                            width: item.width
                        }).data("column", key).data('statement', 'like');

                        //инициализация autocomplete
                        $input.autocomplete({
                            source: function (request, response) {
                                $.ajax({
                                    url: ws.model.dataUrl,
                                    dataType: "json",
                                    data: {
                                        tableName: ws.model.tableName,
                                        headerFilter: {
                                            filter: {
                                                column: key,
                                                statement: $input.data('statement'),
                                                value: '%' + request.term,
                                                limit: 50
                                            },
                                            tableFilter: ws.tableFilter,
                                            hrefFilter: ws.hrefFilter
                                        }
                                    },
                                    success: function (data) {
                                        // приведем полученные данные к необходимому формату и передадим в предоставленную функцию response
                                        // var res = $.map(data.result, function(item){
                                        //     // return{
                                        //     //     label: item,
                                        //     //     value: item
                                        //     // }
                                        //     return item;
                                        // });
                                        console.log('ajax success');
                                        response(data.result);
                                    },
                                    error: function ( jqXHR, textStatus, errorThrown) {
                                        console.log('ajax error');
                                    }
                                });
                            },
                            minLength: 0,
                            delay: 300,
                            create: function (event, ui) {
                                //добавляем в ячейку заголовка бейдж для отображения кол-ва filter items
                                $headerCell.append($('<span/>', {class: 'badge badge-info'}).css({
                                    'font-size': 'x-small',
                                    padding: '3px 3px',
                                    margin: '2px'
                                }));

                                //собираем бокс для фильтра
                                var $filterItemsBox = $('<div/>', {class: 'ui-filter-items-box ui-widget-content ui-helper-clearfix'})
                                    .data('cellId', item.th_id)
                                    .width($(this).width());
                                $filterItemsBox.data({'updateBadge': function (value) {
                                    $headerCell.find('.badge').text(value);
                                }});

                                    // .data("column", key);
                                var boxId = ws.model.hdFilter.selectors.boxPrefix + key;
                                var $filterBox = $('<div/>', {id: boxId.slice(1), 'data-box-name': key, class: 'ui-filter-box ui-widget-content'})
                                    .css({position: 'absolute', 'z-index': 100});
                                $filterBox.append($filterItemsBox).append($(this));
                                //собраный объект добавляем в WS (может и не надо)
                                ws.obj.headerFilters.items[key] = $filterBox;
                                ws.obj.$headerBox.append($filterBox);
                                $filterBox.position({
                                    of: $finder,
                                    my: 'right+5 top',
                                    at: 'right bottom'
                                });
                                $(this).autocomplete("option", "appendTo", boxId);
                                $filterBox.hide();
                            },
                            open: function (e, ui) {
                                //при каждом открытии списка фильтра устанавливаем максимальную высоту
                                var boxId = ws.model.hdFilter.selectors.boxPrefix + $(this).data('column');
                                ws.obj.$headerBox.find(boxId + ' .ui-autocomplete').css({
                                                'max-height': ws.obj.$bodyBox.height() - $(boxId).find('.ui-filter-items-box').height() - $(boxId).find('input').height(),
                                                'overflow-y': 'scroll',
                                                'overflow-x': 'hidden'
                                            });
                                console.log('open');
                            },
                            close: function(e, ui) {
                                console.log('close list', this);
                            },
                            change: function (e, ui) {
                                console.log('change ', this, ui);
                            },
                            select: function (e, ui) {
                                //prevent copy selected item to input tag
                                e.preventDefault();
                                var $filterItemsBox = $(this).siblings('.ui-filter-items-box');
                                if ($filterItemsBox.length === 0) {
                                    inner.debug(ws, 'filter box not found');
                                }
                                if ($filterItemsBox.find('.ui-button').filter(function (index, element) {
                                    return $(element).data('value') == ui.item.value;
                                }).length > 0) {
                                    console.log('this item already exists');
                                    return;
                                }
                                var $newItem = headerFilters.createFilterItem(ui, $(this).data('column'), 'eq');
                                //добавляем в TableFilter и если ОК, то в $filterItemsBox и обновляем контент body
                                if (headerFilters.appendToTableFilter(ws, $newItem)) {
                                    $newItem.appendTo($filterItemsBox);
                                    var badgeText = $filterItemsBox.find('.ui-button').length > 0 ? $filterItemsBox.find('.ui-button').length : '';
                                    $filterItemsBox.data('updateBadge')(badgeText);
                                    methods.updateBodyContent(ws);
                                }

                                console.log('select', this, ui);
                            },
                            focus: function (e, ui) {
                                //prevent copy selected item to input tag
                                e.preventDefault();
                                console.log('focus', this, ui);
                            },
                            search: function (e, ui) {
                                console.log('search', this, ui);
                            },
                            response: function (e, ui) {
                                console.log('response', this, ui);
                            }
                        });
                    }
                });

                //клик по иконке фильтра показывает соответствующее поле input
                ws.obj.$header.on(
                    'click',
                    '.ui-filter-icon-box',
                    ws,
                    function (e) {
                        var column = $(this).data('column');
                        ws.obj.$headerBox.find('[id^="' + ws.model.hdFilter.selectors.boxPrefix.slice(1) + '"]:visible').hide();
                        var box = ws.obj.$headerBox.find(ws.model.hdFilter.selectors.boxPrefix + column);
                        box.show().position({
                            of: e.currentTarget,
                            my: 'right+5 top',
                            at: 'right bottom'
                        }).find('input').focus();
                        //ws.obj.$headerBox.find(ws.model.hdFilter.selectors.boxPrefix + column).show().find('input').focus();
                        //box.show().find('input').focus();
                    }
                );
                //удаление элемента фильтра
                ws.obj.$headerBox.on(
                    'click',
                    '.ui-filter-items-box .ui-icon-close',
                    // 'button',
                    ws,
                    function (e) {
                        e.stopPropagation();
                        var $item = $(this).closest('div.ui-button');
                        var $itemsBox = $(this).closest('.ui-filter-items-box');
                        if (headerFilters.removeFromTableFilter(ws, $item)) {
                            $item.remove();
                            var badgeText = $itemsBox.find('.ui-button').length > 0 ? $itemsBox.find('.ui-button').length : '';
                            $itemsBox.data('updateBadge')(badgeText);
                        }
                        methods.updateBodyContent(ws);
                        console.log('button close', $(this).closest('div.ui-button'));
                    }
                );
                //клик в области headerBodyBox вне filterBox приводит к закрытию открытых фильтров и очистке поля input
                $('body').on(
                    'click',
                    ws,
                    function (e) {
                        if (
                            // $(e.target).parents('[id^="' + ws.model.hdFilter.selectors.boxPrefix.slice(1) + '"]').length > 0 ||
                            $(e.target).closest('.ui-filter-icon-box').length > 0 ||
                            $(e.target).closest('[id^="' + ws.model.hdFilter.selectors.boxPrefix.slice(1) + '"]').length > 0
                        ) {
                            return;
                        }
                        var $openedBoxes = $('[id^="' + ws.model.hdFilter.selectors.boxPrefix.slice(1) + '"]:visible');
                        $openedBoxes.find('input').val('');
                        $openedBoxes.hide();
                        console.log('click out of the filterBox');
                    }
                );
                //закрытие фильтров по esc
                $(document).on(
                    'keyup',
                    ws,
                    function(evt) {
                    if (evt.keyCode == 27) {
                        var $openedBoxes = $('[id^="' + ws.model.hdFilter.selectors.boxPrefix.slice(1) + '"]:visible');
                        $openedBoxes.find('input').val('');
                        $openedBoxes.hide();
                        console.log('pressed ESC');
                    }
                });
            },
            /**
             * @param {Object} ui
             * @param {Object} ui.item
             * @param {string} ui.item.label text that appears on the button
             * @param {string} ui.item.value text that writes in data prop. of button
             * @param {string} column
             * @param {string} statement statement for filter
             *
             * метод создания кнопки элемента фильтра
             */
            createFilterItem: function (ui, column, statement) {
                var $item = $('<div/>').button({
                    label: ui.item.label,
                    icon: 'ui-icon-close'
                }).css({
                        'font-size': 'smaller',
                        padding: '1px',
                        margin: '1px',
                        float: 'left',
                        cursor: 'default'
                    });
                $item.find('.ui-button-icon').css({cursor: 'pointer'});
                $item.data({
                    statement: statement,
                    column: column,
                    value: ui.item.value
                });
                return $item;
            },
            appendToTableFilter: function (ws, $filterItem) {
                if ($.isArray(ws.tableFilter)) {
                    ws.tableFilter = {};
                }
                var filter = ws.tableFilter;
                var column = $filterItem.data().column;
                var statement = $filterItem.data().statement;
                var value = $filterItem.data().value;
                if (! (filter.hasOwnProperty(column) && filter[column].hasOwnProperty(statement))) {
                    filter[column] = filter[column] || {};
                    filter[column][statement] = [];
                }
                var index = $.inArray(value, filter[column][statement]);
                if ( index === -1) {
                    filter[column][statement].push(value);
                    console.log('запись добавлена', filter);
                    return true;
                } else if (index >= 0) {
                    console.log('элемент уже есть в фильтре');
                    return false;
                } else {
                    console.log('appendToTableFilter: неизвестная ошибка');
                    return false;
                }
            },
            /**
             *
             * @param {Object} ws workset
             * @param {Object} $filterItem
             * @returns {boolean}
             */
            removeFromTableFilter: function (ws, $filterItem) {
                if ($.isArray(ws.tableFilter)) {
                    ws.tableFilter = {};
                    console.log('removeFromTableFilter: tableFilter пустой');
                    return false;
                }
                var filter = ws.tableFilter;
                var column = $filterItem.data().column;
                var statement = $filterItem.data().statement;
                var value = $filterItem.data().value;
                if (! (filter.hasOwnProperty(column) && filter[column].hasOwnProperty(statement))) {
                    console.log('removeFromTableFilter: запись не обнаружена');
                    return false;
                }
                var index = $.inArray(value, filter[column][statement]);
                //если запись нашли - удаляем ее и если ветка пустая - удаляем ее
                if (index >= 0) {
                    filter[column][statement].splice(index, 1);
                    if (filter[column][statement].length === 0) {
                        delete filter[column][statement];
                    }
                    if (Object.keys(filter[column]).length === 0) {
                        delete filter[column];
                    }
                    console.log('запись удалена', filter);
                    return true;
                } else {
                    console.log('removeFromTableFilter: неизвестная ошибка');
                    return false;
                }

            },
        };
        var methods = {
            /**
             *
             * @param userOptions пользовательские установки таблицы
             */
            init: function (userOptions) {
                userOptions = userOptions || {};

                userOptions.bodyFooter = userOptions.bodyFooter && userOptions.bodyFooter.header || {};
                userOptions.bodyFooter.columns = userOptions.bodyFooter.columns || {};

                if ($.isArray(userOptions.bodyFooter.columns)) {
                    userOptions.bodyFooter.columns = {};
                }
                var mainSelector = $(this).attr("id");
                //создаем эл-т массива  с селектором в качестве ключа, в который пишем workSet из workSetTmpl(в корень)
                jqTable.tables[mainSelector] = $.extend(true, {}, jqTable.workSetTmpl);
                //в .model пишем все дефолтные значения
                jqTable.tables[mainSelector].model = $.extend(true, jqTable.defaultModel, userOptions);

                var ws = jqTable.tables[mainSelector]; //указатель для workSet
                var model = jqTable.tables[mainSelector].model; //указатель для модели таблицы
                ws.mainSelector = "#" + mainSelector; //запоминаем селектор текущей таблицы в workSet
                inner.windowSize(ws);
                inner.scrollSize(ws);
                inner.fillSelectors(ws);
                inner.baseLayout(ws);

                inner.sizeToPx(ws);
                inner.columnsIdUpdate(ws);
                inner.buildHeader(ws);
                inner.buildFirstRowBody(ws);
                inner.buildFirstRowBodyFooter(ws);
                inner.buildFooter(ws);
                //заполняем в ws начальные данные для пагинатора из Cooks
                inner.initPagerSettings(ws);
                //инициализируем фильтры
                inner.filterInit(ws);
                inner.sortingInit(ws);
                //применяем стили, размеры,
                inner.setInitialStyles(ws);
                inner.setTableSizes(ws);
                inner.setBodyScroll(ws);
                inner.eventHandlersSet(ws);
                return this;
            },
            updateBodyContent: function (ws, requestedURL) {
                if (! ($.isPlainObject(ws) && ws.hasOwnProperty('mainSelector'))) {
                    ws = inner.getWorkSet(this);
                }
                if (typeof ws === 'undefined') {
                    alert('Update Table Content: Fatal Error!');
                    throw 'updateBodyContent: не найден workSet';
                }
                // если передан requested URL то из его GET параметров будет построен href filter на строне сервера
                var requestParams = inner.updateBodyParams(ws, requestedURL);

                ws.obj.$pgPreloader.show();
                $.ajax({
                    url: ws.model.dataUrl,
                    data: requestParams,
                    method: 'post'
                    })
                    .done(function (data, textStatus, jqXHR) {
                        if (data.exception) {
                            console.log(data.exception);
                            return
                        }
                        ws.obj.$body.children('tbody').html(data.body.html);
                        //inject onClick
                        var editBtns = $(".jsEditBtn");
                        editBtns.each(function () {
                            const devId = $(this).data('devId')
                            $(this).click(function (e) {
                                e.preventDefault();
                                console.log('ID - ', devId);

                            })
                        })

                        //==========
                        if (data.bodyFooter && data.bodyFooter.html) {
                            ws.obj.$bodyFooter.children('tbody').html(data.bodyFooter.html);
                            inner.setBodyHeight(ws);
                        }
                        if (data.body.info) {
                            inner.updateFooterInfo(ws, data.body.info);
                        }
                        ws.pager = data.body.pager;
                        ws.tableFilter = $.isPlainObject(data.body.tableFilter) ? data.body.tableFilter : {};
                        ws.hrefFilter = $.isPlainObject(data.body.hrefFilter) ? data.body.hrefFilter : {};

                        inner.updatePager(ws);
                        ws.obj.$pgPreloader.hide();
                    })
                    .fail(function (jqXHR, textStatus, errorThrown) {
                        inner.debug(ws, 'update body content: ' + jqXHR.responseText);
                        ws.obj.$pgPreloader.hide();
                    });
                return this;
            },
            addBodyEventHandler: function (event, selector, handler) {
                if (selector === '') {
                    selector = null;
                }
                var ws = inner.getWorkSet(this);
                if (typeof ws === 'undefined') {
                    inner.debug(ws, 'addBodyEventHandler: Fatal Error! Не найден workSet');
                    throw 'updateBodyContent: не найден workSet';
                }
                ws.obj.$body.on(
                    event,
                    selector,
                    ws,
                    handler
                );
            },
            addHeaderEventHandler: function (event, selector, handler) {
                if (selector === '') {
                    selector = null;
                }
                var ws = inner.getWorkSet(this);
                if (typeof ws === 'undefined') {
                    inner.debug(ws, 'addHeaderEventHandler: Fatal Error! Не найден workSet');
                    throw 'updateBodyContent: не найден workSet';
                }
                ws.obj.$header.on(
                    event,
                    selector,
                    ws,
                    handler
                );
            },
            //для обработки событий самого поисковика (autocomplete)
            addFinderEventHandler: function (event, handler) {
                var ws = inner.getWorkSet(this);
                if (typeof ws === 'undefined') {
                    inner.debug(ws, 'addHeaderEventHandler: Fatal Error! Не найден workSet');
                    throw 'updateBodyContent: не найден workSet';
                }
                ws.obj.$finder.on(
                    event,
                    ws,
                    handler
                );
            },
            getWorkSet: function () {
                var ws = inner.getWorkSet(this);
                return ws;
            }
        };
        var prototype = {
            GlobalFilter: function (columns) {
                var filterValue = '';
                var self = this;
                this.concatenation = 'OR';
                Object.defineProperty(this, 'concatenation', {enumerable: false});
                if ($.isArray(columns) || $.isPlainObject(columns)) {
                    $.each(columns, function (index, value) {
                        if (columns[index].filterable) {
                            self[index] = {'like': []}
                        }
                    });
                    console.log('global filter is created');
                } else {
                    console.log("global filter isn't created");
                }
                Object.defineProperty(this, 'value', {
                    set: function (value) {
                        filterValue = value;
                        $.each(self, function (index, item) {
                            self[index]['like'][0] = '%' + filterValue;
                        })
                    },
                    get: function () {
                        return filterValue;
                    }
                });
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
        if ( headerFilters[method] ) {
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