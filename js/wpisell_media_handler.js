jQuery(document).ready(function() {

    jQuery('#product_file_upload_button').click(function() {
        formfield = jQuery('#product_file_url').attr('name');
        tb_show('Upload Your File', 'media-upload.php?type=image&amp;TB_iframe=true');
        return false;
    });

    window.send_to_editor = function(html) {
        fileurl = jQuery(html).attr('href');
        jQuery('#product_file_url').val(fileurl);
        tb_remove();
    }

});