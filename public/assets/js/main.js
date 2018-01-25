(function ($) {
	$(document).ready(function() {
		window.setTimeout(function(){
			$('.alert.alert-dismissible').alert('close');
		}, 4000);

		// Handling of template-variable-labels in mail templates
		$('.mail-template .template-variable-label').on('click', function(e){

			var newText = $(this).text();

			if($('#mail-template-edit-body_ifr').length > 0){
				var el = $('#mail-template-edit-body_ifr').contents().find('#tinymce');
			} else {
				var el = $('#mail-template-edit-body');
			}

			el.tagger(newText, false, true);
		});

		// Handling of template-variable-labels in tasks
		$('.task-message .template-variable-label').on('click', function(e){

			var newText = $(this).text();

			if($('#task-message-edit-mail_body_ifr').length > 0){
				var el = $('#task-message-edit-mail_body_ifr').contents().find('#tinymce');
			} else {
				var el = $('#task-message-edit-mail_body');
			}

			el.tagger(newText, false, true);

			var textarea = $('#task-message-edit-mail_body');

			if(tinymce.activeEditor){
				textarea.val(tinymce.activeEditor.getContent());
			}
			textarea.trigger('keyup');
		});


		$('.switch-editor').on('click', function(e){
			var el = $(this);

			switch(el.val()){
				case('simple'): default:
					tinymce.remove(
						el.data('selector')
					);

				break;
				case('rich'):
					tinymce.init({
						selector: el.data('selector'),
						setup: function(ed) {
							ed.on('keyup, change', function(e) {
								var textarea = $(el.data('selector'));
								if(textarea.length){
									textarea.val(ed.getContent());
									textarea.trigger('keyup');
								}
							});
						}

					});
				break;
			}
		});

		var n = new Date();
		$('.exec-date').datetimepicker({
			locale: 'ru',
			format: 'YYYY-MM-DD',
			useCurrent: false,
			defaultDate: moment([n.getFullYear(), n.getMonth(), n.getDate(), 0, 0]).add(1, 'day'),
			minDate: moment([n.getFullYear(), n.getMonth(), n.getDate(), 0, 0]).add(1, 'day'),
		});

		$('#mail_template_id').on('change', function(){
			change_task_message_text($(this));
		});

		change_task_message_text = function(templateInput)
		{
			if(!$('#mail_template_id').length || !$('#task-message-edit-mail_body').length){
				// wrong page
				return;
			}

			if(templateInput == undefined){
				var templateInput = $('#mail_template_id');
				changed = false;
			} else {
				changed = true;
			}

			empty = $('#task-message-edit-mail_subject').val() == '' && $('#task-message-edit-mail_body').val() == '';

			// take request
			if(!changed && !empty){
				return;
			}

			confirmed = true;

			if(!empty){
				confirmed = window.confirm('Вы хотите стереть предыдущее содержимое?');
			}

			if(confirmed){
				spin = $('<span id="spin-load" class="glyphicon glyphicon-refresh glyphicon-spin"></span>');
				$('#mail_template_id').parent().find('#spin-load').remove();
				$('#mail_template_id').after(spin);

				$.ajax({
					url: "/mail-template/get-ajax/" + templateInput.val(),
					dataType: 'json'
				}).done(function(data) {
					var data = data.data;
					$('#task-message-edit-mail_subject').val(data.MailTemplate.subject);

					if(tinyMCE.activeEditor){
						tinyMCE.activeEditor.setContent(data.MailTemplate.body);
					} else {
						$('#task-message-edit-mail_body').val(data.MailTemplate.body);
					}

					update_task_message_preview();

				}).fail(function(data){
					if(typeof(data.responseJSON) != 'undefined' && data.responseJSON.error){
						errorText = data.responseJSON.error;
					} else {
						errorText = 'Unknown error, probably not authorized';
					}
					console.error('request failed' + (errorText? ': ' + errorText : ''));
				}).always(function(){
					spin.remove();
				});
			}
		}

		change_task_message_text();

		$('#mail_settings_template').on('change', function(){
			change_mail_settings($(this));
		});

		change_mail_settings = function(templateInput)
		{
			if(!$('#mail_settings_template').length){
				// wrong page
				return;
			}

			if(templateInput == undefined){
				var templateInput = $('#mail_settings_template');
			}

			var name = templateInput.val();

			var form = templateInput.closest('form');

			if(name){
				spin = $('<span id="spin-load" class="glyphicon glyphicon-refresh glyphicon-spin"></span>');

				$('#mail_settings_template').parent().find('#spin-load').remove();

				$('#mail_settings_template').parent().remove('#spin-load');
				$('#mail_settings_template').after(spin);
				$.ajax({
					url: "/mail-settings-template/get-ajax/" + name,
					dataType: 'json'
				}).done(function(data) {
					var data = data.data;

					var inputNameTpl = form.attr('id') + '\\[mail_%name%\\]';

					$.each(data.MailSettingsTemplate, function(key, value){
						var input = $('*[name=' + inputNameTpl.replace('%name%', key) + ']');

						if(input.attr('type').toLowerCase() == 'checkbox'){
							if(value){
								input.prop('checked', true);
							} else {
								input.prop('checked', false);
							}
						} else {
							input.val(value);
						}
					});

				}).fail(function(data){
					if(typeof(data.responseJSON) != 'undefined' && data.responseJSON.error){
						errorText = data.responseJSON.error;
					} else {
						errorText = 'Unknown error, probably not authorized';
					}
					console.error('request failed' + (errorText? ': ' + errorText : ''));
				}).always(function(){
					spin.remove();
				});

			}
		}

		process_template_variables = function(string, map, data) {
			$.each(map, function(name, row){
				$.each(row, function (index, variable) {
					if (typeof(data[name]) !== 'undefined' && data[name] !== null && data[name].length) {
						var re = new RegExp(variable, 'gi');
						string = string.replace(re, data[name]);
					}
				});
			})
			return string;
		}

		update_task_message_preview = function()
		{
			if(!$('#task-message-edit-mail_subject').length && !$('#task-message-edit-mail_body').length) {
				// wrong page
				return;
			}
			var subject = $('#task-message-edit-mail_subject').val();
			var body = $('#task-message-edit-mail_body').val();

			var preview_subject = $('#task-message-preview .subject .content');
			var preview_body = $('#task-message-preview .body .content')

			if(!subject.length) {
				preview_subject.parent().hide();
			} else {
				preview_subject.parent().show();
			}
			if(!body.length) {
				preview_body.parent().hide();
			} else {
				preview_body.parent().show();
			}

			if(typeof('template_variables_data') != 'undefined' && typeof('template_variables_map') != 'undefined'){
				template_variables_data['recipient_name'] = $('#task-message-edit-recipient_name').val();

				$.each(template_variables_map, function(name, row){
					var source = $('#task-message-edit-' + name);
					if(source.length) {
						template_variables_data[name] = source.val();
					}
				});
				subject = process_template_variables(subject, template_variables_map, template_variables_data);
				body = process_template_variables(body, template_variables_map, template_variables_data);
			}

			//alert(subject.length);
			preview_subject.html(subject);
			preview_body.html(body);
		}

		update_task_message_preview();
		$('#task-message-edit input, #task-message-edit textarea').on('keyup', function(){
			update_task_message_preview();
		});

		// Firefox fix for password autofill
		window.setTimeout(function(){
		var el = $('#user-edit-password');
			if( el.length > 0) {
				el.val('');
			}
		}, 100);

		$('.in-place').inplace();

		$('.filter-date').datetimepicker({
			locale: 'ru',
			format: 'YYYY-MM-DD',
			useCurrent: false
		});

		$('#filter-user_name').typeahead({
			source: function(query, process){
				spin = $('<span id="spin-load" class="glyphicon glyphicon-refresh glyphicon-spin"></span>');
				$('#filter-user_name').parent().find('#spin-load').remove();
				$('#filter-user_name').after(spin);

				$.ajax({
					url: "/user/search-ajax/" + query,
					dataType: 'json'
				}).done(function(data) {
					var data = data.data.User;

					var prepared = [];
					$.each(data,function(key,value){
						prepared.push({'id': value.id, 'name': value.email})
					});
					process(prepared);

				}).fail(function(data){
					if(typeof(data.responseJSON) != 'undefined' && data.responseJSON.error){
						errorText = data.responseJSON.error;
					} else {
						errorText = 'Unknown error, probably not authorized'
					}
					console.error('request failed' + (errorText? ': ' + errorText : ''));
				}).always(function(){
					spin.remove();
				});

			},
			autoSelect: true}
		);

		$('#filter-user_name').on('keyup', function(){
			$('#filter-user_id').val('');
		});

		$('#filter-user_name').on('change', function(){
			var current = $(this).typeahead("getActive");

			if(current && typeof(current.id) != 'undefined'){
				$('#filter-user_id').val(current.id);
			}
		});
	});




}(jQuery));