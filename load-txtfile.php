<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           Load_Txtfile
 *
 * @wordpress-plugin
 * Plugin Name:       WP Custom Plugin Load Textfile
 * Plugin URI:        http://example.com/load-txtfile-uri/
 * Description:       This plugin provides a simple user interface within the Wordpress Admin for loading the provided text file.
 * Version:           1.0.0
 * Author:            Wencheng Li
 * Author URI:        http://example.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       load-txtfile
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'LOAD_TXTFILE_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-load-txtfile-activator.php
 */
function activate_load_txtfile() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-load-txtfile-activator.php';
	Load_Txtfile_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-load-txtfile-deactivator.php
 */
function deactivate_load_txtfile() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-load-txtfile-deactivator.php';
	Load_Txtfile_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_load_txtfile' );
register_deactivation_hook( __FILE__, 'deactivate_load_txtfile' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-load-txtfile.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_load_txtfile() {

	$plugin = new Load_Txtfile();
	$plugin->run();

}
run_load_txtfile();


/**
 * *************************************************************************************
 * My Custom Codes for Plugin
 * *************************************************************************************
 */

/**
 * create plugin db table on activation
 */
function create_plugin_database_table()
{
    global $wpdb;
    $tblname = $wpdb->prefix . "loadtxtfile";
	$charset_collate = $wpdb->get_charset_collate();

	# Check to see if the table exists already, if not, then create it
    if ($wpdb->get_var("SHOW TABLES LIKE '$tblname'") != $tblname)
    {
        $sql = "CREATE TABLE $tblname (
        		id mediumint(9) NOT NULL AUTO_INCREMENT,
				loadtime datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				txtline varchar(200) DEFAULT '' NOT NULL,
				wordnum  int(11) NOT NULL,
				PRIMARY KEY (id)
				) $charset_collate;";
        require_once(ABSPATH . "/wp-admin/includes/upgrade.php");
        dbDelta($sql);
    }
}

register_activation_hook(__FILE__, "create_plugin_database_table");


/**
 * include WP_List_Table class in plugin
 */
if (!class_exists('WP_List_Table'))
{
	require_once(ABSPATH . "wp-admin/includes/class-wp-list-table.php");
}

class Txtfiles_List extends WP_List_Table
{
	public function __construct()
	{
		parent::__construct([
			"singular" => __("Txtfile", "sp"), # singular name of the listed records
			"plural"   => __("Txtfiles", "sp"), # plural name of the listed records
			"ajax"     => false # should this table support ajax?
		]);
	}

	public static function get_txtfiles($per_page = 5, $page_number = 1)
	{
		global $wpdb;

		$sql = "SELECT * FROM {$wpdb->prefix}loadtxtfile";
		if (!empty($_REQUEST["orderby"]))
		{
			$sql .= " ORDER BY " . esc_sql($_REQUEST["orderby"]);
			$sql .= !empty($_REQUEST["order"]) ? " " . esc_sql($_REQUEST["order"]) : "ASC";
		}
		$sql .= " LIMIT $per_page";
		$sql .= " OFFSET " . ($page_number - 1) * $per_page;

		$result = $wpdb->get_results($sql, "ARRAY_A");

		return $result;
	}

	public static function delete_txtfile($id)
	{
		global $wpdb;

		$wpdb->delete(
			"{$wpdb->prefix}loadtxtfile",
			[ "ID" => $id ],
			[ "%d" ]
		);
	}

	public static function record_count()
	{
		global $wpdb;

		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}loadtxtfile";

