<?php

/**
 * @file plugins/generic/objectsForReview/ObjectsForReviewPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ObjectsForReviewPlugin
 * @ingroup plugins_generic_objectsForReview
 * @brief Add objectsForReview data to the submission metadata and display them on the submission view page.
 *
 */

import('lib.pkp.classes.plugins.GenericPlugin');
define('OBJECTSFORREVIEW_NMI_TYPE', 'OBJECTSFORREVIEW_NMI');

class ObjectsForReviewPlugin extends GenericPlugin {

	/**
	 * @copydoc Plugin::getName()
	 */
	function getName() {
		return 'ObjectsForReviewPlugin';
    }

	/**
	 * @copydoc Plugin::getDisplayName()
	 */
    function getDisplayName() {
		return __('plugins.generic.objectsForReview.displayName');
    }

	/**
	 * @copydoc Plugin::getDescription()
	 */
    function getDescription() {
		return __('plugins.generic.objectsForReview.description');
    }

	/**
	 * @see PKPPlugin::getInstallEmailTemplatesFile()
	 */
	function getInstallEmailTemplatesFile() {
		return ($this->getPluginPath() . '/emailTemplates.xml');
	}


	/**
	 * @copydoc Plugin::getActions()
	 */
	function getActions($request, $verb) {
		$router = $request->getRouter();
		import('lib.pkp.classes.linkAction.request.AjaxModal');
		return array_merge(
			$this->getEnabled()?[
				new LinkAction(
					'settings',
					new AjaxModal(
						$router->url($request, null, null, 'manage', null, array('verb' => 'settings', 'plugin' => $this->getName(), 'category' => 'generic')),
						$this->getDisplayName()
					),
					__('manager.plugins.settings'),
					null
				),
			]:[],
			parent::getActions($request, $verb)
		);
	}

	/**
	 * @copydoc Plugin::manage()
	 */
	function manage($args, $request) {
		switch ($request->getUserVar('verb')) {
			case 'settings':
				$context = $request->getContext();

				AppLocale::requireComponents(LOCALE_COMPONENT_APP_COMMON,  LOCALE_COMPONENT_PKP_MANAGER);
				$templateMgr = TemplateManager::getManager($request);
				$templateMgr->register_function('plugin_url', array($this, 'smartyPluginUrl'));

				$this->import('ObjectsForReviewSettingsForm');
				$form = new ObjectsForReviewSettingsForm($this, $context->getId());

				if ($request->getUserVar('save')) {
					$form->readInputData();
					if ($form->validate()) {
						$form->execute();
						return new JSONMessage(true);
					}
				} else {
					$form->initData();
				}
				return new JSONMessage(true, $form->fetch($request));
		}
		return parent::manage($args, $request);
	}

	/**
	 * @copydoc Plugin::register()
	 */
    function register($category, $path, $mainContextId = null) {

		$success = parent::register($category, $path, $mainContextId);
		if ($success && $this->getEnabled($mainContextId)) {

			$request = Application::get()->getRequest();
			$context = $request->getContext();

			import('plugins.generic.objectsForReview.classes.ObjectForReviewDAO');
			$objectForReviewDao = new ObjectForReviewDAO();
			DAORegistry::registerDAO('ObjectForReviewDAO', $objectForReviewDao);

			HookRegistry::register('Templates::Submission::SubmissionMetadataForm::AdditionalMetadata', array($this, 'metadataFieldEdit'));
			HookRegistry::register('Template::Workflow::Publication', array($this, 'addToPublicationForms'));

			HookRegistry::register('LoadComponentHandler', array($this, 'setupGridHandler'));
			HookRegistry::register('TemplateManager::display',array($this, 'addJs'));

			HookRegistry::register('Template::Settings::website', array($this, 'callbackShowWebsiteSettingsTabs'));

			// if list display is enabled
			if ($this->getSetting($context->getId(), 'displayAsList')) {
				HookRegistry::register('Templates::Article::Main', array($this, 'addSubmissionDisplay'));
				HookRegistry::register('Templates::Catalog::Book::Main', array($this, 'addSubmissionDisplay'));
			}

			// If subtitle display is enabled
			// Not working in 3.2, need to find a new solution
			#if ($this->getSetting($context->getId(), 'displayAsSubtitle')) {
			#	HookRegistry::register('pkp\\services\\pkppublicationservice::_getmany', array($this, 'addSubtitleDisplay'));
			#}
			
			//HookRegistry::register('pkp\\services\\pkppublicationservice::_getmany', array($this, 'addSubtitleDisplay'));

			// Handler for public objects for review page
			HookRegistry::register('LoadHandler', array($this, 'loadPageHandler'));
			HookRegistry::register('NavigationMenus::itemTypes', array($this, 'addMenuItemTypes'));
			HookRegistry::register('NavigationMenus::displaySettings', array($this, 'setMenuItemDisplayDetails'));
			HookRegistry::register('SitemapHandler::createJournalSitemap', array($this, 'addSitemapURLs'));

			HookRegistry::register('Schema::get::context', array($this, 'addToSchema')); // to add variables to context schema

		}
		return $success;
	}
	


