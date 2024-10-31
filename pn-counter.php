<?php
/*
Plugin Name: pn-counter
Plugin URI: http://en.platonov.name/pn-counter/
Description: Visitor counter.
Author: Roman Platonov <r.v.platonov@gmail.com>
Contributor: Roman Platonov <r.v.platonov@gmail.com>
Author URI: http://en.platonov.name/
Version: 1.3.3
*/

function pn_counter_get_user_timeshift() {
  return get_option('gmt_offset') * 60 * 60;
}

function pn_counter_get_user_date() {
  $user_timeshift = pn_counter_get_user_timeshift();
  return time() + $user_timeshift;
}

function pn_counter_log($message) {
  $user_date        = pn_counter_get_user_date();
  $user_date_string = gmdate("Y-m-d H:i:s", $user_date);
  
  $log_file_path = ABSPATH;
  $last_char = substr($log_file_path, strlen($log_file_path) - 1);
  $file_separator = '/';
  if (strpos($log_file_path, '\\') > 0) {
    $file_separator = '\\';
  }
  if ($last_char == '/' || $last_char == '\\') {
    $log_file_path = substr($log_file_path, 0, strlen($log_file_path) - 1);
  }
  $log_file_path .= $file_separator.'pn_counter_log.txt';
  $handle = fopen($log_file_path, "a");
  fwrite($handle, $user_date_string." - ".$message."\n");
  fclose($handle);
}

function pn_counter_install() {
  global $wpdb;
  
  $table_prefix = pn_counter_get_table_prefix();
  if ($table_prefix == null || $table_prefix == '') {
    return;
  }
  
  $last_migration = pn_counter_get_property('last_migration');
  
  $stats_table_name = $table_prefix.'stats';
  if($last_migration == null
      && $wpdb->get_var("SHOW TABLES LIKE '$stats_table_name'") != $stats_table_name) {
    $sql = "CREATE TABLE  $stats_table_name (
      id bigint(20) unsigned NOT NULL auto_increment,
      local_date timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
      ip varchar(15) NOT NULL,
      url varchar(255) NOT NULL,
      referer varchar(255) default NULL,
      user_agent varchar(255) default NULL,
      hostname varchar(64) default NULL,
      uid bigint(20) unsigned default NULL,
      last_access bigint(20) unsigned default NULL,
      country_code varchar(2) default NULL,
      region varchar(32) default NULL,
      city varchar(32) default NULL,
      postal_code varchar(6) default NULL,
      latitude float default NULL,
      longitude float default NULL,
      area_code varchar(16) default NULL,
      dma_code varchar(16) default NULL,
      PRIMARY KEY (id)
    ) CHARACTER SET utf8 COLLATE utf8_general_ci;";
    $wpdb->query($sql);
  }
  
  $properties_table_name = $table_prefix.'properties';
  if($last_migration == null
      && $wpdb->get_var("SHOW TABLES LIKE '$properties_table_name'") != $properties_table_name) {
    $sql = "CREATE TABLE  $properties_table_name (
      id bigint(20) unsigned NOT NULL auto_increment,
      property_name varchar(255) NOT NULL,
      property_value varchar(255),
      PRIMARY KEY (id)
    ) CHARACTER SET utf8 COLLATE utf8_general_ci;";
    $wpdb->query($sql);
  }
  
  
  // migration 1: create index for local_date
  if ($last_migration == null || $last_migration == '') {
    $sql = "ALTER TABLE $stats_table_name ADD INDEX idx_".$stats_table_name."_local_date (local_date);";
    $wpdb->query($sql);
    $last_migration = 1;
    pn_counter_put_property('last_migration', $last_migration);
  }
  
  // migration 2: rename table pn_counter_stats to pn_counter_raw_values
  $raw_values_table_name = $table_prefix.'raw_values';
  if ($last_migration == 1) {
    $sql = "ALTER TABLE $stats_table_name RENAME TO $raw_values_table_name;";
    $wpdb->query($sql);
    $last_migration = 2;
    pn_counter_put_property('last_migration', $last_migration);
  }
  
  // migration 3: rename index idx_pn_counter_stats_local_date to idx_pn_counter_raw_values_local_date
  if ($last_migration == 2) {
    $sql = "ALTER TABLE $raw_values_table_name DROP INDEX idx_".$stats_table_name."_local_date, 
      ADD INDEX idx_".$raw_values_table_name."_local_date (local_date);";
    $wpdb->query($sql);
    $last_migration = 3;
    pn_counter_put_property('last_migration', $last_migration);
  }
  
  // migration 4: create table pn_counter_stats
  if ($last_migration == 3) {
    $sql = "CREATE TABLE $stats_table_name (
      id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
      local_date DATE NOT NULL,
      page_loads INTEGER UNSIGNED NOT NULL,
      unique_visitors INTEGER UNSIGNED NOT NULL,
      first_time_visitors INTEGER UNSIGNED NOT NULL,
      returning_visitors INTEGER UNSIGNED NOT NULL,
      PRIMARY KEY (id),
      INDEX idx_".$stats_table_name."_local_date (local_date)
    ) CHARACTER SET utf8 COLLATE utf8_general_ci;";
    $wpdb->query($sql);
    $last_migration = 4;
    pn_counter_put_property('last_migration', $last_migration);
  }
}

