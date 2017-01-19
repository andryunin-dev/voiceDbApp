$(function () {
    $( "#date" ).datepicker();
    $("#accordion").accordion();
    $( "#menu" ).menu();

// Добавление статуса
    var addStatus = $("#addStatus-modal").dialog({
        autoOpen: false,
        height:400,
        width: 350,
        modal: true
    });

    $("#addStatus-btnOpen").on("click", function () {
        addStatus.dialog("open");
    })
    $("#addStatus-btnClose").on("click", function () {
        addStatus.dialog("close");
    })

// Добавление региона
    var addReg = $("#addReg-modal").dialog({
        autoOpen: false,
        height:400,
        width: 350,
        modal: true
    });

    $("#addReg-btnOpen").on("click", function () {
        addReg.dialog("open");
    })
    $("#addReg-btnClose").on("click", function () {
        addReg.dialog("close");
    })
//Редактирование региона
    var editReg = $("#editReg-modal").dialog({
        autoOpen: false,
        height:400,
        width: 350,
        modal: true
    });


    var addCity = $("#addCity-modal").dialog({
        autoOpen: false,
        height:400,
        width: 350,
        modal: true
    });

    $("#regsTable").on("click", "#editReg-btnOpen", function (event) {
        editReg.dialog("open");
    })
    $("#editReg-btnClose").on("click", function () {
        editReg.dialog("close");
    })
// Добавление города
    var addCity = $("#addCity-modal").dialog({
        autoOpen: false,
        height:220,
        width: 350,
        modal: true
    });

    $("#addCity-btnOpen").on("click", function () {
        addCity.dialog("open");
    })
    $("#addCity-btnClose").on("click", function () {
        addCity.dialog("close");
    })

// Добавление офиса
    var addOffice = $("#addOffice-modal").dialog({
        autoOpen: false,
        height:670,
        width: 600,
        modal: true
    });

    $("#addOffice-btnOpen").on("click", function () {
        addOffice.dialog("open");
    })
    $("#addOffice-btnClose").on("click", function () {
        addOffice.dialog("close");
    })

//оборудование
//bootstrap
//     $('.hw-sw-dict-tabs a[href="#vendors"]' ).on("click", function (e) {
//         e.preventDefault();
//         $(this).tab('show')
//     });
//     $('.hw-sw-dict-tabs a[href="#platforms"]' ).on("click", function (e) {
//         e.preventDefault();
//         $(this).tab('show')
//     });
//     $('.hw-sw-dict-tabs a[href="#software"]' ).on("click", function (e) {
//         e.preventDefault();
//         $(this).tab('show')
//     });
//     $('.hw-sw-dict-tabs a[href="#clusters"]' ).on("click", function (e) {
//         e.preventDefault();
//         $(this).tab('show')
//     });
//JQuery
    var deviceTab = $('#hw-sw-dict-tabs');
    var vendorTab = $('#hw-sw-dict-tabs #tab-vendors');
    var platformTab = $('#hw-sw-dict-tabs #tab-platforms');
    deviceTab.tabs();
    vendorTab.click(function() {
        deviceTab.removeClass(function (index, className) {
            var clName = className.match(/(^|\s)col-(xs|sm|md|lg)-\d+/g).join(' ');
            console.log(clName);
            return clName;
        }).addClass('col-xs-5');
    });
    platformTab.click(function() {
        deviceTab.removeClass(function (index, className) {
            var clName = className.match(/(^|\s)col-(xs|sm|md|lg)-\d+/g).join(' ');
            console.log(clName);
            return clName;
        }).addClass('col-xs-6');
    });

// Добавление вендора
    var addVendor = $("#addVendor-modal").dialog({
        autoOpen: false,
        height:400,
        width: 350,
        modal: true
    });

    $("#addVendor-btnOpen").on("click", function () {
        addVendor.dialog("open");
    })
    $("#addVendor-btnClose").on("click", function () {
        addVendor.dialog("close");
    })
// Редактирование вендора
    var editVendor = $("#editVendor-modal").dialog({
        autoOpen: false,
        height:400,
        width: 350,
        modal: true
    });

    $("#editVendor-btnOpen").on("click", function () {
        editVendor.dialog("open");
    })
    $("#editVendor-btnClose").on("click", function () {
        editVendor.dialog("close");
    })

})