	public function addToSchema($hookName, $params) {
		$schema =& $params[0];

		$schema->properties->{"objectsForReviewInstruction"} = (object) [
			'type' => 'string',
			'multilingual' => true,
			'validation' => ['nullable'],
		];
		return false;
	}

	/**
	 * Extend the website settings tabs to include objects for review tab
	 * @param $hookName string The name of the invoked hook
	 * @param $args array Hook parameters
	 * @return boolean Hook handling status
	 */
	function callbackShowWebsiteSettingsTabs($hookName, $args) {
		
		$output =& $args[2];
	
		$templateMgr =& $args[1];
		$output =& $args[2];
		$request =& Registry::get('request');
		$context = $request->getContext();
		$contextId = $context->getId();
		$dispatcher = $request->getDispatcher();

		# url to handle form dialog (we add our vars to the context schema)
		$contextApiUrl = $dispatcher->url(
			$request,
			ROUTE_API,
			$context->getPath(),
			'contexts/' . $context->getId()
		);
		$contextUrl = $request->getRouter()->url($request, $context->getPath());

		$supportedFormLocales = $context->getSupportedFormLocales();
		$localeNames = AppLocale::getAllLocales();
		$locales = array_map(function($localeKey) use ($localeNames) {
			return ['key' => $localeKey, 'label' => $localeNames[$localeKey]];
		}, $supportedFormLocales);

		// instantinate settings form
		$this->import('AdditionalSettingsForm');
		$this->import('ObjectsForReviewSettingsForm');				
		$additionalSettingsForm = new AdditionalSettingsForm($contextApiUrl, $locales, $context);

		// setup template
		$templateMgr->setConstants([
			'FORM_ADDITIONAL_SETTINGS',
		]);

		$state = $templateMgr->getTemplateVars('state');
		$state['components'][FORM_ADDITIONAL_SETTINGS] = $additionalSettingsForm->getConfig();
		$templateMgr->assign('state', $state); // In OJS 3.3 $templateMgr->setState doesn't seem to update template vars anymore

		$output .= $templateMgr->fetch($this->getTemplateResource('additionalSettingsForm.tpl'));	
		return false;
	}

	/**
	 * Permit requests to the ObjectsForReview grid handler
	 * @param $hookName string The name of the hook being invoked
	 * @param $args array The parameters to the invoked hook
	 */
	function setupGridHandler($hookName, $params) {
		$component =& $params[0];
		if ($component == 'plugins.generic.objectsForReview.controllers.grid.ObjectsForReviewGridHandler') {
			import($component);
			ObjectsForReviewGridHandler::setPlugin($this);
			return true;
		}
		if ($component == 'plugins.generic.objectsForReview.controllers.grid.ObjectsForReviewManagementGridHandler') {
			import($component);
			ObjectsForReviewManagementGridHandler::setPlugin($this);
			return true;
		}
		return false;
	}

	/**
	 * Insert ObjectsForReview grid in the publication tabs
	 */
	function addToPublicationForms($hookName, $params) {
		$smarty =& $params[1];
		$output =& $params[2];
		$submission = $smarty->getTemplateVars('submission');
		$smarty->assign([
			'submissionId' => $submission->getId(),
		]);

		$output .= sprintf(
			'<tab id="objectsForReviewGridInWorkflow" label="%s">%s</tab>',
			__('plugins.generic.objectsForReview.management.gridTitle'),
			$smarty->fetch($this->getTemplateResource('metadataForm.tpl'))
		);

		return false;
	}

	/**
	 * Insert ObjectsForReview grid in the submission metadata form
	 */
	function metadataFieldEdit($hookName, $params) {
		$smarty =& $params[1];
		$output =& $params[2];
		$output .= $smarty->fetch($this->getTemplateResource('metadataForm.tpl'));
		return false;
	}

