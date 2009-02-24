<?php
/*
Plugin Name: 30bwidget
Plugin URI: http://wordpress.org/extend/plugins/30boxes-calendar-widget-shortcode/
Description: This plugin allows to add <a href="http://30boxes.com">30Boxes.com</a> powered calendars to your blog. Currently it supports an eventlist, an embedded calendar and a sidebar calendar which is css compatible with the post calendar and should fit in most themes without further adjustments.
Version: 1.0
Author: Thorsten Ott
Author URI: http://30bwidget.wordpress.com
*/

/* 
 * This function implements a sidebar widget for widgetbox.com widgets
 */

function add_30b_scripts() {
    wp_enqueue_script('30boxes', '/wp-content/plugins/30boxes/30boxes.js', array('jquery'), '1.0' );
}

add_action( 'init', 'add_30b_scripts' );


function thirtyb_getsidebarcalendar($url, $pastdays = 24, $futuredays = 24, $number='shortcode', $initial = true) {
    global $wpdb, $m, $monthnum, $year, $wp_locale, $posts;

    if ( !$args = parse_30boxes_url( $url ) )
        return __( "The 30Boxes calendar URL is invalid!" );

    extract( $args );

    if( empty( $pastdays ) || 0 == (int) $pastdays )
        $pastdays = date("j");
    if( empty( $futuredays ) || 0 == (int) $futuredays )
        $futuredays = date("t")-date("j");

	$cache = array();
	$key = md5( $m . $monthnum . $year );
	if ( $cache = wp_cache_get( 'get_30bcalendar', 'calendar' ) ) {
		if ( is_array($cache) && isset( $cache[ $key ] ) ) {
			//return $cache[ $key ];
		}
	}

	if ( !is_array($cache) )
		$cache = array();

	ob_start();
    
    require_once(ABSPATH . WPINC . '/rss.php');

    if ( !empty( $tags ) )
        $rss_url = 'http://30boxes.com/rss/' . $ident . '/' . $name . '/' . $secret . '/' . $tags . '?daysForward=' . $futuredays . '&daysBack=' . $pastdays;
    else
        $rss_url = 'http://30boxes.com/rss/' . $ident . '/' . $name . '/' . $secret . '/' . '?daysForward=' . $futuredays . '&daysBack=' . $pastdays;

    
    $rss = fetch_rss( $rss_url );
    if ( !is_array( $rss->items ) || empty( $rss->items ) ) {
        return;
    }

    $items = array();
    foreach ( $rss->items as $item ) {
	if( empty( $item['dc:date'] ) ) 
	        $item_date = strtotime( $item['dc']['date'] );
	else
		$item_date = strtotime( $item['dc:date'] );

        $items[$item_date][] = array_merge( $item, array('date_unix' => $item_date ) );

    }
    
	if ( isset($_GET['w']) )
		$w = ''.intval($_GET['w']);

	// week_begins = 0 stands for Sunday
	$week_begins = intval(get_option('start_of_week'));

	// Let's figure out when we are
	if ( !empty($monthnum) && !empty($year) ) {
		$thismonth = ''.zeroise(intval($monthnum), 2);
		$thisyear = ''.intval($year);
	} elseif ( !empty($w) ) {
		// We need to get the month from MySQL
		$thisyear = ''.intval(substr($m, 0, 4));
		$d = (($w - 1) * 7) + 6; //it seems MySQL's weeks disagree with PHP's
		$thismonth = $wpdb->get_var("SELECT DATE_FORMAT((DATE_ADD('${thisyear}0101', INTERVAL $d DAY) ), '%m')");
	} elseif ( !empty($m) ) {
		$thisyear = ''.intval(substr($m, 0, 4));
		if ( strlen($m) < 6 )
				$thismonth = '01';
		else
				$thismonth = ''.zeroise(intval(substr($m, 4, 2)), 2);
	} else {
		$thisyear = gmdate('Y', current_time('timestamp'));
		$thismonth = gmdate('m', current_time('timestamp'));
	}
    
	$unixmonth = mktime(0, 0 , 0, $thismonth, 1, $thisyear);
    
    ksort( $items );

    $last_item_month_unix = 0;
    $next_item_month_unix = 0;

    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false || strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'camino') !== false || strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'safari') !== false)
		$ak_title_separator = "\n";
	else
		$ak_title_separator = ', ';

    $daywithpost = array();
    $ak_titles_for_day = array();
    $day_links = array();
    
    foreach( (array) $items as $item_date => $day_items ) {
        $item_year = date( "Y", $item_date );
        $item_month = date( "m", $item_date );
        $item_day = date( "d", $item_date );
        $item_month_unix = mktime(0, 0 , 0, $item_month, 1, $item_year);

        if( $item_month_unix == $unixmonth ) {
            $daywithpost[] = $item_day;
            foreach( (array) $day_items as $item ) {
                if ( !isset( $day_links[ $item_day ] ) )
                    $day_links[ $item_day ] = clean_url( $item['link'], true );
                
                $post_title = apply_filters( "the_title", $item['title'] );
                $post_title = str_replace('"', '&quot;', wptexturize( $post_title ));

                if ( empty($ak_titles_for_day['day_'.$item_day]) )
                    $ak_titles_for_day['day_'.$item_day] = '';
                if ( empty($ak_titles_for_day["$item_day"]) ) // first one
                    $ak_titles_for_day["$item_day"] = $post_title;
                else
                    $ak_titles_for_day["$item_day"] .= $ak_title_separator . $post_title;
            }

        }
        if( $item_month_unix < $unixmonth && $item_month_unix > $last_item_month_unix )
            $last_item_month_unix = $item_month_unix;
        if( $item_month_unix > $unixmonth && ( $item_month_unix < $next_item_month_unix || 0 == $next_item_month_unix ) )
            $next_item_month_unix = $item_month_unix;
        
    }

	// Get the next and previous month and year with at least one post
	$previous = array( 'year' => date("Y", $last_item_month_unix),
                       'month' => date("m", $last_item_month_unix) );
    
    $next = array( 'year' => date("Y", $next_item_month_unix),
                   'month' => date("m", $next_item_month_unix) );
    
	echo '<table id="wp-calendar" summary="' . __('Calendar') . '">
	<caption>' . sprintf(_c('%1$s %2$s|Used as a calendar caption'), $wp_locale->get_month($thismonth), date('Y', $unixmonth)) . '</caption>
	<thead>
	<tr>';

	$myweek = array();

	for ( $wdcount=0; $wdcount<=6; $wdcount++ ) {
		$myweek[] = $wp_locale->get_weekday(($wdcount+$week_begins)%7);
	}

	foreach ( $myweek as $wd ) {
		$day_name = (true == $initial) ? $wp_locale->get_weekday_initial($wd) : $wp_locale->get_weekday_abbrev($wd);
		echo "\n\t\t<th abbr=\"$wd\" scope=\"col\" title=\"$wd\">$day_name</th>";
	}

	echo '
	</tr>
	</thead>

	<tfoot>
	<tr>';

    /* upcoming feature
	if ( $previous ) {
		echo "\n\t\t".'<td abbr="' . $wp_locale->get_month($previous['month']) . '" colspan="3" id="prev"><a href="' .
		get_month_link($previous['year'], $previous['month']) . '" title="' . sprintf(__('View posts for %1$s %2$s'), $wp_locale->get_month($previous['month']),
			date('Y', mktime(0, 0 , 0, $previous['month'], 1, $previous['year']))) . '">&laquo; ' . $wp_locale->get_month_abbrev($wp_locale->get_month($previous['month'])) . '</a></td>';
	} else {
		echo "\n\t\t".'<td colspan="3" id="prev" class="pad">&nbsp;</td>';
	}

	echo "\n\t\t".'<td class="pad">&nbsp;</td>';
    
	if ( $next ) {
		echo "\n\t\t".'<td abbr="' . $wp_locale->get_month($next['month']) . '" colspan="3" id="next"><a href="' .
		get_month_link($next['year'], $next['month']) . '" title="' . sprintf(__('View posts for %1$s %2$s'), $wp_locale->get_month($next['month']),
			date('Y', mktime(0, 0 , 0, $next['month'], 1, $next['year']))) . '">' . $wp_locale->get_month_abbrev($wp_locale->get_month($next['month'])) . ' &raquo;</a></td>';
            } else {
		echo "\n\t\t".'<td colspan="3" id="next" class="pad">&nbsp;</td>';
    }
    */
    
	echo '
	</tr>
	</tfoot>

	<tbody>
	<tr>';


	// See how much we should pad in the beginning
	$pad = calendar_week_mod(date('w', $unixmonth)-$week_begins);
	if ( 0 != $pad )
		echo "\n\t\t".'<td colspan="'.$pad.'" class="pad">&nbsp;</td>';

	$daysinmonth = intval(date('t', $unixmonth));
	for ( $day = 1; $day <= $daysinmonth; ++$day ) {
		if ( isset($newrow) && $newrow )
			echo "\n\t</tr>\n\t<tr>\n\t\t";
		$newrow = false;

		if ( $day == gmdate('j', (time() + (get_option('gmt_offset') * 3600))) && $thismonth == gmdate('m', time()+(get_option('gmt_offset') * 3600)) && $thisyear == gmdate('Y', time()+(get_option('gmt_offset') * 3600)) )
			echo '<td id="today">';
		else
			echo '<td>';

		if ( in_array($day, $daywithpost) ) // any posts today?
				echo '<a href="' . $day_links[$day] . "\" title=\"$ak_titles_for_day[$day]\">$day</a>";
		else
			echo $day;
		echo '</td>';

		if ( 6 == calendar_week_mod(date('w', mktime(0, 0 , 0, $thismonth, $day, $thisyear))-$week_begins) )
			$newrow = true;
	}

	$pad = 7 - calendar_week_mod(date('w', mktime(0, 0 , 0, $thismonth, $day, $thisyear))-$week_begins);
	if ( $pad != 0 && $pad != 7 )
		echo "\n\t\t".'<td class="pad" colspan="'.$pad.'">&nbsp;</td>';

	echo "\n\t</tr>\n\t</tbody>\n\t</table>";

	$output = ob_get_contents();
	ob_end_clean();
    $cache[ $key ] = $output;
	wp_cache_set( 'get_30bcalendar', $cache, 'calendar' );
    return $output;
}



