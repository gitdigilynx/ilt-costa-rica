//Add file type image resolution to input
jQuery(document).ready(function () {
    jQuery('input[type=file]').each(function () {
        addImgResAttr(jQuery(this));
        var nextElm = jQuery(this).next();
        if (nextElm.attr('class') == 'easyadmin-thumbnail' && nextElm.find('img')) {
            jQuery(this).removeAttr('required');
        }
    });
    jQuery('input[type=file]').on('change', function () {
        addImgResAttr(jQuery(this));
    });
});

function validateForm() {
    jQuery("*[class^=invalid-feedback]").remove();
    let error = false;
    let formElm = jQuery("form[class^=validate-form]");

    let errorCount = 0;
    let errorFound = validateRequired(formElm, error, errorCount);
    countErrorOnTabs();

    if (errorFound[0]) {
        if (jQuery(".has-error:first").length > 0) {
            scrollToValidationError(jQuery(".has-error:first").offset().top - 200);
        }
        return errorFound[0];
    }

    errorCount = 0;
    var errorFoundAttr = validateAttr(formElm, error, errorCount);
    if (errorFoundAttr[0]) {
        if (jQuery(".has-error:first").length > 0) {
            scrollToValidationError(jQuery(".has-error:first").offset().top);
        }
    }
    return errorFoundAttr[0];
}

function scrollToValidationError(location) {
    const TOP_NAV_BAR_OFFSET = 64;
    let page = jQuery("html, body");
    const scrollAnimationInterrupts = "scroll mousedown wheel DOMMouseScroll mousewheel keyup touchmove";

    page.on(scrollAnimationInterrupts, function () {
        page.stop();
    });

    location -= TOP_NAV_BAR_OFFSET;
    page.stop(); // Halt any current animations.
    page.animate({
            scrollTop: location
        },
        2000,
        function () {
            page.off(scrollAnimationInterrupts);
        }
    );
}

function validateRequired(elm, error, errorCount) {
    elm.find("input").each(function () {
        if (jQuery(this).attr("type") !== "hidden") {
            jQuery(this).parents(".has-error:eq(0)").removeClass("has-error");
            jQuery(this).parents('.form-group:first').removeClass("has-error");
            // check required fields
            if (jQuery(this).attr("required") == "required" && !jQuery(this).val() && !jQuery(this).attr("disabled")) {
                if (jQuery(this).attr("type") == "file" && !jQuery(this).parents(".easyadmin-vich-image").find(".easyadmin-thumbnail").length) {
                    flagElementError(jQuery(this), 'required');
                    error = true;
                    errorCount = parseInt(errorCount) + parseInt(1);
                } else if (jQuery(this).attr("type") != "file") {
                    flagElementError(jQuery(this), "required");
                    error = true;
                    errorCount = parseInt(errorCount) + parseInt(1);
                }
            }
        }
    });

    elm.find('textarea').each(function () {
        jQuery(this).parents(".has-error:eq(0)").removeClass('has-error');
        var parent = jQuery(this).prev();
        if (parent.hasClass('required') || jQuery(this).attr('required')) {
            if (!jQuery(this).val() && !jQuery(this).attr('readonly')) {
                flagElementError(jQuery(this), 'required');
                error = true;
                errorCount = parseInt(errorCount) + parseInt(1);
            }
        }
    });

    var nameRadio = '';
    elm.find('input[type=radio]').each(function () {
        if (nameRadio == jQuery(this).attr("name")) {
            return;
        }
        jQuery(this).parents(".has-error:eq(0)").removeClass('has-error');
        nameRadio = jQuery(this).attr("name");
        // check required fields
        if (!jQuery("input:radio[name=\'" + nameRadio + "\']").is(":checked") && jQuery("input:radio[name=\'" + nameRadio + "\']").attr('required') == 'required' && (typeof jQuery("input:radio[name=\'" + nameRadio + "\']").attr('readonly') === 'undefined' && typeof jQuery("input:radio[name=\'" + nameRadio + "\']").attr('disabled') === 'undefined')) {
            flagElementError(jQuery(this).parents('.form-check').siblings(":last"), 'required');
            error = true;
            errorCount = parseInt(errorCount) + parseInt(1);
        }
    });

    var nameCheckbox = '';
    elm.find('input[type=checkbox]').each(function () {
        if (nameCheckbox == jQuery(this).attr("name")) {
            return;
        }
        jQuery(this).parents(".has-error:eq(0)").removeClass('has-error');
        nameCheckbox = jQuery(this).attr("name");
        // check required fields
        if (jQuery("input:checkbox[name=\'" + nameCheckbox + "\']").length > 1) {
            if (!jQuery("input:checkbox[name=\'" + nameCheckbox + "\']").is(":checked") && jQuery("input:checkbox[name=\'" + nameCheckbox + "\']").parents('.form-group:first').find('legend:first').hasClass('required') && (typeof jQuery("input:checkbox[name=\'" + nameCheckbox + "\']").attr('readonly') === 'undefined' && typeof jQuery("input:checkbox[name=\'" + nameCheckbox + "\']").attr('disabled') === 'undefined')) {
                console.log(jQuery(this).parents('.form-check').siblings(":last"));
                flagElementError(jQuery(this).parents('.form-check').siblings(":last"), 'required');
                error = true;
                errorCount = parseInt(errorCount) + parseInt(1);
            }
        } else {
            if (!jQuery("input:checkbox[name=\'" + nameCheckbox + "\']").is(":checked") && jQuery("input:checkbox[name=\'" + nameCheckbox + "\']").attr('required') == 'required' && (typeof jQuery("input:checkbox[name=\'" + nameCheckbox + "\']").attr('readonly') === 'undefined' && typeof jQuery("input:checkbox[name=\'" + nameCheckbox + "\']").attr('disabled') === 'undefined')) {
                flagElementError(jQuery(this).parent('div'), 'required');
                error = true;
                errorCount = parseInt(errorCount) + parseInt(1);
            }
        }
    });

    //validate collection required
    elm.find('.required-collection .field-collection').each(function () {
        jQuery(this).parents(".has-error:eq(0)").removeClass('has-error');
        if (jQuery(this).find('input').length < 1) {
            flagElementError(jQuery(this), 'required');
            error = true;
            errorCount = parseInt(errorCount) + parseInt(1);
        }
    });

    elm.find('select').each(function () {
        jQuery(this).parents(".has-error:eq(0)").removeClass('has-error');
        if (jQuery(this).attr('type') !== 'hidden') {
            jQuery(this).parents('.form-group:first').removeClass('has-error');
            // check required fields
            var element = jQuery(this);
            if(element != null) {
                if (element && element.attr('required') == 'required' && (!element.val() || element.val().length === 0) && (!element.attr('readonly') && !element.attr('disabled'))) {
                    flagElementError(jQuery(this), 'required');
                    error = true;
                    errorCount = parseInt(errorCount) + parseInt(1);
                }
            }
        }
    });

    return [error, errorCount];
}

