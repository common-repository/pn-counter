<?php
require_once('pn-counter.php');

if (pn_counter_is_configured()) {
  pn_counter_stats_page();
} else {
  wp_redirect(get_option('siteurl').'/wp-admin/admin.php?page=pn-counter/settings.php');
}

function pn_counter_stats_page() {
  global $wpdb;
  
  $stats_table_name = pn_counter_get_stats_table_name();
  $sql = "SELECT count(*) FROM $stats_table_name;";
  $row_count = $wpdb->get_var($sql);
  if ($row_count != null && $row_count != '') {
    $page_count = round(($row_count / 30), 0);
    if ($page_count < ($row_count / 30)) {
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
  $first_row_index = ($page_number - 1) * 30;
  
  
  $sql = "SELECT id, local_date, page_loads, unique_visitors,
    first_time_visitors, returning_visitors FROM $stats_table_name
    ORDER BY id DESC LIMIT $first_row_index, 30";
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
	height:12px;
}
.pn_counter_table_row_even {
	background-color: #EEEEEE;
	height:12px;
}
.col_date {
  width:70px;
  text-align:center;
}
.col_page_loads {
  width:110px;
  text-align:right;
}
.col_unique_visitors {
  width:110px;
  text-align:right;
}
.col_first_time_visitors {
  width:110px;
  text-align:right;
}
.col_returning_visitors {
  width:110px;
  text-align:right;
}
-->
</style>
<table border="0">
<thead>
<tr class="pn_counter_table_header">
<th>Date</th>
<th>Page loads</th>
<th>Unique visitors</th>
<th>First time visitors</th>
<th>Returning visitors</th>
</tr>
</thead>
<?php
  for ($i = 0; $i < $wpdb->num_rows; $i++) {
    $id = $wpdb->last_result[$i]->id;
    $local_date = $wpdb->last_result[$i]->local_date;
    $page_loads = $wpdb->last_result[$i]->page_loads;
    $unique_visitors = $wpdb->last_result[$i]->unique_visitors;
    $first_time_visitors = $wpdb->last_result[$i]->first_time_visitors;
    $returning_visitors = $wpdb->last_result[$i]->returning_visitors;
    if ($i % 2 == 0) {
      echo("<tr class=\"pn_counter_table_row_even\">");
    } else {
      echo("<tr class=\"pn_counter_table_row_odd\">");
    }
    echo("<td class=\"col_date\">$local_date</td>");
    echo("<td class=\"col_page_loads\">$page_loads</td>");
    echo("<td class=\"col_unique_visitors\">$unique_visitors</td>");
    echo("<td class=\"col_first_time_visitors\">$first_time_visitors</td>");
    echo("<td class=\"col_returning_visitors\">$returning_visitors</td>");
    echo("</tr>\n");
  }
?>
</table>
<form method="post">
<table width="572" border="0">
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
