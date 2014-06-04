<?php
/**
 * Copyright (c) 2013 Georg Ehrke georg@ownCloud.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
OCP\JSON::checkAdminUser();
OCP\JSON::callCheck();

if (!array_key_exists('appid', $_POST)) {
	OCP\JSON::error(array(
		'message' => 'No AppId given!'
	));
	exit;
}

$appId = $_POST['appid'];

if (!is_numeric($appId)) {
	$appId = OC_Appconfig::getValue($appId, 'ocsid', null);

	if ($appId === null) {
		OCP\JSON::error(array(
			'message' => 'No OCS-ID found for app!'
		));
		exit;
	}
}

$appId = OC_App::cleanAppId($appId);

$result = OC_Installer::updateAppByOCSId($appId);
if($result !== false) {
	OC_JSON::success(array('data' => array('appid' => $appId)));
} else {
	$l = OC_L10N::get('settings');
	OC_JSON::error(array("data" => array( "message" => $l->t("Couldn't update app.") )));
}
