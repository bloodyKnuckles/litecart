<?php
  
  define('PLATFORM_NAME', 'LiteCart');
  define('PLATFORM_VERSION', '1.0.1.1');
  
// Start redirecting output to the output buffer
  ob_start();
  
// Get config
  if (!file_exists(realpath(dirname(__FILE__)) . '/config.inc.php')) {
    header('Location: ./install/');
    exit;
  }
  require_once(realpath(dirname(__FILE__)) . '/config.inc.php');
  
// Compatibility
  require_once(FS_DIR_HTTP_ROOT . WS_DIR_INCLUDES . 'compatibility.inc.php');
  
// Autoloader
  function __autoload($name) {
    switch($name) {
      case (substr($name, 0, 5) == 'ctrl_'):
        require_once FS_DIR_HTTP_ROOT . WS_DIR_CONTROLLERS . $name . '.inc.php';
        break;
      case (substr($name, 0, 3) == 'cm_'):
        require_once FS_DIR_HTTP_ROOT . WS_DIR_MODULES . 'customer/' . $name . '.inc.php';
        break;
      case (substr($name, 0, 4) == 'job_'):
        require_once FS_DIR_HTTP_ROOT . WS_DIR_MODULES . 'jobs/' . $name . '.inc.php';
        break;
      case (substr($name, 0, 4) == 'lib_'):
        require_once FS_DIR_HTTP_ROOT . WS_DIR_LIBRARY . $name . '.inc.php';
        break;
      case (substr($name, 0, 3) == 'oa_'):
        require_once FS_DIR_HTTP_ROOT . WS_DIR_MODULES . 'order_action/' . $name . '.inc.php';
        break;
      case (substr($name, 0, 3) == 'ot_'):
        require_once FS_DIR_HTTP_ROOT . WS_DIR_MODULES . 'order_total/' . $name . '.inc.php';
        break;
      case (substr($name, 0, 3) == 'os_'):
        require_once FS_DIR_HTTP_ROOT . WS_DIR_MODULES . 'order_success/' . $name . '.inc.php';
        break;
      case (substr($name, 0, 3) == 'pm_'):
        require_once FS_DIR_HTTP_ROOT . WS_DIR_MODULES . 'payment/' . $name . '.inc.php';
        break;
      case (substr($name, 0, 4) == 'ref_'):
        require_once FS_DIR_HTTP_ROOT . WS_DIR_REFERENCES . $name . '.inc.php';
        break;
      case (substr($name, 0, 3) == 'sm_'):
        require_once FS_DIR_HTTP_ROOT . WS_DIR_MODULES . 'shipping/' . $name . '.inc.php';
        break;
      case (substr($name, 0, 4) == 'url_'):
        require_once FS_DIR_HTTP_ROOT . WS_DIR_MODULES . 'seo_links/' . $name . '.inc.php';
        break;
      default:
        require_once FS_DIR_HTTP_ROOT . WS_DIR_CLASSES . $name . '.inc.php';
        break;
    }
  }
  
// Set error handler
  function error_handler($errno, $errstr, $errfile, $errline, $errcontext) {
    if (!(error_reporting() & $errno)) return;
    $errfile = preg_replace('#^'. FS_DIR_HTTP_ROOT . WS_DIR_HTTP_HOME .'#', '~/', str_replace('\\', '/', $errfile));
    
    switch($errno) {
      case E_WARNING:
      case E_USER_WARNING:
        $output = "<b>Warning:</b> $errstr in <b>$errfile</b> on line <b>$errline</b>";
        break;
      case E_STRICT:
      case E_NOTICE:
      case E_USER_NOTICE:
        $output = "<b>Notice:</b> $errstr in <b>$errfile</b> on line <b>$errline</b>";
        break;
      case E_DEPRECATED:
      case E_USER_DEPRECATED:
        $output = "<b>Deprecated:</b> $errstr in <b>$errfile</b> on line <b>$errline</b>";
        break;
      default:
        $output = "<b>Fatal error:</b> $errstr in <b>$errfile</b> on line <b>$errline</b>";
        $fatal = true;
        break;
    }
    
    /*
    $backtraces = debug_backtrace();
    $backtraces = array_slice($backtraces, 2);
    
    if (!empty($backtraces)) {
      foreach ($backtraces as $backtrace) {
        if (empty($backtrace['file'])) continue;
        $backtrace['file'] = preg_replace('#^'. FS_DIR_HTTP_ROOT . WS_DIR_HTTP_HOME .'#', '~/', str_replace('\\', '/', $backtrace['file']));
        $output .= "<br />" . PHP_EOL . "  <- <b>{$backtrace['file']}</b> on line <b>{$backtrace['line']}</b> in <b>{$backtrace['function']}()</b>";
      }
    }
    */
    
    if (in_array(strtolower(ini_get('display_errors')), array('on', 'true', '1'))) {
      if (in_array(strtolower(ini_get('html_errors')), array(0, 'off', 'false')) || PHP_SAPI == 'cli') {
        echo strip_tags($output) . PHP_EOL;
      } else {
        echo $output . '<br />' . PHP_EOL;
      }
    } else {
      if (!empty($_SERVER['REQUEST_URI'])) $output .= " {$_SERVER['REQUEST_URI']}";
    }
    
    if (ini_get('log_errors')) {
      error_log(strip_tags($output));
    }
    
    if (in_array($errno, array(E_ERROR, E_USER_ERROR))) exit;
  }
  
  set_error_handler('error_handler');
  
// Set up the system object 
  $system = new system();
  
// Load dependencies
  $system->run('load_dependencies');
  
// Initiate system modules
  $system->run('initiate');
  
// Run start operations
  $system->run('startup');
  
// Run operations before capture
  $system->run('before_capture');
  
// If page should be overriden
  $override_file = FS_DIR_HTTP_ROOT . WS_DIR_TEMPLATES . $system->document->template .'/overrides/'. $system->link->relpath($system->link->get_base_link());
  if (file_exists($override_file)) {
    require_once($override_file);
    exit;
  }
  
?>