<?php
/*
Plugin Name: Cross-theme Stylesheets
Plugin URI: http://scott.sauyet.com/php/wp-plugins/stylesheets/
Description: Adds stylesheets to all themes.
Author: Scott Sauyet
Version: 0.1
Author URI: http://scott.sauyet.com/
*/
?>
<?php
define("STYLESHEETS_DEBUG", false);
$stylesheets_mgt_page_name = "Cross-theme Stylesheets";
$stylesheets_media_types = array("aural", "braille", "embossed", "handheld", "projection", "print", "screen", "tty", "tv");


// There must be a better way.  Want "../../wp-config.php", but that doesn't work?!
require dirname(dirname(dirname(__FILE__))) . "/wp-config.php"; 

$stylesheets_filename = basename(__FILE__);
$stylesheets_adminurl = "edit.php?page=$stylesheets_filename";
$stylesheets_index = -1;

$stylesheets_all = get_option("stylesheets_all_sheets");

if (!$stylesheets_all) {
    if (!$_REQUEST['action'] == 'add-new') {
        stylesheets_new_defaults();
    }
}

if (!$_REQUEST['action'] || $_REQUEST['action'] != "add-new") {
    $stylesheets_current = stylesheets_choose_custom();
}


if (stristr(urldecode($_SERVER['REQUEST_URI']), stripslashes($stylesheets_current->name) . '.css')):
    header('Content-type: text/css');
    if ($stylesheets_current) {
        echo(stripslashes($stylesheets_current->text));
    } else {
        echo("/* Stylesheet not found */\n");
    }
else:

add_action('wp_head', 'stylesheets_link');
add_action('admin_menu', 'stylesheets_add_pages');
add_action('admin_head','stylesheets_styles');

endif; 

// ======================================================================

function stylesheets_choose_custom() {
    global $stylesheets_all, $stylesheets_index;
    $request = urldecode($_SERVER['REQUEST_URI']);
    if (substr($request, strlen($request) - 4) == '.css') {
        $name = urldecode(basename($_SERVER['REQUEST_URI'], ".css"));
        $stylesheets_current = stylesheets_get_custom($name);
    } else if ($_REQUEST['stylesheet_name']) {
        $stylesheets_current = stylesheets_get_custom($_REQUEST['stylesheet_name']);
    } else if (count($stylesheets_all) == 1) {
        $stylesheets_current = $stylesheets_all[0];
        $stylesheets_index = 0;
    }

    return $stylesheets_current;
}

function stylesheets_new_defaults() {
    global $stylesheets_all;
    $stylesheets_current->name = "my-styles";
    $stylesheets_current->media = "all";
    $stylesheets_current->import = false;
    $stylesheets_current->text = "/* Enter your CSS here */\n";
    $stylesheets_current->visible = true;
    $stylesheets_all = array($stylesheets_current);
    update_option("stylesheets_all_sheets", $stylesheets_all);
}

function stylesheets_get_custom($name) {
    global $stylesheets_all, $stylesheets_index;
    for($index = 0; $index < count($stylesheets_all); $index++) {
        $stylesheets_current = $stylesheets_all[$index];
        if ($stylesheets_current->name == $name) {
            $stylesheets_index = $index;
            return $stylesheets_current;
        }
    }
    return false;
}

function stylesheets_link($unused) {
    global $stylesheets_all, $stylesheets_filename;
    $dir = get_settings('home') . "/wp-content/plugins";
    if ($stylesheets_all) {
        echo "\n";
    }
    foreach($stylesheets_all as $stylesheets_current) {
        if ($stylesheets_current->visible) {
            $media_list = stylesheets_get_media_list($stylesheets_current);
            $name = urlencode(stripslashes($stylesheets_current->name));
            if ($stylesheets_current->import) {
                if ($media_list == "all")  {
                    $media_list = "";
                } else {
                    $media_list = " " . $media_list;
                }
                echo "\t<style type='text/css'>\n\t\t@import url($dir/$stylesheets_filename/$name.css)$media_list;\n\t</style>\n";
            } else {
                echo "\t<link rel='stylesheet' type='text/css' media='$media_list' href='$dir/$stylesheets_filename/$name.css' />\n";
            }
        }
    }
}

function stylesheets_get_media_list(&$stylesheets_current) {
    $media = $stylesheets_current->media;
    if (is_array($media)) {
        $result = "";
        $count = count($media);
        if ($count > 0) {
            $result =  $media[0];
        }
        for ($index = 1; $index < $count; $index++) {
            $result .= ", " . $media[$index];
        }
        return $result;
    }
    return $media;
}

