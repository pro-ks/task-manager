jQuery(function($) {
    // Обработчик сортировки
    $('.sort').on('click', function(e) {
        e.preventDefault();
        var column = $(this).data('column');
        var order_type = 'ASC'; // По умолчанию сортируем по возрастанию
        if ($(this).hasClass('sorted')) {
            // Если колонка уже отсортирована, меняем порядок сортировки
            order_type = $(this).hasClass('asc') ? 'DESC' : 'ASC';
        }
        $('.sort').removeClass('sorted asc desc').filter(this).addClass('sorted ' + order_type.toLowerCase());
        // Вызываем функцию обновления задач с новыми параметрами сортировки
        updateTaskList(column, order_type);
    });

	// Обработчик кнопок пагинации
	$('.pagination').on('click', '.page-item', function() {
	    var page = $(this).data('page');
	    $('.pagination').attr('data-current-page', page); // Обновляем текущую страницу
	    var order_by = $('.sorted').data('column');
	    var order_type = $('.sorted').hasClass('asc') ? 'ASC' : 'DESC';
	    updateTaskList(order_by, order_type, page);
	});

	// Функция для создания кнопок пагинации
	function createPaginationButtons(totalPages) {
	    var paginationContainer = $('.pagination');
	    paginationContainer.empty(); // Очищаем содержимое пагинации 

	    for (var i = 1; i <= totalPages; i++) {
	        var buttonClass = (i === parseInt($('.pagination').attr('data-current-page'))) ? 'page-item active' : 'page-item';
	        var button = $('<li class="' + buttonClass + '" data-page="' + i + '"><a class="page-link" href="#">' + i + '</a></li>');
	        paginationContainer.append(button);
	    }
	}

    // Функция обновления списка задач с учетом сортировки и пагинации
	function updateTaskList(order_by, order_type, page) {
	    $.ajax({
	        url: TaskManager.url,
	        type: 'POST',
	        data: {
	            action: 'display_tasks',
	            order_by: order_by,
	            order_type: order_type,
	            page: page, // передаем номер текущей страницы
	            nonce: TaskManager.nonce // Добавляем nonce
	        },
	        success: function(response) {
	            $('.task-list').html(response);

	            // Получаем общее количество страниц из скрытого поля
	            var totalPages = parseInt($('#total-pages').val());
	            
	            // Устанавливаем номер текущей страницы в атрибуте data-page
	            $('.pagination').attr('data-current-page', page);
	            
	            // Создаем кнопки пагинации
	            createPaginationButtons(totalPages);
	        }
	    });
	}

    // По умолчанию сортируем по дате
    updateTaskList('due_date', 'ASC', 1);

    //сохраняем данные поагинации и сортировки затем обновляем данные страницы
    function save_page_update(){
		var currentPage = $('.pagination').attr('data-current-page');
        var order_by = $('.sorted').data('column');
        var order_type = $('.sorted').hasClass('asc') ? 'ASC' : 'DESC';

        updateTaskList(order_by, order_type, currentPage);
    }

	// AJAX запрос для добавления задачи
	$('#add-task-form').on('submit', function(e) {
	    e.preventDefault();
	    var form_data = $(this).serialize();
	    // Добавляем nonce к данным формы
	    form_data += '&nonce=' + TaskManager.nonce;

	    $.ajax({
	        url: TaskManager.url, // Используем url, переданный через wp_localize_script
	        type: 'POST',
	        data: form_data + '&action=add_task',
	        success: function(response) {
	        	var taskData = response.data;
	            if (response.success) {
	                // Очистка всех полей формы
	                $('#add-task-form')[0].reset();
	                // Отображение сообщения
	                showTaskAddedToast(taskData.task_name, taskData.task_status, taskData.task_color, taskData.task_time);
	                save_page_update();
	            } else {
	                // В случае ошибки отображаем сообщение об ошибке
	                showTaskAddedToast(taskData.task_name, taskData.task_status, taskData.task_color, taskData.task_time);
	            }
	        }
	    });
	});

	function showTaskAddedToast(taskName, taskStatus, taskColor, taskTime) {
	    var toastEl = $('#liveToast');
	    // Установить название задачи и статус
	    toastEl.find('.toast-body').text(taskName);
	    toastEl.find('.me-auto').text(taskStatus);
	    toastEl.find('small').text(taskTime);
	    // Изменить цвет SVG
	    toastEl.find('.toast-header svg rect').attr('fill', taskColor);
	    // Создать объект Toast с опцией autohide: false
	    var toast = new bootstrap.Toast(toastEl.get(0), { autohide: true });
	    // Показать всплывающее сообщение
	    toast.show();
	}

	// AJAX запрос для удаления задачи
	$('.task-list').on('click', '.btn-delete', function() {
	    var task_id = $(this).data('id');
	    if (confirm('Вы уверены, что хотите удалить эту задачу?')) {
	        $.ajax({
	            url: TaskManager.url,
	            type: 'POST',
	            data: {
	                action: 'delete_task',
	                task_id: task_id,
	                nonce: TaskManager.nonce
	            },
	            success: function(response) {
	                var taskData = response.data;
	                save_page_update();
	                showTaskAddedToast(taskData.task_name, taskData.task_status, taskData.task_color, taskData.task_time);
	            }
	        });
	    }
	});

    // AJAX запрос для изменения статуса задачи
    $('.task-list').on('change', 'select[name="status_completed"]', function() {
        var task_id = $(this).data('id');
        var new_status = $(this).val();
        var $row = $(this).closest('tr');
        var data = {
            action: 'complete_task',
            task_id: task_id,
            new_status: new_status,
            nonce: TaskManager.nonce
        };
        $.ajax({
            url: TaskManager.url,
            type: 'POST',
            data: data,
            success: function(response) {
                $row.find('.status-column').html(response);
            }
        });
    });

	// Функция для конвертации строки времени из формата '0000-00-00 00:00:00' в формат 'YYYY-MM-DDTHH:MM'
	function convertToDateTimeLocalFormat(dateTimeString) {
	    // Разбиваем строку на дату и время
	    var parts = dateTimeString.split(' ');
	    var datePart = parts[0];
	    var timePart = parts[1];
	    
	    // Разбиваем дату на год, месяц и день
	    var dateParts = datePart.split('-');
	    var year = dateParts[0];
	    var month = dateParts[1];
	    var day = dateParts[2];
	    
	    // Формируем строку в формате 'YYYY-MM-DDTHH:MM'
	    var dateTimeLocalString = year + '-' + month + '-' + day + 'T' + timePart;
	    
	    return dateTimeLocalString;
	}
	// AJAX запрос для получения информации о задаче для редактирования
	$(document).on('click', '.btn-edit', function() {
	    var task_id = $(this).data('id');
	    var nonce = TaskManager.nonce;
	    $.ajax({
	        url: TaskManager.url,
	        type: 'POST',
	        data: {
	            action: 'get_task',
	            task_id: task_id,
	            nonce: nonce
	        },
	        success: function(response) {
	            var task = JSON.parse(response);

	            // Заполнение формы данными о задаче
	            $('#edit-task-id').val(task.id);
	            $('#edit-task-name').val(task.name);
	            $('#edit-task-description').val(task.description);
	            $('#edit-task-date').val(convertToDateTimeLocalFormat(task.due_date));
	            $("#update-task-form select").val(task.completed);
	            
	            // Открытие модального окна
	            $('#editing-task').modal('show');
	        },
	        error: function(xhr, status, error) {
	            alert("Произошла ошибка при загрузке данных о задаче.");
	            console.error(xhr.responseText);
	        }
	    });
	});

	// AJAX запрос для редактирования задачи
	$('#update-task-form').on('submit', function(e) {
	    e.preventDefault();
	    var form_data = $(this).serialize();
	    var nonce = TaskManager.nonce;
	    $.ajax({
	        url: TaskManager.url,
	        type: 'POST',
	        data: form_data + '&action=update_task&nonce=' + nonce,
	        success: function(response) {
	            if (response.success) {
	                var updated_task = response.data;
	                var $edited_row = $('tr[data-id="' + updated_task.id + '"]');
	                // $edited_row.find('th:eq(0)').text(updated_task.id);
	                $edited_row.find('td:eq(0)').text(updated_task.name);
	                $edited_row.find('td:eq(1)').text(updated_task.description);
	                $edited_row.find('td:eq(2)').text(updated_task.due_date);
	                $edited_row.find('select[name="status_completed"]').val(updated_task.completed);
	                $('#editing-task').modal('hide');
	            } else {
	            	var taskData = response.data;
	                //console.error("Произошла ошибка при обновлении задачи:", response);
	                showTaskAddedToast(taskData.task_name, taskData.task_status, taskData.task_color, taskData.task_time);
	            }
	        },
	        error: function(xhr, status, error) {
	            alert("Произошла ошибка при обновлении задачи.");
	            console.error(xhr.responseText);
	        }
	    });
	});
});
