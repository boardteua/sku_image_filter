<?php

/**
 *  located image by sku plugin
 *
 * @link              #
 * @since             1.0.0
 * @package           image_sku_filter
 *
 * @wordpress-plugin
 * Plugin Name:       Located image by sku
 * Plugin URI:        #
 * Description:       These plugin helps to located image by sku number in the media libary and the product updated with that image
 * Version:           1.0.0
 * Author:            org100h
 * Author URI:        #
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */
if (!defined('WPINC')) {
    die;
}

class locate_image {

    private static $instance = null;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // check attachment by sku
        add_action('add_attachment', [$this, 'auto_attach_img_to_product']);
        add_action('admin_enqueue_scripts', [$this, 'add_recheck_button']);
        add_action('admin_footer', [$this, 'print_modal']);
        add_action('wp_ajax_proces_product', [$this, 'proces_product']);
    }

    public function print_modal($page) {
        echo '<div class="assing-modal hidden">    
            <h3>Assign Images to Product - <span class="reassign-estimate">' . $this->get_count() . '</span> / <span class="reassign-raady">0</span> </h3>
                 <div class="reassign-output"><ul></ul></div>
                 <hr /><button class="button media-button" id="start_reassign">Start</button>
                 <button class="button media-button hidden" id="stop_reassign">Stop</button>
                 <button class="button media-button" id="reset_reassign">Reset</button>
                 <button class="button media-button right" id="close_reassign">Close</button>
              </div>';
    }

    public function add_recheck_button($page) {
        if ('upload.php' !== $page) {
            return;
        }
        wp_enqueue_style('image_reasign_button_css', plugins_url('css.css', __FILE__));
        wp_enqueue_script('image_reasign_button_cookies_js', plugins_url('cookies.js', __FILE__), ['jquery'], false, true);
        wp_enqueue_script('image_reasign_button_js', plugins_url('js.js', __FILE__), ['jquery'], false, true);
    }

    public function auto_attach_img_to_product($attachmentID) {

        $src = wp_get_attachment_image_src($attachmentID, 'full');
       // $this->img_resize($attachmentID);

        $imgname = pathinfo($src[0], PATHINFO_FILENAME);
        $imgname = preg_replace('/[-]+\d$/i', '', $imgname);
        error_log($imgname);
        $args = array(
            'meta_key' => '_sku',
            'meta_value' => $imgname,
            'post_type' => 'product',
            'posts_per_page' => '1'
        );
        $products = get_posts($args);
        if (!empty($products)) {

            $product = array_pop($products);
            if (has_post_thumbnail($product)) {
                $string = get_post_meta($product->ID, '_product_image_gallery', true) . ',' . $attachmentID;
                $res = update_post_meta($product->ID, '_product_image_gallery', $string);

                if (!$res) {
                    error_log('attachement gallery assigment error id - ' . $attachmentID);
                }
            } else {
                $res = $this->set_attachment($product, $attachmentID);
                if (!$res) {
                    error_log('attachement feature assigment error id - ' . $attachmentID);
                }
            }
        }
    }

    public function proces_product() {
        $pointer = $_POST['pointer'];
        $count = $this->get_count();
        $msg = '';
        $args = [
            'post_type' => 'product',
            'posts_per_page' => 1,
            'offset' => $pointer
        ];

        $products = get_posts($args);

        if (!empty($products)) {
            $product = array_pop($products);
            $sku = wc_get_product($product->ID)->get_sku();
            if (!empty($sku)) {
                $attachment = $this->check_attachment($sku);
            } else {
                $msg = 'SKU not defined';
            }
            if ($attachment) {
                $this->set_attachment($product, $attachment->ID);
                $msg = '<img src="' . $attachment->guid . '" width="128px" height="128px" />';
            } else {
                $msg = 'Image not found';
            }

            if ($pointer == $count) {
                wp_send_json_success(
                        ['msg' => $msg, 'product' => $product->post_name, 'pointer' => $pointer, 'end' => true]
                );
            }
            wp_send_json_success(
                    ['msg' => $msg, 'product' => '<a target="_blank" href="' . get_the_permalink($product->ID) . '">' . $product->post_name . '</a>', 'pointer' => $pointer, 'end' => false]
            );
        } else {
            wp_send_json_error(
                    ['msg' => 'no products', 'pointer' => 1]
            );
        }
    }

    private function get_count() {
        $count = wp_count_posts('product');
        return $count->publish + $count->draft + $count->pending;
    }

    private function set_attachment($product, $attachmentID) {
        // set the thumbnail for the product
        set_post_thumbnail($product, $attachmentID);
        // "attach" the post to the product setting 'post_parent'
        $attachment = get_post($attachmentID);
        $attachment->post_parent = $product->ID;
        return wp_update_post($attachment);
    }

    private function check_attachment($sku) {

//        $args = [
//            's' => trim($sku),
//            'post_type' => 'attachment',
//            'posts_per_page' => -1,
//        ];
//         $attachments = get_posts($args);

        $attachment = get_page_by_title(trim($sku), OBJECT, 'attachment');


        if (!empty($attachment)) {
            return $attachment;
        } else {
            return false;
        }
    }

    private function img_resize($attachmentID) {
        $path = get_attached_file($attachmentID);
        $imgname = pathinfo($path, PATHINFO_FILENAME);
        if (!file_exists($path)) {
            error_log('empty img src');
            return false;
        }
        $image = wp_get_image_editor($path);
        if (is_wp_error($image)) {
            error_log('error getting editor object');
            return false;
        }

        $image->resize(500, 500, false);
        $image->set_quality(70);

        $set = $image->save($image->generate_filename());

        return $set;
    }

}

if (!function_exists('run_image_sku_filter')) {

    function run_image_sku_filter() {
        return locate_image::get_instance();
    }

}
run_image_sku_filter();
