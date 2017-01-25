jQuery(function ($) {
    var tabs = $(".nav-tabs[role='tablist'] li[role='tab']");
    var panels = $(".tab-content div[role=tabpanel]");

    tabs.each(function() {
        $(this).on( "click", function (event) {
            tabs.filter("[aria-selected='true']").removeClass("active").attr('aria-selected','false');
            $(this).addClass("active").attr('aria-selected','true');
            panels.filter(".active").removeClass("active");
            var panelId = $(this).attr("aria-controls");
            console.log("[id=" + panelId + "]");
            panels.filter("[id=" + panelId + "]").addClass("active");
        })
    })
});

