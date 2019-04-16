<?php

  return $app_config = array(
    'name' => language::translate('title_countries', 'Countries'),
    'default' => 'countries',
    'priority' => 0,
    'theme' => array(
      'color' => '#43bbe7',
      'icon' => 'fa-flag',
    ),
    'menu' => array(),
    'docs' => array(
      'countries' => 'countries.inc.php',
      'edit_country' => 'edit_country.inc.php',
    ),
  );
