<?php
// Проверка на прямой доступ
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Выход, если ABSPATH не определен
}
// Функция для получения кэшированного содержимого шорткода
function get_cached_task_manager() {
    $cache_key = 'task_manager_cache'; // Ключ кэша
    $cached_content = wp_cache_get( $cache_key ); // Пытаемся получить закэшированное содержимое

    if ( false === $cached_content ) {
        // Если в кэше нет содержимого, запускаем шорткод и кэшируем его
        $cached_content = do_shortcode( '[task_manager]' );
        wp_cache_set( $cache_key, $cached_content ); // Кэшируем содержимое
    }

    return $cached_content;
}
add_shortcode( 'task_manager', 'get_cached_task_manager' );

// Добавляем шорткод для вывода списка задач и формы добавления задачи
add_shortcode( 'task_manager', 'task_manager_shortcode' );
function task_manager_shortcode( $atts ) {
    $output = '';

    // Добавляем форму добавления задачи
    if ( current_user_can('edit_posts') ) {
        $output .= display_task_form();
    }

    // Добавляем список задач
    $output .= display_task_list();

    // Добавляем модальное окно редактирования задачи
    $output .= display_edit_modal();

    return $output;
}