function validateAttr(elm, error, errorCount) {
    elm.find('input').each(function () {
        var element = jQuery(this);
        if (jQuery(this).attr('type') !== 'hidden') {
            jQuery(this).parents(".has-error:eq(0)").removeClass('has-error');
            if (jQuery(this).val() && !jQuery(this).attr('readonly')) {
                if (jQuery(this).attr('data-validate') && jQuery(this).data('validate')) {
                    var rules = jQuery(this).data('validate').split(',');
                    jQuery.each(rules, function (index, vals) {
                        var validate = vals.split(':');
                        //check file
                        if (element.attr('type') == 'file') {
                            if (!error) {
                                if (validate[0] == 'filetype') {
                                    var types = validate[1].split('|');
                                    if (types.indexOf(element[0].files[0].type) < 0) {
                                        flagElementError(element, 'filetype');
                                        error = true;
                                        errorCount = parseInt(errorCount) + parseInt(1);
                                    }
                                }
                            }

                            if (!error) {
                                if (validate[0] == 'filesize') {
                                    if (element[0].files[0].size > validate[1]) {
                                        flagElementError(element, 'filesize', (validate[1] / 1000) + ' kb');
                                        error = true;
                                        errorCount = parseInt(errorCount) + parseInt(1);
                                    }
                                }
                            }

                            if (!error) {
                                if (validate[0] == 'filewidth') {
                                    if (element.attr('data-imgwidth')) {
                                        if (parseInt(element.attr('data-imgwidth')) < parseInt(validate[1])) {
                                            flagElementError(element, 'filewidth', validate[1] + 'px');
                                            error = true;
                                            errorCount = parseInt(errorCount) + parseInt(1);
                                        }
                                    } else {
                                        flagElementError(element, 'filewidth', validate[1] + 'px');
                                        error = true;
                                        errorCount = parseInt(errorCount) + parseInt(1);
                                    }
                                }
                            }

                            if (!error) {
                                if (validate[0] == 'fileheight') {
                                    if (element.attr('data-imgheight')) {
                                        if (parseInt(element.attr('data-imgheight')) < parseInt(validate[1])) {
                                            flagElementError(element, 'fileheight', validate[1] + 'px');
                                            error = true;
                                            errorCount = parseInt(errorCount) + parseInt(1);
                                        }
                                    } else {
                                        flagElementError(element, 'fileheight', validate[1] + 'px');
                                        error = true;
                                        errorCount = parseInt(errorCount) + parseInt(1);
                                    }
                                }
                            }
                        }

                        if (validate[0] == 'minlength') {
                            if (element.val().length < validate[1]) {
                                flagElementError(element, 'minlength', validate[1]);
                                error = true;
                                errorCount = parseInt(errorCount) + parseInt(1);
                            }
                        }

                        if (validate[0] == 'maxlength') {
                            if (element.val().length > validate[1]) {
                                flagElementError(element, 'maxlength', validate[1]);
                                error = true;
                                errorCount = parseInt(errorCount) + parseInt(1);
                            }
                        }

                        if (validate[0] == 'isnumber') {
                            if (!jQuery.isNumeric(element.val())) {
                                flagElementError(element, 'isnumber');
                                error = true;
                                errorCount = parseInt(errorCount) + parseInt(1);
                            }
                        }

                        if (validate[0] == 'isfloat') {
                            if (element.val() % 1 == 0) {
                                flagElementError(element, 'isfloat');
                                error = true;
                                errorCount = parseInt(errorCount) + parseInt(1);
                            }
                        }

                        if (validate[0] == 'morethanzero') {
                            if (parseInt(element.val()) < 1) {
                                flagElementError(element, 'morethanzero');
                                error = true;
                                errorCount = parseInt(errorCount) + parseInt(1);
                            }
                        }
                    });
                }
            }
        }
    });

    return [error, errorCount];
}