function stylesheets_get_checked(&$stylesheets_current, $medium) {
    if ($medium == $stylesheets_current->media) {
        return true;
    }
    if ($medium == "choose" && $stylesheets_current->media != "all") {
        return true;
    }
    if (!is_array($stylesheets_current->media)) {
        return false;
    }
    return in_array($medium, $stylesheets_current->media);
}

function stylesheets_get_unused_name(&$stylesheets_all) {
    $suffix = 0;
    do {
        $suffix++;
        $matched = false;
        foreach ($stylesheets_all as $stylesheets_current) {
            if ($stylesheets_current->name == "stylesheet-" . $suffix) {
                $matched = true;
            }
        }
    } while ($matched);
    return "stylesheet-" . $suffix;
}

function stylesheets_add_pages() {
    global $stylesheets_mgt_page_name;
    add_management_page($stylesheets_mgt_page_name, 'Stylesheets', 8, 'stylesheets.php', 'stylesheets_management');
}

function stylesheets_management() {
    global $stylesheets_current, $stylesheets_media_types, $stylesheets_all, $stylesheets_index;
    if (isset($_REQUEST['action'])) {
        $action = $_REQUEST['action'];
        $changes = false;
        if ($action == "update") {
            $stylesheets_current = stylesheets_get_custom($_REQUEST['current_stylesheet']);
            if ($_REQUEST['stylesheet_new_name']) {
                $stylesheets_current->name = $_REQUEST['stylesheet_new_name'];
                $stylesheets_current->name = str_replace("\\", "", $stylesheets_current->name);
                $stylesheets_current->name = str_replace("/", "", $stylesheets_current->name);
            }
            if ($_REQUEST['stylesheet_text']) {
                $stylesheets_current->text = $_REQUEST['stylesheet_text'];
            }
            if ($_REQUEST['stylesheet_import']) {
                $stylesheets_current->import = true;
            } else {
                $stylesheets_current->import = false;
            }
            if ($_REQUEST['media_types'] == "all") {
                $stylesheets_current->media = "all";
            } else {
                $stylesheets_current->media = array();
                foreach ($stylesheets_media_types as $medium) {
                    if ($_POST['media_type_' . $medium]) {
                        $stylesheets_current->media[] = $medium;
                    }
                }
            }
        } else if ($action == "edit") {
            // Will show the edit screen since stylesheet_name is selected
        } else if ($action == "hide") {
            $stylesheets_current->visible = false;
            $simple_action = true;
        } else if ($action == "show") {
            $stylesheets_current->visible = true;
            // $simple_action = true;
        } else if ($action == "delete") {
            array_splice($stylesheets_all, $stylesheets_index, 1);
            $delete = true;
            $simple_action = true;
        } else if ($action == "add-new") {
            // unset($stylesheets_current);
            $stylesheets_current=null;
            $stylesheets_current->name = stylesheets_get_unused_name($stylesheets_all);
            $stylesheets_current->media = "all";
            $stylesheets_current->import = false;
            $stylesheets_current->text = "/* Enter your CSS here */\n";
            $stylesheets_current->visible = true;
            $stylesheets_all[] = $stylesheets_current;
        } else if ($action == "reset-defaults") {
            // This is for debugging purposes.  Probably won't keep live, since it's open to abuse.
            stylesheets_new_defaults();
        } else {
            echo("<h2>Unknown Action</h2>");
        }

        if ($stylesheets_index >= 0 && !$delete) {
            $stylesheets_all[$stylesheets_index] = $stylesheets_current;
        }
        update_option("stylesheets_all_sheets", $stylesheets_all);
    }
    ?><div class="stylesheets"><?php
    if ($stylesheets_current && !$simple_action) {
        stylesheets_current_controls($stylesheets_current);
    }
    stylesheets_list_controls();
    stylesheets_debug();
    ?></div><?php
}

