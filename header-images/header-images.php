<?php
/*
Plugin Name: Header Images
Plugin URI: http://www.fineonly.com
Description: Allows header rotating image management
Version: 0.5
Author: Vital
Author URI: http://www.fineonly.com
License: Free
*/

//include(ABSPATH.'wp-admin/includes/file.php');
include(ABSPATH.'wp-includes/pluggable.php');

//register_activation_hook(__FILE__,'header_images_install'); 

// Hook for adding new admin menu(s)
add_action('admin_menu', 'add_header_images_menu');
add_action('admin_head', 'header_images_css');
add_action('admin_head', 'header_images_javascript');
add_action('wp_ajax_delete_image', 'delete_img');
add_action('wp_ajax_change_status', 'change_status');
add_action('wp_ajax_save_link', 'save_link');
add_action('wp_ajax_move', 'move');
add_action( 'wp_enqueue_scripts', 'add_slideshow_styles' );
add_action( 'wp_footer', 'slideshow_script' );
add_action('admin_init', 'header_images_options_function' );
register_activation_hook(__FILE__,'header_images_install');

function slideshow_script() {
    $options = get_option('header_images_options');
    //if ( !isset($options["delay_time"]) || !isset($options["transition_time"]) 
    ?>
    <script type="text/javascript">
      jQuery(window).load(function(){
    if(jQuery('#slideshow a').length >1)play();
});


function play() {
    var lastImage = jQuery('#slideshow a:last');
        lastImage.delay(<?php echo $options['delay_time']; ?>).animate({opacity: 0.0}, <?php echo $options['transition_time']; ?>, function (){jQuery('#slideshow a:first').before(lastImage);lastImage.css({opacity: 1});/* jQuery('#slideshow a:last').remove();*/if(jQuery('#slideshow a').length >1)play();});
}
    </script>
    <?php
    
}


function add_slideshow_styles() {
        wp_register_style( 'slideshow_styles', plugins_url('style.css', __FILE__) );
        wp_enqueue_style( 'slideshow_styles' );
    }


// action function for the above hook
function add_header_images_menu() {

    // Add a new top-level menu:
    add_menu_page('Header Image Management', 'Header Images', 'delete_users', 'header-images', 'header_images', 'http://www.hospitaller-soulspirithealing.org/wp-content/themes/ahimsa/images/header.png');
}


function header_images() {
    global $wpdb;
    
    $sql = "SELECT * FROM wp_header_images ORDER BY image_ID";
    $result = $wpdb->get_results($sql, ARRAY_A);
    ?>
    <div id="header-images" class="wrap">
    <div class="icon32" id="icon-users"><br></div><h2>Header Image Management</h2>
    
    
        <fieldset id="HISettings">
        <legend> Settings </legend>
    <form method="post" action="options.php">
            <?php
            settings_fields('header_images_options');
            $options = get_option('header_images_options'); 
            ?>
            <table class="form-table">
                <tr valign="top"><th scope="cell">Rest time between slides (in milliseconds)</th>
                    <td><input name="header_images_options[delay_time]" type="text" length="5" value="<?php echo $options['delay_time']; ?>" />
                    </td>
                    <th scope="cell">Transition time (in milliseconds)</th>
                    <td><input name="header_images_options[transition_time]" type="text" length="5" value="<?php echo $options['transition_time']; ?>" /></td>
                    <!--<tr>
                        
                    </tr>-->

                </tr>
            </table>

            <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
            </p>

        </form>
    </fieldset>
    
    
    
    <fieldset id="uploadNew">
        <legend> Upload a New Image </legend>
        <table class="form-table">
            <tbody>
          <tr><th>

        <form method="post" enctype="multipart/form-data" action="admin.php?page=header-images">
                      <?php
             echo '<input type="file" name="new_image" size="40" />';
            ?>
    <input type="submit" value="<?php esc_attr_e('Upload') ?>" name="html-upload" class="button">
    </form>
            
          </th></tr>
    </tbody></table></fieldset>
    <fieldset id="images">
        <legend>Uploaded Header Images</legend>
        <table class="form-table">
            <tbody>
            <?php
            if ($_FILES['new_image']) {
                $overrides = array('test_form' => false); 
                $file = wp_handle_upload($_FILES['new_image'], $overrides);
                //var_dump($file);
                if( stripos($file["type"], "image") == 0 ) {
                $wpdb->insert( 'wp_header_images', array( 'image_URL' => $file['url'], 'image_status' => 'visible' ));
                echo "<meta http-equiv='refresh' content='0' />";
                //display the newly uploaded image
                echo '<tr id="'.$wpdb->insert_id.'"><th>'.$file['url'].'<br /><img class="visible" src="'.$file['url'].'" /><br />';
                echo '<span class="image-order-up"><input type="submit" value="up" name="'.$image["image_ID"].'" class="button up" /></span><span class="image-order-down"><input type="submit" value="Down" name="'.$image["image_ID"].'" class="button down" /></span>';
                echo 'Status: <a class="visible" href="'.$file['url'].'" title="Click to Change Status">visible</a>';
                echo '<span class="image-link">Link to: <input type="text" name="image-link" size="40" value="'.$image["image_link"].'" /><input type="submit" value="Save link" name="'.$image["image_URL"].'" class="button save-link"></span>';
                echo '<span class="notifications"></span>';
                echo '<input type="submit" value="Delete" class="button-primary" style="float:right;" name="'.$wpdb->insert_id.'" />';
                echo '<hr /></th></tr>';
                }
                else echo "Error - type:".$file["type"]."   ".stripos($file["type"], "image");
                //echo "<meta http-equiv='refresh' content='0' />";
            }
    foreach(array_reverse($result, true) as $image) {
        echo '<tr id="'.$image["image_ID"].'"><th>'.$image["image_URL"].'<br /><img class="'.$image["image_status"].'" src="'.$image["image_URL"].'" /><br />';
        echo '<span class="image-order"><input type="submit" value="&uarr;" name="'.$image["image_ID"].'" class="button up" title="Move Up"/>
        <input type="submit" value="&darr;" name="'.$image["image_ID"].'" class="button down" title="Move Down" /></span>';
        echo 'Status: <a class="'.$image["image_status"].'" href="'.$image["image_URL"].'" title="Click to Change Status">'.$image["image_status"].'</a>';
        echo '<span class="image-link">Link to: <input type="text" name="image-link" size="40" value="'.$image["image_link"].'" /></span><input type="submit" value="Save link"  name="'.$image["image_URL"].'" class="button save-link">';
        echo '<span class="notifications"></span>';
        echo '<input type="submit" value="Delete" class="button-primary" style="float:right;" name="'.$image["image_ID"].'" />';
        echo '<hr /></th></tr>';
    }
    ?>
    </tbody></table></fieldset>  
    
    <?php
    echo '</div>';
}

