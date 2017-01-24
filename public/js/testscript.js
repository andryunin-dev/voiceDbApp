jQuery(function ($) {
    var allTestTabs = $("ul[data-panelName='test'].nav-tabs li[role='presentation']");
    var allTestPanels = $("div[data-panelName='test'].tab-content div[role=tabpanel]");

    function removeActiveClass(collection) {
        collection.each(function() {
                $(this).filter(".active").removeClass("active");
        });
    }

    allTestTabs.each(function() {
        $(this).on( "click", function (event) {
            removeActiveClass(allTestTabs);
            $(event.target).parent().addClass("active");
            removeActiveClass(allTestPanels);
            allTestPanels.filter($(event.target).attr("href")).addClass("active");
        })
    })
});

