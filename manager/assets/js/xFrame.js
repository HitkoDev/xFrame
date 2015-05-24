$(function(){
	fetchAll();
});

var fetch = function(i, item, target){
	if(target == undefined) target = item;
	var url = $(item).attr('data-url');		// target url
	var form = $(item).attr('data-form');	// form to be appended
	var data = '';
	if(form){
		data = $(form).serialize();	// if form is specified, fetch input
		var name = $(item).attr('name');
		if(data == '') data = name + '=';
	}
	// $(target).toggleClass('xFrame-loadable', true);
	// $.ajax(url, {
		// data: data,							// append form data
		// dataType: 'json',
		// cache: false,
		// method: 'POST',
		// error: function(el, status, err){
			// console.log(status, err);
			// var callback = $(item).attr('data-error');
			// if(callback){
				// window[ callback ](data, status);
			// } else {
				
			// }
			// $(target).toggleClass('xFrame-loadable', false);
		// },
		// success : function(data, status, el){
			// console.log(data, status);
			// var callback = $(item).attr('data-success');
			// var updated = false;
			// for(var key in data[0]){
				// $(key).html(data[0][key]);
				// updated = true;
			// }
			// if(!callback || typeof window[ callback ] != 'function' || window[ callback ](data, status, updated)){
				
			// }
			// $(target).toggleClass('xFrame-loadable', false);
		// }
	// });
	fetchContent(url, target, data, $(item).attr('data-success'), $(item).attr('data-error'));
};

var fetchContent = function(url, target, data, succ, err){
	$(target).toggleClass('xFrame-loadable', true);
	$.ajax(url, {
		data: data,							// append form data
		dataType: 'json',
		cache: false,
		method: 'POST',
		error: function(el, status, err){
			console.log(status, err);
			if(typeof window[err] == 'function' &&window[err](data, status)){
				
			} else {
				
			}
			$(target).toggleClass('xFrame-loadable', false);
		},
		success : function(data, status, el){
			console.log(data, status);
			var updated = false;
			for(var key in data[0]){
				$(key).html(data[0][key]);
				updated = true;
			}
			if(typeof window[succ] == 'function' && window[succ](data, status, updated)){
				
			}
			$(target).toggleClass('xFrame-loadable', false);
		}
	});
}

var fetchAll = function(){
	$('.xFrame-loadable').each(fetch);
};

var loginSuccess = function(data, status){
	var message = $('#login-response');
	if(data[0]['success']){
		message.toggleClass('warning', false);
		message.toggleClass('success', true);
		message.html(data[0]['message']);
		$('#login-form').css('display', 'none');
		fetch(0, $('#main-container'));
	} else {
		message.toggleClass('warning', true);
		message.toggleClass('success', false);
		message.html(data[0]['message']);
	}
};

var loginError = function(data, status){
	
};

var toggleTab = function(tab){
	$(tab).parent().find('.active').toggleClass('active');
	$(tab).toggleClass('active');
};

var initEditor = function(func){
	if(typeof tinymce == 'undefined'){
		$.getScript('assets/tinymce/tinymce.min.js', function(script, status, el){
			tinymce.baseURL = 'assets/tinymce/';
			func();
		});
	} else {
		func();
	}
};

var toggleEditor = function(editor, button){
	if($(button).is(':checked')){
		tinymce.get(editor).show();
	} else {
		tinymce.get(editor).hide();
	}
};

var setEditorUpdate = function(data, status, updated){
	if(!updated) return;
	$('#manager-editor input, #manager-editor select, #manager-editor textarea').each(function(i, el){
		var name = $(el).attr('name');
		if(typeof name != 'undefined'){
			$(el).attr('data-form', "#manager-editor [name='" + name + "']");
			$(el).attr('data-url', "execute/model:editor/action:updateFields/id:" + name.split('_')[2]);
			$(el).attr('data-success', 'fieldError');
			$(el).on('change', function(){
				$('#editor-save, #editor-discard').toggleClass('disabled', false);
				fetch(i, this);
			});
		}
	});
	$('#editor-form .editor-stortable').each(function(i, el){
		Sortable.create(el, {
			handle: '.field-move',
			animation: 150,
			group: 'editor',
			onStart: function(evt){
				$('#editor-form .editor-stortable').toggleClass('sorting', true);
			},
			onEnd: function(evt){
				$('#editor-form .editor-stortable').toggleClass('sorting', false);
				$('#editor-save, #editor-discard').toggleClass('disabled', false);
				editorReorder();
			},
		});
	});
	$("#manager-editor [name^='main_identifier']").on('change', function(){
		$('#editor-title').html($(this).val());
		$('#page-title').html($(this).val() + ' - xFrame manager');
	});
}

var editorReorder = function(){
	var obj = {};
	$('#editor-form .editor-stortable>*').each(function(i, el){
		var gr = $(el).parent().attr('data-group');
		if(typeof obj[gr] == 'undefined') obj[gr] = [];
		obj[gr].push($(el).attr('data-name'));
	});
	var data = {};
	for(var group in obj){
		for(var index in obj[group]){
			data[ obj[group][index] ] = [group, index];
		}
	}
	var cls = $('#editor-data').attr('data-class');
	var id = $('#editor-data').attr('data-id');
	var tab = $('#editor-data').attr('data-tab');
	var set = $('#editor-data').attr('data-set');
	var url = 'execute/model:parser/action:parse/item:function.Order fields/class:' + cls + '/id:' + id + '/tab:' + tab + '/set:' + set;
	fetchContent(url, $('#editor-form'), data, 'setEditorUpdate', '');
}

var toggleActive = function(el, on){
	if(typeof on == 'undefined'){
		$(el).siblings().toggleClass('active', false);
		$(el).toggleClass('active', true);
	} else {
		$(el).toggleClass('active', on);
	}
	$('.navbar-collapse').collapse('hide');
}

var collapseSidebar = function(){
	if (window.matchMedia('(max-width: 767px)').matches){
		$('#sidebar-content .panel-collapse').collapse('hide');
	}
}

var fieldError = function(data, status, updated){
	if(updated) setEditorUpdate(data, status, updated);
	console.log(data[0]);
}

var showOverlay = function(){
	$('#overlay').toggleClass('visible', true);
}

var hideOverlay = function(){
	$('#overlay').toggleClass('visible', false);
}