var mk_plugin_count_per_request = 10,
    mk_disable_until_server_respone = false,
    mk_install_types = ['reset_db', 'upload', 'unzip', 'validate', 'plugin', 'theme_content', 'menu_locations', 'setup_pages', 'theme_options', 'theme_widget', 'finilize'],
    mk_template_id = null,
    mk_template_name = null;
(function($) {
    if ($('.abb-template-page-load-more').length == 0) {
        return false;
    }
    $(".hidden").hide().removeClass("hidden");
    mkGetTemplatesCategories();
    mkGetTemplatesList(mk_plugin_count_per_request);
    $(window).scroll(function() {
        var hT = $('.abb-template-page-load-more').offset().top,
            hH = $('.abb-template-page-load-more').outerHeight(),
            wH = $(window).height(),
            wS = $(this).scrollTop();
        if (wS > (hT + hH - wH) && mk_disable_until_server_respone === false) {
            mkGetTemplatesList(mk_plugin_count_per_request);
        }
    });
    $(document).on('click', '.abb_template_install', function() {
        var $btn = $(this);
        swal({
            title: mk_cp_textdomain.important_notice,
            text: mk_cp_textdomain.installing_sample_data_will_delete_all_data_on_this_website,
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#32d087",
            confirmButtonText: mk_cp_textdomain.yes_install + $btn.data('name'),
            closeOnConfirm: false
        }, function() {
            swal({
                title: "<small>" + mk_cp_textdomain.install_sample_data + "</small>",
                text: '<div class="import-modal-container"><ul><li class="upload">' + mk_cp_textdomain.downloading_sample_package_data + '<span class="result-message"></span></li><li class="plugin">' + mk_cp_textdomain.install_required_plugins + '<span class="result-message"></span></li><li class="install">' + mk_cp_textdomain.install_sample_data + '<span class="result-message"></span></li></ul><div id="progressBar" class="default-theme"><div></div></div></div>',
                html: true,
                showConfirmButton: false,
            });
            mkInstallTemplate(0, $btn.data('slug'));
        });
    });
    $(document).on('click', '.abb_template_uninstall', function() {
        var $btn = $(this);
        swal({
            title: mk_cp_textdomain.important_notice,
            text: mk_cp_textdomain.uninstalling_template_will_remove_all_your_contents_and_settings,
            type: "warning",
            showCancelButton: true,
            confirmButtonColor: "#dd5434",
            confirmButtonText: mk_cp_textdomain.yes_uninstall + $btn.data('name'),
            closeOnConfirm: false
        }, function() {
            mkUninstallTemplate($btn.data('slug'));
        });
    });
    $(document).on('change', '.mk_templates_categories', function() {
        var $select = $(this);
        mkResetGetTemplateInfo();
        mk_template_id = $select.val();
        mkGetTemplatesList(mk_plugin_count_per_request);
    });
    $(document).on('keyup', '.mk_seach_template', function() {
        var txt = $(this);
        mkSearchTemplateByName(txt.val());
    });
}(jQuery));

function mkUninstallTemplate(template_slug) {
    jQuery.post(ajaxurl, {
        action: 'abb_uninstall_template',
    }).done(function(response) {
        console.log('Ajax Req : ', response);
        jQuery('a[data-slug="' + template_slug + '"]').addClass('green').removeClass('red');
        jQuery('a[data-slug="' + template_slug + '"]').html('Install');
        jQuery('a[data-slug="' + template_slug + '"]').addClass('abb_template_install').removeClass('abb_template_uninstall');
        swal(mk_cp_textdomain.template_uninstalled, "", "success");
    }).fail(function(data) {
        console.log('Failed msg : ', data);
    });
}

