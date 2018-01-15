<?php
/*
Plugin name: Expose more pagedata in REST
Author: Christian Nikkanen
Author URI: http://kisu.li
*/

add_action("plugins_loaded", function() {
  $homepage = get_option("page_on_front");
  $blogpage = get_option("page_for_posts");

  add_action("rest_api_init", function() use ($blogpage, $homepage) {
    $isHomepage = function($page) use ($homepage) {
      return (int) $homepage === $page["id"];
    };

    $isBlogpage = function($page) use ($blogpage) {
      return (int) $blogpage === $page["id"];
    };

    register_rest_field("page", "isHomepage", [
      "get_callback" => $isHomepage,
    ]);

    register_rest_field("page", "isBlogpage", [
      "get_callback" => $isBlogpage,
    ]);

    register_rest_route("emp/v1", "archives", [
      "methods" => "GET",
      "callback" => function() {
        $post_types = [];
        $ptypes = get_post_types(["public" => true], "objects");

        foreach ($ptypes as $type) {
          if ($type->show_in_rest) {
            $type->archive_link = $type->name === "post"
              ? get_permalink(get_option("page_for_posts"))
              : get_post_type_archive_link($type);

            if ($type->archive_link) {
              $post_types[] = $type;
            }
          }
        }


        // Sadly there's no archive pages for taxonomies, only terms.
        // https://wordpress.stackexchange.com/a/48440
        $taxonomies = [];
        $tnomies = get_taxonomies(["public" => true], 'objects');

        foreach ($tnomies as $tax) {
          $t = [];
          if ($tax->show_in_rest) {
            $terms = get_terms($tax->name);

            foreach ($terms as $term) {
              $term->archive_link = get_term_link($term);
              error_log('add rest base here maybe if its possible to even query with taxonomies');
              $t[] = $term;
            }

            $taxonomies[$tax->name] = $t;
          }
        }

        return new WP_REST_Response([
          "post_types" => $post_types,
          "taxonomies" => $taxonomies,
        ]);
      },
    ]);
  });
});