function stylesheets_current_controls(&$stylesheets_current) {
    global $stylesheets_media_types;
    $tabindex = 6;
    ?>
    <form name="update-stylesheet" method="post" action="<?php echo $stylesheets_adminurl; ?>"><div class="wrap">
        <h2>Edit Stylesheet</h2>
        <p>Styles entered here will appear on your blog regardless of the 
           template you choose.  This can be used to override specific CSS 
           for your chosen theme without making changes to the theme.  Or 
           it can add formatting to all themes at once.
        </p>
        <input type="hidden" name="action" value="update"/>
        <div id="stylesheet-main-controls">
            <p id="name-label"><label for="stylesheet_new_name">Name:</label></p>
            <p id="name-holder"">
                <input type="text" name="stylesheet_new_name" id="stylesheet_new_name" tabindex="1" value="<?php echo stripslashes($stylesheets_current->name); ?>" />
            </p>
            <p id="css-string"><code>.css</code></p>
            <p id="import-holder"><input type="checkbox" name="stylesheet_import" id="stylesheet_import"<?php if ($stylesheets_current->import) echo ' checked="checked"'; ?>/><label for="stylesheet_import"> Import?</label></p>
            <p id="styles-label"><label for="stylesheet_text">Styles:</label></p>
            <p id="styles-holder">
                <textarea rows="12" name="stylesheet_text" id="stylesheet_text" tabindex="2"><?php echo stripslashes($stylesheets_current->text); ?></textarea>
            </p>
        </div>
        <div id="stylesheet-media-types">
            <h3>Media Types</h3>
            <ul>
                <li><input type="radio" name="media_types" value="all" id="media_types_all" tabindex="4"<?php if (stylesheets_get_checked($stylesheets_current, "all")) echo ' checked="checked"'; ?>/> <label for="media_types_all">All</label></li>
                <li><input type="radio" name="media_types" value="choose" id="media_types_choose" tabindex="5"<?php if (stylesheets_get_checked($stylesheets_current, "choose")) echo ' checked="checked"'; ?>/> <label for="media_types_choose">Choose:</label></li>
                <li>
                    <ul>
<?php foreach($stylesheets_media_types as $medium) { ?>
                        <li><input type="checkbox" name="media_type_<?php echo $medium; ?>" id="media_type_<?php echo $medium; ?>" tabindex="<?php echo($tabindex++); ?>"<?php if (stylesheets_get_checked($stylesheets_current, $medium)) echo ' checked="checked"'; ?>/> <label for="media_type_<?php echo $medium; ?>"><?php echo $medium; ?></label></li>
<?php } ?>
                    </ul>
                </li>
            </ul>
        </div>
        <div id="stylesheet-buttons" style="clear: both; text-align: center; padding-top: 1em;";>
            <input type="submit" name="submit" value="Update" tabindex="3"/>
        </div>
        <div style="clear: both'">&nbsp;</div>
        <input type="hidden" name="current_stylesheet" value="<?php echo($stylesheets_current->name); ?>"/>
    </div></form>
<?php
}