function flagElementError(elm, msg, customvalue = '') {

    if (elm.parents('.form-group:first').length > 0) {
        elm.parents('.form-group:first').addClass('has-error');
    } else {
        elm.parent().addClass('has-error');
    }
    elm.after('<div class="invalid-feedback d-block ' + elm.attr('id') + '"><span class="d-block"><span class="form-error-icon badge badge-danger text-uppercase">' + validateErrorMsg['error'] + '</span></span><span class="form-error-message">' + validateErrorMsg[msg] + ' ' + customvalue + '</span></div>');
}

function removeFlagElementError(elm, msg, customvalue = '') {

    if (elm.parents('.form-group:first').length > 0) {
        elm.parents('.form-group:first').removeClass('has-error');
    } else {
        elm.parent().removeClass('has-error');
    }
    elm.next().remove();
}

function addImgResAttr(elm) {
    var _URL = window.URL || window.webkitURL;
    var element = elm;
    if (element[0].files.length > 0 && element.data('validate')) {
        var rules = element.data('validate').split(',');
        jQuery.each(rules, function (index, vals) {
            var validate = vals.split(':');
            if (validate[0] == 'filetype') {
                var types = ['image/jpeg', 'image/jpg', 'image/png', 'image/bmp'];
                if (types.indexOf(element[0].files[0].type) < 0) {
                    return false;
                }
            }

            img = new Image();
            img.src = _URL.createObjectURL(element[0].files[0]);
            img.onload = function () {
                element.attr('data-imgwidth', this.width);
                element.attr('data-imgheight', this.height);
            }
        });
    }
}

function countErrorOnTabs() {
    if (jQuery('.nav-tabs-custom ul.nav-tabs').length > 0) {
        var errorCount = 0;
        jQuery('.nav-tabs-custom ul.nav-tabs li').each(function () {
            var tab = jQuery(this).find('a');
            tab.find('span.badge').remove();
            errorCount = jQuery(tab.attr('href') + ' .has-error').length;

            if (errorCount > 0) {
                tab.append('<span class="badge badge-danger">' + errorCount + '</span>');
            }
        });
    }
}

/**
 *
 * @param {jQuery} selector
 */
function initDatePicker(selector) {
    let classToLookFor = "form_datepicker";

    let target = selector;
    if (!target.hasClass(classToLookFor)) {
        target = selector.find("." + classToLookFor);
    }

    if (target.length > 0) {
        if (!target.hasClass("readonly")) {
            target.addClass("readonly");
        }

        target.datepicker({
            dateFormat: 'MM dd, yy',
            minDate: new Date(),
            onSelect: function () {
                $(this).trigger("input");
            }
        });
    }
}

/**
 *
 * @param {jQuery} selector
 */
function initTimePicker(selector) {
    let classToLookFor = "form_timepicker";

    let target = selector;
    if (!target.hasClass(classToLookFor)) {
        target = selector.find("." + classToLookFor);
    }

    if (target.length > 0) {
        target.timepicker({
            timeFormat: "h:i a",
            interval: 30,
            dynamic: false,
            dropdown: true,
            scrollbar: true
        });
    }
}