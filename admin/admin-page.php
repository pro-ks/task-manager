<?php
// Проверка на прямой доступ
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Выход, если ABSPATH не определен
}

class Task_Manager_Settings_Page {
    
    /**
     * Конструктор класса.
     * Добавляет необходимые хуки и фильтры.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }
    
    /**
     * Добавление страницы настроек плагина в административную панель.
     */
    public function add_settings_page() {
        add_options_page(
            __('Настройки Личного таск-менеджера', 'my-task-manager'),    // Заголовок страницы
            __('Настройки Личного таск-менеджера', 'my-task-manager'),    // Название в меню
            'manage_options',                     // Роль, которая может получить доступ к этой странице
            'task-manager-settings',              // Идентификатор страницы
            array( $this, 'render_settings_page' )      // Метод отображения содержимого страницы
        );
    }
    
    /**
     * Функция отображения страницы настроек плагина.
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Настройки Личного таск-менеджера', 'my-task-manager'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('task_manager_options_group'); ?>
                <?php do_settings_sections('task-manager-settings'); ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Регистрация настроек плагина и добавление полей.
     */
    public function register_settings() {
        register_setting('task_manager_options_group', 'task_manager_options');

        add_settings_section(
            'task_manager_general_settings_section',          // Идентификатор секции
            'Основные настройки',                             // Заголовок секции
            array( $this, 'general_settings_section_callback' ),    // Callback-метод для отображения содержимого секции
            'task-manager-settings'                           // Идентификатор страницы
        );

        add_settings_field(
            'task_manager_records_per_page',                  // Идентификатор поля
            'Количество записей на странице',                // Заголовок поля
            array( $this, 'records_per_page_callback' ),           // Callback-метод для отображения поля
            'task-manager-settings',                         // Идентификатор страницы
            'task_manager_general_settings_section'           // Идентификатор секции
        );

        add_settings_field(
            'task_manager_delete_table_on_uninstall',                 // Идентификатор поля
            'Удалять таблицу при удалении плагина',                 // Заголовок поля
            array( $this, 'delete_table_on_uninstall_callback' ),         // Callback-метод для отображения поля
            'task-manager-settings',                                // Идентификатор страницы
            'task_manager_general_settings_section'                  // Идентификатор секции
        );
    }

    /**
     * Функция обратного вызова для отображения содержимого секции "Основные настройки".
     */
    public function general_settings_section_callback() {
        echo '<p>' . __('Здесь вы можете настроить основные параметры вашего Личного таск-менеджера.', 'my-task-manager') . '</p>';
    }

    /**
     * Функция обратного вызова для отображения поля "Количество записей на странице".
     */
    public function records_per_page_callback() {
        $options = get_option('task_manager_options');
        $records_per_page = isset($options['records_per_page']) ? $options['records_per_page'] : '';
        echo '<input type="number" name="task_manager_options[records_per_page]" value="' . esc_attr($records_per_page) . '" />';
    }

    /**
     * Функция обратного вызова для отображения поля "Удалять таблицу при удалении плагина".
     */
    public function delete_table_on_uninstall_callback() {
        $options = get_option('task_manager_options');
        $delete_table_on_uninstall = isset($options['delete_table_on_uninstall']) ? $options['delete_table_on_uninstall'] : '';
        echo '<input type="checkbox" name="task_manager_options[delete_table_on_uninstall]" ' . checked($delete_table_on_uninstall, 'on', false) . ' />';
    }
}

// Создаем объект класса для инициализации страницы настроек
new Task_Manager_Settings_Page();
