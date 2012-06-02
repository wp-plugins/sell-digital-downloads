jQuery(document).ready(function($){
	
	

if ( pagenow != undefined && pagenow == 'isell-product'){
jQuery('#deletefile').click(function(){
        if ( jQuery('#deletefile').hasClass('disabled') )return;
        if ( !confirm('Delete this file ?') ) return;
        jQuery.ajax({
                type: 'POST',
                url: isell.ajaxurl,
                data: { action: 'isell_delete_file',post_id: jQuery('#isell_product_id').val(), nonce: isell.file_delete_nonce  },
                dataType: "json",
                success: function(response){
                        if ( response.status === 1 ){
                                jQuery('#post').delay(500).submit();
                        }else{
                                alert(response.message);
                        }
                }

        });
});
var isell_uploader = new plupload.Uploader({
        runtimes: 'gears,html5,flash,silverlight,browserplus',
        flash_swf_url: isell.flash_swf_url,
        silverlight_xap_url: isell.silverlight_xap_url,
        browse_button: 'pickfiles',
        container: 'uploader',
        chunk_size : '2mb',
        unique_names : true,
        multi_selection: false,
        multipart: true,
        url: isell.ajaxurl,
        multipart_params: {
                nonce: isell.file_upload_nonce,
                action: 'isell_file_upload',
                post_id: jQuery('#isell_product_id').val()
         }
        
    });

isell_uploader.init();
    
document.getElementById('uploadfiles').onclick = function() {
    if ( jQuery('#uploadfiles').hasClass('disabled') )return;
    isell_uploader.start();
};

isell_uploader.bind('FilesAdded', function(up, files) {
        //alert('Click on start upload button to start the upload of this file');
        if ( !jQuery('#deletefile').hasClass('disabled') )return;
        jQuery('#uploadfiles').removeClass('disabled');
        up.refresh();
});

isell_uploader.bind('FileUploaded', function(up, file, response) {
        //alert('The file is uploaded successfully');
        var result = jQuery.parseJSON(response["response"]);
        if ( result.status === 1 )
                jQuery('#post').delay(1000).submit();
        else
                alert(result.message);
});
isell_uploader.bind('UploadProgress', function(up, file) {
        jQuery( "#file_upload_progressbar" ).progressbar({
                        value: file.percent
                });

});

jQuery('#product_url_label').click(function(e){
        jQuery('#product_url').select();
});




}

if ( pagenow != undefined && pagenow == 'isell-order'){

jQuery('#download_url_label').click(function(e){
        jQuery('#download_url').select();
});

}

});

