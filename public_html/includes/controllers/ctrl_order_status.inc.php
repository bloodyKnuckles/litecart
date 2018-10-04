<?php

  class ctrl_order_status {
    public $data;

    public function __construct($order_status_id=null) {

      if ($order_status_id !== null) {
        $this->load((int)$order_status_id);
      } else {
        $this->reset();
      }
    }

    public function reset() {

      $this->data = array();

      $fields_query = database::query(
        "show fields from ". DB_TABLE_ORDER_STATUSES .";"
      );
      while ($field = database::fetch($fields_query)) {
        $this->data[$field['Field']] = null;
      }

      $info_fields_query = database::query(
        "show fields from ". DB_TABLE_ORDER_STATUSES_INFO .";"
      );

      while ($field = database::fetch($info_fields_query)) {
        if (in_array($field['Field'], array('id', 'order_status_id', 'language_code'))) continue;

        $this->data[$field['Field']] = array();
        foreach (array_keys(language::$languages) as $language_code) {
          $this->data[$field['Field']][$language_code] = null;
        }
      }
    }

    public function load($order_status_id) {

      $this->reset();

      $order_status_query = database::query(
        "select * from ". DB_TABLE_ORDER_STATUSES ."
        where id = ". (int)$order_status_id ."
        limit 1;"
      );

      if ($order_status = database::fetch($order_status_query)) {
        $this->data = array_replace($this->data, array_intersect_key($order_status, $this->data));
      } else {
        trigger_error('Could not find order_status (ID: '. (int)$order_status_id .') in database.', E_USER_ERROR);
      }

      $order_status_info_query = database::query(
        "select * from ". DB_TABLE_ORDER_STATUSES_INFO ."
        where order_status_id = ". (int)$this->data['id'] .";"
      );

      while ($order_status_info = database::fetch($order_status_info_query)) {
        foreach ($order_status_info as $key => $value) {
          if (in_array($key, array('id', 'order_status_id', 'language_code'))) continue;
          $this->data[$key][$order_status_info['language_code']] = $value;
        }
      }
    }

    public function save() {

      if (empty($this->data['id'])) {
        database::query(
          "insert into ". DB_TABLE_ORDER_STATUSES ."
          (date_created)
          values ('". date('Y-m-d H:i:s') ."');"
        );
        $this->data['id'] = database::insert_id();
      }

      $this->data['keywords'] = explode(',', $this->data['keywords']);
      $this->data['keywords'] = array_map('trim', $this->data['keywords']);
      $this->data['keywords'] = array_unique($this->data['keywords']);
      $this->data['keywords'] = implode(',', $this->data['keywords']);

      database::query(
        "update ". DB_TABLE_ORDER_STATUSES ."
        set
          icon = '". database::input($this->data['icon']) ."',
          color = '". database::input($this->data['color']) ."',
          keywords = '". database::input($this->data['keywords']) ."',
          is_sale = '". (empty($this->data['is_sale']) ? '0' : '1') ."',
          is_archived = '". (empty($this->data['is_archived']) ? '0' : '1') ."',
          notify = '". (empty($this->data['notify']) ? '0' : '1') ."',
          priority = ". (int)$this->data['priority'] .",
          date_updated = '". date('Y-m-d H:i:s') ."'
        where id = ". (int)$this->data['id'] ."
        limit 1;"
      );

      foreach (array_keys(language::$languages) as $language_code) {

        $order_status_info_query = database::query(
          "select * from ". DB_TABLE_ORDER_STATUSES_INFO ."
          where order_status_id = ". (int)$this->data['id'] ."
          and language_code = '". $language_code ."'
          limit 1;"
        );
        $order_status_info = database::fetch($order_status_info_query);

        if (empty($order_status_info['id'])) {
          database::query(
            "insert into ". DB_TABLE_ORDER_STATUSES_INFO ."
            (order_status_id, language_code)
            values (". (int)$this->data['id'] .", '". $language_code ."');"
          );
          $order_status_info['id'] = database::insert_id();
        }

        database::query(
          "update ". DB_TABLE_ORDER_STATUSES_INFO ."
          set
            name = '". database::input($this->data['name'][$language_code]) ."',
            description = '". database::input($this->data['description'][$language_code]) ."',
            email_subject = '". database::input($this->data['email_subject'][$language_code], true) ."',
            email_message = '". database::input($this->data['email_message'][$language_code], true) ."'
          where id = ". (int)$order_status_info['id'] ."
          and order_status_id = ". (int)$this->data['id'] ."
          and language_code = '". $language_code ."'
          limit 1;"
        );
      }

      cache::clear_cache('order_statuses');
    }

    public function delete() {

      if (database::num_rows(database::query("select id from ". DB_TABLE_ORDERS ." where order_status_id = ". (int)$this->data['id'] ." limit 1;"))) {
        trigger_error('Cannot delete the order status because there are orders using it', E_USER_ERROR);
        return;
      }

      database::query(
        "delete from ". DB_TABLE_ORDER_STATUSES_INFO ."
        where order_status_id = ". (int)$this->data['id'] .";"
      );

      database::query(
        "delete from ". DB_TABLE_ORDER_STATUSES ."
        where id = ". (int)$this->data['id'] ."
        limit 1;"
      );

      cache::clear_cache('order_statuses');

      $this->data['id'] = null;
    }
  }
