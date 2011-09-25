<?php
/**
 * Created by JetBrains PhpStorm.
 * User: michaeldewildt
 * Date: 11/05/11
 * Time: 8:41 PM
 * To change this template use File | Settings | File Templates.
 */
date_default_timezone_set( 'Australia/NSW' );

global $cache;
global $schedule;
global $current_time;

$options = array();
$next_schedule = array();
$schedule = array();

function get_option( $key ) {
    global $options;
    return array_key_exists( $key, $options ) ? $options[$key] : false;
}

function add_option( $key, $value, $not_used, $load ) {
    if ( $load != 'no' ) {
        throw new Exception( 'Load should be no' );
    }
    update_option( $key, $value );
}

function update_option( $key, $value ) {
    global $options;
    $options[$key] = $value;
}

function wp_next_scheduled( $key ) {
    global $schedule;
    return array_key_exists( $key, $schedule ) ? $schedule[$key][0] : false;
}

function wp_get_schedule( $key ) {
    global $schedule;
    return array_key_exists( $key, $schedule ) ? $schedule[$key][1] : false;
}

function __( $str ) {
    return $str;
}

function current_time( $str ) {
    if ( $str != 'mysql' ) {
        throw new Exception( 'Current time var must be mysql' );
    }
    global $current_time;
    if ( $current_time ) {
        return $current_time;
    }
    return date( 'Y-m-d H:i:s' );
}

function wp_schedule_event( $server_time, $frequency, $hook ) {
    global $schedule;
    $schedule[$hook] = array( $server_time, $frequency );
}

function wp_schedule_single_event( $server_time, $key ) {
    global $schedule;
    $schedule[$key] = array( $server_time );
    return true;
}

function wp_unschedule_event( $server_time, $key ) {
    global $schedule;
    if ( !array_key_exists( $key, $schedule ) ) {
        throw new Exception( "Key '$key' does not exist" );
    }
    if ( $schedule[$key][0] != $server_time ) {
        throw new Exception( "Invalid timestamp '$server_time' not equal to '{$schedule[$key][0]}'" );
    }
    return $schedule[$key];
}