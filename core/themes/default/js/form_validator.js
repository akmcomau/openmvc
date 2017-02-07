var FormValidator = {};
$.extend(FormValidator, {
	_forms: [],
	_validators: [],
	_disables: [],

	isFloat: function (string) {
		if (/^-?[0-9]+(\.[0-9]+)?$/.test(string)){
			return true;
		}
		return false;
	},
	isInteger: function (string) {
		if (/^-?[0-9]+$/.test(string)){
			return true;
		}
		return false;
	},
	isMoney: function (string) {
		if (/^[0-9]+(\.[0-9]{2,4})?$/.test(string)){
			return true;
		}
		return false;
	},
	isUrl: function (string) {
		if (/^(https?:\/\/)?[\da-z\.\-]+\.[a-z\.]{2,6}[#&+_\?\/\w \.\-=]*$/i.test(string)){
			return true;
		}
		return false;
	},
	isDate: function (string) {
		if (/^\s*[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}\s*$/i.test(string)){
			return true;
		}
		return false;
	},
	isTime: function (string) {
		if (/^\s*[0-9]{2}:[0-9]{2}\s*$/i.test(string)){
			return true;
		}
		return false;
	},
	isDateTime: function (string) {
		if (/^\s*[0-9]{4}[\/-][0-9]{1,2}[\/-][0-9]{1,2}\s*[0-9]{2}:[0-9]{2}(:[0-9]{2})?\s*$/i.test(string)){
			return true;
		}
		return false;
	},
	isUrlFragment: function (string) {
		if (/[^A-Za-z0-9-_]/i.test(string)){
			return false;
		}
		return true;
	},
	isEmail: function (string) {
		if (/^[a-zA-Z][\w\.-]*[a-zA-Z0-9]@[a-zA-Z0-9][\w\.-]*[a-zA-Z0-9]\.[a-zA-Z][a-zA-Z\.]*[a-zA-Z]$/.test(string)){
			return true;
		}
		return false;
	},
	displayPageNotification: function (type, message, no_scroll, timeout) {
		var notification = $('<div class="'+type+'">'+message+'</div>');
		$('#notifications_area').html(notification).show();
		if (typeof no_scroll == 'undefined') {
			scroll = $("#notifications_area").position().top-80;
			$(document).scrollTop(scroll);
		}
		if (typeof(timeout) != 'undefined' && timeout) {
			setTimeout(function(){
				notification.fadeOut(1000);
			}, 5000);
		}
	},
	clearPageNotification: function (message) {
		$('#notifications_area').html('');
	},
	displayValidationError: function (form_id, element, element_name, element_index, message) {
		var array_name = element_name+'-error';
		if (element.is_array) {
			array_name += '-'+element_index;
		}
		var error = $('#'+form_id+' #'+array_name);
		error.html(message);
		error.show()
		$("#"+form_id+" *[name='"+array_name+"']").addClass('form-error-input');
	},
	registerForm: function (form_id, form, validators, disable_button) {
		this._forms[form_id] = form;
		this._validators[form_id] = validators;
		this._disables[form_id] = disable_button;
	},
	checkElementValue: function (element, element_value) {
		is_this_valid = true;
		switch (element.type) {
			case 'integer':
				if (!this.isInteger(element_value)) {
					is_this_valid = false;
				}
				else if (typeof(element.max_value) != 'undefined' && element_value > element.max_value) {
					is_this_valid = false;
				}
				else if (typeof(element.min_value) != 'undefined' && element_value < element.min_value) {
					is_this_valid = false;
				}
				break;

			case 'float':
				if (!this.isFloat(element_value)) {
					is_this_valid = false;
				}
				else if (typeof(element.max_value) != 'undefined' && element_value > element.max_value) {
					is_this_valid = false;
				}
				else if (typeof(element.min_value) != 'undefined' && element_value < element.min_value) {
					is_this_valid = false;
				}
				break;

			case 'date':
				if (!this.isDate(element_value)) {
					is_this_valid = false;
				}
			break;

			case 'datetime':
				if (!this.isDateTime(element_value)) {
					is_this_valid = false;
				}
			break;

			case 'time':
				if (!this.isTime(element_value)) {
					is_this_valid = false;
				}
			break;

			case 'money':
				if (!this.isMoney(element_value)) {
					is_this_valid = false;
				}
				else if (element.zero_allowed && parseFloat(element_value) == 0) {
					// this is ok
				}
				else if (parseFloat(element_value) <= 0 && !element.zero_allowed) {
					is_this_valid = false;
				}
			break;

			case 'string':
				if (typeof(element.max_length) != 'undefined' && element_value && element_value.length > element.max_length) {
					is_this_valid  = false;
				}
				else if (typeof(element.min_length) != 'undefined' && element_value && element_value.length < element.min_length) {
					is_this_valid  = false;
				}
			break;

			case 'email':
				if (!this.isEmail(element_value)) {
					is_this_valid = false;
				}
			break;

			case 'url':
				if (!this.isUrl(element_value)) {
					is_this_valid = false;
				}
			break;

			case 'url-fragment':
				if (!this.isUrlFragment(element_value)) {
					is_this_valid = false;
				}
			break;

			case 'date-segements':
				var year = $('#'+element_name+'_year').val();
				var month = $('#'+element_name+'_month').val();
				var day = $('#'+element_name+'_day').val();
				element_value = ""+year+month+day
				if (!this.isDate(year+'-'+month+'-'+day)) {
					is_this_valid = false;
				}
			break;
		}

		return is_this_valid;
	},
	checkElement: function (form_id, element, element_name, element_index, validators) {
		var element_value = '';
		var array_name = element_name;
		var error_name = '#'+form_id+' #'+element_name+'-error';
		if (element.is_array) {
			array_name += '[]';
			error_name += '-'+element_index;
		}
		if (element.array_value) {
			array_name += '[]';
		}
		if (element.type != 'date_segements') {
			if (element.array_value) {
				element_value = $('#'+form_id+' *[name="'+array_name+'"]').val()[element_index];
			}
			else {
				if ($($('#'+form_id+' input[name="'+array_name+'"][type="checkbox"]')[element_index])) {
					element_value = $($('#'+form_id+' input[name="'+array_name+'"]')[element_index]).is(':checked');
					element_value = element_value ? $($('#'+form_id+' *[name="'+array_name+'"]')[element_index]).val() : null;
				}
				else {
					element_value = $($('#'+form_id+' *[name="'+array_name+'"]')[element_index]).val();
				}
			}
		}
		console.log(element_value);

		if (typeof(element.required) == 'undefined') {
			element.required = true;
		}

		if ((element_value == '' || element_value == null) && !element.required) {
			is_this_valid = true;
		}
		else if ((element_value == '' || element_value == null) && element.required) {
			is_this_valid = false;
		}
		else {
			is_this_valid = this.checkElementValue(element, element_value);
		}

		if (is_this_valid) {
			//$(error_name).hide();
		}

		var validator_error = false;
		if (is_this_valid && typeof(validators[element_name]) != 'undefined') {
			for(var i=0; i<validators[element_name].length; i++) {
				var validator = validators[element_name][i];
				switch(validator['type']) {
					case 'params-equal':
						if (element_value != $($('#'+form_id+' input[name="'+validator.param+'"]')[element_index]).val()) {
							is_this_valid = false;
							validator_error = true;
							this.displayValidationError(form_id, element, element_name, element_index, validator.message);
						}
					break;

					case 'regex':
						var patt = new RegExp(validator.regex, validator.modifiers);
						if (element_value != '' && !patt.test(element_value)) {
							is_this_valid = false;
							validator_error = true;
							this.displayValidationError(form_id, element, element_name, element_index, validator.message);
						}
					break;
				}
			}
		}

		return [is_this_valid, validator_error];
	},
	validateForm: function (button, form_id, event) {
		$('#'+form_id+' .form-error').hide();

		var form = this._forms[form_id];
		var validators = this._validators[form_id];
		var is_valid = true;
		var scroll_position = 999999;
		for(var element_name in form) {
			var array_name = element_name;
			var element = form[element_name];
			if (element.is_array) {
				array_name += '[]';
			}

			var elements_length = $("#"+form_id+" *[name='"+array_name+"']").length;

			for (var element_index = 0; element_index < elements_length; element_index++) {
				var element_result = this.checkElement(form_id, element, element_name, element_index, validators);
				var is_this_valid = element_result[0];
				var validator_error = element_result[1];
				is_valid = is_valid & is_this_valid;
				console.log(element_name + ' ' +is_this_valid);

				if (!is_this_valid) {
					if (!validator_error) {
						this.displayValidationError(form_id, element, element_name, element_index, element.message);
					}
					is_valid = false;

					if ($($("#"+form_id+" *[name='"+element_name+"']")[element_index]).length) {
						scroll = $($("#"+form_id+" *[name='"+element_name+"']")[element_index]).offset().top-80;
						if (scroll < scroll_position) {
							scroll_position = scroll;
						}
					}
				}
				else {
					$($("#"+form_id+" *[name='"+element_name+"']")[element_index]).removeClass('form-error-input');
				}
			}
		}

		if (!is_valid) {
			$('body').animate({ scrollTop: scroll_position }, 500);
			event.stopPropagation();
			return false;
		}

		if (this._disables[form_id]) {
			setTimeout(function() {
				$(button).attr('disabled', 'disabled');
			}, 100);
		}

		return true;
	}
});
