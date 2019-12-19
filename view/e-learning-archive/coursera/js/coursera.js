// we only have jQuery 3.2 and Bootstrap 3.3 here, no ES6, no React....
// Bootstrap docs https://getbootstrap.com/docs/3.3/
const $ = jQuery = window.jQuery;

/**
 * jQuery Get Browser Plugin
 *
 * @version: 1.01
 * @author: Pablo E. Fernández (islavisual@gmail.com).
 * Copyright 2016-2018 Islavisual.
 * Licensed under MIT (https://github.com/islavisual/getBrowser/blob/master/LICENSE).
 * Last update: 07/02/2018
 **/
(function ($) {
    $.extend({
        browser: function () {
            var ua = navigator.userAgent, tem,
                M = ua.match(/(opera|chrome|safari|firefox|msie|trident(?=\/))\/?\s*(\d+)/i) || [];
            if (/trident/i.test(M[1])) {
                tem = /\brv[ :]+(\d+)/g.exec(ua) || [];
                M[1] = "Internet Explorer";
                M[2] = tem[1];
            }
            if (M[1] === 'Chrome') {
                tem = ua.match(/\b(OPR|Edge)\/(\d+)/);
                if (tem != null) M[1] = tem.slice(1).join(' ').replace('OPR', 'Opera'); else M[1] = "Chrome";

            }
            M = M[2] ? [M[1], M[2]] : [navigator.appName, navigator.appVersion, '-?'];
            if ((tem = ua.match(/version\/(\d+)/i)) != null) M.splice(1, 1, tem[1]);

            var firefox = /firefox/.test(navigator.userAgent.toLowerCase()) && !/webkit    /.test(navigator.userAgent.toLowerCase());
            var webkit = /webkit/.test(navigator.userAgent.toLowerCase());
            var opera = /opera/.test(navigator.userAgent.toLowerCase());
            var msie = /edge/.test(navigator.userAgent.toLowerCase()) || /msie/.test(navigator.userAgent.toLowerCase()) || /msie (\d+\.\d+);/.test(navigator.userAgent.toLowerCase()) || /trident.*rv[ :]*(\d+\.\d+)/.test(navigator.userAgent.toLowerCase());
            var prefix = msie ? "" : (webkit ? '-webkit-' : (firefox ? '-moz-' : ''));

            return {
                name: M[0],
                version: M[1],
                firefox: firefox,
                opera: opera,
                msie: msie,
                chrome: webkit,
                prefix: prefix
            };
        }
    });
    jQuery.browser = $.browser();
})(jQuery);


function isValid(element, validation, message) {
    if (validation(element.val())) {
        element.removeClass('error');
        element.siblings('.error-message').empty();
        return true;
    }

    element.addClass('error');
    element.siblings('.error-message').empty().append(document.createTextNode(message))
    return false;
}

function validate(modal, page) {
    console.log("Validate page", page, "on modal", modal)
    var form = $('form', modal);
    console.log("Validate form", form)
    const required = function(val) {
        return val.trim().length > 0
    };

    switch (page) {
        case 1:
            var has_error = false;
            has_error |= !isValid(
                $('input[name=coursera-link]', form),
                required,
                "This field is required"
            );
            has_error |= !isValid(
                $('#cauth', form),
                required,
                "This field is required"
            );

            return ! has_error;
    }
}

function getCourseraSyllabus(data, progress) {
    console.log("getCourseraSyllabus");
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: '/view/e-learning-archive/controllers/coursera.php',
            method: 'get',
            data: data,
            xhr: function() {
                var xhr = new window.XMLHttpRequest();
                xhr.addEventListener(
                    "progress",
                    function(evt){
                        progress(xhr.responseText)
                    },
                    false
                );
                return xhr;
            },
        })
            .done(resolve)
            .fail(reject)
    });
}

