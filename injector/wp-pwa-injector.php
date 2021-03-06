<?php

// Copy on header.php, just after <head> the following code:
// if (isset($GLOBALS['wp_pwa_path'])) { require(WP_PLUGIN_DIR . $GLOBALS['wp_pwa_path'] .'/injector/wp-pwa-injector.php'); }

$siteId = null;
$listType = null;
$listId = null;
$page = null;
$singleType = null;
$singleId = null;
$env = 'prod';
$perPage = get_option('posts_per_page');
$ssr = 'https://ssr.wp-pwa.com';
$static = 'https://static.wp-pwa.com';
$inject = false;
$pwa = false;
$exclusion = false;
$dev = 'false';
$break = false;
$prettyPermalinks = get_option('permalink_structure') !== '';
$url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER[HTTP_HOST]
  . $_SERVER[REQUEST_URI];
$initialUrl = $prettyPermalinks ? strtok($url, '?') : $url;
$settings = get_option('wp_pwa_settings');

$pwaStatus = $settings['wp_pwa_status'];
$forceFrontpage = $settings['wp_pwa_force_frontpage'];
$excludes = $settings['wp_pwa_excludes'];

if (($forceFrontpage === true && is_front_page()) || is_home()) {
  $listType = 'latest';
  $listId = 'post';
} elseif (is_page() || is_single()) {
  if (get_queried_object()->post_type !== 'attachment') {
    $singleType = get_queried_object()->post_type;
    $singleId = get_queried_object()->ID;
  }
} elseif (is_post_type_archive()) {
  $queriedObject = get_queried_object();
  if ((isset($queriedObject->show_in_rest)) && (isset($queriedObject->rest_base)) &&
  ($queriedObject->show_in_rest === true)) {
    $listType = 'latest';
    $listId = $queriedObject->rest_base;
  }
} elseif (is_tax()) {
  $listType = get_queried_object()->taxonomy;
  $listId = get_queried_object()->term_id;
} elseif (is_category()) {
  $listType = 'category';
  $listId = get_queried_object()->term_id;
} elseif (is_tag()) {
  $listType = 'tag';
  $listId = get_queried_object()->term_id;
} elseif (is_author()) {
  $listType = 'author';
  $listId = get_queried_object()->ID;
}

if (is_paged()) {
  $page = get_query_var('paged');
} elseif (is_home() || is_category() || is_tag() || is_author() || is_search() || is_date() ||
  is_tax() || is_post_type_archive()) {
  $page = 1;
}

if (isset($_GET['siteId'])) {
  $siteId = $_GET['siteId'];
} elseif (isset($settings['wp_pwa_siteid']) && $settings['wp_pwa_siteid'] !== '' ) {
  $siteId = $settings['wp_pwa_siteid'];
}

if (isset($_GET['env']) && ($_GET['env'] === 'pre' || $_GET['env'] === 'prod')) {
  $env = $_GET['env'];
} elseif (isset($settings['wp_pwa_env'])) {
  $env = $settings['wp_pwa_env'];
}

if (isset($_GET['ssrUrl'])) {
  $ssr = $_GET['ssrUrl'];
} elseif (isset($_GET['server'])) {
  $ssr = $_GET['server'];
} elseif (isset($settings['wp_pwa_ssr'])) {
  $ssr = $settings['wp_pwa_ssr'];
}
if (isset($_GET['staticUrl'])) {
  $static = $_GET['staticUrl'];
} elseif (isset($_GET['server'])) {
  $static = $_GET['server'];
} elseif (isset($settings['wp_pwa_static'])) {
  $static = $settings['wp_pwa_static'];
}

if (isset($_GET['pwa']) && $_GET['pwa'] === 'true' ){
  $pwa = true;
}

if (isset($_GET['pwa']) || isset($_GET['server']) || isset($_GET['staticUrl']) ||
  isset($_GET['ssrUrl']) || isset($_GET['env']) || isset($_GET['siteId'])) {
    $dev = 'true';
  }
if (isset($_GET['dev'])) {
  $dev = $_GET['dev'];
}
if (isset($_GET['break']) && ($_GET['break'] === 'true')) {
  $break = true;
}

if (sizeof($excludes) !== 0 && $pwa === false) {
  foreach ($excludes as $regex) {
    $output = array();
    $regex = str_replace('/', '\/', $regex);
    preg_match('/' . $regex . '/', $url, $output);
    if (sizeof($output) > 0) {
      $exclusion = true;
    }
  }
}

if ($siteId && ($listType || $singleType)) {
  if ($pwa || ($pwaStatus === 'mobile' && $exclusion === false)) {
    $inject = true;
  }
}
?>



<?php if ($inject) { ?>
  <script type='text/javascript'>window['wp-pwa'] = { siteId: '<?php echo $siteId; ?>',<?php if ($listType) echo ' listType: \'' . $listType . '\',' ?><?php if ($listId) echo ' listId: \'' . $listId . '\',' ?><?php if ($singleType) echo ' singleType: \'' . $singleType . '\',' ?><?php if ($singleId) echo ' singleId: \'' . $singleId . '\',' ?><?php if ($page) echo ' page: \'' . $page . '\',' ?> env: '<?php echo $env; ?>', dev: <?php echo $dev; ?>, perPage: '<?php echo $perPage; ?>', ssr: '<?php echo $ssr; ?>', initialUrl: '<?php echo $initialUrl; ?>', static: '<?php echo $static; ?>'<?php if ($break) echo ', break: true' ?><?php if (sizeof($excludes) !== 0) echo ', excludes: ["' . str_replace('\\\\', '\\', implode('", "', $excludes)) . '"]' ?> };
  <?php if ($break) {
    echo 'debugger;';
    require(WP_PLUGIN_DIR . $GLOBALS['wp_pwa_path'] . '/injector/injector.js');
  } else {
    require(WP_PLUGIN_DIR . $GLOBALS['wp_pwa_path'] . '/injector/injector.min.js');
  } ?></script>
<?php } ?>
