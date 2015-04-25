<?php
/*
Plugin Name: EdwinFrontPage
Plugin URI: https://gist.github.com/ideag/f97d513e84b523bd2dac
Description: Enable CPT posts to be set as front page. Usage: EdwinFrontPage::init( 'your_custom_post_type' ); Should be run on 'init' hook.
Author: Arūnas Liuiza
Version: 0.1.0
Author URI: http://arunas.co/
*/

// Enable CPT posts to be set as front page.
// Usage: EdwinFrontPage::init( 'your_custom_post_type' );
// Should be run on 'init' hook.
class EdwinFrontPage {
  private static $post_type = '';
  public static function init( $post_type ) {
    self::$post_type = $post_type;
    add_filter( 'get_pages',          array( 'EdwinFrontPage', 'dropdown'   ), 10, 2 );
    add_action( 'pre_get_posts',      array( 'EdwinFrontPage', 'query_vars' ) );
    add_filter( 'frontpage_template', array( 'EdwinFrontPage', 'template'   ) );
    add_action( 'template_redirect',  array( 'EdwinFrontPage', 'redirect'   ) );
    add_filter( 'post_type_link',     array( 'EdwinFrontPage', 'permalink'  ), 10, 4 );
  }
  // Filter posts the dropdown in WP Admin > Settings > Reading and in Theme Customizer
  public static function dropdown( $pages, $r ){
      if(isset( $r['name'] ) && in_array( $r['name'], array( '_customize-dropdown-pages-page_on_front', 'page_on_front' ) ) ) {
          $args = array(
              'post_type' => self::$post_type,
          );
          $stacks = get_posts($args);
          $pages = array_merge($pages, $stacks);
      }
      return $pages;
  }
  // Fix query_vars
  // Notice: $query->is_page is left `true`, even if technically it shouldn't be.
  //   If it is set to false, is_front_page() template tag would break and
  //   there is no hook to fix that.
  public static function query_vars( $query ){
    if ( !isset( $query->query_vars['post_type'] ) ) {
      $query->query_vars['post_type'] = '';
    }
    if('' == $query->query_vars['post_type'] && 0 != $query->query_vars['page_id'] && get_post_type( $query->query_vars['page_id'] ) == self::$post_type ) {
      $query->query_vars['post_type'] = self::$post_type;
      $query->query_vars['p'] =  $query->query_vars['page_id'];
      //$query->is_page = false;
      $query->is_single = true;
      unset( $query->query_vars['page_id'] );
    }
  }
  // Fix the correct template (single.php, single-[CPT].php, etc.) to be used (instead of page.php, etc.) for CPT front page.
  public static function template( $template ) {
    if ( self::_is_in_front() ) {
      return get_single_template();
    }
  }
  // Redirect to front page if CTP post is accessed directly via permalink
  public static function redirect( ) {
    global $wp_query;
    if ( self::_is_in_front() ) {
      $frontpage_id = get_option('page_on_front');
      if ( $frontpage_id == $wp_query->post->ID && !is_front_page() ) {
        wp_redirect( get_bloginfo( 'url' ) );
      }
    }
  }
  // Fix CPT post permalink to point to bloginfo('url') if the post is set as front page
  public static function permalink( $post_link, $post, $leavename, $sample ) {
    if ( self::_is_in_front() ) {
      $frontpage_id = get_option('page_on_front');
      if ( $frontpage_id == $post->ID ) {
        $post_link = get_bloginfo( 'url' );
      }
    }
    return $post_link;
  }
  // checks if a CPT post is being used as Front Page
  private static function _is_in_front() {
    $frontpage_id = get_option('page_on_front');
    return ( self::$post_type == get_post_type( $frontpage_id ) );
  }
}
?>