function header_images_css() {
 echo '
<style type="text/css">
#header-images fieldset {background: none repeat scroll 0 0 #FDFDFD; border: 1px solid #BBBBBB; border-radius: 11px 11px 11px 11px; margin: 1em 0; padding: 0 1em 1em;}
#header-images img{margin-bottom: 7px;}
#header-images img.unused {opacity: 0.3; filter: alpha(opacity = 30);}
#header-images fieldset#images input.button-primary {background: gray !important;}
#header-images fieldset#images input.button-primary:hover {background: red !important;}
#header-images a.unused {font-weight: bold; color: red;}
#header-images a.visible {font-weight: bold; color: green;}
#header-images hr {clear: both; margin-top:15px; color: #FDFDFD}
#header-images fieldset legend {color: #999999; font-weight: bold; padding: 0 5px;}
#header-images fieldset#images span.notifications {margin-left: 250px; color: #aaaaaa; font-weight: bold; padding: 1px 5px;}
#header-images fieldset#images span.image-link{margin-left: 25px;}
#header-images fieldset#images span.image-order{margin-right: 25px;}
image-link
.redBorder { border: 1px dashed red; background: yellow;}

</style>
 ';

}

function header_images_javascript() {
?>
<script type="text/javascript" >

function changeStatus() {
    jQuery(this).next(".notifications").text("...updating").addClass("redBorder");    
    var cssClass = jQuery(this).attr("class");
    var changeTo = "unused";
    if(cssClass == "unused") changeTo = "visible";
    var data = {action: 'change_status', url: jQuery(this).attr("href"), status: changeTo};
    jQuery.post(ajaxurl, data, function(response) {
        jQuery('#header-images a[href="'+response+'"], #header-images img[src="'+response+'"]').addClass(changeTo).removeClass(cssClass).text(changeTo).next(".notifications").html("").removeClass("redBorder");
    });
    return false;  
}

function deleteImage() {
    jQuery(this).prev(".notifications").text("...deleting").addClass("redBorder");
    var data = {action: 'delete_image', image: jQuery(this).attr("name")};
    jQuery.post(ajaxurl, data, function(response) {jQuery("#header-images fieldset#images tr#" + response).slideUp(5000).remove();});
}

function saveLink() {
    jQuery(this).next(".notifications").text("...saving").addClass("redBorder");
    alert(jQuery(this).prev("span").children("input").val());
    var data = {action: 'save_link', url: jQuery(this).attr("name"), image_link: jQuery(this).prev("span").children("input").val()};
    jQuery.post(ajaxurl, data, function(response) {jQuery("span.notifications").html("").removeClass("redBorder");});
}

function move() {
    var this_id = jQuery(this).attr("name");
    var this_row = jQuery("tr#" + this_id );
    var is_up = jQuery(this).hasClass("up");
    var next_row = is_up ? this_row.prev("tr") : this_row.next("tr");
    var next_id = next_row.find("input.down").attr("name");
    
    if( next_row.length != 0 ){
        //jQuery(this).next(".notifications").text("...saving").addClass("redBorder");
        //alert(next_row.attr("id"));
        var data = {action: 'move', this_url: this_row.find("img").attr("src"), this_link: this_row.find("input[name='image-link']").val(), this_id : this_id, this_status : this_row.find("img").attr("class"), next_url: next_row.find("img").attr("src"), next_link: next_row.find("input[name='image-link']").val(), next_id: next_id, next_status: next_row.find("img").attr("class")
        };
        //alert( JSON.stringify(data) );
        jQuery.post(ajaxurl, data, function(response) {
                //alert(response);
                next_row.attr("id", this_id);
                next_row.find("input.up, input.down, input.button-primary").attr("name", this_id);
                this_row.find("input.up, input.down, input.button-primary").attr("name", next_id);
                this_row.attr("id", next_id);
                is_up ? next_row.slideToggle("fast").before(this_row).slideToggle() : next_row.slideToggle().after(this_row).slideToggle() ;
            });
    }
    else alert("Nowhere to move");
}


jQuery(document).ready(function($) {
    jQuery("#header-images img").css("max-width", (jQuery("#uploadNew").width() - 20) + "px");
    jQuery("#header-images fieldset#images input.button-primary").click(deleteImage);
    jQuery("#header-images fieldset#images a").click(changeStatus);
    jQuery("#header-images fieldset#images input.save-link").click(saveLink);
    jQuery("#header-images fieldset#images input.up, #header-images fieldset#images input.down").click(move);
});
</script>
<?php
}

