// we only have jQuery 3.2 and Bootstrap 3.3 here, no ES6, no React....
// Bootstrap docs https://getbootstrap.com/docs/3.3/
const $ = jQuery = window.jQuery;

/**
 * jQuery "Get Browser" Plugin
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
    if (validation(element)) {
        element.removeClass('error');
        element.eq(0).closest('.logical-form-group').find('.error-message').empty();
        return true;
    }

    element.addClass('error');
    element.eq(0).closest('.logical-form-group').find('.error-message').empty().append(document.createTextNode(message))
    return false;
}

function validateCoursera(modal, page) {
    var form = $('form', modal);
    const required = function(element) {
        return element.val().trim().length > 0
    };

    var has_error = false;
    switch (page) {
        case 1:
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

            break;
        case 2:
            var checkboxes = $('input[name=syllabus\\[\\]]', form);
            has_error |= !isValid(
                checkboxes,
                function(element) { return $('input[name=syllabus\\[\\]]:checked', form).length > 0; },
                "Please select at least one"
            )

    }

    return ! has_error;
}

function validateEdx(modal, page) {
    return true;
}

function validate(modal, page) {
    if (isCoursera(modal)) {
        return validateCoursera(modal, page);
    } else {
        return validateEdx(modal, page);
    }
}

function getCourseraSyllabus(data, progress) {
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: '/view/e-learning-archive/controllers/coursera/syllabus.php',
            method: 'get',
            data: data,
            xhr: xhr(progress),
        })
            .done(resolve)
            .fail(reject)
    });
}

function xhr(progress) {
    return function() {
        var xhr = new window.XMLHttpRequest();
        xhr.addEventListener(
            "progress",
            function(evt){
                progress(xhr.responseText)
            },
            false
        );
        return xhr;
    }
}

function downloadCoursera(data, progress) {
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: '/view/e-learning-archive/controllers/coursera/download.php',
            method: 'post',
            data: data,
            xhr: xhr(progress),

            // we need the following two to be able to use
            // a FormData object in a jQuery ajax request
            processData: false,
            contentType: false,
        })
            .done(resolve)
            .fail(reject)
    });
}

function getEdxCourses(data, progress) {
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: '/view/e-learning-archive/controllers/edx/courses.php',
            method: 'post',
            data: data,
            xhr: xhr(progress),
        })
            .done(resolve)
            .fail(reject)
    });
}
function getEdxSections(data, progress) {
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: '/view/e-learning-archive/controllers/edx/sections.php',
            method: 'post',
            data: data,
            xhr: xhr(progress),
        })
            .done(resolve)
            .fail(reject)
    });
}

function downloadEdx(data, progress) {
    return new Promise(function(resolve, reject) {
        $.ajax({
            url: '/view/e-learning-archive/controllers/edx/download.php',
            method: 'post',
            data: data,
            xhr: xhr(progress),

            // we need the following two to be able to use
            // a FormData object in a jQuery ajax request
            processData: false,
            contentType: false,
        })
            .done(resolve)
            .fail(reject)
    });
}

function getSpinner(modal, page) {
    return $(modal).find('.modal-split').eq(page - 1).find('.loader');
}
function getContent(modal, page) {
    return $(modal).find('.modal-split').eq(page - 1).find('.content');
}
function createProgressFunction(modal, page) {
    var spinner = getSpinner(modal, page);
    var content = getContent(modal, page);
    return function (response) {
        spinner.hide();
        content.empty().append(response);
    };
}
function createErrorFunction(modal, page) {
    var spinner = getSpinner(modal, page);
    var content = getContent(modal, page);

    return function(message) {
        return function (response) {
            spinner.hide();
            content.empty().append(
                "<h2>Error!</h2>" + message +
                "<p>Received <strong>code " + response.status + "</strong> and the following output: <div class='alert alert-danger'>" + response.responseText + "</div>"
            );
        };
    }
}


function showCourseraModalPage(modal, page) {
    var url = $('input[name=coursera-link]', modal).val();
    var cauth = $('#cauth', modal).val();

    var update = createProgressFunction(modal, page);
    var error = createErrorFunction(modal, page);

    switch (page) {
        case 1:
            // nothing
            break;

        case 2:
            getCourseraSyllabus({url: url, cauth: cauth}, update)
                .then(update, error("<p>Could not get the Coursera syllabus for <a href='" + url + "'>" + url + "</a></p>"));
            break;

        case 3:
            var data = new FormData();
            data.append('url', url);
            data.append('cauth', cauth);

            $(modal)
                .find('.modal-split')
                .eq(page - 2)
                .find('.content input:checked')
                .filter(function() {
                    var hasData = (typeof $(this).data('video-count') !== 'undefined');
                    if (hasData) {
                        return $(this).data('video-count') > 0;
                    }
                    return false;
                })
                .each(function() {
                    data.append('syllabus[]', this.getAttribute('value'));
                });

            downloadCoursera(data, update)
                .then(
                    update,
                    error("<p>There was an error downloading the Coursera lectures</p>")
                );
            break;
    }
}

function showEdxModalPage(modal, page) {
    var username = $('input[name=edx-username]').val();
    var password = $('input[name=edx-password]').val();
    var update = createProgressFunction(modal, page);
    var error = createErrorFunction(modal, page);

    var course_url = $('input[name=course_url]:checked').val();
    var course_title = $('input[name=course_url]:checked+label').text();

    switch (page) {
        case 1:
            getEdxCourses({username: username, password: password}, update)
                .then(update, error("<p>Error retrieving list of edX courses</p>"));
            break;

        case 2:
            getEdxSections( {username: username, password: password, course_url: course_url, course_title: course_title}, update)
                .then(update, error("<p>Could not get the edX sections for '" + course_title + "' course</p>"));
            break;

        case 3:
            var data = new FormData();
            data.append('username', username);
            data.append('password', password);
            data.append('course_url', course_url);
            data.append('course_title', course_title);

            var section_titles = $('input[name=section\\[\\]]:checked+label').map(function(i,e) {
                return $(e).text();
            }).get();

            $('input[name=section\\[\\]]:checked').each(function(i,e) {
                data.append('sections[' + $(e).val() + ']', section_titles[i]);
            });

            downloadEdx(data, update)
                .then(
                    update,
                    error("<p>There was an error downloading the edX lectures</p>")
                );
            break;
    }
}

function isCoursera(modal) {
    return $(modal).is('#courseraModal');
}

function isEdx(modal) {
    return !isCoursera(modal);
}

function showPage(modal, page) {
    if (isCoursera(modal)) {
        showCourseraModalPage(modal, page);
    } else {
        showEdxModalPage(modal, page);
    }
}

function setModalButtonsState(enabled) {
    $('.modal-footer button').attr('disabled', !enabled);
}

function disableModalButtons() {
    setModalButtonsState(false);
}

function enableModalButtons() {
    setModalButtonsState(true);
}

function showOnlyCloseButtonOnModal() {
    var close = document.createElement("button");
    close.setAttribute("type", "button");
    close.setAttribute("class", "btn btn-primary");
    close.innerHTML = 'Close';

    $('.modal-footer').empty().append(close);

    $(close).on('click', function() {
        $('.modal').modal('hide');
    })
}

function prepareModals() {
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

            $(this).find('.modal-footer').empty().append(back).append(next);


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

function initCoursera() {
    $('#coursera-tab button').on('click', function () {
        // manipulate the DOM like a barbarian
        $("#courseraModal .copy-target")
            .empty()
            .append(
                $('#coursera-tab .copy-source > .row')
                    .clone()
                    .removeClass('row')
            );

        prepareModals();

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
}

function initEdx() {
    $('#edx-tab button').on('click', function () {
        prepareModals();

        var modal = $('#edxModal');
        showPage(modal, 1);
    });
}

function init() {
    initCoursera();
    initEdx();

    prepareModals();
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
});