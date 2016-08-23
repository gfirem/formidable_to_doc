jQuery(document).ready(function ($) {
    initialPatterns = jQuery('#template_attachment_patterns').val();
    initialPatterns =  jQuery.parseJSON(initialPatterns);
    if(initialPatterns) {
        jQuery.each(initialPatterns, function (index, value) {
            jQuery("#pattern-list").append("<p>" + value + "</p>");
        });
    }
    jQuery('#upload-template').ajaxForm({
        url: ajax_url,
        data: jQuery('#file_upload').val(),
        type: 'POST',
        contentType: 'json',
        beforeSubmit: function (arr, $form, options) {
            jQuery('#upload_progress_1').show();
        },
        success: function (response) {
            response = JSON.parse(response);
            if (response.file_id) {
                jQuery('#upload_file_template_container').hide();
                jQuery('#uploaded_file_template_container').show();
                jQuery('#template_attachment_url_frm').val(response.file_url);
                jQuery('#template_attachment_url_link').attr('href', response.file_url);
                jQuery('#template_attachment_id_frm').val(response.file_id);
                jQuery('#template_attachment_file_id').val(response.file_id);
            }
            else {
                alert('Error uploading file. Please delete action and try again!');
            }
            if(response.patterns){
                jQuery('#template_attachment_patterns').val(JSON.stringify(response.patterns));
                jQuery.each(response.patterns, function(index, value){
                    jQuery("#pattern-list").append("<p>"+value+"</p>");
                });
            }
            else{
                alert('Not detect patterns, reload de page and try again!');
                jQuery('#detected_patterns').hide();
            }
            jQuery('#upload_progress_1').hide();
        }
    });

    jQuery('#delete-template').submit(function (e) {
        e.preventDefault();

        jQuery('#upload_progress_2').show();
        jQuery.post(jQuery('#delete-template').attr('action'), {
                action: 'delete_template_file',
                template_attachment_file_id: jQuery('#template_attachment_file_id').val(),
                security_delete: jQuery('#security-delete').val()
            },
            function (response) {
                if (response.message) {
                    jQuery('#upload_progress_2').hide();
                    jQuery('#upload_file_template_container').show();
                    jQuery('#uploaded_file_template_container').hide();
                    jQuery('#template_attachment_url_frm').val('');
                    jQuery('#template_attachment_url_link').attr('href', '');
                    jQuery('#template_attachment_id_frm').val('');
                    jQuery('#file_upload').val('');
                }
                else {
                    alert('Error deleting file. Please delete action and try again!');
                    jQuery('#upload_progress_2').hide();
                }
            }, 'json');
    });

    var viewPortWidth = window.innerWidth - 20;
    viewPortWidth = (viewPortWidth * 33)/100;

    dialog = jQuery("#dialog-show-patterns").dialog({
        resizable: true,
        height: "auto",
        width: viewPortWidth,
        modal: true,
        position: {
            my: "left top",
            at: "left top",
            of: window,
            collision: "none"
        },
        create: function (event, ui) {
            jQuery(event.target).parent().css('position', 'fixed');
        },
        autoOpen: false,
        buttons: {
            "Cerrar": function () {
                jQuery(this).dialog("close");
            }
        }
    });

    jQuery("#detected_patterns").click(function () {
        dialog.dialog("open");
        event.preventDefault();
    });

});
