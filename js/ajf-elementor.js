class AJFElementorWidget extends elementorModules.frontend.handlers.Base {
    getDefaultSettings() {

    }
    getDefaultElements() {

    }
    bindEvents() {
        AJF.on("submit", ({ post_type }) => {
            $(".archive-container[data-post-type='" + post_type + "']").addClass("loading");
        });

        AJF.on("load", ({ post_type }) => {
            $(".archive-container[data-post-type='" + post_type + "']").removeClass("loading");
        });
    }
}



jQuery(window).on('elementor/frontend/init', () => {
    const addHandler = ($element) => {
        if(elementorFrontend.isEditMode()) {
            window.AJF = new AJF_class(jQuery);
        }

        elementorFrontend.elementsHandler.addHandler(AJFElementorWidget, {
            $element,
        });
    };

    elementorFrontend.hooks.addAction('frontend/element_ready/ajf-grid.default', addHandler);
    elementorFrontend.hooks.addAction('frontend/element_ready/ajf-filters.default', addHandler);
});