function delete_img() {
	global $wpdb;

	$imageID = $_POST['image'];
        $wpdb->query("DELETE FROM `wp_header_images` WHERE image_ID = '".$imageID."'");
        echo $imageID;
	die(); // this is required to return a proper result
}

function change_status() {
	global $wpdb;

	$imageURL = $_POST['url'];
        $imageStatus = $_POST['status'];
        $wpdb->update( 'wp_header_images', array( 'image_status' => $imageStatus), array( 'image_URL' => $imageURL ));
        echo $imageURL;
	die(); // this is required to return a proper result
}

function save_link() {
	global $wpdb;

	$imageURL = $_POST['url'];
        $imageLink = $_POST['image_link'];
        $wpdb->update( 'wp_header_images', array( 'image_link' => $imageLink), array( 'image_URL' => $imageURL ));
        //echo $imageLink;
	die(); // this is required to return a proper result
}

function move() {
	global $wpdb;
        
        $next_id = $_POST['next_id'];
        $next_link = $_POST['next_link'];
        $next_url = $_POST['next_url'];
        $next_status = $_POST['next_status'];
        
        $this_id = $_POST['this_id'];
        $this_link = $_POST['this_link'];
        $this_url = $_POST['this_url'];
        $this_status = $_POST['this_status'];

        $first = $wpdb->update( 'wp_header_images', array( 'image_link' => $next_link, 'image_URL' => $next_url, 'image_status' => $next_status), array( 'image_ID' => $this_id ));
        $second = $wpdb->update( 'wp_header_images', array( 'image_link' => $this_link, 'image_URL' => $this_url, 'image_status' => $this_status), array( 'image_ID' => $next_id ));
        echo $first + $second;
	die(); // this is required to return a proper result
}

        

function header_images_options_function () {
    register_setting( 'header_images_options', 'header_images_options' );
    $options = get_option('header_images_options');
    if ( !isset($options["delay_time"]) || !isset($options["transition_time"]) )
        update_option( 'header_images_options', array( "delay_time" => 4000, "transition_time" => 2000 ) );
}

function header_images_install () {
   global $wpdb;

   $table_name = $wpdb->prefix . "header_images";
   /*if($wpdb->get_var("show tables like '$table_name'") != $table_name)*/ {
      
      $sql = "CREATE TABLE " . $table_name . " (
	  image_ID bigint(20) NOT NULL AUTO_INCREMENT,
	  image_URL varchar(200) NOT NULL,
          image_status varchar(10) NOT NULL,
          image_link varchar(200) NOT NULL,
	  UNIQUE KEY image_ID (image_ID)
	);";

      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
      dbDelta($sql);
   }
}

function header_slideshow() {
    echo '<div id="slideshow">';
    global $wpdb;
    
    $sql = "SELECT * FROM wp_header_images ORDER BY image_ID";
    $result = $wpdb->get_results($sql, ARRAY_A);
	
    foreach ($result as $pic) {
        if($pic["image_status"] == "visible"){
            $link = $pic["image_link"] == "http://cuttingedgestencils.com" ? "" : $pic["image_link"];
            echo '<a href="'.$link.'"><img alt="Network for the Science of Healing and Spirituality" src="'.$pic["image_URL"].'" /></a>';
        }
        else
            {continue;}
    }
    echo '</div>';
}



?>