	/**
	* Hook to Templates::Article::Details and Templates::Catalog::Book::Details and list object for review information
	* @param $hookName string
	* @param $params array
	*/
	function addSubmissionDisplay($hookName, $params) {
		$templateMgr = $params[1];
		$output =& $params[2];

		$submission = $templateMgr->getTemplateVars('monograph') ? $templateMgr->getTemplateVars('monograph') : $templateMgr->getTemplateVars('article');

		$objectForReviewDao = DAORegistry::getDAO('ObjectForReviewDAO');
		$objectsForReview = $objectForReviewDao->getBySubmissionId($submission->getId());

		if ($objectsForReview){
			$templateData = array();

			while ($objectForReview = $objectsForReview->next()) {
				$objectId = $objectForReview->getId();
				$templateData[$objectId] = array(
					'identifierType' => $objectForReview->getIdentifierType(),
					'identifier' => $objectForReview->getIdentifier(),
					'description' => $objectForReview->getDescription()
				);
			}

			if ($objectsForReview){
				$templateMgr->assign('objectsForReview', $templateData);
				$output .= $templateMgr->fetch($this->getTemplateResource('listReviews.tpl'));
			}

		}

		return false;
	}

	/**
	* Hook to ArticleDAO::_fromRow and MonographDAO::_fromRow and display objectForReview as subtitle
	* @param $hookName string
	* @param $params array
	*/
	function addSubtitleDisplay($hookName, $params) {
		// NOTE Not working in 3.2, need to rewrite this
		$submission =& $params[0];

		$objectForReviewDao = DAORegistry::getDAO('ObjectForReviewDAO');
		$objectsForReview = $objectForReviewDao->getBySubmissionId($submission->getId());

		if ($objectsForReview){
			$objects = array();
			while ($objectForReview = $objectsForReview->next()) {
				$objects[] = $objectForReview->getDescription();
			}

			if ($objects){
				$publication->setSubtitle(implode(" ▪ ", $objects), $publication->getLocale());
			}
		}

		return false;
	}

	/**
	 * @copydoc Plugin::getInstallMigration()
	 */
	function getInstallMigration() {
		$this->import('ObjectsForReviewSchemaMigration');
		return new ObjectsForReviewSchemaMigration();
	}

	/**
	 * Add custom js for backend and frontend
	 */
	function addJs($hookName, $params) {
		$templateMgr = $params[0];
		$template =& $params[1];
		$request = Application::get()->getRequest();

		$gridHandlerJs = $this->getJavaScriptURL($request, false) . DIRECTORY_SEPARATOR . 'ObjectsForReviewGridHandler.js';
		$templateMgr->addJavaScript(
			'ObjectsForReviewGridHandlerJs',
			$gridHandlerJs,
			array('contexts' => 'backend')
		);
		$templateMgr->addStylesheet(
			'ObjectsForReviewGridHandlerStyles',
			'#objectsForReviewGridInWorkflow { margin-top: 32px; }',
			[
				'inline' => true,
				'contexts' => 'backend',
			]
		);

		if (strpos($template, 'frontend/pages/forReview.tpl')) {
			$tablesortJs = $this->getJavaScriptURL($request, false) . DIRECTORY_SEPARATOR . '/tablesort/src/tablesort.js';
			$templateMgr->addJavaScript(
				'TableSortJs',
				$tablesortJs,
				array('contexts' => 'frontend')
			);
			$tablesortCss = $request->getBaseUrl() . '/plugins/generic/objectsForReview/style.css';
			$templateMgr->addStyleSheet('tablesortCss', $tablesortCss);
		}

		return false;
	}

	/**
	 * Get the JavaScript URL for this plugin.
	 */
	function getJavaScriptURL() {
		$request = Application::get()->getRequest();
		return $request->getBaseUrl() . DIRECTORY_SEPARATOR . $this->getPluginPath() . DIRECTORY_SEPARATOR . 'js';
	}

	/**
	 * Load the handler to deal with browse by section page requests
	 *
	 * @param $hookName string `LoadHandler`
	 * @param $args array [
	 * 		@option string page
	 * 		@option string op
	 * 		@option string sourceFile
	 * ]
	 * @return bool
	 */
	public function loadPageHandler($hookName, $args) {
		$page = $args[0];
		if ($this->getEnabled() && $page === 'for-review') {
			$this->import('pages/ObjectsForReviewHandler');
			define('HANDLER_CLASS', 'ObjectsForReviewHandler');
			return true;
		}
		return false;
	}

