<?php
/**
 * Logger class for Tomatillo Media Studio
 * Logs media-related actions for debugging purposes
 */

if (!defined('ABSPATH')) {
    exit;
}

class Tomatillo_Media_Logger {
    
    const OPTION_NAME = 'tomatillo_media_logs';
    const MAX_LOGS = 1000;
    
    /**
     * Log levels
     */
    const ERROR = 'ERROR';
    const WARNING = 'WARNING';
    const INFO = 'INFO';
    const DEBUG = 'DEBUG';
    
    /**
     * Check if debug mode is enabled
     */
    private static function is_debug_enabled() {
        $plugin = tomatillo_media_studio();
        return $plugin && $plugin->settings && $plugin->settings->get('debug_mode');
    }
    
    /**
     * Add a log entry
     */
    public static function log($level, $message, $context = array()) {
        // Only log if debug mode is enabled
        if (!self::is_debug_enabled()) {
            return;
        }
        
        // Get existing logs
        $logs = get_option(self::OPTION_NAME, array());
        
        // Create log entry
        $entry = array(
            'timestamp' => current_time('mysql'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'user_id' => get_current_user_id(),
            'user_name' => wp_get_current_user()->user_login
        );
        
        // Add to beginning of array (newest first)
        array_unshift($logs, $entry);
        
        // Trim to max size (delete oldest)
        if (count($logs) > self::MAX_LOGS) {
            $logs = array_slice($logs, 0, self::MAX_LOGS);
        }
        
        // Save logs
        update_option(self::OPTION_NAME, $logs, false); // autoload = false
    }
    
    /**
     * Log ERROR level
     */
    public static function error($message, $context = array()) {
        self::log(self::ERROR, $message, $context);
    }
    
    /**
     * Log WARNING level
     */
    public static function warning($message, $context = array()) {
        self::log(self::WARNING, $message, $context);
    }
    
    /**
     * Log INFO level
     */
    public static function info($message, $context = array()) {
        self::log(self::INFO, $message, $context);
    }
    
    /**
     * Log DEBUG level
     */
    public static function debug($message, $context = array()) {
        self::log(self::DEBUG, $message, $context);
    }
    
    /**
     * Get all logs
     */
    public static function get_logs() {
        return get_option(self::OPTION_NAME, array());
    }
    
    /**
     * Clear all logs
     */
    public static function clear_logs() {
        delete_option(self::OPTION_NAME);
    }
    
    /**
     * Get log count
     */
    public static function get_log_count() {
        $logs = self::get_logs();
        return count($logs);
    }
}

