<?php

  class ctrl_currency {
    public $data;

    public function __construct($currency_code=null) {

      if ($currency_code !== null) {
        $this->load($currency_code);
      } else {
        $this->reset();
      }
    }

    public function reset() {

      $this->data = array();

      $fields_query = database::query(
        "show fields from ". DB_TABLE_CURRENCIES .";"
      );
      while ($field = database::fetch($fields_query)) {
        $this->data[$field['Field']] = null;
      }
    }

    public function load($currency_code) {

      $this->reset();

      if (!preg_match('#[A-Z]{3}#', $currency_code)) throw new Exception('Invalid currency code ('. $currency_code .')');

      $currency_query = database::query(
        "select * from ". DB_TABLE_CURRENCIES ."
        where code='". database::input($currency_code) ."'
        limit 1;"
      );

      if ($currency = database::fetch($currency_query)) {
        $this->data = array_replace($this->data, array_intersect_key($currency, $this->data));
      } else {
        throw new Exception('Could not find currency (Code: '. htmlspecialchars($currency_code) .') in database.');
      }
    }

    public function save() {

      if (empty($this->data['status']) && $this->data['code'] == settings::get('store_currency_code')) {
        throw new Exception('You cannot disable the store currency.');
        return;
      }

      if (empty($this->data['status']) && $this->data['code'] == settings::get('default_currency_code')) {
        throw new Exception('You cannot disable the default currency.');
        return;
      }

      if (!empty($this->data['id'])) {
        $currencies_query = database::query(
          "select * from ". DB_TABLE_CURRENCIES ."
          where id = ". (int)$this->data['id'] ."
          limit 1;"
        );
        $currency = database::fetch($currencies_query);

        if ($this->data['code'] != $currency['code']) {
          if ($currency['code'] == settings::get('store_currency_code')) {
            throw new Exception('Cannot rename the store currency.');
          } else {
            database::query(
              "alter table ". DB_TABLE_PRODUCTS_PRICES ."
              change `". database::input($currency['code']) ."` `". database::input($this->data['code']) ."` decimal(11, 4) not null;"
            );
            database::query(
              "alter table ". DB_TABLE_PRODUCTS_CAMPAIGNS ."
              change `". database::input($currency['code']) ."` `". database::input($this->data['code']) ."` decimal(11, 4) not null;"
            );
            database::query(
              "alter table ". DB_TABLE_PRODUCTS_OPTIONS ."
              change `". database::input($currency['code']) ."` `". database::input($this->data['code']) ."` decimal(11, 4) not null;"
            );
          }
        }
      }

      if (empty($this->data['id'])) {
        database::query(
          "insert into ". DB_TABLE_CURRENCIES ."
          (date_created)
          values ('". ($this->data['date_created'] = date('Y-m-d H:i:s')) ."');"
        );
        $this->data['id'] = database::insert_id();
      }

      $products_prices_query = database::query(
        "show fields from ". DB_TABLE_PRODUCTS_PRICES ."
        where `Field` = '". database::input($this->data['code']) ."';"
      );
      if (database::num_rows($products_prices_query) == 0) {
        database::query(
          "alter table ". DB_TABLE_PRODUCTS_PRICES ."
          add `". database::input($this->data['code']) ."` decimal(11, 4) not null;"
        );
      }

      $products_campaigns_query = database::query(
        "show fields from ". DB_TABLE_PRODUCTS_CAMPAIGNS ."
        where `Field` = '". database::input($this->data['code']) ."';"
      );
      if (database::num_rows($products_campaigns_query) == 0) {
        database::query(
          "alter table ". DB_TABLE_PRODUCTS_CAMPAIGNS ."
          add `". database::input($this->data['code']) ."` decimal(11, 4) not null;"
        );
      }

      $products_options_query = database::query(
        "show fields from ". DB_TABLE_PRODUCTS_OPTIONS ."
        where `Field` = '". database::input($this->data['code']) ."';"
      );
      if (database::num_rows($products_options_query) == 0) {
        database::query(
          "alter table ". DB_TABLE_PRODUCTS_OPTIONS ."
          add `". database::input($this->data['code']) ."` decimal(11, 4) not null after `price_operator`;"
        );
      }

      database::query(
        "update ". DB_TABLE_CURRENCIES ."
        set
          status = ". (int)$this->data['status'] .",
          code = '". database::input($this->data['code']) ."',
          number = '". database::input($this->data['number']) ."',
          name = '". database::input($this->data['name']) ."',
          value = '". database::input($this->data['value']) ."',
          prefix = '". database::input($this->data['prefix'], false, false) ."',
          suffix = '". database::input($this->data['suffix'], false, false) ."',
          decimals = ". (int)$this->data['decimals'] .",
          priority = ". (int)$this->data['priority'] .",
          date_updated = '". ($this->data['date_updated'] = date('Y-m-d H:i:s')) ."'
        where id = ". (int)$this->data['id'] ."
        limit 1;"
      );

      cache::clear_cache('currencies');
    }

    public function delete() {

      if ($this->data['code'] == settings::get('store_currency_code')) {
        throw new Exception('Cannot delete the store currency');
        return;
      }

      if ($this->data['code'] == settings::get('default_currency_code')) {
        throw new Exception('Cannot delete the default currency');
        return;
      }

      database::query(
        "delete from ". DB_TABLE_CURRENCIES ."
        where id = ". (int)$this->data['id'] ."
        limit 1;"
      );

      database::query(
        "alter table ". DB_TABLE_PRODUCTS_PRICES ." drop `". database::input($this->data['code']) ."`;"
      );

      database::query(
        "alter table ". DB_TABLE_PRODUCTS_CAMPAIGNS ." drop `". database::input($this->data['code']) ."`;"
      );

      database::query(
        "alter table ". DB_TABLE_PRODUCTS_OPTIONS ." drop `". database::input($this->data['code']) ."`;"
      );

      cache::clear_cache('currencies');

      $this->data['id'] = null;
    }
  }
