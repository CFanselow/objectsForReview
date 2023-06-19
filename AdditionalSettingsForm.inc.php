<?php

/**
 * @file plugins/generic/objectsForReview/AdditionalSettingsForm.inc.php
 *
 * Copyright (c) 2021 Universitätsbibliothek Freie Universität Berlin
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class AdditionalSettingsForm
 * @ingroup plugins_generic_objectsforreview
 *
 */

use \PKP\components\forms\FormComponent;
use \PKP\components\forms\FieldText;
use \PKP\components\forms\FieldOptions;
use \PKP\components\forms\FieldRichTextarea;

define('FORM_ADDITIONAL_SETTINGS', 'additionalSettings');

/**
 * A form for implementing shariff settings.
 * 
 * @class ShariffSettingsForm
 * @brief Class implemnting ShariffSettingsForm
 */
class AdditionalSettingsForm extends FormComponent {
	/** @copydoc FormComponent::$id */
	public $id = FORM_ADDITIONAL_SETTINGS;

	/** @copydoc FormComponent::$method */
	public $method = 'PUT';

	/**
	 * Constructor
	 *
	 * @param string $action string URL to submit the form to
	 * @param array $locales array Supported locales
	 * @param object $context Context Journal or Press to change settings for
	 * @param string $baseUrl string Site's base URL. Used for image previews.
	 * @param string $temporaryFileApiUrl string URL to upload files to
	 * @param string $imageUploadUrl string The API endpoint for images uploaded through the rich text field
	 * @param string $publicUrl url to the frontend page
	 * @param array $data settings for form initialization	 
	 */
	public function __construct($action, $locales, $context) {

		$this->action = $action;
		$this->successMessage = __('plugins.generic.objectsForReview.settings.form.success', ['url' => $publicUrl]);
		$this->locales = $locales;

		$this->addGroup([
			'id' => 'additionalSettings',	
		], [])
		->addField(new \PKP\components\forms\FieldRichTextarea('objectsForReviewInstruction', [
			'label' => __('plugins.generic.objectsForReview.settings.instructions'),
			'value' => $context->getData('objectsForReviewInstruction'),
			'isMultilingual' => true,			
			'groupId' => 'additionalSettings',
		]));
	}
}
?>