<?php
	use shanemcc\phpdb\DB;

	EventQueue::get()->subscribe('domain.add', function($domainID) {
		$name = domainName($domainID);

		auditLog('domain.add', [$domainID], 'Domain "' . $name . '" created');
	});

	EventQueue::get()->subscribe('domain.delete', function($domainID, $domainName) {
		auditLog('domain.delete', [$domainID, $domainName], 'Domain "' . $domainName . '" deleted');
	});

	EventQueue::get()->subscribe('domain.rename', function($oldName, $domainID) {
		$newName = domainName($domainID);

		auditLog('domain.rename', [$oldName, $domainID], 'Domain "' . $oldName . '" renamed to "' . $newName . '"');
	});

	EventQueue::get()->subscribe('domain.access.changed', function($domainID, $email, $accessLevel) {
		$name = domainName($domainID);

		auditLog('domain.access.changed', [$domainID, $email, $accessLevel], 'Access for ' . $email . ' on "' . $name . '" set to "' . $accessLevel . '"');
	});

	EventQueue::get()->subscribe('domain.key.created', function($domainID, $maskedKey) {
		$name = domainName($domainID);

		auditLog('domain.key.created', [$domainID, $maskedKey], 'Domain key "' . $maskedKey . '" on "' . $name . '" created');
	});

	EventQueue::get()->subscribe('domain.key.updated', function($domainID, $maskedKey, $oldState, $newState) {
		$name = domainName($domainID);
		$changes = diffStates($oldState, $newState);

		auditLog('domain.key.updated', [$domainID, $maskedKey, $oldState, $newState], 'Domain key "' . $maskedKey . '" on "' . $name . '" updated', $changes);
	});

	EventQueue::get()->subscribe('domain.key.deleted', function($domainID, $maskedKey) {
		$name = domainName($domainID);

		auditLog('domain.key.deleted', [$domainID, $maskedKey], 'Domain key "' . $maskedKey . '" on "' . $name . '" deleted');
	});

	EventQueue::get()->subscribe('domain.hook.created', function($domainID, $hookID) {
		$name = domainName($domainID);
		$hook = DomainHook::load(DB::get(), $hookID);
		$url = ($hook !== FALSE) ? $hook->getUrl() : 'unknown';

		auditLog('domain.hook.created', [$domainID, $hookID], 'Webhook #' . $hookID . ' (' . $url . ') on "' . $name . '" created');
	});

	EventQueue::get()->subscribe('domain.hook.updated', function($domainID, $hookID, $oldState, $newState) {
		$name = domainName($domainID);
		$changes = diffStates($oldState, $newState);

		auditLog('domain.hook.updated', [$domainID, $hookID, $oldState, $newState], 'Webhook #' . $hookID . ' on "' . $name . '" updated', $changes);
	});

	EventQueue::get()->subscribe('domain.hook.deleted', function($domainID, $hookID, $hookURL) {
		$name = domainName($domainID);

		auditLog('domain.hook.deleted', [$domainID, $hookID, $hookURL], 'Webhook #' . $hookID . ' (' . $hookURL . ') on "' . $name . '" deleted');
	});

	EventQueue::get()->subscribe('domain.userdata.updated', function($domainID, $userID, $key, $oldValue, $value) {
		$name = domainName($domainID);
		$email = userName($userID);

		$oldStr = ($oldValue !== null) ? '"' . $oldValue . '"' : 'null';
		$extended = $key . ': ' . $oldStr . ' → "' . $value . '"';

		auditLog('domain.userdata.updated', [$domainID, $userID, $key, $oldValue, $value], 'User data "' . $key . '" for ' . $email . ' on "' . $name . '" updated', $extended);
	});

	EventQueue::get()->subscribe('domain.userdata.deleted', function($domainID, $userID, $key) {
		$name = domainName($domainID);
		$email = userName($userID);

		auditLog('domain.userdata.deleted', [$domainID, $userID, $key], 'User data "' . $key . '" for ' . $email . ' on "' . $name . '" deleted');
	});
