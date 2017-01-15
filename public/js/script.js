$(function () {
    console.log("hello");
    $( "#date" ).datepicker();
    $("#accordion").accordion();
    $( "#menu" ).menu();

    var dialog = $("#modal-1-form").dialog({
        autoOpen: false,
        height:200,
        width: 350,
        modal: true
    });

    $("#add-reg").on("click", function () {
        dialog.dialog("open");
    })
    $("#close").on("click", function () {
        dialog.dialog("close");
    })
})

