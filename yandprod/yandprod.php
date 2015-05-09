<?php
/*
Plugin Name: Yandex Product
Description: Плагин для реализации схемы Product от Yandex
Version: 6.6.6
Author: Itachi261092
Author URI: http://vk.com/Itachi261092
Text Domain: wp-yandex-product
Domain Path: /lang/
*/
/*  Copyright 2015  Itachi261092  (email: demon261092@gmail.ru)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

function call_someClass() {
    new someClass();
}

if ( is_admin() ) {
    add_action( 'load-post.php', 'call_someClass' );
    add_action( 'load-post-new.php', 'call_someClass' );
}

class someClass {

    /**
     * Хук когда класс построен.
     */
    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
        add_action( 'save_post', array( $this, 'save' ) );
        // Media Uploader
        add_action('admin_head', array( $this, 'll_uploader_xls_scripts') );
        add_action('wp_ajax_xls_autocomplet', array( $this, 'xls_autocomplet') );
        add_action('wp_ajax_nopriv_xls_autocomplet', array( $this, 'xls_autocomplet') );
    }

    /**
     * Добавление метабокса
     */
    public function add_meta_box( $post_type ) {
        $post_types = array('post', 'page');
        if ( in_array( $post_type, $post_types )) {
            add_meta_box(
                'yandexproduct_metabox'
                ,__( 'Yandex Product', 'wp-yandex-product' )
                ,array( $this, 'render_meta_box_content' )
                ,$post_type
                ,'advanced'
                ,'high'
            );
        }
    }

    public function save( $post_id ) {

        // Проверки
        if ( ! isset( $_POST['myplugin_inner_custom_box_nonce'] ) )
            return $post_id;

        $nonce = $_POST['myplugin_inner_custom_box_nonce'];

        // Проверка скрытой переменной.
        if ( ! wp_verify_nonce( $nonce, 'myplugin_inner_custom_box' ) )
            return $post_id;

        // Проверка автосохранения.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
            return $post_id;

        // Проверка прав пользователя.
        if ( 'page' == $_POST['post_type'] ) {

            if ( ! current_user_can( 'edit_page', $post_id ) )
                return $post_id;

        } else {

            if ( ! current_user_can( 'edit_post', $post_id ) )
                return $post_id;
        }

        /* Проверка закончена. */

        // Обработка от хтмл кода.
        $product['name'] = sanitize_text_field( $_POST['productName'] );
        $product['description'] = sanitize_text_field( $_POST['productDescription'] );
        $product['price'] = sanitize_text_field( $_POST['productPrice'] );

        // Сохранение изменённых полей.
        update_post_meta( $post_id, 'productName', $product['name'] );
        update_post_meta( $post_id, 'productDescription', $product['description'] );
        update_post_meta( $post_id, 'productPrice', $product['price'] );

        // Сохраняем ссылку на изображение
        $_POST['extra'] = array_map('trim', $_POST['extra']);
        foreach ($_POST['extra'] as $key => $value) {
            if (empty($value)) {
                delete_post_meta($post_id, $key); // удаляем поле если значение пустое
            }
            update_post_meta($post_id, $key, $value); // add_post_meta() работает автоматически
        }
    }
    // Media Uploader
    public function ll_uploader_xls_scripts()
    {
        wp_enqueue_style('thickbox');
        wp_enqueue_script('thickbox');
        wp_enqueue_script('xls-uploader', plugin_dir_url(__FILE__) . 'upload.js', array('jquery'), '0.1', false);
    }

    public function render_meta_box_content( $post ) {

        // Скрытое поле для проверки.
        wp_nonce_field( 'myplugin_inner_custom_box', 'myplugin_inner_custom_box_nonce' );

        // Use get_post_meta to retrieve an existing value from the database.
        $productName = get_post_meta( $post->ID, 'productName', true );
        $productDescription = get_post_meta( $post->ID, 'productDescription', true );
        $productPrice = get_post_meta( $post->ID, 'productPrice', true );
        $productImage = get_post_meta($post->ID, 'file_id', 1);


        // Формы ввода данных в плагине
        ?>
        <p>Введите параметры товара или услуги.</p>
        <p><label for="productName" size="40%"><?php echo _e( 'Название:', 'wp-yandex-product' );?></label>
        <input type="text" id="productName" name="productName" value="<?php echo $productName;?>" size="25" /></p>

        <p><label for="productDescription" size="40%"><?php echo _e( 'Описание:', 'wp-yandex-product' );?></label>
        <input type="text" id="productDescription" name="productDescription" value="<?php echo $productDescription;?>" size="25" /></p>

        <p><label for="productPrice" size="40%"><?php echo _e( 'Цена:', 'wp-yandex-product' );?></label>
        <input type="text" id="productPrice" name="productPrice" value="<?php echo $productPrice;?>" size="25" /></p>

        <input id="xls-uploader-input" type="text" name="extra[file_id]"  value="<?php echo $productImage;?>" style="width:50%"/>
        <p id="xls-uploader-name">Файл не выбран</p>
        <hr/>
        <button id="xls-uploader-submit" class="button button-primary button-large">Выбрать файл</button>
        <input type="hidden" name="extra_fields_nonce" value="<?php echo wp_create_nonce(__FILE__); ?>"/>
        <?php

    }

    public function xls_autocomplet()
    {
        url_to_postid($_POST['url_file']);
    }

}

