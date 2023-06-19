{**
 * plugins/generic/shariff/templates/settingsForm.tpl
 *
 * Copyright (c) 2018 Free University Berlin
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * Shariff plugin settings form template
 *
 *}

<tab id="objectsForReview" label="{translate key="plugins.generic.objectsForReview.tabTitle"}">

	{capture assign="objectsForReviewGridUrl"}{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.objectsForReview.controllers.grid.ObjectsForReviewManagementGridHandler" op="fetchGrid" escape=false}{/capture}
	{load_url_in_div id="objectsForReviewGridContainer" url=$objectsForReviewGridUrl}

	$smarty->assign('lineBreak',"\n");
	{$lineBreak|nl2br}

	<pkp-form
		v-bind="components.{$smarty.const.FORM_ADDITIONAL_SETTINGS}"
		@set="set"
	/>

</tab>
