<?php
/**
 * @copyright Copyright (c) 2016, Georg Ehrke
 *
 * @author Georg Ehrke <georg@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\CalDAV\Retention;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Calendar;
use OCA\DAV\CalDAV\CalendarHome;
use OCA\DAV\CalDAV\CalendarObject;
use OCA\DAV\CalDAV\Subscription;
use Sabre\DAV\PropPatch;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\DAV\Exception\NotFound;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class RetentionPlugin extends ServerPlugin {

	/**
	 * Reference to SabreDAV server object.
	 *
	 * @var \Sabre\DAV\Server
	 */
	protected $server;

	/**
	 * http header that triggers retention mode
	 * the caldav server will only return deleted calendars
	 * and objects in retention mode
	 *
	 * @var string
	 */
	protected $httpHeaderKey='X-Nc-Caldav-Retention-Mode';

	/**
	 * initialize plugin
	 * @param Server $server
	 */
	public function initialize(Server $server) {
		$this->server = $server;

		$this->server->on('beforeMethod:DELETE', 	[$this, 'beforeMethod']);
		$this->server->on('beforeMethod:PROPFIND',	[$this, 'beforeMethod']);
		$this->server->on('beforeMethod:PROPPATCH',	[$this, 'beforeMethod']);
		$this->server->on('beforeMethod:REPORT',	[$this, 'beforeMethod']);

		$this->server->on('propPatch', [$this, 'propPatch']);
	}

	/**
	 * This method should return a list of server-features.
	 *
	 * This is for example 'versioning' and is added to the DAV: header
	 * in an OPTIONS response.
	 *
	 * @return string[]
	 */
	public function getFeatures() {
		return ['nc-calendar-retention'];
	}

	/**
	 * Returns a plugin name.
	 *
	 * Using this name other plugins will be able to access other plugins
	 * using Sabre\DAV\Server::getPlugin
	 *
	 * @return string
	 */
	public function getPluginName()	{
		return 'nc-calendar-retention';
	}

	/**
	 * @param RequestInterface $request
	 */
	public function beforeMethod(RequestInterface $request) {
		$headers = $request->getHeaders();
		if (!isset($headers[$this->httpHeaderKey]) || $headers[$this->httpHeaderKey][0] !== 'ON') {
			return;
		}

		$path = $request->getPath();
		$calendarHome = null;
		$calendar = null;
		$calendarObject = null;

		$needle = '/';
		$firstSlash = strpos($path, $needle);

		switch(substr_count($path, '/')) {
			case 1:
				$calendarHome = $path;
				break;

			case 2:
				$secondSlash = strpos($path, $needle, $firstSlash + 1);
				$calendarHome = substr($path, 0, $secondSlash);
				$calendar = $path;
				break;

			case 3:
				$secondSlash = strpos($path, $needle, $firstSlash + 1);
				$thirdSlash = strpos($path, $needle, $secondSlash + 1);
				$calendarHome = substr($path, 0, $secondSlash);
				$calendar = substr($path, 0, $thirdSlash);
				$calendarObject = $path;
				break;

			default:
				break;
		}

		$calendarHomeNode = null;
		$calendarNode = null;
		$calendarObjectNode = null;

		// Making sure the node exists
		try {
			/** @var CalendarHome $calendarHomeNode */
			$calendarHomeNode = $this->server->tree->getNodeForPath($calendarHome);
			$calendarHomeNode->deletedCalendarsMode();
			if ($calendar) {
				/** @var Calendar|Subscription $calendarNode */
				$calendarNode = $this->server->tree->getNodeForPath($calendar);
				$calendarNode->deletedCalendarsMode();
			}
			if ($calendarObject) {
				/** @var CalendarObject $calendarObjectNode */
				$calendarObjectNode = $this->server->tree->getNodeForPath($calendarObject);
			}
		} catch (NotFound $e) {
			return;
		}

		if (!$calendarHomeNode) {
			return;
		}

		if ($calendarNode) {
		}
		if ($calendarObjectNode) {
			$calendarObjectNode->get();
		}

		/*
// Getting ACL info
$acl = $this->server->getPlugin('acl');

// If there's no ACL support, we allow everything
if ($acl) {
	$acl->checkPrivileges($path, '{DAV:}write');
}*/


	}

	/**
	 * @param string $path
	 * @param PropPatch $propPatch
	 */
	public function propPatch($path, PropPatch $propPatch) {
		// Making sure the node exists
		try {
			$node = $this->server->tree->getNodeForPath($path);
		} catch (NotFound $e) {
			return;
		}

		$propName = '{' . CalDavBackend::NS_NEXTCLOUD . '}deleted-at';
		if ($node instanceof \OCA\DAV\CalDAV\Calendar || $node instanceof \OCA\DAV\CalDAV\CalendarObject) {
			$propPatch->handle($propName, function() use ($node) {
				// was the value removed?
				//if ($value === null) {
					$node->restore();
				//}
				return true;
			});
		}


		// TODO
		// if node === calendar
		// calendar is deleted
		// and proppatch removes deleted_at
		// restore it

		return;
	}
}
