<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBOS_Cache {

    const CACHE_KEY   = 'mbos_dashboard_lifetime_views';
    const STATUS_KEY  = 'mbos_dashboard_lifetime_views_status';
    const CACHE_TTL   = 12 * HOUR_IN_SECONDS;

    public function get() {
        return get_transient( self::CACHE_KEY );
    }

    public function set( $data ) {
        return set_transient( self::CACHE_KEY, $data, self::CACHE_TTL );
    }

    public function get_status() {
        $status = get_transient( self::STATUS_KEY );

        if ( ! is_array( $status ) ) {
            return [];
        }

        return $status;
    }

    public function set_status( $status ) {
        return set_transient( self::STATUS_KEY, $status, self::CACHE_TTL );
    }

    public function clear() {
        delete_transient( self::STATUS_KEY );
        return delete_transient( self::CACHE_KEY );
    }
}
