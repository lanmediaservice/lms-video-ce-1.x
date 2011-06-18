JSAN.require('LMS.Widgets');

LMS.Widgets.Factory = function(widgetName) {
    if (widgetName.indexOf('$') == -1) {
        JSAN.require("LMS.Widgets." + widgetName);
        return new LMS.Widgets[widgetName]();
    } else {
        widgetName = widgetName.substring(1);
        JSAN.require(widgetName);
        var objects = widgetName.split('.');
        var widgetClass = window; 
        for (var i=0; i<objects.length; i++) {
            var objectName = objects[i];
            widgetClass = widgetClass[objectName];
        }
        return new widgetClass();
    }
};