function thirtybcalendar_widget( $url, $width=800, $height=590, $themeUri ) {
    if ( !$args = parse_30boxes_url( $url ) )
        return __( "The 30Boxes calendar URL is invalid!" );

    extract( $args );

    if ( !empty( $themeUri ) )
        $themeadd='%26themeUri%3D' . $themeUri;
    else
        $themeadd='%26themeUri%3D/theme/default';

    return '<iframe frameborder="0" src="http://30boxes.com/widget/' . $ident . '/' . $name . '/' . $secret . '/' . $themeadd . '" width="' . $width . '" height="' . $height . '" style="border: none; overflow: hidden;"></iframe>';
    
}

function thirtybevent_list( $url, $pastdays = 24, $futuredays = 24, $num_events=10, $show_description=1, $number='shortcode' ) {
    if ( !$args = parse_30boxes_url( $url ) )
        return __( "The 30Boxes calendar URL is invalid!" );

    extract( $args );

    if( !empty( $pastdays ) && 0 != (int) $pastdays && !empty( $futuredays ) && 0 != (int) $futuredays )
        $dayfilter = '?daysForward=' . $futuredays . '&daysBack=' . $pastdays;
    
    require_once(ABSPATH . WPINC . '/rss.php');

    if ( !empty( $tags ) )
        $rss_url = 'http://30boxes.com/rss/' . $ident . '/' . $name . '/' . $secret . '/' . $tags . $dayfilter;
    else
        $rss_url = 'http://30boxes.com/rss/' . $ident . '/' . $name . '/' . $secret . '/' . $dayfilter;

    $rss = fetch_rss( $rss_url );
    if ( is_array( $rss->items ) ) {
        $items = array_slice( $rss->items, 0, $num_events );
        $out = "<ul class=\"thirtyBoxes-widget-event-list\" id=\"thirtyBoxes-widget-" . $number . "\">\n";
        $itemcount = 1;
        if ( empty ( $items ) )
            $items[] = array( "title" => __( "No items found" ), "link" => "", "description" => "" );
        
        foreach( $items as $item ) {
            $out .= "<li class=\"thirtyBoxes-widget-event-item\" id=\"thirtyBoxes-widget-item-" . $number . "-" . $itemcount . "\">\n";
            $out .= "\t<span class=\"thirtyBoxes-widget-event-item-title\">";
            if ( !empty ( $item['link'] ) )
                $out .= "<a href=\"" . clean_url( $item['link'], true ) ."\">" . wp_specialchars( $item['title'], true ) . "</a>";
            else
                $out .= wp_specialchars( $item['title'], true );
            $out .= "</span>\n";
            if ( 1 == $show_description && !empty( $item['description'] ) )
                $out .= "\t<div class=\"thirtyBoxes-widget-event-item-description\">" . wp_specialchars( $item['description'], true ) . "</div>\n";
            $out .= "</li>\n";
        }
        $out .= "</ul>\n";
    }

    return $out;

}