	/**
	 * Add Navigation Menu Item types for linking to objects for review page
	 *
	 * @param $hookName string
	 * @param $args array [
	 *		@option array Existing menu item types
	 * ]
	 */
	public function addMenuItemTypes($hookName, $args) {
		$types =& $args[0];
		$request = Application::get()->getRequest();
		$context = $request->getContext();
		$contextId = $context ? $context->getId() : CONTEXT_ID_NONE;
		$types[OBJECTSFORREVIEW_NMI_TYPE] = array(
			'title' => __('plugins.generic.objectsForReview.navMenuItem'),
			'description' => __('plugins.generic.objectsForReview.navMenuItem.description'),
		);
	}

	/**
	 * Set the display details for the custom menu item types
	 *
	 * @param $hookName string
	 * @param $args array [
	 *		@option NavigationMenuItem
	 * ]
	 */
	public function setMenuItemDisplayDetails($hookName, $args) {
		$navigationMenuItem =& $args[0];
		if ($navigationMenuItem->getType() == OBJECTSFORREVIEW_NMI_TYPE) {
			$request = Application::get()->getRequest();
			$context = $request->getContext();
			if ($context){
				$dispatcher = $request->getDispatcher();
				$navigationMenuItem->setUrl($dispatcher->url(
					$request,
					ROUTE_PAGE,
					null,
					'for-review'
				));
			}
		}
	}

	/**
	 * Add the objects for review page URL to the sitemap
	 *
	 * @param $hookName string
	 * @param $args array
	 * @return boolean
	 */
	function addSitemapURLs($hookName, $args) {
		$doc = $args[0];
		$rootNode = $doc->documentElement;
		$request = Application::get()->getRequest();
		$context = $request->getContext();
		if ($context) {
			// Create and append sitemap XML "url" element
			$url = $doc->createElement('url');
			$url->appendChild($doc->createElement('loc', htmlspecialchars($request->url($context->getPath(), 'for-review'), ENT_COMPAT, 'UTF-8')));
			$rootNode->appendChild($url);
		}
		return false;
	}

	/**
	 * Get the URL for JQuery JS.
	 * @param $request PKPRequest
	 * @return string
	 */
	private function _getJQueryUrl($request) {
		$min = Config::getVar('general', 'enable_minified') ? '.min' : '';
		if (Config::getVar('general', 'enable_cdn')) {
			return '//ajax.googleapis.com/ajax/libs/jquery/' . CDN_JQUERY_VERSION . '/jquery' . $min . '.js';
		} else {
			return $request->getBaseUrl() . '/lib/pkp/lib/vendor/components/jquery/jquery' . $min . '.js';
		}
	}

	/**
	 * Instantiate a MailTemplate
	 *
	 * @param string $emailKey
	 * @param Context $context
	 *
	 * @return MailTemplate
	 */
	function getMailTemplate($emailKey, $context = null) {
		import('lib.pkp.classes.mail.MailTemplate');
		return new MailTemplate($emailKey, null, $context, false);
	}

	/**
	 * Send mail to editor when object is reserved or cancelled
	 *
	 * @param User $user
	 * @param $object
	 * @param $template Send either the reserve or cancel mail
	 */
	public function notifyEditor($user, $objectDescription, $mailTemplate) {

		$request = Application::get()->getRequest();
		$context = $request->getContext();

		// This should only ever happen within a context, never site-wide.
		assert($context != null);
		$contextId = $context->getId();

		$mail = $this->getMailTemplate($mailTemplate, $context);

		// Set From to user
		$mail->setFrom($user->getData('email'), $user->getFullName());

		// Set To to editor or the email given in plugin settings
		if ($this->getSetting($contextId, 'ofrNotifyEmail')){
			$mail->setRecipients(array(array('name' =>  __('plugins.generic.objectsForReview.notifyDefaultName'), 'email' => $this->getSetting($contextId, 'ofrNotifyEmail'))));
		} else{
			$mail->setRecipients(array(array('name' => $context->getData('contactName'), 'email' => $context->getData('contactEmail'))));
		}

		// Send the mail with parameters
		$mail->sendWithParams(array(
			'objectDescription' => $objectDescription,
			'userName' => $user->getFullName(),
			'userEmail' => $user->getData('email'),
		));

	}
}
?>