function stylesheets_list_controls() {
    global $stylesheets_all, $stylesheets_adminurl;
?>
    <form name="all-stylesheets" method="post" action="<?php echo $stylesheets_adminurl; ?>"><div class="wrap">
        <h2>Choose Stylesheet</h2>
<?php
    if ($stylesheets_all):
?>
        <h3>Existing Stylesheets</h3>

        <table style="clear: both; margin-bottom: 1em;">
          <tr>
            <th>Name</th>
            <th>Media Type(s)</th>
            <th>Link / Import</th>
            <th>Visibility</th>
            <th>&nbsp;</th>
            <th>&nbsp;</th>
          </tr>
<?php
    $row = -1;
    foreach ($stylesheets_all as $stylesheets_current) {
        $row++;
        $alternate = ($row %2 == 0) ? '<tr class="alternate">' : '<tr>';
        $media = stylesheets_get_media_list($stylesheets_current);
        $link_import = ($stylesheets_current->import) ? 'Import' : 'Link';
        $visibility = ($stylesheets_current->visible) ? 'Visible' : 'Hidden';
        $switch_visibility = ($stylesheets_current->visible) ? 'hide' : 'show';
        $name = urlencode($stylesheets_current->name);
        echo <<<STYLESHEET_ROW
          $alternate
            <td>$stylesheets_current->name</td>
            <td>$media</td>
            <td>$link_import</td>
            <td>$visibility (<a href="$stylesheets_adminurl&stylesheet_name=$name&action=$switch_visibility">$switch_visibility</a>)</td>
            <td><a href="$stylesheets_adminurl&stylesheet_name=$name&action=edit" class="edit">Edit</a></td>
            <td><a href="$stylesheets_adminurl&stylesheet_name=$name&action=delete" onclick="return confirm('WARNING: You are about to delete this stylesheet.  You cannot undo this operation.\\n  \'Cancel\' to abort, \'OK\' to delete.');" class="delete">Delete</a></td>
          </tr>
STYLESHEET_ROW;
    }
?>
        </table>
<?php
    endif;
?>
        <h3>New Stylesheet</h3>
        <p style="float: left; width: 20%;"><input type="submit" name="submit" value="Add Stylesheet"/></p>
        <p style="float: left; width: 70%;">You can keep all your styles 
          together in one main sheet.  But if you want to be able to 
          turn certain styles on or off, or just to organize the styles
          in some more granular manner, you can maintain separate sheets.
          Remember, though, that each stylesheet is a separate server hit.
        </p>
        <p style="clear:both; margin: 0; padding: 0">&nbsp;</p>
        <input type="hidden" name="action" value="add-new"/>
      </div>
      <div class="wrap">
        <h2>Notes</h2>
        <ul>
          <li>Only stylesheets marked "visible" will be added to the 
              document.  But the others are retained in the database
              until you delete them.</li>
          <li>"Import" means that the stylesheet will be called in this 
               manner:
<pre>    &lt;style type="text/css">
        @import url(http://my-blog.com/wp-content/plugins/stylesheets.php/my-styles.css) screen, projection;
    &lt;/style></pre>
          "Link" means that they will be called this way:
<pre>   &lt;link rel="stylesheet" type="text/css" media="screen, projection" 
           href="http://my-blog.com/wp-content/plugins/stylesheets.php/my-styles.css" /></pre>
           One good reason to importing a stylesheet is to <a 
             href="http://css-discuss.incutio.com/?page=ImportHack">hide advanced
             styles</a> from older browsers.</li>
           <li>Remember the <em>cascade</em> in <b>C</b>ascading 
               <b>S</b>tyle <b>S</b>heets.  Other styles included in the
               theme might well override your styles here.  These are most
               likely to take effect if you add classes specifically for
               your new styles.  Alternately, you can mark your styles as
               <strong><code>!important</code></strong>, and it's unlikely
               they'll be overridden.</li>
         </ul>
    </div></form>
<?php
}

function stylesheets_styles() {
    global $stylesheets_mgt_page_name;
	if ($stylesheets_mgt_page_name != get_admin_page_title()) return;
?>
<style type="text/css">
.stylesheets pre {
    border: 1px solid #ccc;
    color: #009;
}
.stylesheets .wrap table th {
    text-align: left;
}
.stylesheets .wrap th, .wrap td {
    padding: 3px;
}
.stylesheets h3 {
    clear: both;
    margin-top: 1.5em;
}

.stylesheets #stylesheet-main-controls {
    width: 72%;
    float: left;
    padding: .5em;
    margin-top: 1em;
    background: #fff;
    border: 1px solid #ccc;
}
.stylesheets #name-label {
    float: left;
    width: 10%;
    text-align: right;
    padding-right: .5em;
}
.stylesheets #name-holder {
    width: 50%;
    float: left;
}
.stylesheets #stylesheet_new_name {
    width: 100%;
}
.stylesheets #css-string {
    float: left;
    width: 8%;
    padding-left: 1em;
}
.stylesheets #import-holder {
    float: left;
    width: 20%;
    text-align: right;
}
.stylesheets #styles-label {
    float: left;
    width: 10%;
    clear: both;
    text-align: right;
    padding-right: .5em;
}
.stylesheets #styles-holder {
    width: 80%;
    float: left;
    margin-top: 1em;
}
.stylesheets #stylesheet-main-controls textarea {
    width: 100%; font-family: monospace;
}
.stylesheets #stylesheet-media-types {
    width: 20%;

    float: left;
    margin-left: 3%;
    margin-top: 1em;
    padding: .5em;
    background: #fff;
    border: 1px solid #ccc;
}
.stylesheets #stylesheet-media-types ul {
    list-style-type: none;
    padding-left: .5em;;
    margin-left: 1em;
}
.stylesheets #stylesheet-media-types h3 {
    margin-top: 0;
}
.stylesheets .wrap h3 {
    border-bottom: 1px dotted #aaa;
}
.stylesheets .wrap table {
    width: 100%;
}
.stylesheets .wrap th, .wrap td {
    padding: 3px;
}

</style>
<?php  
}

function stylesheets_debug() {
    global $stylesheets_all, $stylesheets_current, $stylesheets_index;
    if (STYLESHEETS_DEBUG) {
        echo "<div class='wrap'>";
        echo "<h2>Debug Info</h2>";

        echo "<h3>$" . "_REQUEST:</h3><pre>";
        print_r($_REQUEST);
        echo "</pre>";

        echo "<h3>$" . "stylesheets_all:</h3><pre>";
        print_r($stylesheets_all);
        echo "</pre>";

        echo "<h3>$" . "stylesheets_index: $stylesheets_index</h3>";

        echo "<h3>$" . "stylesheet:</h3><pre>";
        print_r($stylesheets_current);
        echo "</pre>";

        echo "<h3>$" . "_SERVER:</h3><pre>";
        print_r($_SERVER);
        echo "</pre>";

        echo "</div>";
    }
}
?>