function get_30boxes_vars() {
    $all_vars = array(
                      // name => check method|arguments|fallback
                      'title' => 'string|80|30Boxes',
                      'url' => 'string|255|',
                      'type' => 'in_array|sidebarcalendar,calwidget,eventlist|eventlist',
                      'width' => 'integer||',
                      'height' => 'integer||',
                      'themeUri' => 'string|255||',
                      'num_events' => 'integer||',
                      'num_events_list' => 'integer||',
                      'show_description' => 'integer||',
                      'futuredays' => 'futuredays|24|',
                      'pastdays' => 'pastdays|24|',
                      'sbfuturedays' => 'futuredays|24|',
                      'sbpastdays' => 'pastdays|24|',
                      );
    return $all_vars;
}

function sanitize_30boxes_var($value, $check_string, $context = 'default' ) {
    $check_array = split( "\|", $check_string );

    if ( count( $check_array ) < 3 )
        return false;

    $method = $check_array[0];
    $params = $check_array[1];
    $default = $check_array[2];

    $safe_value = '';
    
    switch ( $method ) {
    case "in_array":
        if ( !in_array( $value, split(",", $params) ) )
            $safe_value = $default;
        else
            $safe_value = $value;
        break;
    case "integer":
        $safe_value = (int) $value;
        break;
    case "futuredays":
        if( (int) $value > 24 )
            $safe_value = 24;
        else
            $safe_value = (int) $value;
        break;
    case "pastdays":
        if( (int) $value > 24 )
            $safe_value = 24;
        else
            $safe_value = (int) $value;
        break;
    case "default":
    case "string":
        if ( empty( $value ) || strlen( $value ) > $params )
            $safe_value = $default;
        else
            $safe_value = wp_specialchars( $value );
        break;        
    }

    if ( 'output' == $context )
        $safe_value = attribute_escape( $safe_value );

    return $safe_value;
}    

