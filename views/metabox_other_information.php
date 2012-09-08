<?php
if (!current_user_can('edit_post')) wp_die( __('You do not have sufficient permissions to access this page.') );
?>
<table class="form-table">
	<tr valign="top">
       <th scope="row">
       		<strong>Storage</strong>
       </th>
       <td>
       		<?php echo $storage; ?>
       </td>
    </tr>
	<tr valign="top">
       <th scope="row">
       		<strong>Size</strong>
       </th>
       <td>
       		<?php echo $storage_size; ?>
       </td>
    </tr>
</table>