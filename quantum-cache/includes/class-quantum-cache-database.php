<?php
if ( ! defined( 'WPINC' ) ) {
    die;
}

class Quantum_Cache_Database {
    protected static $instance = null;

    public static function get_instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function delete_post_revisions() {
        global $wpdb;
        $query = "DELETE FROM {$wpdb->posts} WHERE post_type = 'revision'";
        return $wpdb->query( $query );
    }

    public function delete_auto_drafts() {
        global $wpdb;
        $query = "DELETE FROM {$wpdb->posts} WHERE post_status = 'auto-draft'";
        return $wpdb->query( $query );
    }

    public function delete_spam_comments() {
        global $wpdb;
        $query = "DELETE FROM {$wpdb->comments} WHERE comment_approved = 'spam'";
        return $wpdb->query( $query );
    }
}
