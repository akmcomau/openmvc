function is_number(string) {
	if (/^-?[0-9]+(\.[0-9]+)?$/.test(string)){
		return true;
	}
	return false;
}
function is_integer(string) {
	if (/^-?[0-9]+$/.test(string)){
		return true;
	}
	return false;
}
function is_money(string) {
	if (/^[0-9]+(\.[0-9]{2})?$/.test(string)){
		return true;
	}
	return false;
}
function is_url(string) {
	if (/^(https?:\/\/)?[\da-z\.\-]+\.[a-z\.]{2,6}[#&+_\?\/\w \.\-=]*$/i.test(string)){
		return true;
	}
	return false;
}
function is_date(string) {
	if (/^\s*[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{4}\s*$/i.test(string)){
		return true;
	}
	return false;
}
function is_time(string) {
	if (/^\s*[0-9]{2}:[0-9]{2}\s*$/i.test(string)){
		return true;
	}
	return false;
}
function is_url_fragment(string) {
	if (/[^A-Za-z0-9-_]/i.test(string)){
		return false;
	}
	return true;
}
function is_email(string) {
	if (/^[a-zA-Z][\w\.-]*[a-zA-Z0-9]@[a-zA-Z0-9][\w\.-]*[a-zA-Z0-9]\.[a-zA-Z][a-zA-Z\.]*[a-zA-Z]/.test(string)){
		return true;
	}
	return false;
}
function display_validation_error(form_id, element_name, message) {
	var error = $('#'+form_id+' #'+element_name+'-error');
	error.html(message);
	error.show()
}
function validate_form(form_id, form) {
	var is_valid = true;
	var scroll_position = 999999;
	for(var element_name in form) {
		var is_this_valid = true;
		var element = form[element_name]
		// jquery does not work properly for this next line $('#'+element_name).val();
		var element_value = '';
		if (element.type != 'date_segements') {
			element_value = $('#'+form_id+' input[name="'+element_name+'"]').val();
		}
		var element_error = $('#'+form_id+' #'+element_name+'-error');

		if (typeof(element.required) == 'undefined') {
			element.required = true;
		}

		if (element.type == 'integer') {
			if ((element_value != '' && !is_integer(element_value)) ||
				(element.required && element_value == '')) {

				if (element.message) {
					display_validation_error(form_id, element_name, element.message);
				}
				else {
					display_validation_error(form_id, element_name, "Please enter a valid number");
				}
				is_this_valid = false;
			}
			else element_error.hide();
		}
		else if (element.type == 'date') {
			if ((element_value != '' && !is_date(element_value)) ||
				(element.required && element_value == '')) {
				if (element.message) {
					display_validation_error(form_id, element_name, element.message);
				}
				else {
					display_validation_error(form_id, element_name, "Please enter a valid date");
				}
				is_this_valid = false;
			}
			else element_error.hide();
		}
		else if (element.type == 'time') {
			if (!is_time(element_value)) {
				if (element.message) {
					display_validation_error(form_id, element_name, element.message);
				}
				else {
					display_validation_error(form_id, element_name, "Please enter a valid time");
				}
				is_this_valid = false;
			}
			else element_error.hide();
		}
		else if (element.type == 'money') {
			if (element.zero_allowed && element_value == '') {
				// this is ok
			}
			else if (!is_money(element_value)) {
				if (element.message) {
					display_validation_error(form_id, element_name, element.message);
				}
				else {
					display_validation_error(form_id, element_name, "Please enter a valid value");
				}
				is_this_valid = false;
			}
			else if (parseFloat(element_value) <= 0 && !element.zero_allowed) {
				if (element.message) {
					display_validation_error(form_id, element_name, element.message);
				}
				else {
					display_validation_error(form_id, element_name, "You must enter a value larger than 0");
				}
				is_this_valid = false;
			}
			else element_error.hide();
		}
		else if (element.type == 'string') {
			if (!element.required && element_value == '') {
				// its fine
			}
			else if (element.required && element_value == '') {
				if (element.message) {
					display_validation_error(form_id, element_name, element.message);
				}
				else {
					display_validation_error(form_id, element_name, "This field is required");
				}
				is_this_valid  = false;
			}
			else if (typeof(element.max_length) != 'undefined' && element_value.length > element.max_length) {
				if (element.message) {
					display_validation_error(form_id, element_name, element.message);
				}
				else {
					display_validation_error(form_id, element_name, "This cannot be longer than " + element.max_length + ' characters');
				}
				is_this_valid  = false;
			}
			else if (typeof(element.min_length) != 'undefined' && element_value.length < element.min_length) {
				if (element.message) {
					display_validation_error(form_id, element_name, element.message);
				}
				else {
					display_validation_error(form_id, element_name, "This cannot be shorter than " + element.min_length + ' characters');
				}
				is_this_valid  = false;
			}
			else element_error.hide();
		}
		else if (element.type == 'email') {
			if ((element_value != '' && !is_email(element_value)) ||
				(element.required && element_value == '')) {
				if (element.message) {
					display_validation_error(form_id, element_name, element.message);
				}
				else {
					display_validation_error(form_id, element_name, "Please enter a valid email address");
				}
				is_this_valid = false;
			}
			else element_error.hide();
		}
		else if (element.type == 'url') {
			if ((element_value != '' && !is_url(element_value)) ||
				(element.required && element_value == '')) {
				if (element.message) {
					display_validation_error(form_id, element_name, element.message);
				}
				else {
					display_validation_error(form_id, element_name, "");
				}
				display_validation_error(form_id, element_name, "Please enter a valid URL");
				is_this_valid = false;
			}
			else element_error.hide();
		}
		else if (element.type == 'urlfragment') {
			if ((element_value != '' && !is_url_fragment(element_value)) ||
				(element.required && element_value == '')) {
				if (element.message) {
					display_validation_error(form_id, element_name, element.message);
				}
				else {
					display_validation_error(form_id, element_name, "");
				}
				display_validation_error(form_id, element_name, "<br />Invalid, must only contain dashes, underscores, or A-Z a-z 0-9");
				is_this_valid = false;
			}
			else element_error.hide();
		}
		else if (element.type == 'date_segements') {
			var year = $('#'+element_name+'_year').val();
			var month = $('#'+element_name+'_month').val();
			var day = $('#'+element_name+'_day').val();
			element_value = ""+year+month+day
			if ((element_value != '' && !is_date(day+'/'+month+'/'+year)) ||
				(element.required && element_value == '')) {
				if (element.message) {
					display_validation_error(form_id, element_name, element.message);
				}
				else {
					display_validation_error(form_id, element_name, "");
				}
				display_validation_error(form_id, element_name, "Please enter a valid date");
				is_this_valid = false;
			}
			else element_error.hide();
			element_name = element_name+'_year';
		}

		if (!is_this_valid) {
			is_valid = false;

			$("#"+form_id+" input[name='"+element_name+"']").addClass('form-error-input');

			scroll = $("#"+form_id+" input[name='"+element_name+"']").offset().top-80;
			if (scroll < scroll_position) {
				scroll_position = scroll;
			}
		}
		else {
			$("#"+form_id+" input[name='"+element_name+"']").removeClass('form-error-input');
		}
	}

	if (!is_valid) {
		$('body').animate({ scrollTop: scroll_position }, 500);
	}

	return is_valid;
}
