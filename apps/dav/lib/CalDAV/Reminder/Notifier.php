<?php
/**
 * Created by PhpStorm.
 * User: tcit
 * Date: 09/10/16
 * Time: 22:54
 */

namespace OCA\DAV\CalDAV\Reminder;


use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class Notifier implements INotifier {
	protected $factory;

	public function __construct(IFactory $factory) {
		$this->factory = $factory;
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 * @return INotification
	 */
	public function prepare(INotification $notification, $languageCode) {
		if ($notification->getApp() !== 'dav') {
			throw new \InvalidArgumentException();
		}

		// Read the language from the notification
		$l = $this->factory->get('dav', $languageCode);

		switch ($notification->getSubject()) {
			// Deal with known subjects
			case 'calendar_reminder':
				$notification->setParsedSubject(
					(string)$l->t('Your event "%s" is in %s', $notification->getSubjectParameters())
				);

				return $notification;
				break;

			default:
				// Unknown subject => Unknown notification => throw
				throw new \InvalidArgumentException();
		}
	}
}