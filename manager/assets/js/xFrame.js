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
	$(target).toggleClass('xFrame-loadable', true);
	$.ajax(url, {
		data: data,							// append form data
		dataType: 'json',
		cache: false,
		method: 'POST',
		error: function(el, status, err){
			console.log(status, err);
			var callback = $(item).attr('data-error');
			if(callback){
				window[ callback ](data, status);
			} else {
				
			}
			$(target).toggleClass('xFrame-loadable', false);
		},
		success : function(data, status, el){
			console.log(data, status);
			var callback = $(item).attr('data-success');
			for(var key in data[0]){
				$(key).html(data[0][key]);
			}
			if(!callback || typeof window[ callback ] != 'function' || window[ callback ](data, status)){
				
			}
			$(target).toggleClass('xFrame-loadable', false);
		}
	});
};

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

var setEditorUpdate = function(data, status){
	$('#manager-editor input, #manager-editor select, #manager-editor textarea').each(function(i, el){
		var name = $(el).attr('name');
		$(el).attr('data-form', "#manager-editor [name='" + name + "']");
		$(el).attr('data-url', "execute/model:editor/action:updateFields/id:" + name.split('_')[2]);
		$(el).attr('data-success', 'fieldError');
		$(el).on('change', function(){
			fetch(i, this);
		});
	});
}

var fieldError = function(data, status){
	console.log(data[0]);
}