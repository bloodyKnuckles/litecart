<?php

// Delete old files
  $deleted_files = [
    FS_DIR_ADMIN . '.htaccess',
    FS_DIR_ADMIN . '.htpasswd',
  ];

  foreach ($deleted_files as $pattern) {
    if (!file_delete($pattern)) {
      echo '<span class="error">[Skipped]</span></p>';
    }
  }

// Modify some files
  $modified_files = [
    [
      'file'    => FS_DIR_APP . 'includes/config.inc.php',
      'search'  =>   "~## Backwards Compatible Directory Definitions \(LiteCart <2\.2\)  #######\R+"
                   . "######################################################################\R+"
                   . ".*?"
                   . "######################################################################\R+~s",
      'replace' => "",
      'regex' => true,
    ],
    [
      'file'    => FS_DIR_APP . 'includes/config.inc.php',
      'search'  => "~  define\('DB_PERSISTENT_CONNECTIONS', '[^']+'\);(\n\|\r\n?)?~",
      'replace' => "",
      'regex' => true,
    ],
    [
      'file'    => FS_DIR_APP . 'includes/config.inc.php',
      'search'  => "~// Password Encryption Salt\R+"
                 . "  define('PASSWORD_SALT', '[^']+');\R+~s",
      'replace' => "",
      'regex' => true,
    ],
  ];

  foreach ($modified_files as $modification) {

      echo 'Modify '. $file . '<br />' . PHP_EOL;

      $contents = file_get_contents($file);
      $contents = preg_replace('#\R#u', PHP_EOL, $contents);
      $contents = preg_replace($search, $replace, $contents);
      $result = file_put_contents($file, $contents);

    if (!file_modify($modification['file'], $modification['search'], $modification['replace'], !empty($modification['regex']))) {
      die('<span class="error">[Error]</span><br />Could not find: '. $modification['search'] .'</p>');
    }
  }