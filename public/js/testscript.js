jQuery(function ($) {
    var tabs = $(".nav-tabs li[role='tab']");
    var panels = $(".tab-content div[role=tabpanel]");

    tabs.each(function() {
        $(this).on( "click", function (event) {
            tabs.filter(".active").removeClass("active");
            $(event.target).parent().addClass("active");
            panels.filter(".active").removeClass("active");
            var panelId = $(event.target).parent().attr("aria-controls");
            console.log("[id=" + panelId + "]");
            panels.filter("[id=" + panelId + "]").addClass("active");
        })
    })
});

