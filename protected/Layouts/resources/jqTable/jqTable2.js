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
    width: 100, //относительно контейнера таблицы
    height: 100, //От верха заголовка, включая заголовок и футер(если он есть).
    marginBottom: '10px',
    dataUrl: "", //URL для запроса данных
    header: {
        fixed: true, //фиксировать или нет заголовок таблицы
        buildOnServer: true, //если true - заголовок собирается на стороне сервера, false - локально
        columns: {}, // модель хедера (см. описание структуры)
        selectors: {
            table: '-hd',
            scrollCell: '-scrollCell' //ячейка для компенсации скрола
        }
    },
    body: {
        selectors: {
            table: '-bd',
            firstRow: '-bd-fr'
        }
    },
    footer: {
        classes: {
            table: "ui-state-default"
        },
        selectors: {
            table: '-ft',
            infoCell: '-ft-info-cell',
            firstRow: '-ft-1st-row'
        }
    },
    wrappers: {
        selectors: {
            table: '-tb-wrap', //селектор обертки всей таблицы
            header: '-hd-wrap',
            body: '-bd-wrap',
            footer: '-ft-wrap',
            headerBody: '-bd-hd-wrap' //селектор обертки хедера и боди
        }
    },

    pager: {
        width: '300px',
        rowsOnPage: 50, //значение строк на страницу
        rowList: [10, 20, 30, 50, 100, 200], //дефолтные значения списка для установки rows
        startPage: 1, //стартовый номер страницы
        selectors: {
            pager: '-pg',//весь пагинатор
            input: '-pg-input',//input поле пейджинатора
            rowsOnPageList: '-pg-row-list',//список значение строк на страницу
            pagesCount: '-pg-pages-count' //общее кол-во страниц
        }
    },
    /*
    * шаблон объекта фильтов:
    *   filter: {
    *       fieldName1: {
    *           like: '',
    *           eq: ''
    *       },
    *       fieldNameN: {
    *           like: '',
    *           eq: ['москва', 'саратов']
    *       }
    *   }
    */
    filter: {
        "region": {
            eq: "регион 1, рег 2"
        },
        "__city": {
            eq: "city 1, city2 2"
        }
    },
    sorting: {
        fieldName: undefined, //field name for sort
        direct: undefined //ASC or DESC
    },
    debug: {
        console: true,
        alert: false
    },
    styles: {
        header: {
            table: {
                classes: ["jqt-common-table", "jqt-hd-table", "bg-primary", "table-bordered"],
                css: {}
            },
            tr: {
                classes: [],
                css: {}
            },
            th: {
                classes: [],
                css: {}
            },
            td: {
                classes: [],
                css: {}
            }
        },
        body: {
            table: {
                classes: ["jqt-common-table", "jqt-bd-table"],
                css: {}
            },
            tr: {
                classes: [],
                css: {}
            },
            th: {
                classes: [],
                css: {height: 0, padding: 0, border: "none"}
            },
            td: {
                classes: [],
                css: {}
            }
        },
        footer: {
            table: {
                classes: ["ui-state-default"],
                css: {}
            },
            tr: {
                classes: [],
                css: ''
            },
            th: {
                classes: [],
                css: {}
            },
            td: {
                classes: [],
                css: {}
            }
        },
        tableBox: {
            classes: ["jqtable"],
            css: {"background-color": "#dfdfdf"}
        },
        headerBox: {
            classes: ['test1', 'test2'],
            css: {width: '100%'}
        },
        bodyBox: {
            classes: [],
            css: {}
        },
        headerBodyBox: {
            classes: [],
            css: {}
        },
        footerBox: {
            classes: [],
            css: {}
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
        $headerBox: undefined,
        $headerBodyBox: undefined, //контейнер в котором контейнеры хедера и боди
        $body: $($.parseHTML('<table><thead></thead><tbody></tbody></table>')), //body ($(#main_selector-bd))
        $bodyFirstRow: $($.parseHTML('<tr></tr>')), //первая строка body (у нее выставляем ширину колонок)
        $bodyBox: undefined,
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
        $pgPagesCount: undefined
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

        isBuilt: false
    },

    header: {
        columns: {}, //сюда копируется header.columns из  model.header.columns с пересчетом всех размеров в px
        fixedColWidth: 0, //суммарная ширина колонок которые заданы в px. Заполняется в ходе расчетов
        isBuilt: false
    },
    body: {
        data: {} //данные для body которые получаем по AJAX (rendered body template )
    },
    footer: {
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
    filter: {}, //текущие параметры фильтра
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
                //если передан сам workSet - возвращаем его
                $.each(arguments, function (key, arg) {
                        if ($.isPlainObject(arg) && arg.hasOwnProperty("mainSelector")) {
                            res = arg;
                            return false;
                        }
                });
                if (res) { return res; }

                    //пытаемся найти по селектору(предварительно отрезав #)
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
                //дописываем id к объектам  bodyObj, footerObj
                ws.obj.$body.attr("id", ws.model.body.selectors.table.slice(1)); //body
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
                //добавляем объекты body и footer в таблицу
                ws.obj.$body.appendTo(ws.obj.$headerBodyBox);
                ws.obj.$body.wrap($("<div></div>").attr("id", ws.model.wrappers.selectors.body.slice(1)));
                //добавляем firstRow in $body
                ws.obj.$body.find('thead').html(ws.obj.$bodyFirstRow);
                ws.obj.$bodyBox = $(ws.model.wrappers.selectors.body);
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
                var height = ws.table.height - (ws.obj.$headerBox.height() + ws.obj.$footerBox.height() + ws.table.marginBottom);
                ws.obj.$bodyBox.css('height', height);
            },

            footerInfo: function (workSet, info) {
                workSet = inner.getWorkSet(this, workSet);
                workSet.footer.infoCellObj.empty();
                $.each(info, function (index, value) {
                    var info = $('<span/>').html(value);
                    workSet.footer.infoCellObj.append(info);
                    $('<span/>').html(' / ').appendTo(workSet.footer.infoCellObj);
                });
                workSet.footer.infoCellObj.find('span').last().remove();
            },
            eventsPager: function (workSet) {
                workSet = inner.getWorkSet(this, workSet);
                $(workSet.model.pager.selector).on(
                    'click',
                    workSet,
                    function (event) {
                        var currentTime = $.now();
                        if ((currentTime - APP.lastClick) < 300) {
                            return;
                        }
                        APP.lastClick = currentTime;
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
            globalEvents: function (ws) {
                $(window).resize(function (event) {
                    //Обновляем внешние размеры
                    inner.windowSize(ws);
                    inner.tableBoxParentWidth(ws);

                    inner.sizeToPx(ws);
                    inner.columnsIdUpdate(ws);
                    inner.updateSizes(ws);
                    //inner.setFirstRowBodyWidth(workSet);
                });
            },
            //пересчет размеров в px
            sizeToPx: function (ws) {
                //Обновляем внешние размеры
                inner.windowSize(ws);
                inner.tableBoxParentWidth(ws);

                inner.tableWidthToPx(ws);
                inner.tableHeightToPx(ws);
                inner.colSizesToPx(ws);
                inner.pagerWidthToPx(ws);
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
                    console.log('Не задана ширина таблицы ' + ws.mainSelector);
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
                    console.log('Не задана высота таблицы ' + ws.mainSelector);
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
                var maxWidth = {val: 0, key: ''};
                $.each(md.header.columns, function (key, colModel) {
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
                ws.header.fixedColWidth = acc; //запоминаем фиксированную часть ширины, чтобы каждый раз не пересчитывать
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
                /**
                 * корректируем расхождение суммарной ширины ячеек и ширины контейнера
                 * путем вычитания разности из ширины самой широкой ячейки
                 */
                headerPx.columns[maxWidth.key].width -= acc - tableWidth;
                // workSet.header = headerPx; //запоминаем получившийся массив колонок в workSet.header.columns
                ws.header = $.extend(true, ws.header, headerPx); //запоминаем получившийся массив колонок в workSet.header.columns
            },
            updateColSizesInPx: function (ws) {
                var md = ws.model;
                var tableWidth = ws.table.width - ws.scrolls.Y_scrollWidth - ws.scrolls.scrollMargin;
                var freeWidth = tableWidth - ws.header.fixedColWidth;

                var acc = 0;
                var maxWidth = {val: 0, key: ''}; //воременный объект для хранения самой широкой колонки
                $.each(ws.header.columns, function (name, column) {
                    if (column.fixed === false) {
                        column.width = Math.round(freeWidth *  md.header.columns[name].width / 100);
                        acc += column.width;

                        if (maxWidth.val < column.width) {
                            maxWidth.val = column.width;
                            maxWidth.key = name;
                        }
                    } else {
                        if (maxWidth.val < column.width) {
                            maxWidth.val = column.width;
                            maxWidth.key = name;
                        }
                    }
                });
                /**
                 * корректируем расхождение суммарной ширины ячеек и ширины контейнера
                 * путем вычитания разности из ширины самой широкой ячейки
                 */
                ws.header.columns[maxWidth.key].width -= (acc + ws.header.fixedColWidth) - tableWidth;
            },
            pagerWidthToPx: function (ws) {
                ws.pager.width = parseInt(ws.model.pager.width)
            },
            //управление стилями
            setStylesOld: function (ws) {
                //depricated !!! use setStyles
                ws.obj.$bodyFirstRow.find('td').height(0);
                //добавляем к общему контейнеру класс для привязки стилей
                ws.obj.$tableBox.addClass("jqtable");

                /*=== Применение стилей к HEADER ===*/
                /* уровень table */
                ws.obj.$header.addClass("jqt-common-table jqt-hd-table");
                /* уровень th */
                $.each(ws.header.columns, function (key,value) {
                    if (value.class) {
                        ws.obj.$header.find('#' + value.id).addClass(value.class);
                    }
                });
                //для ячейки компенсирующей скролл удаляем все классы и добавляем "ui-state-default"
                ws.obj.$header.find('th').last().removeClass().addClass("ui-state-default");

                /*=== Применение стилей к BODY ===*/
                /*уровень контейнера tbody*/
                //todo обязательно вынести в модель таблицы
                ws.bodyObj.parent().css({'background-color': "#dfdfdf"});
                /* у первой строки делаем нулевые паддинги*/
                ws.obj.$bodyFirstRow.find('td').css({'padding': 0, border: 'none'});
                /* уровень tbody - общие классы*/
                ws.obj.$body.addClass("jqt-common-table jqt-bd-table");
                /* уровень tr (через метод applyBodyStyles, т.к он вызывается при каждом апдейте содержимого таблицы)*/
                inner.applyBodyStyles(ws);
                /*=== Применение стилей к FOOTER ===*/
                /* уровень table */
                ws.obj.$footer.addClass(ws.model.footer.tableClasses);
            },
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
            setScrollCellStyle: function (ws) {
                ws.obj.$headerScrollCell.removeClass().addClass("ui-state-default");
            },
            setInitialStyles: function (ws) {
                inner.setStyles(ws, 'body');
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
                md.header.selectors.table = ws.mainSelector + md.header.selectors.table;
                md.header.selectors.scrollCell = ws.mainSelector + md.header.selectors.scrollCell;

                md.body.selectors.table = ws.mainSelector + md.body.selectors.table;
                md.body.selectors.firstRow = ws.mainSelector + md.body.selectors.firstRow;

                md.footer.selectors.table = ws.mainSelector + md.footer.selectors.table;
                md.footer.selectors.infoCell = ws.mainSelector + md.footer.selectors.infoCell;
                md.footer.selectors.firstRow = ws.mainSelector + md.footer.selectors.firstRow;

                md.wrappers.selectors.table = ws.mainSelector + md.wrappers.selectors.table;
                md.wrappers.selectors.header= ws.mainSelector + md.wrappers.selectors.header;
                md.wrappers.selectors.body = ws.mainSelector + md.wrappers.selectors.body;
                md.wrappers.selectors.footer = ws.mainSelector + md.wrappers.selectors.footer;
                md.wrappers.selectors.headerBody = ws.mainSelector + md.wrappers.selectors.headerBody;

                md.pager.selectors.pager = ws.mainSelector + md.pager.selectors.pager;
                md.pager.selectors.input = ws.mainSelector + md.pager.selectors.input;
                md.pager.selectors.rowsOnPageList = ws.mainSelector + md.pager.selectors.rowsOnPageList;
                md.pager.selectors.pagesCount = ws.mainSelector + md.pager.selectors.pagesCount;
            },
            buildFooter: function (ws) {
                inner.buildPager(ws);
                inner.buildInfoCell(ws);
                //append first row to footer and info cell and pager
                ws.obj.$footer.find('tbody').append(
                    $('<tr/>', {
                        id: ws.model.footer.selectors.firstRow.slice(1)
                    })
                );
                //add to $footer $footerInfo and $pager
                var $firstRw = ws.obj.$footer.find(ws.model.footer.selectors.firstRow);
                $firstRw.append(ws.obj.$footerInfo).append(ws.obj.$pager);
            },
            buildInfoCell: function (ws) {
                ws.obj.$footerInfo = $('<td/>', {
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
                }).width(20);
                ws.obj.$pgPagesCount = $('<span/>', {
                    id: ws.model.pager.selectors.pagesCount.slice(1)
                }).text('');

                ws.obj.$pager = $('<td/>', {
                    id: ws.model.pager.selectors.pager.slice(1),
                    class: 'jqt-pager',
                    align: 'center'
                }).append(
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
                ws.obj.$rowsOnPageList = $('<select/>', { id: ws.model.pager.selectors.rowsOnPageList});
                $.each(ws.model.pager.rowList, function () {
                    $('<option/>', {
                        value: this,
                        text: this
                    }).appendTo(ws.obj.$rowsOnPageList);
                });
            },
            columnsIdUpdate: function (ws) {
                $.each(ws.header.columns, function (key,value) {
                    if (typeof value.id === 'undefined') {
                        value.id = key;
                    }
                    value.th_id = ws.mainSelector.slice(1) + '_th_' + value.id;
                    value.td_id = ws.mainSelector.slice(1) + '_td_' + value.id;
                });
                $.each(ws.model.header.columns, function (key,value) {
                    if (typeof value.id === 'undefined') {
                        value.id = key;
                    }
                    value.th_id = ws.mainSelector.slice(1) + '_th_' + value.id;
                    value.td_id = ws.mainSelector.slice(1) + '_td_' + value.id;
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
                        data: dataRequest
                    })
                        .done(function (data, textStatus, jqXHR) {
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
                        tag.attr({"id": value.th_id, title: value.name})
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
                    $('<th></th>').attr({"id": value.td_id}).appendTo(ws.obj.$bodyFirstRow);
                });
            },
            //управление размерами
            setColumnWidth: function (ws) {
                $.each(ws.header.columns, function (key,value) {
                    ws.obj.$header.find('#' + value.th_id).outerWidth(value.width);
                    ws.obj.$bodyFirstRow.find('#' + value.td_id).outerWidth(value.width);
                });
                //установка ширины ячейки компенсации скрола
                ws.obj.$headerScrollCell.outerWidth(ws.scrolls.Y_scrollWidth + ws.scrolls.scrollMargin);
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
                ws.obj.$tableBox.width(ws.table.tableBoxParentWidth);
                ws.table.X_Scroll_enable = ws.table.width > 0 && ws.table.width > ws.table.tableBoxParentWidth;
                //ширина заголовка
                ws.obj.$header.outerWidth(ws.table.width);
                //ширину контейнера body
                ws.obj.$bodyBox.width(ws.table.width);
                //ширина таблицы body
                var bodyWidth = ws.table.width;
                if (ws.table.Y_Scroll_enable) {
                    bodyWidth = bodyWidth - ws.scrolls.Y_scrollWidth - ws.scrolls.scrollMargin
                }
                ws.obj.$body.width(bodyWidth);
                //ширина ячейки пейджинатора
                ws.obj.$pager.outerWidth(ws.pager.width);
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
                var element = $("<div></div>").attr("id", ws.mainSelector.slice(1) + '-scrl-outer').css(styles).append($("<div></div>").attr("id", ws.mainSelector.slice(1) + '-scrl-inner').css({height: "100%"}));
                $('body').append(element);
                ws.scrolls.Y_scrollWidth = $(ws.mainSelector + '-scrl-outer').width() - $(ws.mainSelector + '-scrl-inner').width();
                ws.scrolls.X_scrollWidth = $(ws.mainSelector + '-scrl-outer').height() - $(ws.mainSelector + '-scrl-inner').height();
                $(ws.mainSelector + '-scrl-outer').remove();
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
                            params = inner.paramsForAjaxRequest(ws);
                            methods.updateBodyContent(ws, params)
                        } else if ($(event.target).hasClass('ui-icon-seek-end')) {
                            event.data.pager.page = event.data.pager.pages;
                            params = inner.paramsForAjaxRequest(ws);
                            methods.updateBodyContent(ws, params);
                        } else if ($(event.target).hasClass('ui-icon-seek-prev')) {
                            event.data.pager.page -= 1;
                            params = inner.paramsForAjaxRequest(ws);
                            methods.updateBodyContent(ws, params);
                        } else if ($(event.target).hasClass('ui-icon-seek-first')) {
                            event.data.pager.page = 1;
                            params = inner.paramsForAjaxRequest(ws);
                            methods.updateBodyContent(ws, params);
                        }
                    }
                );
                ws.obj.$pager.change(ws, function (event) {
                    if ($(event.target).attr('id') == event.data.model.pager.rowsOnPageSelector) {
                        ws = event.data;
                        var rowsOnPage = $(event.target).find("option:selected").text();
                        if ($.isNumeric(rowsOnPage)) {
                            ws.pager.rowsOnPage = parseInt(rowsOnPage);
                        } else {
                            ws.pager.rowsOnPage = -1;
                        }
                        Cookies.set(ws.mainSelector.slice(1) + '_rowsOnPage', rowsOnPage);
                        params = inner.paramsForAjaxRequest(ws);
                        methods.updateBodyContent(ws, params);
                    }
                });
            },
            eventHandlersSet: function (ws) {
                inner.windowResizeEvent(ws);
                inner.pagerEvents(ws)
            },
            //методы фильтра и сортировки
            filterInit: function (ws) {
                ws.filter = $.extend(true, {}, ws.model.filter);
            },
            sortingInit: function (ws) {
                ws.sorting = $.extend(true, {}, ws.model.sorting);
            },

            //методы пейджинатора
            initPagerSettings: function (ws) {
                //если в куке хранится текущая страница - берем ее в качестве текущей
                ws.pager.page = + Cookies(ws.mainSelector.slice(1) + '_page') || ws.model.pager.startPage;
                ws.pager.rowsOnPage = + Cookies(ws.mainSelector.slice(1) + '_rowsOnPage') || ws.model.pager.rowsOnPage;
            },
            updatePager: function (ws) {
                //вставить в пейджинатор номер текушей страницы, кол-во страниц
                ws.obj.$pgInput.val(ws.pager.page);
                ws.obj.$pgPagesCount.text(ws.pager.pages);
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
            paramsForAjaxRequest: function (ws) {
                return {
                    body: {
                        filter: ws.filter,
                        sorting: ws.sorting,
                        columns: ws.header.columns,
                        pager: ws.pager
                    }
                };
            }
        };
        var methods = {
            /**
             *
             * @param userOptions пользовательские установки таблицы
             */
            init: function (userOptions) {
                userOptions = userOptions || {};
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
                return $(ws);
            },
            updateBodyJSON: function (workSet, params) {

                // workSet = inner.getWorkSet(this, workSet);
                // //все данные для пейджинатора передаем через куки main_selector_pagesCount, _recordsCount, _rowsOnPage
                // //для этого передаем на сервер главный селектор
                // var requestParams = {
                //     columnList: '', //либо массив с именами полей для выгрузки, либо пусто (undefined)
                //     tableId: workSet.mainSelector.slice(1),
                //     rowsOnPage: workSet.pager.rowsOnPage, //если -1 то все строки
                //     page: workSet.pager.page,
                //     order: 'default',
                //     filters: workSet.params.filters,
                //     search: {}
                // };
                // requestParams = $.extend(true, requestParams, params);
                // function renderBody(bodyData) {
                //     var body = workSet.bodyObj.children('tbody').first();
                //     body.html(workSet.bodyFirstRowObj);
                //     body.append(bodyData.renderResult);
                //     workSet.pager.page = parseInt(bodyData.params.currentPage);
                //     workSet.pager.pages = parseInt(bodyData.params.pagesCount);
                //     workSet.pager.records = parseInt(bodyData.params.recordsCount);
                //     // //из Cookies читаем currentPage, pagesCount, recordsCount и пишем в workSet
                //     // workSet.pager.page = +Cookies(workSet.mainSelector.slice(1) + '_currentPage');
                //     // workSet.pager.pages = +Cookies(workSet.mainSelector.slice(1) + '_pagesCount');
                //     // workSet.pager.records = +Cookies(workSet.mainSelector.slice(1) + '_recordsCount');
                //     var info = {};
                //     if (bodyData.info.recordsCount) {
                //         info.recordsCount = 'Всего записей: ' + bodyData.info.recordsCount;
                //     }
                //     if (bodyData.info.peopleCount) {
                //         info.peopleCount = 'Сотрудников: ' + bodyData.info.peopleCount;
                //     }
                //     inner.footerInfo(workSet, info);
                //     inner.updatePager(workSet);
                //     inner.applyBodyStyles(workSet);
                //     $(".toggler").on(
                //         "click",
                //         function ($e) {
                //             $(this).closest("table").find("tbody tr td").slideToggle("fast");
                //             $(this).find(".js-caret").toggleClass("caret-down");
                //         }
                //     );
                // }
                // $.ajax({
                //     url: workSet.model.dataUrl,
                //     data: requestParams,
                //     success: renderBody,
                //     error: function () {
                //         console.log('Error in Ajax');
                //     }
                // });

            },
            updateBodyContent: function (ws, requestParams) {
                requestParams.tableId = ws.mainSelector.slice(1);
                $.ajax({
                    url: ws.model.dataUrl,
                    data: requestParams
                    })
                    .done(function (data, textStatus, jqXHR) {
                        ws.obj.$body.find('tbody').html(data.body.html);
                        //todo Добавить сюда запись значений пейджинатора
                        inner.updatePager(ws);
                    })
                    .fail(function (jqXHR, textStatus, errorThrown) {
                        inner.debug(ws, 'update body content: ' + jqXHR.responseText);
                    });

            },
            updateBodyHTML: function (workSet, params) {
            //
            //     workSet = inner.getWorkSet(this, workSet);
            //     //все данные для пейджинатора передаем через куки main_selector_pagesCount, _recordsCount, _rowsOnPage
            //     //для этого передаем на сервер главный селектор
            //     var requestParams = {
            //         columnList: '', //либо массив с именами полей для выгрузки, либо пусто (undefined)
            //         tableId: workSet.mainSelector.slice(1), //все данные передаем через куки main_selector_pagesCount ...
            //         rowsOnPage: workSet.pager.rowsOnPage, //если -1 то все строки
            //         page: workSet.pager.page,
            //         order: 'default',
            //         filters: {},
            //         search: {}
            //     };
            //     requestParams = $.extend(true, requestParams, params);
            //     function renderBody(bodyData) {
            //         var body = workSet.bodyObj.children('tbody').first();
            //         body.html(workSet.bodyFirstRowObj);
            //         body.append(bodyData);
            //         //из Cookies читаем currentPage, pagesCount, recordsCount и пишем в workSet
            //         workSet.pager.page = +Cookies(workSet.mainSelector.slice(1) + '_currentPage');
            //         workSet.pager.pages = +Cookies(workSet.mainSelector.slice(1) + '_pagesCount');
            //         workSet.pager.records = +Cookies(workSet.mainSelector.slice(1) + '_recordsCount');
            //         inner.updatePager(workSet);
            //         inner.applyBodyStyles(workSet);
            //         $(".toggler").on(
            //             "click",
            //             function ($e) {
            //                 $(this).closest("table").find("tbody tr td").slideToggle("fast");
            //                 $(this).find(".js-caret").toggleClass("caret-down");
            //             }
            //         );
            //     }
            //     $.ajax({
            //         url: workSet.model.dataUrl,
            //         data: requestParams,
            //         success: renderBody,
            //         error: function () {
            //             console.log('Error in Ajax');
            //         }
            //     });
            //
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