		return $wpdb->get_var($sql);
	}

	public function no_items()
	{
		_e("No txtfiles avaliable.", "sp");
	}

	function column_name($item)
	{
		// create a nonce
		$delete_nonce = wp_create_nonce("sp_delete_txtfile");

		$title = "<strong>" . $item["name"] . "</strong>";

		$actions = [
			"delete" => sprintf(
				"<a href='?page=%s&action=%s&txtfile=%s&_wpnonce=%s'>Delete</a>",
				esc_attr($_REQUEST["page"]),
				"delete",
				absint($item["ID"]),
				$delete_nonce
			)
		];

		return $title . $this->row_actions($actions);
	}

	public function column_default($item, $column_name)
	{
		switch ($column_name)
		{
			case "loadtime":
				return $item[$column_name];
			case "txtline":
				return $item[$column_name];
			case "wordnum":
				return $item[$column_name];
			default:
				return print_r($item, true); # Show the whole array for troubleshooting purpose
		}
	}

	function column_cb($item)
	{
		return sprintf(
			"<input type='checkbox' name='bulk-delete[]' value='%s' />",
			$item["ID"]
		);
	}

	function get_columns()
	{
		$columns = [
			"cb"		=> "<input type='checkbox' />",
			"loadtime"	=> __("Loadtime", "sp"),
			"txtline"	=> __("Txtline", "sp"),
			"wordnum"	=> __("Wordnum", "sp")
		];

		return $columns;
	}

	public function get_sortable_columns()
	{
		$sortable_columns = array(
			"loadtime"	=> array("loadtime", true),
			"txtline"	=> array("txtline", true),
			"wordnum"	=> array("wordnum", false)
		);
		# not working properly
		return $sortable_columns;
	}

	public function get_bulk_actions()
	{
		$actions = [
			"bulk-delete" => "Delete"
		];

		return $actions;
	}

	public function prepare_items()
	{
		$this->_column_headers = $this->get_column_info();

		# Process bulk action
		$this->process_bulk_action();

		$per_page     = $this->get_items_per_page("txtfiles_per_page", 5);
		$current_page = $this->get_pagenum();
		$total_items  = self::record_count();

		$this->set_pagination_args([
			"total_items" => $total_items,
			"per_page"    => $per_page
		]);

		$this->items = self::get_txtfiles($per_page, $current_page);
	}

	public function process_bulk_action()
	{
		# Detect when a bulk action is being triggered
		if ("delete" == $this->current_action())
		{
			# In our file that handles the request, verify the nonce
			$nonce = esc_attr($_REQUEST["_wpnonce"]);

			if (!wp_verify_nonce($nonce, "sp_delete_txtfile"))
			{
				die("Go get a life script kiddies");
			}
			else
			{
				self::delete_txtfile(absint($_GET["txtfile"]));

				wp_redirect(esc_url(add_query_arg()));
				exit;
			}
		}

		# If the delete bulk action is triggered
		if (
			(isset($_POST["action"]) && $_POST["action"] == "bulk-delete" ) ||
			(isset($_POST["action2"]) && $_POST["action2"] == "bulk-delete")
		)
		{
			$delete_ids = esc_sql($_POST["bulk-delete"]);

			# loop over the array of record IDs and delete them
			foreach ($delete_ids as $id)
			{
				self::delete_txtfile($id);
			}

			wp_redirect(esc_url(add_query_arg()));
			exit;
		}
	}

}


/**
 * building the page
 */
class LoadTxtfile_Plugin
{
	static $instance;

	# txtfile WP_List_Table object
	public $txtfiles_obj;

	public function __construct()
	{
		add_filter("set-screen-option", [__CLASS__, "set_screen"], 10, 3);
		add_action("admin_menu", [$this, "plugin_menu"]);
	}

	public static function set_screen($status, $option, $value)
	{
		return $value;
	}

	public function plugin_menu()
	{
		$hook = add_menu_page(
			"My Plugin Page",
			"Load-Text-File Plugin",
			"manage_options",
			"my-plugin",
			[$this, "plugin_txtfiles_page"]
		);

		add_action("load-$hook", [$this, "screen_option"]);
	}

	public function screen_option()
	{
		$option = "per_page";
		$args   = [
			"label"   => "Txtlines",
			"default" => 5,
			"option"  => "txtfiles_per_page"
		];

		add_screen_option($option, $args);

		$this->txtfiles_obj = new Txtfiles_List();
	}

	public function plugin_txtfiles_page()
	{
		$this->handle_text_load();
	?>

		<h1>Hello PlayersVoice!</h1>
		<div class="wrap">
			<h2>My WP Plugin for Loading Txtfile</h2>
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="post-body-content">
						<div class="meta-box-sortables ui-sortable">
							<form method="post">
								<?php
									$this->txtfiles_obj->prepare_items();
									$this->txtfiles_obj->display();
								?>
							</form>
						</div>
					</div>
				</div>
				<br class="clear">
			</div>
			<!--  -->
			<h2>Load a text file</h2>
			<form method="post" enctype="multipart/form-data">
				<input type="file" id="btn_load_txtfile" name="btn_load_txtfile">
				<?php
					submit_button("Load Text");
				?>
			</form>
		</div>

	<?php
	}

	public function handle_text_load() {
		if (isset($_FILES["btn_load_txtfile"]))
		{
			$file_handle = $_FILES["btn_load_txtfile"]["tmp_name"];
			if ($file_handle)
			{
				try {
					# read text file as array
					$my_txt = file($file_handle);
	
					# save to DB
					$this->save_db($my_txt);
				} catch (\Throwable $ex) {
					echo $ex->getMessage();
				}
			}
			else
			{
				echo "<script>alert('Please choose .txt file')</script>";
				return;
			}
		}
	}

	public function save_db($data) {
		global $wpdb;
		$tblname = $wpdb->prefix . "loadtxtfile";
	
		if (!empty($data))
		{
			try {
				foreach ($data as $line)
				{
					if (str_word_count($line) != 0)
					{
						$wpdb->insert(
							$tblname,
							array(
								"loadtime" => current_time("mysql"), # timestamp
								"txtline" => mb_strimwidth($line, 0, 200, "..."), # sentences aren't longer than 200 characters in length
								"wordnum" => str_word_count($line) # number of words
							)
						);
					}
				}
			} catch (\Throwable $ex) {
				echo $ex->getMessage();
			}
		}
	}

	public static function get_instance()
	{
		if (!isset(self::$instance))
		{
			self::$instance = new self();
		}

		return self::$instance;
	}

}

add_action("plugins_loaded", function() {

	LoadTxtfile_Plugin::get_instance();

});

