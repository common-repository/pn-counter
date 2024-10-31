<?php
require_once('pn-counter.php');

if (array_key_exists('_submit_check', $_POST)) {
  $is_configured_before = pn_counter_is_configured();
  
  $table_prefix = $_POST['table_prefix'];
  pn_counter_set_table_prefix($table_prefix);
  if ($table_prefix != null && $table_prefix != '') {
    pn_counter_install();
  }
  
  $geocity_file_path = $_POST['geocity_file_path'];
  if ($is_configured_before || ($geocity_file_path != null && $geocity_file_path != '')) {
    pn_counter_put_property('geocity_file_path', $geocity_file_path);
  }
  
  $excluded_ip_patterns = $_POST['excluded_ip_patterns'];
  if ($is_configured_before || ($excluded_ip_patterns != null && $excluded_ip_patterns != '')) {
    pn_counter_put_property('excluded_ip_patterns', $excluded_ip_patterns);
  }
  
  $is_configured_after = pn_counter_is_configured();
  if ($is_configured_before != $is_configured_after) {
    wp_redirect(get_option('siteurl').'/wp-admin/admin.php?page=pn-counter/settings.php');
  } else {
    pn_counter_settings_page();
  }
} else {
  pn_counter_settings_page();
}

function pn_counter_settings_page() {
?>
<div class="wrap">
<form method="post">
<input type="hidden" name="_submit_check" id="_submit_check" value="1" />
<table class="form-table">
<tbody>
<tr valign="top">
<th scope="row">Table prefix</th>
<td><input name="table_prefix" id="table_prefix" value="<?php echo(pn_counter_get_table_prefix()); ?>" style="width: 95%;" type="text" /><br />
pn-counter table prefix (for example: pn_counter_)</td>
</tr>
<tr valign="top">
<th scope="row">GeoIP City file</th>
<td><input name="geocity_file_path" id="geocity_file_path" value="<?php echo(pn_counter_get_property('geocity_file_path')); ?>" style="width: 95%;" value="" type="text" /><br />
Absolute path to GeoLiteCity.dat (for example: /home/username/geoip/GeoLiteCity.dat)</td>
</tr>
<tr valign="top">
<th scope="row">Excluded IP patterns</th>
<td><input name="excluded_ip_patterns" id="excluded_ip_patterns" value="<?php echo(pn_counter_get_property('excluded_ip_patterns')); ?>" style="width: 95%;" type="text" /><br /> 
Excluded IP patterns delimited by semicolon (for example: 127.0.0.1; 1.2.3.*; 1.12.123.12*)
</td>
</tr>
</tbody>
</table>

<p class="submit"><input name="Submit" value="Submit" type="submit"></p>
</form>
</div>
<?php
}
?>