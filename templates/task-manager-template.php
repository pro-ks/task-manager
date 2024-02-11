<?php
// Проверка на прямой доступ
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Выход, если ABSPATH не определен
}

// Функция для отображения формы добавления задачи
function display_task_form() {
    ob_start();

 	if ( current_user_can('edit_posts') ) : 
 		$task_manager = new Task_Manager();
 	?>
        <!-- Форма добавления задачи -->
        <form id="add-task-form">
            <div class="mb-3">
                <label for="task-name" class="form-label"><?php _e('Название задачи:', 'my-task-manager'); ?></label>
                <input type="text" id="task-name" name="task_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="task-description" class="form-label"><?php _e('Описание задачи:', 'my-task-manager'); ?></label>
                <textarea id="task-description" name="task_description" class="form-control" required></textarea>
            </div>
            <div class="mb-3">
                <label for="task-date" class="form-label"><?php _e('Дата и время:', 'my-task-manager'); ?></label>
                <input type="datetime-local" id="task-date" min="2018-06-07T00:00" name="task_date" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="status_completed-new" class="form-label"><?php _e('Статус:', 'my-task-manager'); ?></label>
                <?php echo $task_manager->status_completed_task(0, 0, 'new'); ?>
            </div>
            <button type="submit" class="btn btn-primary"><?php _e('Добавить задачу', 'my-task-manager'); ?></button>
        </form>
    <?php endif; 

    return ob_get_clean();
}

// Функция для отображения списка задач
function display_task_list() {
    ob_start();
    ?>
    <div class="task-manager table-responsive-xll">
        <!-- Список задач -->
        <table class="table table-striped table-hover ">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col"><a href="#" class="sort" data-column="name"><?php _e('Название задачи:', 'my-task-manager'); ?></a></th>
                    <th scope="col"><a href="#" class="sort" data-column="description"><?php _e('Описание задачи', 'my-task-manager'); ?></a></th>
                    <th scope="col"><a href="#" class="sort" data-column="due_date"><?php _e('Дата и время', 'my-task-manager'); ?></a></th>
                    <th scope="col"><a href="#" class="sort" data-column="completed"><?php _e('Статус', 'my-task-manager'); ?></a></th>
                    <?php if ( current_user_can('edit_posts') ) : ?>
	                    <th scope="col"><?php _e('Действия', 'my-task-manager'); ?></th>
	                <?php endif; ?>
                </tr>
            </thead>
            <tbody class="task-list">
            </tbody>
        </table>
        <nav aria-label="...">
          <ul class="pagination pagination-sm" data-current-page="1">
            <li class="page-item active" aria-current="page">
              <span class="page-link">1</span>
            </li>
          </ul>
        </nav>
	</div>
    <?php
    return ob_get_clean();
}

// Функция для отображения модального окна редактирования задачи
function display_edit_modal() {
    ob_start();

    if ( current_user_can('edit_posts') ) : 
    	$task_manager = new Task_Manager();
    ?>
	<!-- Модальное окно -->
	    <div class="modal fade" id="editing-task" tabindex="-1" aria-labelledby="editing-taskLabel" aria-hidden="true">
	        <div class="modal-dialog">
	            <form class="modal-content" id="update-task-form">
	                <div class="modal-header">
	                    <h5 class="modal-title" id="editing-taskLabel"><?php _e('Редактирование задачи', 'my-task-manager'); ?></h5>
	                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php _e('Закрыть', 'my-task-manager'); ?>"></button>
	                </div>
	                <div class="modal-body">
	                    <input type="hidden" id="edit-task-id" name="edit_task_id">
	                    <div class="mb-3">
	                        <label for="edit-task-name" class="form-label"><?php _e('Название задачи:', 'my-task-manager'); ?></label>
	                        <input type="text" id="edit-task-name" name="edit_task_name" class="form-control" required>
	                    </div>
	                    <div class="mb-3">
	                        <label for="edit-task-description" class="form-label"><?php _e('Описание задачи:', 'my-task-manager'); ?></label>
	                        <textarea id="edit-task-description" name="edit_task_description" class="form-control" required></textarea>
	                    </div>
	                    <div class="mb-3">
	                        <label for="edit-task-date" class="form-label"><?php _e('Дата и время:', 'my-task-manager'); ?></label>
	                        <input type="datetime-local" id="edit-task-date" name="edit_task_date" class="form-control" required min="2018-06-07T00:00">
	                    </div>
	                    <div class="mb-3">
	                        <label for="status_completed-edit" class="form-label"><?php _e('Статус:', 'my-task-manager'); ?></label>
	                        <?php echo $task_manager->status_completed_task(0, 0, 'edit'); ?>
	                    </div>
	                </div>
	                <div class="modal-footer">
	                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php _e('Закрыть', 'my-task-manager'); ?></button>
	                    <button type="submit" class="btn btn-primary"><?php _e('Сохранить изменения', 'my-task-manager'); ?></button>
	                </div>
	            </form>
	        </div>
	    </div>
	    <!-- Уведомление -->
		<div class="toast-container position-fixed bottom-0 end-0 p-3">
			<div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
				<div class="toast-header">
					<svg class="bd-placeholder-img rounded me-2" width="20" height="20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" preserveAspectRatio="xMidYMid slice" focusable="false">
						<rect width="100%" height="100%" fill="green"></rect>
					</svg>
				  <strong class="me-auto"></strong>
				  <small></small>
				  <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="<?php _e('Закрыть', 'my-task-manager'); ?>"></button>
				</div>
				<div class="toast-body"></div>
			</div>
		</div>
    <?php endif; 
    return ob_get_clean();
}