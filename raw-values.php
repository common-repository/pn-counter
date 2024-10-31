<?php
require_once('pn-counter.php');

if (pn_counter_is_configured()) {
  pn_counter_raw_values_page();
} else {
  wp_redirect(get_option('siteurl').'/wp-admin/admin.php?page=pn-counter/settings.php');
}

function pn_counter_raw_values_page() {
  global $wpdb;
  
  $raw_values_table_name = pn_counter_get_raw_values_table_name();
  $sql = "SELECT count(*) FROM $raw_values_table_name;";
  $row_count = $wpdb->get_var($sql);
  if ($row_count != null && $row_count != '') {
    $page_count = round(($row_count / 100), 0);
    if ($page_count < ($row_count / 100)) {
      $page_count++;
    }
  } else {
    $page_count = 1;
  }
  
  $page_number = 1;
  if (array_key_exists('current_page', $_POST)) {
    $page_number = $_POST['current_page'];
    $prev_page = $_POST['prev_page'];
    $next_page = $_POST['next_page'];
    $selected_page = $_POST['selected_page'];
    $go_page = $_POST['go_page'];
    $first_page = $_POST['first_page'];
    $last_page = $_POST['last_page'];
    if ($prev_page != null && $prev_page != '') {
      $page_number--;
    }
    if ($next_page != null && $next_page != '') {
      $page_number++;
    }
    if ($go_page != null && $go_page != '' && $selected_page != null && $selected_page != '') {
      $page_number = $selected_page;
    }
    if ($first_page != null && $first_page != '') {
      $page_number = 1;
    }
    if ($last_page != null && $last_page != '') {
      $page_number = $page_count;
    }
    if ($page_number < 1) {
      $page_number = 1;
    }
    if ($page_number > $page_count) {
      $page_number = $page_count; 
    }
  }
  $first_row_index = ($page_number - 1) * 100;
  
  
  $sql = "SELECT id, local_date, ip, url, referer, user_agent, country_code, city FROM $raw_values_table_name ORDER BY id DESC LIMIT $first_row_index, 100";
  $wpdb->query($sql);
  
?>
<style type="text/css">
<!--
body, td {
	font-family: Arial, Helvetica, sans-serif;
	font-size: 11px;
  padding-left:5px;
  padding-right:5px;
}
.pn_counter_table_header {
	font-family: Arial, Helvetica, sans-serif;
	font-size: 12px;
	color: #FFFFFF;
	background-color: #666666;
}
.pn_counter_table_row_odd {
	background-color: #CCCCCC;
	height:30px;
}
.pn_counter_table_row_even {
	background-color: #EEEEEE;
	height:30px;
}
.col_id {
  width:40px;
  text-align:right;
}
.col_date {
  width:50px;
  text-align:center;
}
.col_ip {
  width:70px;
  text-align:center;
}
.col_url {
  width:400px;
  text-align:left;
}
.col_referer {
  width:400px;
  text-align:left;
}
.col_user_agent {
  text-align:left;
}
.col_country {
  width:60px;
  text-align:left;
}
.col_city {
  width:60px;
  text-align:left;
}
.selected_page {
	width: 40px;
}
-->
</style>
<table width="100%" border="0">
<thead>
<tr class="pn_counter_table_header">
<th>ID</th>
<th>Date</th>
<th>IP</th>
<th>URL</th>
<th>Referer</th>
<th>User Agent</th>
<th>Country</th>
<th>City</th>
</tr>
</thead>
<?php
  for ($i = 0; $i < $wpdb->num_rows; $i++) {
    $id = $wpdb->last_result[$i]->id;
    $local_date = $wpdb->last_result[$i]->local_date;
    $ip = $wpdb->last_result[$i]->ip;
    $url = $wpdb->last_result[$i]->url;
    $url_string = substr($wpdb->last_result[$i]->url, 0, 80);
    if ($url_string != $url) {
      $url_string .= "...";
    }
    $referer = $wpdb->last_result[$i]->referer;
    $referer_string = substr($wpdb->last_result[$i]->referer, 0, 80);
    if ($referer_string != $referer) {
      $referer_string .= "...";
    }
    $user_agent = $wpdb->last_result[$i]->user_agent;
    $country_code = $wpdb->last_result[$i]->country_code;
    $city = $wpdb->last_result[$i]->city;
    if ($i % 2 == 0) {
      echo("<tr class=\"pn_counter_table_row_even\">");
    } else {
      echo("<tr class=\"pn_counter_table_row_odd\">");
    }
    echo("<td class=\"col_id\">$id</td>");
    echo("<td class=\"col_date\">$local_date</td>");
    echo("<td class=\"col_ip\">$ip</td>");
    echo("<td class=\"col_url\"><a href=\"$url\">$url_string</a></td>");
    echo("<td class=\"col_referer\"><a href=\"$referer\">$referer_string</a></td>");
    echo("<td class=\"col_user_agent\">$user_agent</td>");
    echo("<td class=\"col_country\">$country_code</td>");
    echo("<td class=\"col_city\">$city</td>");
    echo("</tr>\n");
  }
?>
</table>
<form method="post">
<table width="100%" border="0">
<thead>
<tr class="pn_counter_table_header">
<th align="left">
<div>
<input type="hidden" name="current_page" id="current_page" value="<?php echo($page_number); ?>" />
<input type="submit" name="first_page" id="first_page" value="&lt;&lt;" title="First page" />
<input type="submit" name="prev_page" id="prev_page" value="&lt;" title="Previous page" />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Page:&nbsp;
<input type="text" name="selected_page" id="selected_page" size="6" class="selected_page" value="<?php echo($page_number); ?>" />
&nbsp;from&nbsp;<?php echo($page_count); ?>
&nbsp;<input type="submit" name="go_page" id="go_page" value="Go" title="Go to page number" />
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input type="submit" name="next_page" id="next_page" value="&gt;" title="Next page" />
<input type="submit" name="last_page" id="last_page" value="&gt;&gt;" title="Last page" />
</div>
</th>
</tr>
</thead>
</table>
</form>
<?php
}
?>
