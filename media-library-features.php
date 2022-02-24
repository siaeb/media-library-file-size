<?php

class MediaLibraryFeatures {

	private static $instance = null;

	/**
	 * Get single instance of this class - Singleton
	 *
	 * @since 1.0
	 */
	public static function getInstance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * MediaLibrary constructor.
	 */
	private function __construct() {
		$this->initHooks();
	}

	/**
	 * Initialize Hooks.
	 *
	 * @author Siavash Ebrahimi
	 * @since 1.0
	 * @return void
	 */
	public function initHooks() {
		// Register size column for media library table
		add_filter( 'manage_media_columns', [ $this, 'registerSizeColumn' ], 15 );
		add_action( 'manage_media_custom_column', [ $this, 'populateCustomColumns' ], 15, 2 );
		add_action( 'added_post_meta', [ $this, 'addFileSizeMetadataToImages' ], 15, 4 );
		add_filter( 'manage_upload_sortable_columns', [ $this, 'registerColumnSortableFileSize' ], 15 );
		add_action( 'pre_get_posts', [ $this, 'sortableFileSizeSortingLogic' ], 15 );
	}

	/**
	 * Column sorting logic (query modification)
	 *
	 * @param $query
	 */
	public function sortableFileSizeSortingLogic( $query ) {
		global $pagenow;
		if ( is_admin() && 'upload.php' == $pagenow && $query->is_main_query() && ! empty( $_REQUEST['orderby'] ) && 'filesize' == $_REQUEST['orderby'] ) {
			$query->set( 'order', 'ASC' );
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'meta_key', 'filesize' );
			if ( 'desc' == strtolower($_REQUEST['order']) ) {
				$query->set( 'order', 'DSC' );
			}
		}
	}

	/**
	 * Make column sortable
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function registerColumnSortableFileSize( $columns ) {
		$columns['filesize'] = 'filesize';
		return $columns;
	}

	/**
	 * Ensure file size meta gets added to new uploads
	 *
	 * @param $meta_id
	 * @param $post_id
	 * @param $meta_key
	 * @param $meta_value
	 */
	public function addFileSizeMetadataToImages( $meta_id, $post_id, $meta_key, $meta_value ) {
		if ( '_wp_attachment_metadata' == $meta_key ) {
			$file = get_attached_file( $post_id );
			update_post_meta( $post_id, 'filesize', filesize( $file ) );
		}
	}

	/**
	 * Populate media custom columns
	 *
	 * @return string
	 * @since 1.0
	 * @access public
	 */
	public function populateCustomColumns( $column_name, $post_id ) {
		if ( 'filesize' == $column_name ) {
			if ( ! get_post_meta( $post_id, 'filesize', true ) ) {
				$file      = get_attached_file( $post_id );
				$file_size = filesize( $file );
				update_post_meta( $post_id, 'filesize', $file_size );
			} else {
				$file_size = get_post_meta( $post_id, 'filesize', true );
			}
			echo size_format( $file_size, 2 );
		}
	}

	/**
	 * Register size column
	 *
	 * @param array $cols
	 *
	 * @return mixed
	 * @since 1.0
	 */
	public function registerSizeColumn( $cols ) {
		$cols['filesize'] = _x( 'File Size', 'Media Library Admin Table', 'media-library-file-size' );
		return $cols;
	}

}
