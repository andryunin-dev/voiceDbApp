$(function () {
    $( "#date" ).datepicker();
    $("#accordion").accordion();
    $( "#menu" ).menu();
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
        height:400,
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
        height:400,
        width: 350,
        modal: true
    });

    $("#addOffice-btnOpen").on("click", function () {
        addOffice.dialog("open");
    })
    $("#addOffice-btnClose").on("click", function () {
        addOffice.dialog("close");
    })
})


