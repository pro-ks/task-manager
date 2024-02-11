<?php
/*
Plugin Name: Личный таск-менеджер
Description: Плагин "Личный таск-менеджер" предоставляет простой и удобный способ организации вашего списка задач прямо на любой странице через шорткод  <code>[task_manager]</code>. С его помощью вы можете создавать, редактировать и удалять задачи, а также устанавливать различные параметры для управления вашими задачами. 
Version: 1.0
Author: KosTeams
Author URI: http://t.me/kosteams
Plugin URI: https://github.com/pro-ks/task-manager
*/

// Проверка на прямой доступ
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Выход, если ABSPATH не определен
}

// Подключение файлов
require_once plugin_dir_path( __FILE__ ) . 'includes/ajax.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/shortcode.php';
require_once plugin_dir_path( __FILE__ ) . 'templates/task-manager-template.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/admin-page.php';


// Создаем таблицу в базе данных при активации плагина
register_activation_hook(__FILE__, 'task_manager_activate');
function task_manager_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'tasks';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        description text NOT NULL,
        due_date datetime NOT NULL,
        completion_date datetime NOT NULL,
        user_id mediumint(9) NOT NULL,
        completed tinyint(1) DEFAULT 0,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Подключение скрипта, если на странице есть указанный шорткод.
add_filter( 'the_posts', 'has_task_manager_shortcode' );
function has_task_manager_shortcode( $posts ){
	if( is_admin() || empty( $posts ) || ! is_main_query() ){
		return $posts;
	}
	$shortcode_name = 'task_manager';
	foreach( $posts as $post ){
		if( has_shortcode( $post->post_content, $shortcode_name ) ){
			add_action( 'wp_enqueue_scripts', 'enqueue_task_manager_scripts' );
			break;
		}
	}
	return $posts;
}

// Скрипты подключать, если есть шорткод.
function enqueue_task_manager_scripts() {
    // Подключаем jQuery, если он еще не подключен
    wp_enqueue_script('jquery');
	if ( ! wp_style_is( 'bootstrap', 'enqueued' ) ) {
	    // Подключаем стили Bootstrap
	    wp_enqueue_style( 'bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css', array(), '5.3.2' );
	}
	if ( ! wp_script_is( 'bootstrap', 'enqueued' ) ) {
	    // Подключаем скрипты Bootstrap
	    wp_enqueue_script( 'bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js', array('jquery'), '5.3.2', true );
	}

    wp_enqueue_script('task-manager', plugins_url('js/my-task-manager.js', __FILE__), array('jquery'), '1.3', true);
    // Передача данных в скрипт
    wp_localize_script( 'task-manager', 'TaskManager',
        [
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('task-manager-nonce')
        ]
    );
}

// Добавляем ссылку на страницу настроек плагина в раздел "Действия" на странице управления плагинами
function task_manager_plugin_settings_link( $links ) {
    $settings_link = '<a href="options-general.php?page=task-manager-settings">' . __('Settings') . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'task_manager_plugin_settings_link' );
