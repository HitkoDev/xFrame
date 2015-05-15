$(function(){
	fetchAll();
});

var fetch = function(i, item, target){
	if(target == undefined) target = item;
	var url = $(item).attr('data-url');		// target url
	var form = $(item).attr('data-form');	// form to be appended
	var data = '';
	if(form) data = $(form).serialize();	// if form is specified, fetch input
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
			if(callback){
				window[ callback ](data, status);
			} else {
				for(var key in data[0]){
					$(key).html(data[0][key]);
				}
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
}