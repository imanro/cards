var inPlaceEvents = {
    START_EDIT: "startEdit",
    CANCEL_EDIT: "cancelEdit",
    SHOW_CONTROL: "showControl",
    HIDE_CONTROL: "hideControl",
    SHOW_MESSAGE: "showMessage",
    HIDE_MESSAGE: "hideMessage",
    SAVE: "save"
};

var inPlaceMsgType = {
    ERROR: 'error',
    SUCCESS: 'success'
};

/**
 * Jquery Plugin for InPlace widget
 */
(function($){
	$.fn.inplace = function(){
		
		$(document).bind(inPlaceEvents.START_EDIT, function(e){
			$.fn.inplace_start_edit.call(e.target, e);
		});
		$(document).bind(inPlaceEvents.CANCEL_EDIT, function(e){
			$.fn.inplace_cancel_edit.call(e.target, e);
		});
		$(document).bind(inPlaceEvents.SHOW_CONTROL, function(e){
			$.fn.inplace_show_control.call(e.target, e);
		});
		$(document).bind(inPlaceEvents.HIDE_CONTROL, function(e){
			$.fn.inplace_hide_control.call(e.target, e);
		});
		$(document).bind(inPlaceEvents.SHOW_MESSAGE, function(e, type, text){
			$.fn.inplace_show_message.call(e.target, e, type, text);
		});
		$(document).bind(inPlaceEvents.HIDE_MESSAGE, function(e){
			$.fn.inplace_hide_message.call(e.target, e);
		});
		$(document).bind(inPlaceEvents.SAVE, function(e){
			$.fn.inplace_save.call(e.target, e);
		});
		
		$(document).on('click', function(event){ $(event.target).trigger(inPlaceEvents.CANCEL_EDIT); });
		$(document).on('keypress', function(event){
			if(event.which == 27) {
				// sic, not input because we dont handle cancel events from input in click case
				$(document).trigger(inPlaceEvents.CANCEL_EDIT);
			}
		});
		$(this).on('mouseenter', function(event){ $(this).trigger(inPlaceEvents.SHOW_CONTROL)});
		$(this).parent().on('mouseleave', function(event){ $(event.target).trigger(inPlaceEvents.HIDE_CONTROL)});
	};
	
	$.fn.inplace_show_control = function(){
		if($(this).data('has-editor') == true){
			return;
		}
		$(this).trigger(inPlaceEvents.HIDE_CONTROL)

		// show edit button next to input
		var control = $('<span class="in-place-control" />');
		control.html($(this).data('control').replace(/\\/g, ''));
		$(this).parent().append(control);
		
		var element = $(this);
		control.on('click', function(event){
			event.stopPropagation();
			element.trigger(inPlaceEvents.START_EDIT);
		});
		
		if($(this).data('js-callback-show-control')){
			eval($(this).data('js-callback-show-control').replace(/\\/g, ''));
		}
	};
	
	$.fn.inplace_hide_control = function(){
		$(this).parent().find('.in-place-control').remove();
		if($(this).data('js-callback-hide-control')){
			eval($(this).data('js-callback-hide-control').replace(/\\/g, ''));
		}
	};
	
	$.fn.inplace_start_edit = function(){

		$(this).trigger(inPlaceEvents.CANCEL_EDIT);
		$(this).trigger(inPlaceEvents.HIDE_CONTROL);
		
		var element = $(this);

		var content = element.data('value') != undefined? element.data('value') : element.html();
	    var input = $('<' + element.data('input-tag') + '/>');

	    $.each(element.data(), function(key, value){
	    	if(key.match(/^inputAttribute.+/)){
	    		input.attr(key.replace('inputAttribute', '').toLowerCase(), value);
	    	}
	    });
	    
	    element.data('orig-content', content);
		input.val(content);
		element.empty();
		element.append(input);
		element.data('has-editor', true);
		window._inplace_active_editable = element;
		
		input.on('keyup', function(event){
			if(event.which == 13){
				$(this).trigger(inPlaceEvents.SAVE);
			}
		});
		
		if($(this).data('js-callback-start-edit')){
			eval($(this).data('js-callback-start-edit').replace(/\\/g, ''));
		}
	};

	$.fn.inplace_cancel_edit = function(event){
		var element = window._inplace_active_editable;
		if(typeof(element) == 'undefined') {
			return;
		}
		if(typeof(event) == 'undefined' || 
			!$(event.target).is(window._inplace_active_editable) &&
			window._inplace_active_editable.find($(event.target)).length == 0){
			// we're not in editor or its children
			element.empty();
			element.html(element.data('orig-content'));
			element.data('has-editor', false);
			delete window._inplace_active_editable;
		}
		
		if($(this).data('js-callback-cancel-edit')){
			eval($(this).data('js-callback-cancel-edit').replace(/\\/g, ''));
		}
	};
	
	$.fn.inplace_escape = function(string) {
		var entityMap = {
				"&": "&amp;",
				"<": "&lt;",
				">": "&gt;",
				'"': '&quot;',
				"'": '&#39;',
				"/": '&#x2F;'
		};


		return String(string).replace(/[&<>"'\/]/g, function (s) {
			return entityMap[s];
		});
	},
	
		
	$.fn.inplace_unescape = function(string) {
		var entityMap = {
				"&amp;" : "&",
				"&lt;": "<",
				"&gt;": ">",
				'&quot;': '"',
				'&#39;': "'",
				'&#x2F;': "/" 
		};


		return String(string).replace(/&.+?;/g, function (s) {
			return typeof(entityMap[s]) != 'undefined'? entityMap[s] : s; 
		});
	},
	
	$.fn.inplace_save = function(){
		var element = $(this).parent();
		var content = $(this).val();

		content = $.fn.inplace_escape(content);
		
		var ajax_settings = $.extend({
				url: element.data('url'),
				dataType: 'json',
				method: 'post'
			}, element.data('ajax-settings')? $.parseJSON(element.data('ajax-settings').replace(/\\/g, '')): {});
		
		ajax_settings.data = element.data('data')? $.parseJSON(element.data('data').
				replace(/\\/g, '').
				replace('{value}', content)) : {};
		
		$.ajax(ajax_settings).done(function(data) {
			var data = data.data;
			console.log('success: ' + data );
			element.data('value', content);
			element.trigger(inPlaceEvents.SHOW_MESSAGE, [inPlaceMsgType.SUCCESS, 'Saved successfully']);

		}).fail(function(data){
			console.error('request status: ' + data.status);
			var userText = false;
			
			switch(data.status + '') {
				case('500'):
					userText = 'Backend server error';
					break;
				case('403'):
					userText = 'Backend permission denied';
					break;
				default:
					break;
			}
			
			if(typeof(data.responseJSON) != 'undefined' && data.responseJSON.error){
				errorText = data.responseJSON.error;
				userText = errorText;
			} else if(data.responseText){
				errorText = data.responseText;
				//console.error('request failed' + (errorText? ': ' + errorText : ''));
			} else {
				console.error('request failed for unknown reason');
			}
			element.html(element.data('orig-content'));
			element.data('value', element.data('orig-content'));
			
			userText = 'Saving failed' + (userText? ': ' + userText : '');
			element.trigger(inPlaceEvents.SHOW_MESSAGE, [inPlaceMsgType.ERROR, userText]);
			
		}).always(function(){
			;
		});

		
		element.empty();
		element.html(content);
		element.data('has-editor', false);
		
		delete window._inplace_active_editable;
		
		if($(this).data('js-callback-save')){
			eval($(this).data('js-callback-save').replace(/\\/g, ''));
		}
	};
	
	$.fn.inplace_show_message = function(event, type, text){	
		element = $(this);
		var message = $('<div class="in-place-message ' + type + '" />');
		message.html(text);
		element.parent().append(message);
		window.setTimeout(function(){
			message.trigger(inPlaceEvents.HIDE_MESSAGE);;
		}, 3000 );
	};
	
	$.fn.inplace_hide_message = function() {
		$(this).remove();
	};


})(jQuery);