register_activation_hook(__FILE__, 'pn_counter_install');

//---------------------------------------------------------------------------------------------------------//

function pn_counter_get_table_prefix() {
  return get_option('pn-counter.table_prefix');
}

function pn_counter_set_table_prefix($table_prefix) {
  delete_option('pn-counter.table_prefix');
  add_option('pn-counter.table_prefix', $table_prefix);
}

function pn_counter_get_raw_values_table_name() {
  return get_option('pn-counter.table_prefix').'raw_values';
}

function pn_counter_get_stats_table_name() {
  return get_option('pn-counter.table_prefix').'stats';
}

function pn_counter_get_property($property_name) {
  global $wpdb;
  
  $table_prefix = pn_counter_get_table_prefix();
  if ($table_prefix != null && $table_prefix != '') {
    $properties_table_name = $table_prefix.'properties';
    if ($wpdb->get_var("SHOW TABLES LIKE '$properties_table_name'") == $properties_table_name) {
      return $wpdb->get_var("SELECT property_value FROM $properties_table_name WHERE property_name = '$property_name';");
    }
  }
  return null;
}

function pn_counter_put_property($property_name, $property_value) {
  global $wpdb;
  
  $properties_table_name = get_option('pn-counter.table_prefix').'properties';
  $count = $wpdb->get_var("SELECT count(*) FROM $properties_table_name WHERE property_name = '$property_name';");
  if ($count == 1) {
    $wpdb->query("UPDATE $properties_table_name SET property_value = '$property_value' WHERE property_name = '$property_name';");
  } else {
    $wpdb->query("INSERT INTO $properties_table_name (property_name, property_value) VALUES ('$property_name', '$property_value');");
  }
}

function pn_counter_is_configured() {
  global $wpdb;
  
  $table_prefix = get_option('pn-counter.table_prefix');
  if ($table_prefix == null || $table_prefix == '') {
    return false;
  }
  
  $raw_values_table_name = $table_prefix.'raw_values';
  if($wpdb->get_var("SHOW TABLES LIKE '$raw_values_table_name'") != $raw_values_table_name) {
    return false;
  }
  
  $properties_table_name = $table_prefix.'properties';
  if($wpdb->get_var("SHOW TABLES LIKE '$properties_table_name'") != $properties_table_name) {
    return false;
  }
  
  $stats_table_name = $table_prefix.'stats';
  if($wpdb->get_var("SHOW TABLES LIKE '$stats_table_name'") != $stats_table_name) {
    return false;
  }
  
  return true;
}

function pn_counter_is_ip_excluded($ip) {
  global $wpdb;
  
  $excluded_ip_patterns = pn_counter_get_property('excluded_ip_patterns');
  if ($excluded_ip_patterns == null || $excluded_ip_patterns == '') {
    return false;
  }
  
  $excluded_ip_arr = split(" *; *", $excluded_ip_patterns);
  foreach ($excluded_ip_arr as $key => $value) {
    $pattern = str_replace("*", "\\d+", $value);
    $pattern = str_replace(".", "\\.", $pattern);
    $pattern = "/^".$pattern."$/";
    $matches = preg_match($pattern, $ip);
    if ($matches) {
      return true;
    }
  }
  
  return false;
}

