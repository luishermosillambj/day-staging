var mk_plugin_count_per_request = 3,
    mk_disable_until_server_respone = false;
(function($) {
    $(".hidden").hide().removeClass("hidden");
    mkGetPlugins(mk_plugin_count_per_request);
    $(window).scroll(function() {
        var hT = $('.abb-plugin-page-load-more').offset().top,
            hH = $('.abb-plugin-page-load-more').outerHeight(),
            wH = $(window).height(),
            wS = $(this).scrollTop();
        if (wS > (hT + hH - wH) && mk_disable_until_server_respone == false) {
            mkGetPlugins(mk_plugin_count_per_request);
        }
    });
    $(document).on('click', '.abb_plugin_action', function() {
        var btn = $(this);
        var action = btn.data('action');
        var plugin_name = btn.data('name');
        var plugin_slug = btn.data('slug');
        switch (action) {
            case 'install':
                mkMessage('show', 'Installing ' + plugin_name + ' plugin.');
                $.post(ajaxurl, {
                    action: 'abb_install_plugin',
                    abb_controlpanel_plugin_name: plugin_name,
                    abb_controlpanel_plugin_slug: plugin_slug,
                }, function(response) {
                    if (response.status == true) {
                        btn.replaceWith('<a class="cp-button red small abb_plugin_action" data-action="remove" data-name="' + plugin_name + '" data-slug="' + plugin_slug + '">Remove</a>');
                        mkMessage('hide');
                    } else {
                        mkMessage(false, response.message);
                    }
                });
                break;
            case 'remove':
                mkMessage('show', 'Removing ' + plugin_name + ' plugin.');
                $.post(ajaxurl, {
                    action: 'abb_remove_plugin',
                    abb_controlpanel_plugin_name: plugin_name,
                    abb_controlpanel_plugin_slug: plugin_slug,
                }, function(response) {
                    if (response.status == true) {
                        btn.parent('.action-btn').html('<a class="cp-button green small abb_plugin_action" data-action="install" data-name="' + plugin_name + '" data-slug="' + plugin_slug + '">Install</a>');
                        mkMessage('hide');
                    } else {
                        mkMessage(false, response.message);
                    }
                });
                break;
            case 'update':
                mkMessage('show', 'Updating ' + plugin_name + ' plugin.');
                $.post(ajaxurl, {
                    action: 'abb_update_plugin',
                    abb_controlpanel_plugin_name: plugin_name,
                    abb_controlpanel_plugin_slug: plugin_slug,
                }, function(response) {
                    if (response.status == true) {
                        btn.parent('.action-btn').html('<a class="cp-button red small abb_plugin_action" data-action="remove" data-name="' + plugin_name + '" data-slug="' + plugin_slug + '">Remove</a>');
                        mkMessage('hide');
                    } else {
                        mkMessage(false, response.message);
                    }
                });
                break;
        }
    });
}(jQuery));

function mkGetPlugins(count_number) {
    var from_number = parseInt(jQuery('.abb-plugin-page-load-more').data('from'));
    mk_disable_until_server_respone = true;
    jQuery.post(ajaxurl, {
        action: 'abb_lazy_load_plugin_list',
        from: from_number,
        count: count_number,
    }, function(response) {
        if (response.status == true) {
            if (response.data.length > 0) {
                jQuery('.abb-plugin-page-load-more').data('from', from_number + count_number);
                jQuery.each(response.data, function(key, val) {
                    jQuery('.abb-premium-plugins > .container').append(mkTemplateGenerator(val));
                });
                mk_disable_until_server_respone = false;
            }
        } else {
            mkMessage('show', response.message);
        }
    });
}

function mkTemplateGenerator(data) {
    var btn = '';
    if (data.installed == true && data.need_update == true) {
        btn = '<a class="cp-button red small abb_plugin_action" data-action="remove" data-name="' + data.name + '" data-slug="' + data.slug + '">Remove</a>&nbsp;&nbsp;<a class="cp-button blue small abb_plugin_action" data-action="update" data-name="' + data.name + '" data-slug="' + data.slug + '">Update</a>';
    } else if (data.installed == true && data.need_update == false) {
        btn = '<a class="cp-button red small abb_plugin_action" data-action="remove" data-name="' + data.name + '" data-slug="' + data.slug + '">Remove</a>';
    }
    if (data.installed == false) {
        btn = '<a class="cp-button green small abb_plugin_action" data-action="install" data-name="' + data.name + '" data-slug="' + data.slug + '">Install</a>';
    }
    var template = '<div class="plugin-box"><figure><img src="' + data.img_url + '"></figure><div class="plugin-footer"><h3 class="plugin-name">' + data.name + '</h3><p class="plugin-version">Version ' + data.version + '</p><div class="action-btn">' + btn + '</div></div></div>';
    return template;
}

function mkMessage(status, message = '') {
    if (status == 'show' && message != '') {
        jQuery('.abb-plugin-action-messages').slideDown();;
        jQuery('.abb-plugin-action-messages').children('.warning-message').text(message);
    } else if (status == false && message != '') {
        jQuery('.abb-plugin-action-messages').children('.warning-message').text(message);
    } else if (status == 'hide') {
        jQuery('.abb-plugin-action-messages').slideUp();
    }
}