function mkInstallTemplate(index, template_name) {
    if (mk_install_types[index] == undefined) {
        jQuery('a[data-slug="' + template_name + '"]').addClass('red').removeClass('green');
        jQuery('a[data-slug="' + template_name + '"]').html('Uninstall');
        jQuery('a[data-slug="' + template_name + '"]').addClass('abb_template_uninstall').removeClass('abb_template_install');
        swal(mk_cp_textdomain.hooray, mk_cp_textdomain.template_installed_successfully, "success");
        return;
    }
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        data: { action: 'abb_install_template_procedure', type: mk_install_types[index], template_name: template_name },
        dataType: "json",
        timeout: 60000,
        success: function(response) {
            if (response.hasOwnProperty('status')) {
                if (response.status == true) {
                    mkProgressBar(mkCalcPercentage(mk_install_types.length - 1, index), jQuery('#progressBar'));
                    mkShowResult(mk_install_types[index], response.message);
                    mkInstallTemplate(++index, template_name);
                } else {
                    // Something goes wrong in install progress
                    swal(mk_cp_textdomain.oops, response.message, "error");
                }
            } else {
                // Something goes wrong in server response
                swal(mk_cp_textdomain.oops, mk_cp_textdomain.something_wierd_happened_please_retry_again, "error");
            }
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            console.log(XMLHttpRequest);
            if (XMLHttpRequest.readyState == 4) {
                // HTTP error (can be checked by XMLHttpRequest.status and XMLHttpRequest.statusText)
                swal(mk_cp_textdomain.oops, 'Error in API (' + XMLHttpRequest.status + ')', "error");
            } else if (XMLHttpRequest.readyState == 0) {
                // Network error (i.e. connection refused, access denied due to CORS, etc.)
                swal(mk_cp_textdomain.oops, mk_cp_textdomain.error_in_network_please_check_your_connection_and_try_again, "error");
                mkRequestErrorHandling(XMLHttpRequest, textStatus, errorThrown);
            }
        }
    });
}

function mkGetTemplatesCategories() {
    var empty_category_list = '<option value="no-category">No template found</option>';
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        data: { action: 'abb_get_templates_categories' },
        dataType: "json",
        timeout: 60000,
        success: function(response) {
            if (response.hasOwnProperty('status') === true) {
                if (response.status === true) {
                    var category_list = '<option value="all-categories">All Categories</option>';
                    jQuery.each(response.data, function(key, val) {
                        category_list += '<option value="' + val.id + '">' + val.name + ' - ' + val.count + '</option>';
                    });
                    jQuery('.mk_templates_categories').html(category_list);
                } else {
                    jQuery('.mk_templates_categories').html(empty_category_list);
                    swal("Oops ...", response.message, "error");
                }
            } else {
                jQuery('.mk_templates_categories').html(empty_category_list);
                swal(mk_cp_textdomain.oops, mk_cp_textdomain.something_wierd_happened_please_retry_again, "error");
            }
        },
        error: function(XMLHttpRequest, textStatus, errorThrown) {
            jQuery('.mk_templates_categories').html(empty_category_list);
            mkRequestErrorHandling(XMLHttpRequest, textStatus, errorThrown);
        }
    });
}

function mkShowResult(type, message) {
    message = '-    ' + message;
    switch (type) {
        case 'reset_db':
            jQuery('.import-modal-container .upload .result-message').text(message);
            break;
        case 'upload':
            jQuery('.import-modal-container .upload .result-message').text(message);
            break;
        case 'unzip':
            jQuery('.import-modal-container .upload .result-message').text(message);
            break;
        case 'validate':
            jQuery('.import-modal-container .upload .result-message').text(message);
            break;
        case 'plugin':
            jQuery('.import-modal-container .plugin .result-message').text(message);
            break;
        case 'theme_content':
            jQuery('.import-modal-container .install .result-message').text(message);
            break;
        case 'menu_locations':
            jQuery('.import-modal-container .install .result-message').text(message);
            break;
        case 'setup_pages':
            jQuery('.import-modal-container .install .result-message').text(message);
            break;
        case 'theme_options':
            jQuery('.import-modal-container .install .result-message').text(message);
            break;
        case 'theme_widget':
            jQuery('.import-modal-container .install .result-message').text(message);
            break;
        case 'finilize':
            jQuery('.import-modal-container .install .result-message').text(message);
            break;
    }
}

