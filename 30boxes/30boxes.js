function boxeswidgetsbcontrol( number ) {
    if(number.toString().search(/^-?[0-9]+$/) == 0) {
        jQuery("#30boxeswidget-" + number + "-extras > div").hide();
        var $el= jQuery('#30boxeswidget-' + number + '-extras-' + jQuery("#30boxeswidget-" + number + "-type").val() );
        if ( jQuery("#30boxeswidget-" + number + "-extras > div:visible").length == 0) {
            $el.show();
        }
        else{
            jQuery("#30boxeswidget-" + number + "-extras > div:visible").hide();
            $el.show();
        }
    } 
 }
