$(function () {
    $( "#menu" ).menu();

// Добавление статуса
    var addStatus = $("#addStatus-modal").dialog({
        autoOpen: false,
        height: "auto",
        width: "auto",
        modal: true
    });

    $("#addStatus-btnOpen").on("click", function () {
        addStatus.dialog("open");
    });
    $("#addStatus-btnClose").on("click", function () {
        addStatus.dialog("close");
    });

// Добавление региона
    var addRegion = $("#addRegion-modal").dialog({
        autoOpen: false,
        height: "auto",
        width: "auto",
        modal: true
    });

    $("#addRegion-btnOpen").on("click", function () {
        addRegion.dialog("open");
    });
    $("#addRegion-btnClose").on("click", function () {
        addRegion.dialog("close");
    });

// Добавление города
    var addCity = $("#addCity-modal").dialog({
        autoOpen: false,
        height: "auto",
        width: "auto",
        modal: true
    });

    $("#addCity-btnOpen").on("click", function () {
        addCity.dialog("open");
    });
    $("#addCity-btnClose").on("click", function () {
        addCity.dialog("close");
    });

// Добавление офиса
    var addOffice = $("#addOffice-modal").dialog({
        autoOpen: false,
        height: "auto",
        width: "auto",
        modal: true
    });

    $("#addOffice-btnOpen").on("click", function () {
        addOffice.dialog("open");
    });
    $("#addOffice-btnClose").on("click", function () {
        addOffice.dialog("close");
    });

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
        }).addClass('col-xs-7');
    });
    platformTab.click(function() {
        deviceTab.removeClass(function (index, className) {
            var clName = className.match(/(^|\s)col-(xs|sm|md|lg)-\d+/g).join(' ');
            console.log(clName);
            return clName;
        }).addClass('col-xs-7');
    });

// Добавление вендора
    var addVendor = $("#addVendor-modal").dialog({
        autoOpen: false,
        height: "auto",
        width: "auto",
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
        height: "auto",
        width: "auto",
        modal: true
    });

    $("#editVendor-btnOpen").on("click", function () {
        editVendor.dialog("open");
    })
    $("#editVendor-btnClose").on("click", function () {
        editVendor.dialog("close");
    })


// Добавление платформы
    var addPlatform = $("#addPlatform-modal").dialog({
        autoOpen: false,
        height: "auto",
        width: "auto",
        modal: true
    });

    $("#addPlatform-btnOpen").on("click", function () {
        addPlatform.dialog("open");
    })
    $("#addPlatform-btnClose").on("click", function () {
        addPlatform.dialog("close");
    })

// Добавление ПО платформы
    var addSoftware = $("#addSoftware-modal").dialog({
        autoOpen: false,
        height: "auto",
        width: "auto",
        modal: true
    });

    $("#addSoftware-btnOpen").on("click", function () {
        addSoftware.dialog("open");
    })
    $("#addSoftware-btnClose").on("click", function () {
        addSoftware.dialog("close");
    })
// Детальная инфа по офису
    var officeDetail = $("#officeDetails-modal").dialog({
        autoOpen: false,
        height: "auto",
        width: "auto",
        modal: true
    })
    $(".office-row").on("click", function() {
        console.log($(this).data("id"));
        officeDetail.dialog("open");
    })
})


