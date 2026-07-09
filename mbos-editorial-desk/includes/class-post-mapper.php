<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBOS_Post_Mapper {

    public function map_path_to_post_row( $page_path, $views ) {
        $post_id = url_to_postid( home_url( $page_path ) );

        if ( ! $post_id ) {
            $slug_path = trim( wp_parse_url( $page_path, PHP_URL_PATH ), '/' );
            $post      = get_page_by_path( $slug_path, OBJECT, [ 'post', 'page' ] );
            $post_id   = $post ? $post->ID : 0;
        }

        if ( ! $post_id ) {
            return null;
        }

        return [
            'post_id'        => $post_id,
            'title'          => get_the_title( $post_id ),
            'permalink'      => get_permalink( $post_id ),
            'edit_link'      => get_edit_post_link( $post_id ),
            'lifetime_views' => absint( $views ),
        ];
    }
}
