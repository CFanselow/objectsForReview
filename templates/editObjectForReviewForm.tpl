{**
 * plugins/generic/objectsForReview/templates/editObjectForReviewForm.tpl
 *
 * Copyright (c) 2014-2019 Simon Fraser University
 * Copyright (c) 2003-2019 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Form for editing a objectsForReview item
 *}

{if $reservedObjects}

	<h2>You can select one of the reserved objects offered:</h2>
	
	{foreach from=$reservedObjects item=reservedObject}
		<script>
			$(function() {ldelim}
				// Attach the form handler.
				$('#reservedObjectsForReviewForm-{$reservedObject.objectId}').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
			{rdelim});
		</script>
		<p>

			{capture assign="reserveActionUrl"}{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.objectsForReview.controllers.grid.ObjectsForReviewGridHandler" op="addReservedObjectForReview" submissionId=$submissionId escape=false}{/capture}

			<form class="pkp_form" id="reservedObjectsForReviewForm-{$reservedObject.objectId}" method="post" action="{$reserveActionUrl}">
				{csrf}
				<input type="hidden" name="objectId" value="{$reservedObject.objectId|escape}" />
				{assign var=buttonId value="submitFormButton"|concat:"-"|uniqid}
				{$reservedObject.description} {fbvElement type="submit" class="submitFormButton" id=$buttonId label="plugins.generic.objectsForReview.objectsForReviewData.addReservedObject"}
			</form>
		</p>
	{/foreach}
{else}
 <p>No objects available.</p>	
{/if}

{if !$onlyReserved}
	<script>
		$(function() {ldelim}
			// Attach the form handler.
			$('#objectsForReviewForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
		{rdelim});
	</script>

	{capture assign="actionUrl"}{url router=$smarty.const.ROUTE_COMPONENT component="plugins.generic.objectsForReview.controllers.grid.ObjectsForReviewGridHandler" op="updateObjectForReview" submissionId=$submissionId escape=false}{/capture}

	<h2>You can add a new object here:</h2>

	<form class="pkp_form" id="objectsForReviewForm" method="post" action="{$actionUrl}">
		{csrf}
		{if $objectId}
			<input type="hidden" name="objectId" value="{$objectId|escape}" />
		{/if}
		{fbvFormArea id="objectsForReviewFormArea" class="border"}


			{fbvFormSection for="resourceType" label="plugins.generic.objectsForReview.resourceType"}
				{fbvElement type="select" id="resourceType" from=$resourceTypes selected=$resourceType size=$fbvStyles.size.SMALL}
			{/fbvFormSection}

			{fbvFormSection for="identifierType" label="plugins.generic.objectsForReview.itemIdentifierType"}
				{fbvElement type="select" id="identifierType" from=$identifierTypes selected=$identifierType translate=false size=$fbvStyles.size.SMALL} 
			{/fbvFormSection}

			{fbvFormSection label="plugins.generic.objectsForReview.itemIdentifier" for="identifier"}
				{fbvElement type="text" id="identifier" value=$identifier maxlength="255" inline=true multilingual=false size=$fbvStyles.size.MEDIUM}
			{/fbvFormSection}

			{fbvFormSection label="plugins.generic.objectsForReview.itemAuthors" for="authors"}
				{fbvElement type="text" id="authors" value=$authors maxlength="255" inline=true multilingual=false size=$fbvStyles.size.MEDIUM}
			{/fbvFormSection}

			{fbvFormSection label="plugins.generic.objectsForReview.itemTitle" for="title"}
				{fbvElement type="textarea" multilingual=false name="title" id="title" value=$title rich=true height=$fbvStyles.height.SHORT}
			{/fbvFormSection}

			{fbvFormSection label="plugins.generic.objectsForReview.itemPublisher" for="publisher"}
				{fbvElement type="text" id="publisher" value=$publisher maxlength="255" inline=true multilingual=false size=$fbvStyles.size.MEDIUM}
			{/fbvFormSection}

			{fbvFormSection label="plugins.generic.objectsForReview.itemYear" for="year"}
				{fbvElement type="text" id="year" value=$year maxlength="255" inline=true multilingual=false size=$fbvStyles.size.SMALL}
			{/fbvFormSection}

		{/fbvFormArea}

		{fbvFormSection class="formButtons"}
			{assign var=buttonId value="submitFormButton"|concat:"-"|uniqid}
			{fbvElement type="submit" class="submitFormButton" id=$buttonId label="common.save"}
		{/fbvFormSection}
	</form>
{/if}

