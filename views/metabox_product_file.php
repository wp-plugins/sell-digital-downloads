<?php
if (!current_user_can('edit_post')) wp_die( __('You do not have sufficient permissions to access this page.') );

?>
<table class="form-table">
	<input type="hidden" name="product_token" />
	<tr valign="top">
       <th scope="row"><strong><label><?php echo __('File:','isell'); ?></label><strong></th>
       <td>
        <div id="uploader">
          <?php if ( get_post_meta($post_id,'product_contains_file',true) ): ?>
            <a id="pickfiles" href="javascript:;" class="button disabled"><?php echo __('Select File','isell'); ?></a>
          <?php else: ?>
            <a id="pickfiles" href="javascript:;" class="button"><?php echo __('Select File','isell'); ?></a>
          <?php endif; ?>
          <a style="margin-left:10px;" id="uploadfiles" href="javascript:;" class="button button-highlighted disabled"><?php echo __('Start Upload','isell'); ?></a>
          
          <?php if ( get_post_meta($post_id,'product_contains_file',true) ): ?>
            <a style="margin-left:10px;" id="deletefile" href="javascript:;" class="button button-highlighted"><?php echo __('Delete File','isell'); ?></a>
          <?php else: ?>
            <a style="margin-left:10px;" id="deletefile" href="javascript:;" class="button button-highlighted disabled"><?php echo __('Delete File','isell'); ?></a>
          <?php endif; ?>
        </div>

        <p class="description"><?php echo __('Click on select file to select the file you wish to upload and then click on start upload button.','isell'); ?></p>
        <div id="file_upload_progressbar"></div>
        </td>
    </tr>
    <tr valign="top">
       <th scope="row"><strong><label for="product_file_name"><?php echo __('File Name:','isell'); ?></label><strong></th>
       <td>
       		<input type="text" value="<?php echo get_post_meta($post_id,'product_file_name',true); ?>" id="isell_product_file_name" name="product_file_name" required class="regular-text" />
       		<p class="description"><?php echo __('File will be downloaded on computers with this name. Example: filename.extension','isell'); ?></p>
        </td>
    </tr>
   
<input type="hidden" value='<?php echo wp_create_nonce("isell_file_upload"); ?>' name="isell_file_upload_nonce" id="isell_file_upload_nonce" />
<input type="hidden" value='<?php echo $post_id; ?>' name="post_id" id="isell_product_id" />

</table>