function parse_30boxes_url( $url ) {
    $url_split = split( "/", $url );

    if ( count( $url_split ) < 8 )
        return false;

    if ( count( $url_split ) >= 8 )
        $tags = $url_split[8];
    else
        $tags = '';
    
    return array( 'ident' => $url_split[4], 'name' => $url_split[5], 'secret' => $url_split[6] . '/' . $url_split[7], 'private' => $url_split[7], 'secret_plain' => $url_split[6], 'tags' => $tags );
}

function thirtyb_shortcode( $attributes ) {
    $allattributes = get_30boxes_vars();

    foreach ( $allattributes AS $var_name => $check_string )
            $$var_name = sanitize_30boxes_var( $attributes[$var_name], $check_string, 'output' );

    switch ( $type ) {
        case "sidebarcalendar":
            $widget = thirtyb_getsidebarcalendar( $url, $pastdays, $futuredays, $number );
            break;
        case "calwidget":
            $widget = thirtybcalendar_widget( $url, $width, $height, $themeUri );
            break;
        case "eventlist":
            $widget = thirtybevent_list( $url, $pastdays, $futuredays, $num_events, $show_description, $number );
            break;            
    }
    
    return $widget;    
}
add_shortcode( '30boxes', 'thirtyb_shortcode' );

function widget_30boxeswidget( $args, $widget_args = 1 ) {
        extract( $args, EXTR_SKIP );
        if ( is_numeric($widget_args) )
                $widget_args = array( 'number' => $widget_args );
        $widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
        extract( $widget_args, EXTR_SKIP );
        
        $options = get_option('widget_30boxeswidget');
        if ( !isset($options[$number]) )
                return;

        $allvars = get_30boxes_vars();

        foreach ( $allvars AS $var_name => $check_string )
            $$var_name = sanitize_30boxes_var( $options[$number][$var_name], $check_string, 'output' );

        switch ( $type ) {
        case "calwidget":
            $widget = thirtybcalendar_widget( $url, $width, $height, $themeUri );
            break;
        case "eventlist":
            $widget = thirtybevent_list( $url, $pastdays, $futuredays, $num_events_list, $show_description, $number );
            break;
        case "sidebarcalendar":
            $widget = thirtyb_getsidebarcalendar( $url, $pastdays, $futuredays, $number );
            break;
            
        }
        
		?>
		<?php echo $before_widget; ?>
			<?php echo $before_title . "<a href='" . clean_url($url) . "'>" . $title . "</a>" . $after_title; ?>
			<div id="30boxeswidget-<?php echo $number ?>">
			<?php echo $widget; ?>
			</div>			
		<?php echo $after_widget; ?>
		<?php
		
}

