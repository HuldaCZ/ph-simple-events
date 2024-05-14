<?php
/**
 * @package PH Simple events
 */

/*
    Plugin Name: PH Simple events
    Plugin URI: https://petrhuli.com
    Description: This plugin allows you to create and manage events
    Version: 1.0.0
    Author: Petr Huli
    Author URI: https://petrhuli.com
    License: GPLv2 or later
    Text Domain: ph-simple-events
 */

/*
   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License, version 2, as
   published by the Free Software Foundation.  You may NOT assume that you can
   use any other version of the GPL.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.


   You should have received a copy of the GNU General Public License
   along with this program; if not, write to the Free Software
   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

defined("ABSPATH") or die("Hey, you can't access this file, you silly human!");

add_action("init", "ph_event_post_type");
function ph_event_post_type()
{
    register_post_type(
        "event",
        array(
            "labels" => array(
                "name" => __("Events", "ph-simple-events"),
                "singular_name" => __("Event", "ph-simple-events"),
                'menu_name' => __('Events', 'ph-simple-events'),
                'name_admin_bar' => __('Event', 'ph-simple-events'),
                'archives' => __('Event Archives', 'ph-simplee-events'),
                'attributes' => __('Event Attributes', 'ph-simplee-events'),
                'parent_item_colon' => __('Parent Event:', 'ph-simplee-events'),
                'all_items' => __('All Events', 'ph-simple-events'),
                'add_new_item' => __('Add New Event', 'ph-simple e-events'),
                'add_new' => __('Add New Event', 'ph-simpe-events'),
                'new_item' => __('New Event', 'ph-simple e-events'),
                'edit_item' => __('Edit Event', 'ph-simple-events'),
                'update_item' => __('Update Event', 'ph-simple-events'),
                'view_item' => __('View Event', 'ph-simple-events'),
                'view_items' => __('View Events', 'ph-simple e-events'),
                'search_items' => __('Search Event', 'ph-simpe-events'),
                'not_found' => __('Event not found', 'ph-simpe-events'),
                'not_found_in_trash' => __('Event not found in Trash', 'ph-simplee-events'),
                'featured_image' => __('Featured Image', 'ph-simpe-events'),
                'set_featured_image' => __('Set featured image', 'ph-simpe-events'),
                'remove_featured_image' => __('Remove featured image', 'ph-simplee-events'),
                'use_featured_image' => __('Use as featured image', 'ph-simple-events'),
                'insert_into_item' => __('Insert into event', 'ph-simple e-events'),
                'uploaded_to_this_item' => __('Uploaded to this event', 'ph-simple-events'),
                'items_list' => __('Events list', 'ph-simple e-events'),
                'items_list_navigation' => __('Events list navigation', 'ph-simple-events'),
                'filter_items_list' => __('Filter events list', 'ph-simple-events'),
            ),
            "has_archive" => true,
            "public" => true,
            "publicly_queryable" => true,
            "show_ui" => true,
            "show_in_menu" => true,
            "show_in_rest" => true,
            "supports" => array("title", "editor", "custom-fields", "revisions"),
            "can_export" => true,
        )
    );
}

// Add event date field to the event post type

function ph_add_event_date_metabox()
{
    add_meta_box(
        "post_metadata_events_post", // div id containing rendered fields
        "Event Date", // section heading displayed as text
        "post_meta_box_events_post", // callback function to render fields
        "event", // name of the post type
        "side", // location on the screen
        "high" // priority of the box in the column
    );
}

add_action("admin_init", "ph_add_event_date_metabox");

// Save field value 
function ph_save_post_meta_boxes()
{
    global $post;
    if (defined("DOING_AUTOSAVE") && DOING_AUTOSAVE) {
        return;
    }

    if (!isset($_POST["_event_date"])) {
        return;
    }
    update_post_meta($post->ID, "_event_date", $_POST["_event_date"]);
    update_post_meta($post->ID, "_event_start_date", $_POST["_event_start_date"]);

}
add_action("save_post", "ph_save_post_meta_boxes");


function post_meta_box_events_post()
{
    global $post;
    $custom = get_post_custom($post->ID);
    if (isset($custom["_event_date"]) && $custom["_event_date"][0] != "") {
        $event_date = $custom["_event_date"][0];
    } else {
        $event_date = "";
    }
    if (isset($custom["_event_start_date"]) && $custom["_event_start_date"][0] != "") {
        $event_start_date = $custom["_event_start_date"][0];
    } else {
        $event_start_date = "";
    }
    ?>
    <label><abbr title="Set only if event have duration over multiple days.">Event Start Date</abbr></label>
    <input type="date" name="_event_start_date" value="<?php echo $event_start_date; ?>" placeholder="Event Start Date">
    <br>
    <br>
    <label>Event Date</label>
    <input type="date" name="_event_date" value="<?php echo $event_date; ?>" placeholder="Event Date">

    <?php
}

// generate shortcode for displaying events\
add_shortcode("ph-events-list", "ph_events");
function ph_events()
{
    global $post;
    $args = array(
        "post_type" => "event",
        "post_status" => "publish",
        "posts_per_page" => 50,
        "orderby" => "meta_value",
        "meta_key" => "_event_date",
        "order" => "ASC"
    );

    $query = new WP_Query($args);

    $content = '<div class="ph-simple-events-wrapper" >';

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            // trash event if older than today
            $event_date = get_post_meta($post->ID, "_event_date", true);
            $event_start_date = get_post_meta($post->ID, "_event_start_date", true);

            if ($event_date == "") {
               $event_date = $event_start_date;
               $event_start_date = "";
            }

            $formatted_event_date = date_format(date_create($event_date), 'd.m.o');

            if (strtotime($event_date) < strtotime("today")) {
                wp_trash_post($post->ID);
            } elseif ($event_start_date != "") {
                $content .= '
                <div class="ph-simple-events-event" >
                    <h4>' . get_the_title() . '</h4>
                    <h5>' .date_format(date_create($event_start_date), 'd.m.o')  . ' - ' . $formatted_event_date . '</h5>
                    <p>' . get_the_content() . '</p>
                </div>
                ';
            } else {
                $content .= '
                <div class="ph-simple-events-event" >
                    <h4>' . get_the_title() . '</h4>
                    <h5>' . $formatted_event_date . '</h5>
                    <p>' . get_the_content() . '</p>
                </div>
                ';
            }
        }
    } else {
        $content .= "<p>Žádné nadcházející události.</p>";
    }
    $content .= "</div>";

    return $content;
}

// assign custom template to event post type 
// function load_event_template($template)
// {
//     global $post;

//     if ($post->post_type == "event" && is_single() && locate_template(array("single-event.php") !== $template)) {
//        return plugin_dir_path(__FILE__) . "single-event.php";
//     }

//     return $template;
// }

// add_filter("single_template", "load_event_template");



function enqueue()
{
    wp_enqueue_style("phpluginstyle", plugins_url("/assets/phstyle.css", __FILE__));
}

add_action("wp_enqueue_scripts", "enqueue");

