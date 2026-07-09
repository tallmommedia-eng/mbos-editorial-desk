<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class MBOS_GA_Service {

    const DEFAULT_START_DATE = '2020-01-01';
    const DEFAULT_LIMIT      = 500;

    private $cache;
    private $mapper;
    private $status = [];

    public function __construct() {
        $this->cache  = new MBOS_Cache();
        $this->mapper = new MBOS_Post_Mapper();
    }

    public function get_lifetime_post_views( $force_refresh = false ) {
        if ( $force_refresh ) {
            $this->cache->clear();
        }

        $rows = $this->cache->get();

        if ( false !== $rows ) {
            $this->status = $this->cache->get_status();
            return $rows;
        }

        $raw_rows = $this->fetch_lifetime_views_from_site_kit();
        $rows     = $this->map_raw_rows_to_posts( $raw_rows );

        usort(
            $rows,
            function( $a, $b ) {
                return absint( $b['lifetime_views'] ) <=> absint( $a['lifetime_views'] );
            }
        );

        $this->cache->set( $rows );
        $this->cache->set_status( $this->status );

        return $rows;
    }

    public function get_status() {
        if ( empty( $this->status ) ) {
            $this->status = $this->cache->get_status();
        }

        return $this->status;
    }

    private function fetch_lifetime_views_from_site_kit() {
        if ( ! defined( 'GOOGLESITEKIT_VERSION' ) && ! class_exists( 'Google\\Site_Kit\\Plugin' ) ) {
            $this->status = [
                'type'    => 'warning',
                'message' => 'Google Site Kit does not appear to be active yet.',
            ];
            return [];
        }

        if ( ! function_exists( 'rest_do_request' ) ) {
            $this->status = [
                'type'    => 'error',
                'message' => 'WordPress REST API is unavailable, so MBOS cannot ask Site Kit for Analytics data.',
            ];
            return [];
        }

        $attempts = $this->get_site_kit_report_attempts();
        $errors   = [];

        foreach ( $attempts as $attempt ) {
            $result = $this->run_site_kit_report( $attempt );

            if ( ! empty( $result['error'] ) ) {
                $errors[] = $attempt['label'] . ': ' . $result['error'];
                continue;
            }

            $rows = ! empty( $result['rows'] ) && is_array( $result['rows'] ) ? $result['rows'] : [];

            if ( ! empty( $rows ) ) {
                $this->status = [
                    'type'       => 'success',
                    'message'    => 'Connected through Google Site Kit. Report: ' . $attempt['label'] . '.',
                    'updated_at' => current_time( 'mysql' ),
                    'debug'      => 'Rows returned: ' . count( $rows ),
                ];
                return $rows;
            }

            $errors[] = $attempt['label'] . ': no rows returned';
        }

        $this->status = [
            'type'       => 'warning',
            'message'    => 'Site Kit responded, but MBOS still could not retrieve page-level GA4 rows.',
            'updated_at' => current_time( 'mysql' ),
            'debug'      => implode( ' | ', array_slice( $errors, 0, 5 ) ),
        ];

        return [];
    }

    private function get_site_kit_report_attempts() {
        $end_date = gmdate( 'Y-m-d' );

        $base = [
            'startDate' => self::DEFAULT_START_DATE,
            'endDate'   => $end_date,
            'limit'     => self::DEFAULT_LIMIT,
        ];

        return [
            [
                'label'  => 'pagePath + screenPageViews',
                'params' => array_merge(
                    $base,
                    [
                        'dimensions' => [ [ 'name' => 'pagePath' ] ],
                        'metrics'    => [ [ 'name' => 'screenPageViews' ] ],
                        'orderby'    => [ [ 'metric' => [ 'metricName' => 'screenPageViews' ], 'desc' => true ] ],
                    ]
                ),
            ],
            [
                'label'  => 'pagePathPlusQueryString + screenPageViews',
                'params' => array_merge(
                    $base,
                    [
                        'dimensions' => [ [ 'name' => 'pagePathPlusQueryString' ] ],
                        'metrics'    => [ [ 'name' => 'screenPageViews' ] ],
                        'orderby'    => [ [ 'metric' => [ 'metricName' => 'screenPageViews' ], 'desc' => true ] ],
                    ]
                ),
            ],
            [
                'label'  => 'landingPagePlusQueryString + sessions',
                'params' => array_merge(
                    $base,
                    [
                        'dimensions' => [ [ 'name' => 'landingPagePlusQueryString' ] ],
                        'metrics'    => [ [ 'name' => 'sessions' ] ],
                        'orderby'    => [ [ 'metric' => [ 'metricName' => 'sessions' ], 'desc' => true ] ],
                    ]
                ),
            ],
            [
                'label'  => 'Site Kit string params',
                'params' => array_merge(
                    $base,
                    [
                        'dimensions' => 'pagePath',
                        'metrics'    => 'screenPageViews',
                    ]
                ),
            ],
        ];
    }

    private function run_site_kit_report( $attempt ) {
        $request = new WP_REST_Request( 'GET', '/google-site-kit/v1/modules/analytics-4/data/report' );

        foreach ( $attempt['params'] as $key => $value ) {
            $request->set_param( $key, $value );
        }

        $response = rest_do_request( $request );

        if ( is_wp_error( $response ) ) {
            return [ 'error' => $response->get_error_message() ];
        }

        $status_code = $response->get_status();
        $data        = $response->get_data();

        if ( 200 !== $status_code ) {
            return [ 'error' => $this->extract_error_message( $data ) ];
        }

        return [ 'rows' => $this->extract_rows_from_response( $data ) ];
    }

    private function extract_error_message( $data ) {
        if ( is_array( $data ) ) {
            if ( ! empty( $data['message'] ) ) {
                return sanitize_text_field( wp_unslash( $data['message'] ) );
            }
            if ( ! empty( $data['error']['message'] ) ) {
                return sanitize_text_field( wp_unslash( $data['error']['message'] ) );
            }
            if ( ! empty( $data['data']['status'] ) ) {
                return sanitize_text_field( wp_unslash( $data['data']['status'] ) );
            }
        }

        return 'Unknown Site Kit Analytics error.';
    }

    private function extract_rows_from_response( $data ) {
        if ( ! is_array( $data ) ) {
            return [];
        }

        $paths = [
            [ 'rows' ],
            [ 'report', 'rows' ],
            [ 0, 'rows' ],
            [ 'reports', 0, 'rows' ],
            [ 'data', 'rows' ],
            [ 'data', 'report', 'rows' ],
        ];

        foreach ( $paths as $path ) {
            $value = $data;
            foreach ( $path as $segment ) {
                if ( is_array( $value ) && array_key_exists( $segment, $value ) ) {
                    $value = $value[ $segment ];
                } else {
                    $value = null;
                    break;
                }
            }
            if ( is_array( $value ) && ! empty( $value ) ) {
                return $value;
            }
        }

        return [];
    }

    private function map_raw_rows_to_posts( $raw_rows ) {
        $mapped = [];
        $seen   = [];

        foreach ( $raw_rows as $raw_row ) {
            $page_path = $this->extract_dimension_value( $raw_row );
            $views     = $this->extract_metric_value( $raw_row );

            if ( empty( $page_path ) || $views <= 0 ) {
                continue;
            }

            $row = $this->mapper->map_path_to_post_row( $page_path, $views );

            if ( empty( $row ) || empty( $row['post_id'] ) ) {
                continue;
            }

            $post_id = absint( $row['post_id'] );

            if ( isset( $seen[ $post_id ] ) ) {
                $mapped[ $seen[ $post_id ] ]['lifetime_views'] += absint( $views );
                continue;
            }

            $seen[ $post_id ] = count( $mapped );
            $mapped[]         = $row;
        }

        if ( empty( $mapped ) && ! empty( $raw_rows ) ) {
            $this->status['type']    = 'warning';
            $this->status['message'] = 'GA4 rows came back, but MBOS could not match them to WordPress posts yet.';
            $this->status['debug']   = 'Raw rows returned: ' . count( $raw_rows ) . '. URL mapping needs adjustment.';
        }

        return $mapped;
    }

    private function extract_dimension_value( $raw_row ) {
        if ( isset( $raw_row['dimensionValues'][0]['value'] ) ) {
            return sanitize_text_field( wp_unslash( $raw_row['dimensionValues'][0]['value'] ) );
        }

        if ( isset( $raw_row['dimensions'][0] ) ) {
            return sanitize_text_field( wp_unslash( $raw_row['dimensions'][0] ) );
        }

        if ( isset( $raw_row[0] ) && is_string( $raw_row[0] ) ) {
            return sanitize_text_field( wp_unslash( $raw_row[0] ) );
        }

        return '';
    }

    private function extract_metric_value( $raw_row ) {
        if ( isset( $raw_row['metricValues'][0]['value'] ) ) {
            return absint( $raw_row['metricValues'][0]['value'] );
        }

        if ( isset( $raw_row['metrics'][0] ) ) {
            return absint( $raw_row['metrics'][0] );
        }

        if ( isset( $raw_row[1] ) ) {
            return absint( $raw_row[1] );
        }

        return 0;
    }
}
