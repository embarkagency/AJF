class AJFElementorWidget extends elementorModules.frontend.handlers.Base {
    getDefaultSettings() {

    }
    getDefaultElements() {

    }
    bindEvents() {
        
    }
}



jQuery(window).on('elementor/frontend/init', () => {
    const addHandler = ($element) => {
        elementorFrontend.elementsHandler.addHandler(AJFElementorWidget, {
            $element,
        });
    };

    elementorFrontend.hooks.addAction('frontend/element_ready/ajf-grid.default', addHandler);
    elementorFrontend.hooks.addAction('frontend/element_ready/ajf-filters.default', addHandler);
});