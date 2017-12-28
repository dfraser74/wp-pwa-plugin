function validateEmail(email) {
  var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
  return re.test(email);
}

function getIframeUrl(e) {
  var url;

  var props = "";
  props += "&wp-version=" + jQuery('input[name=wp-version]').val();
  props += "&wp-url=" + jQuery('input[name=wp-url]').val();
  props += "&site-name=" + jQuery('input[name=site-name]').val();
  props += "&user-name=" + jQuery('input[name=user-name]').val();
  props += "&email=" + jQuery('input[name=email]').val();
  props += "&wp-lan=" + jQuery('input[name=wp-lan]').val();
  props += "&wp-pwa-version=" + jQuery('input[name=wp-pwa-version]').val();
  props += "&wp-pwa-siteid=" + jQuery('input[name=wp-pwa-siteid]').val();

  if (e == "change-site-id") {
      props += "&wp-pwa-siteid-new=" +jQuery('input#wp-pwa-siteid').val();
  }

  url = "https://plugin.worona.org/?event=" + e + props;

  return url;
}

jQuery(document).on('ready', function () {
    //disabling # links
    jQuery('a[href^="#"]').click(function(e) {
      e.preventDefault();
    });

    //Show "Insert siteid form"
    jQuery('.open-change-siteid').on('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      jQuery('#lateral-error-siteid').hide();
      jQuery('#lateral-change-siteid').toggle();
    });

    jQuery('.close-change-siteid').on('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      jQuery('#lateral-change-siteid').hide();
      jQuery('#lateral-error-siteid').hide();
    });

    jQuery('.open-advanced-settings').on('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      jQuery('#lateral-error-advanced-settings').hide();
      jQuery('#lateral-advanced-settings').toggle();
    });

    jQuery('.close-advanced-settings').on('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      jQuery('#lateral-advanced-settings').hide();
      jQuery('#lateral-error-advanced-settings').hide();
    });

    jQuery('.close-error-siteid').on('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      jQuery('#lateral-error-siteid').hide();
    });

    jQuery('.close-error-advanced-settings').on('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      jQuery('#lateral-error-advanced-settings').hide();
    });

    //PWA Status - Enabled / disabled
    jQuery('#wp-pwa-status').on('change', function(e) {
      var valueSelected = this.value;

      jQuery.ajax({
          url: ajaxurl,
          method: "POST",
          data: {
              action: 'wp_pwa_change_status',
              status: valueSelected,
          },
          success: function (response) {
            if (response.hasOwnProperty('status') && response.status == 'ok' ) {
              if (valueSelected == 'disabled') {
                jQuery('#wp-pwa-status-enabled').hide();
                jQuery('#wp-pwa-status-disabled').show();
              }
              if(valueSelected == 'enabled') {
                jQuery('#wp-pwa-status-enabled').show();
                jQuery('#wp-pwa-status-disabled').hide();
              }
            }
          },
          error: function () {

          }
      });

    });

    //Create App via AJAX
    jQuery('#sync-with-wp-pwa').on('click', function (e) {
      jQuery('#sync-with-wp-pwa').addClass('is-loading');
      e.preventDefault();
      e.stopPropagation();

      var name = jQuery('#param-name').val();
      var email = jQuery('#param-email').val();
      var siteURL = jQuery('#param-siteURL').val();
      var siteName = jQuery('#param-siteName').val();
      var siteId = jQuery('#param-siteId').val();

      var registerURL = "https://dashboard.worona.org/register";

      registerURL += "?email=" + email;
      registerURL += "&siteURL=" + siteURL;
      registerURL += "&siteName=" + siteName;
      registerURL += "&siteId=" + siteId;

      if ( name !== 'admin' ) {
          registerURL += "&name=" + name;
      }

      var win = window.open(registerURL, '_blank');
      win.focus();

      jQuery.ajax({
          url: ajaxurl,
          method: "POST",
          data: {
              action: 'sync_with_wp_pwa',
          },
          success: function (response) {
            if (response.hasOwnProperty('status') && response.status == 'ok' ) {
              jQuery('#label-create-buttons').toggle();
              jQuery('#label-created').toggle();
              jQuery('progress')[0].value = 100;
              jQuery('#step-message').text('You are on step 4/4');
              jQuery('#wp-pwa-status-box').show();
              jQuery('#wp-pwa-siteid-lateral').show();
              jQuery('span#wp-pwa-siteid-span').text(response.siteId);
              jQuery('input#wp-pwa-siteid').val(response.siteId);

              jQuery('#dashboard-button').removeClass('disabled');
              jQuery('#dashboard-button').addClass('button-primary button-hero');

              var siteid = jQuery('#wp-pwa-siteid-span').text();
              var url = "https://dashboard.worona.org/" + "site/" + siteid;
              jQuery('#dashboard-button').on('click', function(e){window.open(url)});
            }
          },
          error: function () {

          }
      });
    });

    //Change App ID via ajax
    jQuery('#change-siteid').on('click', function(e) {
      jQuery('#change-siteid').addClass('is-loading');
      e.preventDefault();
      e.stopPropagation();
      var id = jQuery('input#wp-pwa-siteid').val();

      if ( id.length !=17 || id.includes(' ')){
        jQuery('#lateral-error-siteid').show();
        jQuery('#siteid-error-message').text("Invalid Site ID");
        jQuery('#change-siteid').removeClass('is-loading');
      } else {
        jQuery.ajax({
          url: ajaxurl,
          method: "POST",
          data: {
              action: 'wp_pwa_change_siteid',
              siteid: jQuery('input#wp-pwa-siteid').val()
          },
          success: function (response) {
            if (response.hasOwnProperty('status') && response.status == 'ok' ) {

              jQuery('#gtm-iframe').attr('src',getIframeUrl('change-site-id'));

              jQuery('#change-siteid').removeClass('is-loading');
              jQuery('#lateral-error-siteid').hide();
              jQuery('#lateral-change-siteid').hide();
              jQuery('#label-create-buttons').hide(); //they can be hidden already
              jQuery('#label-created').show(); //it can be displayed already
              jQuery('#wp-pwa-status-box').show();
              jQuery('#step-message').text('You are on step 4/4');
              jQuery('#wp-pwa-siteid-lateral').show();
              jQuery('span#wp-pwa-siteid-span').text(jQuery('input#wp-pwa-siteid').val());

              jQuery('#dashboard-button').removeClass('disabled');
              jQuery('#dashboard-button').addClass('button-primary button-hero');

              var siteid = jQuery('#wp-pwa-siteid-span').text();
              jQuery('#dashboard-button').on('click', function(e){window.open(url)});

              var dashboard_url = 'https://dashboard.worona.org/check-site/' + siteid;
              jQuery('#dashboard-button').prop('href',dashboard_url);

            } else if( response.hasOwnProperty('status') && response.status == 'error') {
              jQuery('#lateral-error-siteid').show();
              jQuery('#siteid-error-message').text(response.reason);
              jQuery('#change-siteid').removeClass('is-loading');
            }
          },
          error: function (response) {
            jQuery('#lateral-error-siteid').show();
            jQuery('#siteid-error-message').text("The Site ID couldn't be modified. Please try again.");
            jQuery('#change-siteid').removeClass('is-loading');
          }
        });
      }
    });

    jQuery('#change-advanced-settings').on('click', function(e) {
      jQuery('#change-advanced-settings').addClass('is-loading');
      e.preventDefault();
      e.stopPropagation();

      jQuery.ajax({
        url: ajaxurl,
        method: "POST",
        data: {
            action: 'wp_pwa_change_advanced_settings',
            wp_pwa_env: jQuery('select#wp-pwa-env').find(":selected").val(),
            wp_pwa_ssr: jQuery('input#wp-pwa-ssr').val(),
            wp_pwa_static: jQuery('input#wp-pwa-static').val()
        },
        success: function (response) {
          if (response.hasOwnProperty('status') && response.status == 'ok' ) {

            jQuery('#gtm-iframe').attr('src',getIframeUrl('change-site-id'));

            jQuery('#change-advanced-settings').removeClass('is-loading');
            jQuery('#lateral-error-advanced-settings').hide();

            jQuery('#lateral-change-advanced-settings').hide();

          } else if( response.hasOwnProperty('status') && response.status == 'error') {
            jQuery('#lateral-error-advanced-settings').show();
            jQuery('#advanced-settings-error-message').text(response.reason);
            jQuery('#change-advanced-settings').removeClass('is-loading');
          }
        },
        error: function (response) {
          jQuery('#lateral-error-advanced-settings').show();
          jQuery('#advanced-settings-error-message').text("The Advanced Settings couldn't be modified. Please try again.");
          jQuery('#change-advanced-settings').removeClass('is-loading');
        }
      });
    });
});
