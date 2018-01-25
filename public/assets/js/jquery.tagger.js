/**
 * Jquery tagger plugin, allows to paste code into input/textarea/richEdits like tinyMCE
 * To get work in richEdit, use body of iframe as element
 */
(function($){
	
	$.fn.tagger = function (textStart, textEnd, replaceSelection) {

		if(replaceSelection == undefined){
			replaceSelection = false;
		}
		
		if(textEnd == undefined || !textEnd){
			textEnd = '';
		}

		if($(this).prop('tagName').toLowerCase() == 'body'){
			return _tagger_frame.apply(this, [textStart, textEnd, replaceSelection]);
		} else {
			return _tagger_input.apply(this, [textStart, textEnd, replaceSelection]);
		}
	}
	
	_tagger_frame = function(textStart, textEnd, replaceSelection){
		// Rich Edit such as TinyMCE
		var frame = _get_frame();
		if(!frame) {
			console.error('Could not get richedit frame');
		}

		textStart = _replace_chars(textStart);

		if(textEnd){
			textEnd = _replace_chars(textEnd);
		}

		var doc = frame.document;
		var selection = frame.getSelection();


		if(replaceSelection){
			doc.execCommand( 'insertHTML', false, textStart + textEnd );
		} else {
			var s = selection.toString();

			if( s.length ) {
				s = _replace_chars(s);
			}

			doc.execCommand( 'insertHTML', false, textStart + s + textEnd );
		}
	};
	
	_tagger_input = function(textStart, textEnd, replaceSelection){
		var el = this[0];
		var begin = el.value.substr( 0, el.selectionStart );
		var selection = el.value.substr( el.selectionStart, el.selectionEnd - el.selectionStart );
		var end = el.value.substr( el.selectionEnd );
		var newCursorPos = el.selectionStart;
		var scrollPos = el.scrollTop;

		if( el.setSelectionRange ) {

			if(replaceSelection){
				var text = textStart + textEnd;
				el.value = begin + text + end;
				el.setSelectionRange( newCursorPos + text.length, newCursorPos + text.length );

			} else {
				if( selection.length == 0 ) {
					var text = textStart + textEnd;
					el.value = begin + text + end;
					el.setSelectionRange( newCursorPos + text.length, newCursorPos + text.length );

				} else {
					el.value = begin + textStart + selection + textEnd + end;
					el.setSelectionRange( newCursorPos + textStart.length + selection.length, newCursorPos + textStart.length + selection.length );
				}
			}

			el.focus();
		}

		el.scrollTop = scrollPos;
	}
	
	_get_frame = function() {
		for( var i = 0; i < frames.length; i++ ) {
			if( typeof( frames[ i ].getSelection().focusNode ) != 'undefined' ) {
				return frames[ i ];
			}
		}
		return false;
	};
	
	_replace_chars = function(string) {
		var re = /[<>&]/g;

		var replace_map = {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;'
		};

		return string.replace(re, function(match) {
			return replace_map[match];
		});
	}

})(jQuery);
