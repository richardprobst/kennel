/**
 * Frontend JavaScript for Canil Site Público.
 *
 * @package CanilSitePublico
 */

/* global jQuery, canilSitePublico */

(function($) {
	'use strict';

	/**
	 * Initialize filters for puppies list.
	 */
	function initFilters() {
		$('.canil-filter').on('change', function() {
			filterPuppies();
		});
	}

	/**
	 * Filter puppies based on selected criteria.
	 */
	function filterPuppies() {
		const breed = $('#canil-filter-breed').val() || '';
		const sex = $('#canil-filter-sex').val() || '';
		const color = $('#canil-filter-color').val() || '';

		$('.canil-puppy-card').each(function() {
			const $card = $(this);
			const cardBreed = $card.data('breed') || '';
			const cardSex = $card.data('sex') || '';
			const cardColor = $card.data('color') || '';

			let visible = true;

			if (breed && cardBreed !== breed) {
				visible = false;
			}
			if (sex && cardSex !== sex) {
				visible = false;
			}
			if (color && cardColor !== color) {
				visible = false;
			}

			$card.attr('data-hidden', !visible);
		});
	}

	/**
	 * Initialize interest form.
	 */
	function initInterestForm() {
		$('.canil-form').on('submit', function(e) {
			e.preventDefault();
			submitInterestForm($(this));
		});
	}

	/**
	 * Submit interest form via AJAX.
	 *
	 * @param {jQuery} $form Form element.
	 */
	function submitInterestForm($form) {
		const $submitBtn = $form.find('.canil-form__submit');
		const $success = $form.find('.canil-form__success');
		const $error = $form.find('.canil-form__error');

		// Reset messages
		$success.hide();
		$error.hide();

		// Disable button
		const originalText = $submitBtn.text();
		$submitBtn.text(canilSitePublico.i18n.sending).prop('disabled', true);

		// Collect form data
		const formData = {
			name: $form.find('[name="name"]').val(),
			email: $form.find('[name="email"]').val(),
			phone: $form.find('[name="phone"]').val(),
			city: $form.find('[name="city"]').val() || '',
			message: $form.find('[name="message"]').val() || '',
			puppy_id: $form.find('[name="puppy_id"]').val() || 0,
			puppy_name: $form.find('[name="puppy_name"]').val() || '',
			contact_whatsapp: $form.find('[name="contact_whatsapp"]').is(':checked'),
			privacy_accepted: $form.find('[name="privacy_accepted"]').is(':checked')
		};

		// Validate
		if (!validateForm(formData, $form)) {
			$submitBtn.text(originalText).prop('disabled', false);
			return;
		}

		// Send request
		$.ajax({
			url: canilSitePublico.apiUrl + '/interest',
			method: 'POST',
			data: JSON.stringify(formData),
			contentType: 'application/json',
			headers: {
				'X-WP-Nonce': canilSitePublico.nonce
			},
			success: function(response) {
				$success.show();
				$form.trigger('reset');
				
				// Scroll to success message
				$('html, body').animate({
					scrollTop: $success.offset().top - 100
				}, 300);
			},
			error: function(xhr) {
				const message = xhr.responseJSON?.message || canilSitePublico.i18n.error;
				$error.text(message).show();
			},
			complete: function() {
				$submitBtn.text(originalText).prop('disabled', false);
			}
		});
	}

	/**
	 * Validate form data.
	 *
	 * @param {Object} data Form data.
	 * @param {jQuery} $form Form element.
	 * @return {boolean} Whether form is valid.
	 */
	function validateForm(data, $form) {
		let valid = true;

		// Clear previous errors
		$form.find('.field-error').remove();

		// Name
		if (!data.name || data.name.length < 2) {
			showFieldError($form.find('[name="name"]'), canilSitePublico.i18n.required);
			valid = false;
		}

		// Email
		if (!data.email || !isValidEmail(data.email)) {
			showFieldError($form.find('[name="email"]'), canilSitePublico.i18n.invalidEmail);
			valid = false;
		}

		// Phone
		if (!data.phone || data.phone.replace(/\D/g, '').length < 8) {
			showFieldError($form.find('[name="phone"]'), canilSitePublico.i18n.invalidPhone);
			valid = false;
		}

		// Privacy
		if (!data.privacy_accepted) {
			showFieldError($form.find('[name="privacy_accepted"]').closest('.canil-form__group'), canilSitePublico.i18n.required);
			valid = false;
		}

		return valid;
	}

	/**
	 * Show field error.
	 *
	 * @param {jQuery} $field Field element.
	 * @param {string} message Error message.
	 */
	function showFieldError($field, message) {
		const $error = $('<span class="field-error" style="color: #d63638; font-size: 0.875rem;"></span>').text(message);
		$field.closest('.canil-form__group').append($error);
	}

	/**
	 * Validate email format.
	 *
	 * @param {string} email Email address.
	 * @return {boolean} Whether email is valid.
	 */
	function isValidEmail(email) {
		const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		return re.test(email);
	}

	/**
	 * Initialize gallery lightbox.
	 */
	function initGallery() {
		$('.canil-puppy-detail__gallery-item').on('click', function() {
			const imgSrc = $(this).find('img').attr('src');
			const $main = $(this).closest('.canil-puppy-detail__media').find('.canil-puppy-detail__photo img');
			
			if ($main.length) {
				$main.attr('src', imgSrc);
			}
		});
	}

	/**
	 * Document ready.
	 */
	$(function() {
		initFilters();
		initInterestForm();
		initGallery();
	});

})(jQuery);
