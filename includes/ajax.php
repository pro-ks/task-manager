<?php
// Проверка на прямой доступ
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Выход, если ABSPATH не определен
}

/**
 * Класс Task_Manager - класс для управления задачами.
 */
class Task_Manager {

    public function __construct() {
        add_action('wp_ajax_display_tasks', array($this, 'display_tasks_callback'));
        add_action('wp_ajax_nopriv_display_tasks', array($this, 'display_tasks_callback'));
        add_action('wp_ajax_add_task', array($this, 'add_task_callback'));
        add_action('wp_ajax_get_task', array($this, 'get_task_callback'));
        add_action('wp_ajax_update_task', array($this, 'update_task_callback'));
        add_action('wp_ajax_complete_task', array($this, 'complete_task_callback'));
        add_action('wp_ajax_delete_task', array($this, 'delete_task_callback'));
    }
    /**
     * Приватный метод для проверки доступа пользователя к выполнению AJAX-запросов.
     * Проверяет, имеет ли текущий пользователь права на редактирование записей
     * и соответствует ли переданный ключ безопасности AJAX-запросу.
     */
    private function check_user_access() {
        if (!current_user_can('edit_posts') || !check_ajax_referer('task-manager-nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'task_status' => __('Ошибка', 'my-task-manager'),
                'task_name' => __('Недосточно прав для выполнения этого действия или неверный код безопасности.', 'my-task-manager'),
                'task_color' => 'red',
                'task_time' => current_time('mysql')
            ));
        }
    }
    /**
     * Обработчик AJAX-запроса для отображения списка задач.
     * Отображает список задач с учетом параметров сортировки и пагинации.
     */
    public function display_tasks_callback() {
        // $this->check_user_access();
        global $wpdb;
        $table_name = $wpdb->prefix . 'tasks';
        $order_by = isset($_POST['order_by']) ? $_POST['order_by'] : 'due_date';
        $order_type = isset($_POST['order_type']) ? $_POST['order_type'] : 'ASC';
        check_ajax_referer('task-manager-nonce', 'nonce');

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $options = get_option('task_manager_options');
        $per_page = isset($options['records_per_page']) && !empty($options['records_per_page']) ? intval($options['records_per_page']) : 4; // Получаем количество записей на странице из опций
        $offset = ($page - 1) * $per_page;

        $allowed_columns = array('name', 'description', 'due_date', 'completed');
        $order_by = in_array($order_by, $allowed_columns) ? $order_by : 'due_date';
        $order_type = strtoupper($order_type) === 'DESC' ? 'DESC' : 'ASC';

        $total_tasks = $this->count_total_tasks();
        $total_pages = ceil($total_tasks / $per_page);

        $sql = "SELECT * FROM $table_name ORDER BY $order_by $order_type LIMIT $per_page OFFSET $offset";
        $tasks = $wpdb->get_results($sql, ARRAY_A);

        foreach ($tasks as $task) {
            echo $this->table_template($task['id'], $task['name'], $task['description'], $task['due_date'], $task['completed']);
        }

        echo '<input type="hidden" id="total-pages" value="' . $total_pages . '">';

        wp_die();
    }

    /**
     * Обработчик AJAX-запроса для добавления новой задачи.
     * Позволяет пользователю добавить новую задачу и сохраняет ее в базе данных.
     */
    public function add_task_callback() {
        $this->check_user_access();

        global $wpdb;
        $table_name = $wpdb->prefix . 'tasks';
        $task_name = sanitize_text_field($_POST['task_name']);
        $task_description = sanitize_textarea_field(esc_html($_POST['task_description']));
        $task_date = sanitize_text_field($_POST['task_date']);
        $status_completed = intval($_POST['status_completed']);
        $user_id = get_current_user_id(); // Получаем ID текущего пользователя

        if (empty($task_name) || empty($task_description) || empty($task_date)) {
            wp_send_json_error(array(
                'task_status' => __('Ошибка', 'my-task-manager'),
                'task_name' => __('Пожалуйста, заполните все поля', 'my-task-manager'),
                'task_color' => 'red',
                'task_time' => current_time('mysql')
            ));
        }

        $current_time = current_time('mysql');
        if (strtotime($task_date) <= strtotime($current_time)) {
            wp_send_json_error(array(
                'task_status' => __('Ошибка', 'my-task-manager'),
                'task_name' => __('Дата задачи должна быть в будущем', 'my-task-manager'),
                'task_color' => 'red',
                'task_time' => current_time('mysql')
            ));
        }

        $result = $wpdb->insert($table_name, array(
            'name' => $task_name,
            'description' => $task_description,
            'due_date' => $task_date,
            'completed' => $status_completed,
            'user_id' => $user_id, // Сохраняем ID пользователя
        ), array('%s', '%s', '%s', '%d', '%d'));

        if ($result) {
            wp_send_json_success(array(
                'task_status' => __('Добавлена', 'my-task-manager'),
                'task_name' => $task_name,
                'task_color' => 'green',
                'task_time' => current_time('mysql')
            ));
        } else {
            wp_send_json_error(array(
                'task_status' => __('Ошибка при добавлении', 'my-task-manager'),
                'task_name' => $task_name,
                'task_color' => 'red',
                'task_time' => current_time('mysql')
            ));
        }
    }
    /**
     * Обработчик AJAX-запроса для получения информации о задаче по ее ID.
     * Получает данные о задаче из базы данных и возвращает их в формате JSON.
     */
    public function get_task_callback() {
        $this->check_user_access();

        global $wpdb;
        $table_name = $wpdb->prefix . 'tasks';
        $task_id = intval($_POST['task_id']);
        $task = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $task_id", ARRAY_A);
        echo json_encode($task);
        wp_die();
    }

    /**
     * Обработчик AJAX-запроса для обновления существующей задачи.
     * Позволяет пользователю обновить информацию о задаче и сохраняет изменения в базе данных.
     */
    public function update_task_callback() {
        $this->check_user_access();

        global $wpdb;
        $table_name = $wpdb->prefix . 'tasks';
        $task_id = intval($_POST['edit_task_id']);
        $task_name = sanitize_text_field($_POST['edit_task_name']);
        $task_description = sanitize_textarea_field(esc_html($_POST['edit_task_description']));
        $task_datetime = sanitize_text_field($_POST['edit_task_date']);
        $status_completed = intval($_POST['status_completed']);
        $user_id = get_current_user_id(); // Получаем ID текущего пользователя

        if (empty($task_name) || empty($task_description) || empty($task_datetime)) {
            wp_send_json_error(array(
                'task_status' => __('Ошибка', 'my-task-manager'),
                'task_name' => __('Пожалуйста, заполните все поля', 'my-task-manager'),
                'task_color' => 'red',
                'task_time' => current_time('mysql')
            ));
        }

        $current_time = current_time('mysql');
        if (strtotime($task_datetime) <= strtotime($current_time)) {
            wp_send_json_error(array(
                'task_status' => __('Ошибка', 'my-task-manager'),
                'task_name' => __('Дата задачи должна быть в будущем', 'my-task-manager'),
                'task_color' => 'red',
                'task_time' => current_time('mysql')
            ));
        }

        $wpdb->update($table_name, array(
            'name' => $task_name,
            'description' => $task_description,
            'due_date' => $task_datetime,
            'completed' => $status_completed,
            'user_id' => $user_id, // Обновляем ID пользователя
            'completion_date' => $current_time
        ), array('id' => $task_id), array('%s', '%s', '%s', '%d', '%d', '%s'), array('%d'));

        $updated_task = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $task_id", ARRAY_A);
        wp_send_json_success($updated_task);
    }

    /**
     * Обработчик AJAX-запроса для изменения статуса задачи на выполненный или не выполненный.
     * Позволяет пользователю изменить статус задачи и сохраняет изменения в базе данных.
     */
    public function complete_task_callback() {
        $this->check_user_access();

        global $wpdb;
        $table_name = $wpdb->prefix . 'tasks';
        $task_id = intval($_POST['task_id']);
        $new_status = intval($_POST['new_status']);
        $current_time = current_time('mysql');
        $user_id = get_current_user_id(); // Получаем ID текущего пользователя
        $wpdb->update($table_name,
            array('completed' => $new_status,'user_id' => $user_id, 'completion_date' =>$current_time), array('id' => $task_id), array('%d', '%d', '%s'), array('%d'));

        $task = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $task_id", ARRAY_A);
        wp_send_json_success($this->status_completed_task($task['id'], $task['completed']));
    }

    /**
     * Обработчик AJAX-запроса для удаления задачи по ее ID.
     * Позволяет пользователю удалить задачу из базы данных.
     */
    public function delete_task_callback() {
        $this->check_user_access();

        global $wpdb;
        $table_name = $wpdb->prefix . 'tasks';
        $task_id = isset($_POST['task_id']) ? intval($_POST['task_id']) : 0;

        if ($task_id) {
            $wpdb->delete($table_name, array('id' => $task_id), array('%d'));

            wp_send_json_success(array(
                'task_status' => __('Удалено', 'my-task-manager'),
                'task_name' => sprintf(__('Задача с id "%s" успешно удалена', 'my-task-manager'), $task_id),
                'task_color' => 'green',
                'task_time' => current_time('mysql')
            ));
        } else {
            wp_send_json_error(array(
                'task_status' => __('Ошибка', 'my-task-manager'),
                'task_name' => __('ID задачи не указан', 'my-task-manager'),
                'task_color' => 'red',
                'task_time' => current_time('mysql')
            ));
        }
    }

    /**
     * Приватный метод для подсчета общего количества задач в базе данных.
     * Использует кэширование для повышения производительности.
     *
     * @return int Общее количество задач.
     */
	private function count_total_tasks() {
	    $cache_key = 'total_tasks_count_min'; // Уникальный ключ для кэширования
	    $cached_count = get_transient( $cache_key ); // Получаем закэшированное значение

	    if ( false === $cached_count ) {
	        // Если в кэше нет значения, выполняем запрос к базе данных

	        global $wpdb;
	        $table_name = $wpdb->prefix . 'tasks';
	        $total_tasks = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

	        // Кэшируем результат на 1 минуту
	        set_transient( $cache_key, $total_tasks, MINUTE_IN_SECONDS );
	    } else {
	        // Используем закэшированное значение
	        $total_tasks = $cached_count;
	    }

	    return $total_tasks;
	}
	
    /**
     * Приватный метод для генерации строки HTML-кода для одной задачи в таблице.
     *
     * @param int    $id           ID задачи.
     * @param string $name         Название задачи.
     * @param string $description  Описание задачи.
     * @param string $due_date     Дата и время выполнения задачи.
     * @param int    $completed    Статус выполнения задачи.
     * @return string HTML-код строки таблицы для задачи.
     */
    private function table_template($id, $name, $description, $due_date, $completed) {
	    static $counter = 1; // начальное значение счетчика

	    $html = '<tr data-id="' . esc_attr($id) . '">';
	    $html .= '<th scope="row">' . esc_html($counter++) . '</th>';
	    $html .= '<td>' . esc_html($name) . '</td>';
	    $html .= '<td>' . esc_html($description) . '</td>';
	    $html .= '<td>' . date_i18n('j F Y, H:i', strtotime($due_date)) . '</td>';
	    $html .= '<td>' . $this->status_completed_task($id, $completed,$id) . '</td>';

	    // Проверяем, авторизован ли пользователь
	    if ( current_user_can('edit_posts') ) {
	    // Если пользователь авторизован, отображаем кнопки редактирования и удаления
	    	$html .= '<td>';
	        $html .= '<button class="btn btn-primary btn-edit" data-id="' . esc_attr($id) . '"> ' . __('Редактировать', 'my-task-manager') . '</button> ';
	        $html .= '<button class="btn btn-danger btn-delete" data-id="' . esc_attr($id) . '">' . __('Удалить', 'my-task-manager') . '</button>';
	    	$html .= '</td>';
	    }
	    $html .= '</tr>';

	    return $html;
    }

    /**
     * Публичный метод для отображения выпадающего списка статусов задачи.
     * Используется в шаблоне задач для выбора статуса задачи.
     *
     * @param int    $task_id       ID задачи.
     * @param int    $current_status Текущий статус задачи.
     * @param string $cl            Класс для идентификации элемента (опционально).
     * @return string HTML-код выпадающего списка статусов задачи.
     */
    public function status_completed_task($task_id, $current_status, $cl = 0) {
	    $status_completed = array(
	        0 => __('В ожидании', 'my-task-manager'),
	        1 => __('В работе', 'my-task-manager'),
	        2 => __('Выполнен', 'my-task-manager')
	    );

	    $html = '<select size="1" name="status_completed" id="status_completed-' . $cl . '" class="form-select" data-id="' . $task_id . '"';
	    $html .= current_user_can('edit_posts') ? '>' : ' disabled readonly>';

	    foreach ($status_completed as $key => $value) {
	        $selected = selected( $current_status, $key, false );
	        $html .= '<option value="' . esc_attr($key) . '" ' . $selected . '>' . esc_html($value) . '</option>';
	    }

	    $html .= '</select>';

	    return $html;
    }
}

new Task_Manager();

/*
if( WP_DEBUG && WP_DEBUG_DISPLAY && (defined('DOING_AJAX') && DOING_AJAX) ){
	@ ini_set( 'display_errors', 1 );
}
*/