function widget_30boxeswidget_control( $widget_args = 1 ) {
        global $wp_registered_widgets;
        static $updated = false;

        if ( is_numeric($widget_args) )
                $widget_args = array( 'number' => $widget_args );
                
        $widget_args = wp_parse_args( $widget_args, array( 'number' => -1 ) );
        extract( $widget_args, EXTR_SKIP );

        // Data should be stored as array:  array( number => data for that instance of the widget, ... )
        $options = get_option('widget_30boxeswidget');
        if ( !is_array($options) )
            $options = array();

        $allvars = get_30boxes_vars();

        // We need to update the data
        if ( !$updated && !empty($_POST['sidebar']) ) {
                // Tells us what sidebar to put the data in
                $sidebar = (string) $_POST['sidebar'];

                $sidebars_widgets = wp_get_sidebars_widgets();
                if ( isset($sidebars_widgets[$sidebar]) )
                        $this_sidebar =& $sidebars_widgets[$sidebar];
                else
                        $this_sidebar = array();

                foreach ( $this_sidebar as $_widget_id ) {
                        if ( 'widget_30boxeswidget' == $wp_registered_widgets[$_widget_id]['callback'] && isset($wp_registered_widgets[$_widget_id]['params'][0]['number']) ) {
                                $widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
                                if ( !in_array( "30boxeswidget-$widget_number", $_POST['widget-id'] ) )
									unset($options[$widget_number]);
                        }
                }

                foreach ( (array) $_POST['widget-30boxeswidget'] as $widget_number => $widget_30boxeswidget_instance ) {                        
                    $continue = true;
                    $tmp_options = array();
                    foreach ( $allvars AS $var_name => $check_string ) {
                        if ( isset( $widget_30boxeswidget_instance[$var_name] ) ) {
                            $continue = false;
                            $$var_name = sanitize_30boxes_var( $widget_30boxeswidget_instance[$var_name], $check_string );
                            $tmp_options[$var_name] = $$var_name;
                        } else {
                            $$var_name = '';
                        }
                    }
                    if ( true === $continue )
                        continue;
                    
                    $options[$widget_number] = $tmp_options;                        
                }
                update_option('widget_30boxeswidget', $options);

                $updated = true; // So that we don't go through this more than once
        }


        if ( -1 == $number ) {                 
                $number = '%i%';
                foreach ( $allvars AS $var_name => $check_string) 
                    $$var_name = '';
        } else {
            foreach ( $allvars AS $var_name => $check_string)
                $$var_name = attribute_escape($options[$number][$var_name]);
        }
        
            ?>
            
			<p>
				<label for="30boxeswidget-<?php echo $number; ?>-title">
                <?php echo __( "Widget title:" ) ?>
					<input type="text" id="30boxeswidget-<?php echo $number; ?>-title" name="widget-30boxeswidget[<?php echo $number; ?>][title]" class="widefat" value="<?php echo $title; ?>" />
				</label>
			</p>
			<p>
				<label for="30boxeswidget-<?php echo $number; ?>-url">
                <?php echo __( "30Boxes Url:" ) ?>
					<input type="text" id="30boxeswidget-<?php echo $number; ?>-url" name="widget-30boxeswidget[<?php echo $number; ?>][url]" class="widefat" value="<?php echo $url; ?>" />
                         <small><?php echo __("Hint: <a href=\"http://30bwidget.wordpress.com/faq/\" target=\"_blank\" alt=\"find your 30Boxes URL\">find your 30Boxes URL</a>") ?></small>
				</label>
			</p>
			<p>
				<label for="30boxeswidget-<?php echo $number; ?>-type">
                <?php echo __( "Widget type:" ) ?>
					<select id="30boxeswidget-<?php echo $number; ?>-type" name="widget-30boxeswidget[<?php echo $number; ?>][type]" class="widefat" onChange="boxeswidgetsbcontrol( '<?php echo $number; ?>' )" />
                      <option value="eventlist"<?php if ( 'eventlist' == $type || empty($type) ): ?> selected="selected"<?php endif; ?>>Event list</option>                                                                       
                      <option value="calwidget"<?php if ( 'calwidget' == $type ): ?> selected="selected"<?php endif; ?>>Calendar Widget</option>
                      <option value="sidebarcalendar"<?php if ( 'sidebarcalendar' == $type ): ?> selected="selected"<?php endif; ?>>Sidebar Calendar</option>        
                    </select>    
                    <small><?php echo __( "Hint: see your <a href=\"http://30boxes.com/account\" target=\"_blank\" alt=\"sharing page\">sharing page</a> for samples" ) ?></small>
				</label>
			</p>

            <div id="30boxeswidget-<?php echo $number; ?>-extras">
		  
            <div id="30boxeswidget-<?php echo $number; ?>-extras-calwidget"<?php if( 'calwidget' != $type ): ?> style="display:none"<?php endif; ?>>                          

            <p>
                 <?php echo __( "<b>Remark:</b> Calendar widget needs wide sidebar or <a href=\"http://30boxes.com/blog/index.php/developers/themes/\" target=\"_blank\" alt=\"custom theme\">custom theme</a>. You might want to use the <a href=\"http://30bwidget.wordpress.com/shortcode/\" target=\"_blank\" alt=\"short code\">short code</a> instead." ) ?>
            </p>                                                          

            <p>
				<label for="30boxeswidget-<?php echo $number; ?>-width">
                    <?php echo __( "Calendar widget width:" ) ?>
					<input type="text" id="30boxeswidget-<?php echo $number; ?>-width" name="widget-30boxeswidget[<?php echo $number; ?>][width]" class="widefat" value="<?php echo $width; ?>" />
				</label>
			</p>
            <p>
				<label for="30boxeswidget-<?php echo $number; ?>-height">
					<?php echo __( "Calendar widget height:" ) ?>
					<input type="text" id="30boxeswidget-<?php echo $number; ?>-height" name="widget-30boxeswidget[<?php echo $number; ?>][height]" class="widefat" value="<?php echo $height; ?>" />
				</label>
			</p>

            <p>
				<label for="30boxeswidget-<?php echo $number; ?>-themeUri">
					<?php echo __( "Calendar widget <a href=\"http://30boxes.com/blog/index.php/developers/themes/\" target=\"_blank\" alt=\"custom theme\">ThemeUri</a>:" ) ?>
					<input type="text" id="30boxeswidget-<?php echo $number; ?>-themeUri" name="widget-30boxeswidget[<?php echo $number; ?>][themeUri]" class="widefat" value="<?php echo $themeUri; ?>" />
				</label>
			</p>

                          
            </div><!-- end of 30boxeswidget-<?php echo $number; ?>-extras-calwidget -->

            <div id="30boxeswidget-<?php echo $number; ?>-extras-eventlist"<?php if( !empty($type) && 'eventlist' != $type ): ?> style="display:none"<?php endif; ?>>

            <p>
				<label for="30boxeswidget-<?php echo $number; ?>-num_events_list">
					<?php echo __( "Number of events in list:" ) ?>
					<input type="text" id="30boxeswidget-<?php echo $number; ?>-num_events_list" name="widget-30boxeswidget[<?php echo $number; ?>][num_events_list]" class="widefat" value="<?php echo $num_events_list; ?>" />
				</label>
			</p>

            <p>
				<label for="30boxeswidget-<?php echo $number; ?>-show_description">
					<?php echo __( "Event list show descriptions:" ) ?>
					<select id="30boxeswidget-<?php echo $number; ?>-show_description" name="widget-30boxeswidget[<?php echo $number; ?>][show_description]" class="widefat" />
                        <option value="1"<?php if ( '1' == $show_description ): ?> selected="selected"<?php endif; ?>><?php echo __( "Yes" ) ?></option>
                        <option value="0"<?php if ( '0' == $show_description ): ?> selected="selected"<?php endif; ?>><?php echo __( "No" ) ?></option>                                                  </select>                                                   
				</label>
            </p>

            <p>
				<label for="30boxeswidget-<?php echo $number; ?>-futuredays">
                    <?php echo __( "Eventlist future days:" ) ?>
					<input type="text" id="30boxeswidget-<?php echo $number; ?>-futuredays" name="widget-30boxeswidget[<?php echo $number; ?>][futuredays]" class="widefat" value="<?php echo $futuredays; ?>" />
				</label>
                <small><?php echo __( "Hint: You can show events which are up to 24 days in the future. Defaults to current month" ) ?></small>
			</p>
            <p>
				<label for="30boxeswidget-<?php echo $number; ?>-pastdays">
					<?php echo __( "Eventlist past days:" ) ?>
					<input type="text" id="30boxeswidget-<?php echo $number; ?>-pastdays" name="widget-30boxeswidget[<?php echo $number; ?>][pastdays]" class="widefat" value="<?php echo $pastdays; ?>" />
				</label>
                <small><?php echo __( "Hint: You can show events which are up to 24 days in the past. Defaults to current month" ) ?></small>
			</p>
                          
                                                                                                                            
            </div><!-- end of 30boxeswidget-<?php echo $number; ?>-extras-eventlist -->

            <div id="30boxeswidget-<?php echo $number; ?>-extras-sidebarcalendar"<?php if( 'sidebarcalendar' != $type ): ?> style="display:none"<?php endif; ?>>
            <p>
				<label for="30boxeswidget-<?php echo $number; ?>-sbfuturedays">
                    <?php echo __( "Sidebar calendar future days:" ) ?>
					<input type="text" id="30boxeswidget-<?php echo $number; ?>-futuredays" name="widget-30boxeswidget[<?php echo $number; ?>][sbfuturedays]" class="widefat" value="<?php echo $sbfuturedays; ?>" />
				</label>
                <small><?php echo __( "Hint: You can show events which are up to 24 days in the future. Defaults to current month" ) ?></small>
			</p>
            <p>
				<label for="30boxeswidget-<?php echo $number; ?>-sbpastdays">
					<?php echo __( "Sidebar calendar past days:" ) ?>
					<input type="text" id="30boxeswidget-<?php echo $number; ?>-sbpastdays" name="widget-30boxeswidget[<?php echo $number; ?>][sbpastdays]" class="widefat" value="<?php echo $sbpastdays; ?>" />
				</label>
                <small><?php echo __( "Hint: You can show events which are up to 24 days in the past. Defaults to current month" ) ?></small>
			</p>                                                                                                                               
            </div><!-- end of 30boxeswidget-<?php echo $number; ?>-extras-sidebarcalendar -->

            </div><!-- end of 30boxeswidget-<?php echo $number; ?>-extras -->        

			<input type="hidden" name="30boxeswidget-<?php echo $number; ?>-submit" id="widget-30boxeswidget[<?php echo $number; ?>][submit]" value="1" />
            <?php
			
}

