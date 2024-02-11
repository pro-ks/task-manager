<?php
// Проверяем, что вызов произошел из WordPress и после проверки на безопасность
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Проверяем, установлены ли опции для удаления таблицы и настройки количества записей
$options = get_option('task_manager_options');
$delete_table_on_uninstall = isset( $options['delete_table_on_uninstall'] ) ? $options['delete_table_on_uninstall'] : '';

// Если опция на удаление таблицы не установлена или установлена в false, просто завершаем выполнение скрипта
if ( ! $delete_table_on_uninstall || $delete_table_on_uninstall !== 'on' ) {
    exit;
}

// Удаляем кастомную таблицу из базы данных
global $wpdb;
$table_name = $wpdb->prefix . 'tasks';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// Удаляем кэшированные данные
delete_transient( 'task_manager_cache' );
delete_transient( 'total_tasks_count_min' );

// Удаляем опцию "настройки плагина"
delete_option( 'task_manager_options' );