class Yandex_Product extends WP_Widget {

    function __construct() {
        parent::__construct(
            'yandexproduct_widget', // Base ID
            __( 'Yandex Product Widget', 'wp-yandex-product' ), // Name
            array( 'description' => __( 'Виджет для отображения информации Яндекс Product', 'wp-yandex-product' ), ) // Args
        );
    }

    public function widget( $args, $instance ) {
        global $post;
        $product['name'] = get_post_meta($post->ID, 'productName', $single = true);
        $product['description'] = get_post_meta($post->ID, 'productDescription', $single = true);
        $product['price'] = get_post_meta($post->ID, 'productPrice', $single = true);
        $product['image'] = get_post_meta($post->ID, 'file_id', $single = true);

        if ( $product['name'] & $product['description'] & $product['price'] & $product['image']){
            echo $args['before_widget'];
            if ( ! empty( $instance['title'] ) ) {
                echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
            }
            ?>
            <div itemscope itemtype="http://schema.org/Product">
                <div itemprop="name"><h1><?php echo $product['name']?></h1></div>
                <a itemprop="image" href="<?php echo $product['image']?>">
                    <img src="<?php echo $product['image']?>" title="<?php echo $product['name']?>">
                </a>
                <div itemprop="offers" itemscope itemtype="http://schema.org/Offer">
                    <div><?php echo $product['price']?> руб.</div>
                    <meta itemprop="price" content="<?php echo $product['price']?>">
                    <meta itemprop="priceCurrency" content="RUB">
                    <div>В наличии</div>
                    <link itemprop="availability" href="http://schema.org/InStock">
                </div>
                <div itemprop="description"><?php echo $product['description']?></div>
            </div>
            <?PHP
            echo $args['after_widget'];
        }
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Yandex Product', 'wp-yandex-product' );
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
    <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

        return $instance;
    }

}
function register_yandex_widget() {
    register_widget( 'Yandex_Product' );
}
add_action( 'widgets_init', 'register_yandex_widget' );

// Шорткод
add_shortcode( 'yandexproduct', 'yaproduct' );
function yaproduct( $atts ){
    extract(shortcode_atts(array('postid'), $atts));
    if (empty($postid)) {
        global $post;
        $postid = $post->ID;
    };
    $product['name'] = get_post_meta($postid, 'productName', $single = true);
    $product['description'] = get_post_meta($postid, 'productDescription', $single = true);
    $product['price'] = get_post_meta($postid, 'productPrice', $single = true);
    $product['image'] = get_post_meta($postid, 'file_id', $single = true);

    ob_start();
    ?>
    <div itemscope itemtype="http://schema.org/Product">
        <div itemprop="name"><h1><?php echo $product['name']?></h1></div>
        <a itemprop="image" href="<?php echo $product['image']?>">
            <img src="<?php echo $product['image']?>" title="<?php echo $product['name']?>">
        </a>
        <div itemprop="offers" itemscope itemtype="http://schema.org/Offer">
            <div><?php echo $product['price']?> руб.</div>
            <meta itemprop="price" content="<?php echo $product['price']?>">
            <meta itemprop="priceCurrency" content="RUB">
            <div>В наличии</div>
            <link itemprop="availability" href="http://schema.org/InStock">
        </div>
        <div itemprop="description"><?php echo $product['description']?></div>
    </div>
    <?PHP
  $output_string = ob_get_contents();
  ob_end_clean();
  return $output_string;
}
?>