function widget_30boxeswidget_register() {
        if ( !$options = get_option('widget_30boxeswidget') )
                $options = array();

        $allvars = get_30boxes_vars();
                
        $widget_ops = array('classname' => 'widget_30boxeswidget', 'description' => __('30Boxes.com calendar widget'));
        $control_ops = array('id_base' => '30boxeswidget');
        $name = __('30Boxes');

        $registered = false;        
        foreach ( array_keys($options) as $o ) {
            $continue = true;
            $tmp_options = array();
            foreach ( $allvars AS $var_name => $check_string ) {
                if ( isset( $options[$o][$var_name] ) ) {
                    $continue = false;
                }
            }
            if ( true === $continue )
                continue;

            $id = "30boxeswidget-$o"; 
            $registered = true;
            wp_register_sidebar_widget( $id, $name, 'widget_30boxeswidget', $widget_ops, array( 'number' => $o ) );
            wp_register_widget_control( $id, $name, 'widget_30boxeswidget_control', $control_ops, array( 'number' => $o ) );
            $instcount++;
        }
        
        if ( !$registered ) {
            wp_register_sidebar_widget( '30boxeswidget-1', $name, 'widget_30boxeswidget', $widget_ops, array( 'number' => -1 ) );
            wp_register_widget_control( '30boxeswidget-1', $name, 'widget_30boxeswidget_control', $control_ops, array( 'number' => -1) );
        }
}

add_action( 'init', 'widget_30boxeswidget_register' )

?>
