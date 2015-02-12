<?php
/**
 * Created by PhpStorm.
 * User: Baggett
 * Date: 12/02/2015
 * Time: 14:22
 */

class ExpiresTest extends PHPUnit_Framework_TestCase {

  // First, we define our setUp and tearDown procedures.
  // Both setUp and tearDown will be called for EVERY test.
  public function setUp(){
    $rand = rand(1000, 9999);
    // Okay, first job is to mock up some test data.
    for($i = 0; $i <= 20; $i++) {
      $rand++;

      // Generate a created date 2 weeks + up to 20 days ago)
      $created = strtotime("2 weeks ago") - (rand(0,20) * 86400);
      // And expire it 60 minutes later.
      $expire = $created + (60 * 60);

      // Insert the records using the drupal functions (eww, ActiveRecord ftw)
      db_insert("cache_simplecache")
        ->fields(array(
          "cache_key" => "PHPUnit_{$rand}",
          "cache_value" => $rand,
          "cache_created" => date("Y-m-d H:i:s", $created),
          "cache_expires" => date("Y-m-d H:i:s", $expire),
          "created" => $created,
          "expire" => $expire
        ))
        ->execute();
    }
  }

  public function tearDown(){
    // Remove any reference to PHPUnit incase its leaked through. Just cleaning up.
    db_delete('cache_simplecache')
      ->condition('cache_key', 'PHPUnit_%', 'LIKE')
      ->execute();
  }

  public function testSimpleCacheCanClean(){
    $before = db_select("cache_simplecache",'c_sc')
      ->fields('c_sc')
      ->condition('c_sc.cache_expires', date("Y-m-d H:i:s"), "<")
      ->execute()->rowCount();

    simple_cache_clean();

    $after = db_select("cache_simplecache",'c_sc')
      ->fields('c_sc')
      ->condition('c_sc.cache_expires', date("Y-m-d H:i:s"), "<")
      ->execute()->rowCount();

    $this->assertGreaterThanOrEqual(20, $before, "Before we clear, we found 20 (or more) caches that should be expired.");
    $this->assertGreaterThanOrEqual(0,  $after ,  "After we clear, we found 0 caches that should be expired.");
  }
}