function pn_counter_get_midnight_time($user_date) {
  return $user_date - ($user_date % 86400);
}

function pn_counter_update_stats($user_date, $is_unique, $is_returning) {
  global $wpdb;
  
  $stats_table_name = pn_counter_get_stats_table_name();
  $midnight_time = pn_counter_get_midnight_time($user_date);
  $midnight_time_string = gmdate("Y-m-d", $midnight_time);
  $sql = "SELECT id, local_date, page_loads, unique_visitors,
    first_time_visitors, returning_visitors FROM $stats_table_name
    WHERE local_date='$midnight_time_string';";
  $wpdb->query($sql);
  $id = $wpdb->last_result[0]->id;
  
  if ($id != null && $id != '') {
    $page_loads = $wpdb->last_result[0]->page_loads + 1;
    $unique_visitors = $wpdb->last_result[0]->unique_visitors;
    $first_time_visitors = $wpdb->last_result[0]->first_time_visitors;
    $returning_visitors = $wpdb->last_result[0]->returning_visitors;
    if ($is_unique) {
      $unique_visitors++;
      if ($is_returning) {
        $returning_visitors++;
      } else {
        $first_time_visitors++;
      }
    }
    $sql = "UPDATE $stats_table_name SET
      page_loads=$page_loads,
      unique_visitors=$unique_visitors,
      first_time_visitors=$first_time_visitors,
      returning_visitors=$returning_visitors
      WHERE id=$id;";
  } else {
    $page_loads = 1;
    $unique_visitors = 0;
    $first_time_visitors = 0;
    $returning_visitors = 0;
    if ($is_unique) {
      $unique_visitors = 1;
      if ($is_returning) {
        $returning_visitors = 1;
      } else {
        $first_time_visitors = 1;
      }
    }
    $sql = "INSERT INTO $stats_table_name (
      local_date, page_loads, unique_visitors,
      first_time_visitors, returning_visitors) VALUES (
      '$midnight_time_string', $page_loads, $unique_visitors,
      $first_time_visitors, $returning_visitors);";
  }
  $wpdb->query($sql);
}

