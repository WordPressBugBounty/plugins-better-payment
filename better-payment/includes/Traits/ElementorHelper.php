<?php 

namespace Better_Payment\Lite\Traits;

if (!defined('ABSPATH')) {
    exit();
} // Exit if accessed directly

trait ElementorHelper {

    public function select2_ajax_posts_filter_autocomplete() {
        $post_type = 'post';
        $source_name = 'post_type';
    
        if ( !empty( $_GET[ 'post_type' ] ) ) {
            $post_type = sanitize_text_field( $_GET[ 'post_type' ] );
        }
    
        if ( !empty( $_GET[ 'source_name' ] ) ) {
            $source_name = sanitize_text_field( $_GET[ 'source_name' ] );
        }
    
        $search = !empty( $_GET[ 'term' ] ) ? sanitize_text_field( $_GET[ 'term' ] ) : '';
        $results = $post_list = [];
        switch($source_name){
            case 'taxonomy':
                $args = [
                    'hide_empty' => false,
                    'orderby'    => 'name',
                    'order'      => 'ASC',
                    'search'     => $search,
                    'number'     => '5',
                ];
    
                if ( $post_type !== 'all' ) {
                    $args['taxonomy'] = $post_type;
                }
    
                $post_list = wp_list_pluck( get_terms( $args ), 'name', 'term_id' );
                break;
            case 'user':
                if ( ! current_user_can( 'list_users' ) ) {
                    $post_list = [];
                    break;
                }

                $users = [];

                foreach ( get_users( [ 'search' => "*{$search}*" ] ) as $user ) {
                    $user_id           = $user->ID;
                    $user_name         = $user->display_name;
                    $users[ $user_id ] = $user_name;
                }

                $post_list = $users;
                break;
            default:
                if ( $post_type === 'fluent-products' && function_exists('fluentCart') ) {
                    $post_list = $this->get_fluentcart_products( $search );
                } else {
                    $post_list = $this->get_query_post_list( $post_type, 10, $search );
                }
        }
    
        if ( !empty( $post_list ) ) {
            foreach ( $post_list as $key => $item ) {
                $results[] = [ 'text' => $item, 'id' => $key ];
            }
        }
        wp_send_json( [ 'results' => $results ] );
    }
    
    /**
	 * Select2 Ajax Get Posts Value Titles
	 * get selected value to show elementor editor panel in select2 ajax search box
	 *
	 * @access public
	 * @return void
	 * @since 1.0.0
	 */
    public function select2_ajax_get_posts_value_titles() {
    
        if ( empty( $_POST['id'] ) ) {
            wp_send_json_error( [] );
        }
    
        if ( empty( array_filter($_POST['id']) ) ) {
            wp_send_json_error( [] );
        }
        $ids            = array_map('intval', $_POST['id']);
        $source_name    = ! empty( $_POST['source_name'] ) ? sanitize_text_field( $_POST['source_name'] ) : '';
    
        switch ( $source_name ) {
            case 'taxonomy':
                $args = [
                    'hide_empty' => false,
                    'orderby'    => 'name',
                    'order'      => 'ASC',
                    'include'    => implode( ',', $ids ),
                ];
    
                if ( $_POST['post_type'] !== 'all' ) {
                    $args['taxonomy'] = sanitize_text_field( $_POST[ 'post_type' ] );
                }
    
                $response = wp_list_pluck( get_terms( $args ), 'name', 'term_id' );
                break;
            case 'user':
                $users = [];

                foreach ( get_users( [ 'include' => $ids ] ) as $user ) {
                    $user_id           = $user->ID;
                    $user_name         = $user->display_name;
                    $users[ $user_id ] = $user_name;
                }

                $response = $users;
                break;
            default:
                $post_info = get_posts( [ 'post_type' => sanitize_text_field( $_POST['post_type'] ), 'include' => implode( ',', $ids ) ] );
                $response  = wp_list_pluck( $post_info, 'post_title', 'ID' );
        }
    
        if ( ! empty( $response ) ) {
            wp_send_json_success( [ 'results' => $response ] );
        } else {
            wp_send_json_error( [] );
        }
    }

    /**
	 * Add elementor category
	 *
	 * @since v1.0.0
	 */
	public function register_widget_categories( $elements_manager ) {
		$elements_manager->add_category(
			'better-payment',
			[
				'title' => __( 'Better Payment', 'better-payment' ),
				'icon'  => 'font',
			], 1 );
	}

	/**
	 * Get FluentCart products for select2 dropdown
	 *
	 * @param string $search Search term
	 * @return array
	 */
	private function get_fluentcart_products( $search = '' ) {
		if ( ! function_exists('fluentCart') || ! class_exists( '\FluentCart\App\Models\Product' ) ) {
			return [];
		}

        if ( ! is_user_logged_in() || ! current_user_can('edit_posts') ) {
            return [];
        }

		try {
            $product_model = new \FluentCart\App\Models\Product();
            
            $query = $product_model->newQuery()
                ->where('post_status', 'publish')
                ->limit(10);


			if ( ! empty( $search ) ) {
				$query->where('post_title', 'LIKE', '%' . sanitize_text_field( $search ) . '%');
			}

			$products = $query->get();

			$product_list = [];
			if ( ! empty( $products ) ) {
				foreach ( $products as $product ) {
					$product_list[ $product->ID ] = $product->post_title;
				}
			}

			return $product_list;
		} catch ( \Exception $e ) {
			return [];
		}
	}
}