function mkCalcPercentage(bigNumber, littleNumber) {
    return Math.round((littleNumber * 100) / bigNumber);
}

function mkGetTemplatesList(count_number) {
    var from_number = Number(jQuery('.abb-template-page-load-more').data('from'));
    mk_disable_until_server_respone = true;
    var req_data = {
        action: 'abb_template_lazy_load',
        from: from_number,
        count: count_number,
    }
    if (typeof mk_template_id !== 'undefined' && mk_template_id !== null) {
        req_data['template_category'] = mk_template_id;
    }
    if (typeof mk_template_name !== 'undefined' && mk_template_name !== null) {
        req_data['template_name'] = mk_template_name;
    }
    console.log(req_data);
    jQuery.post(ajaxurl, req_data, function(response) {
        if (response.status == true) {
            if (response.data.length > 0) {
                jQuery('.abb-template-page-load-more').data('from', from_number + count_number);
                jQuery.each(response.data, function(key, val) {
                    jQuery('#template-list').append(mkTemplateGenerator(val));
                });
                mk_disable_until_server_respone = false;
            }
        } else {
            console.log(response);
            swal("Oops ...", response.message, "error");
        }
    });
}

function mkTemplateGenerator(data) {
    if (data.installed == false) {
        var btn = '<a class="cp-button green small abb_template_install  btn-action-install" data-name="' + data.name + '" data-slug="' + data.slug + '">' + mk_cp_textdomain.install + '</a><a href="http://demos.artbees.net/jupiter5/' + data.slug + '" target="_blank" class="cp-button gray small">' + mk_cp_textdomain.preview + '</a>';
    } else {
        var btn = '<a class="cp-button red small abb_template_uninstall  btn-action-install" data-name="' + data.name + '" data-slug="' + data.slug + '">' +
            mk_cp_textdomain.uninstall + '</a><a href="http://demos.artbees.net/jupiter5/' + data.slug + '" target="_blank" class="cp-button gray small">' + mk_cp_textdomain.preview + '</a>';
    }
    var template = '<div class="template-item"><div class="item-holder"><form method="post"><div class="template-image"><img src="' + data.img_url + '" alt="' + data.name + '"></div><div class="template-meta"><h6>' + data.name + '</h6><div class="button-holder">' + btn + '</div></div></form></div></div>';
    return template;
}

function mkProgressBar(percent, $element) {
    var progressBarWidth = percent * $element.width() / 100;
    $element.find('div').animate({ width: progressBarWidth }, 500).html(percent + "% ");
}

function mkRequestErrorHandling(XMLHttpRequest, textStatus, errorThrown) {
    console.log(XMLHttpRequest);
    if (XMLHttpRequest.readyState == 4) {
        // HTTP error (can be checked by XMLHttpRequest.status and XMLHttpRequest.statusText)
        swal("Oops ...", 'Error in API (' + XMLHttpRequest.status + ')', "error");
    } else if (XMLHttpRequest.readyState == 0) {
        // Network error (i.e. connection refused, access denied due to CORS, etc.)
        swal("Oops ...", 'Error in network , please check your connection and try again', "error");
    } else {
        swal("Oops ...", 'Something wierd happened , please retry again', "error");
    }
}

function mkResetGetTemplateInfo() {
    jQuery("#template-list").fadeOut(300, function() {
        jQuery(this).empty().fadeIn(300);
    });
    jQuery('.abb-template-page-load-more').data('from', 0);
}

function mkSearchTemplateByName(template_name) {
    mkResetGetTemplateInfo();
    mk_template_name = template_name;
    mkGetTemplatesList(mk_plugin_count_per_request);
}