function pn_counter_on_request() {
  if (!pn_counter_is_configured()) {
    return;
  }
  
  if ($_SERVER['HTTP_X_FORWARD_FOR']) {
    $ip = $_SERVER['HTTP_X_FORWARD_FOR'];
  } else {
    $ip = $_SERVER['REMOTE_ADDR'];
  }
  if (pn_counter_is_ip_excluded($ip)) {
    return;
  }
  
  $geocity_file_path = pn_counter_get_property('geocity_file_path');
  
  $user_timeshift    = pn_counter_get_user_timeshift();
  $user_date         = time() + $user_timeshift;
  $user_date_string  = gmdate("Y-m-d H:i:s", $user_date);
  $url               = "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
  $referer           = $_SERVER['HTTP_REFERER'];
  $user_agent        = $_SERVER['HTTP_USER_AGENT'];
  $hostname          = null;
  $uid               = $_COOKIE['pn_counter_uid'];
  $last_access       = $_COOKIE['pn_counter_last_access'];
  $country_code      = null;
  $region            = null;
  $city              = null;
  $postal_code       = null;
  $latitude          = null;
  $longitude         = null;
  $area_code         = null;
  $dma_code          = null;
  
  $is_unique    = false;
  $is_returning = false;
  if ($uid == null || $uid == '' || $last_access == null || $last_access == '') {
    $is_unique = true;
    $last_access = null;
    $uid = pn_counter_get_property('next_uid');
    if ($uid == null || $uid == '') {
      $uid = 1;
    }
    pn_counter_put_property('next_uid', $uid + 1);
    setcookie('pn_counter_uid', $uid, time() + 31536000);
  } else {
    $is_returning = true;
    if ($last_access + $user_timeshift - pn_counter_get_midnight_time($user_date) < 0) {
      $is_unique = true;
    }
  }
  setcookie('pn_counter_last_access', time(), time() + 31536000);
  
  if (file_exists($geocity_file_path)) {
    require_once(ABSPATH.'wp-content/plugins/pn-counter/geoipcity.inc');
  
    $gi = geoip_open($geocity_file_path, GEOIP_STANDARD);
    $gir = GeoIP_record_by_addr($gi, $ip);
    
    if ($gir != null) {
      $country_code = $gir->country_code;
      $region       = $gir->region;
      $city         = $gir->city;
      $postal_code  = $gir->postal_code;
      $latitude     = $gir->latitude;
      $longitude    = $gir->longitude;
      $area_code    = $gir->area_code;
      $dma_code     = $gir->dma_code;
    }
  } else {
    pn_counter_log("ERROR: GeoCity file not found [$geocity_file_path]");
  }

  $raw_values_table_name = pn_counter_get_raw_values_table_name();
  $insert = "INSERT INTO $raw_values_table_name (local_date, ip, url";
  $values = "VALUES ('$user_date_string', '$ip', '$url'";
  if ($referer != null && $referer != '') {
    $insert .= ', referer';
    $values .= ', \''.mysql_real_escape_string($referer).'\'';
  }
  if ($user_agent != null && $user_agent != '') {
    $insert .= ', user_agent';
    $values .= ', \''.mysql_real_escape_string($user_agent).'\'';
  }
  if ($hostname != null && $hostname != '') {
    $insert .= ', hostname';
    $values .= ', \''.mysql_real_escape_string($hostname).'\'';
  }
  if ($uid != null && $uid != '') {
    $insert .= ', uid';
    $values .= ', '.mysql_real_escape_string($uid);
  }
  if ($last_access != null && $last_access != '') {
    $insert .= ', last_access';
    $values .= ', '.mysql_real_escape_string($last_access);
  }
  if ($country_code != null && $country_code != '') {
    $insert .= ', country_code';
    $values .= ', \''.mysql_real_escape_string($country_code).'\'';
  }
  if ($region != null && $region != '') {
    $insert .= ', region';
    $values .= ', \''.mysql_real_escape_string($region).'\'';
  }
  if ($city != null && $city != '') {
    $insert .= ', city';
    $values .= ', \''.mysql_real_escape_string($city).'\'';
  }
  if ($postal_code != null && $postal_code != '') {
    $insert .= ', postal_code';
    $values .= ', \''.mysql_real_escape_string($postal_code).'\'';
  }
  if ($latitude != null && $latitude != '') {
    $insert .= ', latitude';
    $values .= ', '.mysql_real_escape_string($latitude);
  }
  if ($longitude != null && $longitude != '') {
    $insert .= ', longitude';
    $values .= ', '.mysql_real_escape_string($longitude);
  }
  if ($area_code != null && $area_code != '') {
    $insert .= ', area_code';
    $values .= ', \''.mysql_real_escape_string($area_code).'\'';
  }
  if ($dma_code != null && $dma_code != '') {
    $insert .= ', dma_code';
    $values .= ', \''.mysql_real_escape_string($dma_code).'\'';
  }
  $insert .= ') ';
  $values .= ');';
  
  global $wpdb;
  $wpdb->query($insert.$values);
  
  pn_counter_update_stats($user_date, $is_unique, $is_returning);
}

add_action('init', 'pn_counter_on_request');


function pn_counter_add_menu() {
  $configured = pn_counter_is_configured();
  if ($configured) {
    $default_file = ABSPATH.'wp-content/plugins/pn-counter/stats.php';
  } else {
    $default_file = ABSPATH.'wp-content/plugins/pn-counter/settings.php';
  }
  add_menu_page('pn-counter', 'pn-counter', 8, $default_file);
  if ($configured) {
  	add_submenu_page($default_file, 'pn-counter: stats', 'stats', 8, ABSPATH.'wp-content/plugins/pn-counter/stats.php');
  	add_submenu_page($default_file, 'pn-counter: raw values', 'raw values', 8, ABSPATH.'wp-content/plugins/pn-counter/raw-values.php');
  }
	add_submenu_page($default_file, 'pn-counter: settings', 'settings', 8, ABSPATH.'wp-content/plugins/pn-counter/settings.php');
}

add_action('admin_init', 'pn_counter_add_menu');

?>