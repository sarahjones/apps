<form id="calendar">
	<p><b><?php echo $l->t('Your calendars'); ?>:</b></p>
	<table width="100%" style="border: 0;">
	<?php
	$option_calendars = OC_Calendar_Calendar::allCalendars(OCP\USER::getUser());
	for($i = 0; $i < count($option_calendars); $i++) {
		echo "<tr data-id='".$option_calendars[$i]['id']."'>";
		$tmpl = new OCP\Template('calendar', 'part.choosecalendar.rowfields');
		$tmpl->assign('calendar', $option_calendars[$i]);
		if ($option_calendars[$i]['userid'] != OCP\User::getUser()) {
			$sharedCalendar = OCP\Share::getItemSharedWithBySource('calendar', $option_calendars[$i]['id']);
			$shared = true;
		} else {
			$shared = false;
		}
		$tmpl->assign('shared', $shared);
		$tmpl->printpage();
		echo "</tr>";
	}
	?>
	<tr>
		<td colspan="6">
			<input type="button" value="<?php echo $l->t('New Calendar') ?>" id="newCalendar">
		</td>
	</tr>
	<tr>
		<td colspan="6">
			<p style="margin: 0 auto;width: 90%;"><input style="display:none;width: 90%;float: left;" type="text" id="caldav_url" title="<?php echo $l->t("CalDav Link"); ?>"><img id="caldav_url_close" style="height: 20px;vertical-align: middle;display: none;" src="<?php echo OCP\Util::imagePath('core', 'actions/delete.svg') ?>" alt="close"/></p>
		</td>
	</tr>
	</table><br>
	</fieldset>
</form>