function showPage(modal, page) {
    switch (page) {
        case 1:
            //
            break;
        case 2:
            var spinner = $(modal).find('.modal-split').eq(page - 1).find('.loader');
            var content = $(modal).find('.modal-split').eq(page - 1).find('.content');
            var url = $('input[name=coursera-link]', modal).val();
            var cauth = $('#cauth', modal).val();

            var update = function(response) {
                spinner.hide();
                content.empty().append(response);
            };
            var error = function(response) {
                    spinner.hide();
                    content.empty().append(
                        "<h2>Error!</h2><p>Could not get the Coursera syllabus for <a href='" + url + "'>" + url + "</a></p>" +
                        "<p>Received <strong>code " + response.status + "</strong> and the following output: <div class='alert alert-danger'>" + response.responseText + "</div>"
                    );
                };

            getCourseraSyllabus({url: url, cauth: cauth}, update).then(update, error);
            break;
    }
}

function prepareModal() {
    $(".modal.multi-step").each(function () {

        var nextLabel = 'Continue ';
        var backLabel = 'Back';
        var submitLabel = 'Done';

        var modal = this;
        var pages = $(modal).find('.modal-split');

        if (pages.length !== 0) {
            pages.hide();
            pages.eq(0).show();

            var back = document.createElement("button");
            back.setAttribute("type", "button");
            back.setAttribute("class", "btn btn-outline-secondary");
            back.setAttribute("style", "display: none;");
            back.innerHTML = backLabel;

            var next = document.createElement("button");
            next.setAttribute("type", "button");
            next.setAttribute("class", "btn btn-primary");
            next.innerHTML = nextLabel;

            $(this).find('.modal-footer').append(back).append(next);


            var page = 0;

            $(next).on('click', function() {

                if (validate(modal, page + 1)) {

                    this.blur();

                    if (page === 0) {
                        $(back).show();
                    }

                    if (page === pages.length - 2) {
                        $(next).text(submitLabel);
                    }

                    if (page === pages.length - 1) {
                        $(modal).find("form").submit();
                    }

                    if (page < pages.length - 1) {
                        page++;

                        pages.hide();
                        pages.eq(page).show();
                        showPage(modal, page + 1);
                    }
                }
            });

            $(back).on('click', function () {

                if (page === 1) {
                    $(back).hide();
                }

                if (page === pages.length - 1) {
                    $(next).text(nextLabel);
                }

                if (page > 0) {
                    page--;

                    pages.hide();
                    pages.eq(page).show();

                    showPage(modal, page + 1);
                }
            });
        }
    });
}


function init() {
    $('#coursera-tab button').on('click', function () {
        // manipulate the DOM like a barbarian
        $("#courseraModal .copy-target")
            .empty()
            .append(
                $('#coursera-tab .copy-source > .row')
                    .clone()
                    .removeClass('row')
            );

        $(".browser").hide();
        if ($.browser.chrome) {
            $(".browser-chrome").show();
        } else if ($.browser.firefox) {
            $(".browser-firefox").show();
        } else {
            $(".browser-other").show();
        }
    });

    $('#open-browser-extension-tooltip').popover({
        html: true,
        trigger: 'focus',
        content: function () {
            return $($(this).data("tooltip-content")).html();
        },
        title: function () {
            return $(this).data('tooltip-title');
        }
    });

    prepareModal();

}


(function(jQuery){
    jQuery.fn.checktree = function(){
        jQuery(':checkbox').on('click', function (event){
            event.stopPropagation();
            var clk_checkbox = jQuery(this),
                chk_state = clk_checkbox.is(':checked'),
                parent_li = clk_checkbox.closest('li'),
                parent_uls = parent_li.parents('ul');
            parent_li.find(':checkbox').prop('checked', chk_state);
            parent_uls.each(function(){
                parent_ul = jQuery(this);
                parent_state = (parent_ul.find(':checkbox').length == parent_ul.find(':checked').length);
                parent_ul.siblings(':checkbox').prop('checked', parent_state);
            });
        });
    };
}(jQuery));

$(function () {
    init();
    // DEV HELPER
    $('#coursera-tab button').get(0).click()
});
