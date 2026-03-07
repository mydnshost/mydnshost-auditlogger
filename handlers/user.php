<?php

	// ---- User events ----

	EventQueue::get()->subscribe('user.new', function($userID) {
		$email = userName($userID);

		auditLog('user.new', [$userID], 'User ' . $email . ' registered');
	});

	EventQueue::get()->subscribe('user.new.confirmed', function($userID) {
		$email = userName($userID);

		auditLog('user.new.confirmed', [$userID], 'User ' . $email . ' registration confirmed');
	});

	EventQueue::get()->subscribe('user.new.pending', function($userID) {
		$email = userName($userID);

		auditLog('user.new.pending', [$userID], 'User ' . $email . ' registration pending approval');
	});

	EventQueue::get()->subscribe('user.updated', function($userID, $oldState, $newState) {
		$old = is_string($oldState) ? json_decode($oldState, true) : $oldState;
		$new = is_string($newState) ? json_decode($newState, true) : $newState;
		$email = $new['email'] ?? $old['email'] ?? userName($userID);

		$changes = diffStates($oldState, $newState);

		auditLog('user.updated', [$userID, $oldState, $newState], 'User ' . $email . ' updated', $changes);
	});

	EventQueue::get()->subscribe('user.deleted', function($userID, $email) {
		auditLog('user.deleted', [$userID, $email], 'User ' . $email . ' deleted');
	});

	EventQueue::get()->subscribe('user.terms.accepted', function($userID, $acceptTime) {
		$email = userName($userID);

		auditLog('user.terms.accepted', [$userID, $acceptTime], 'User ' . $email . ' accepted terms');
	});

	EventQueue::get()->subscribe('user.forgotpassword.requested', function($userID) {
		$email = userName($userID);

		auditLog('user.forgotpassword.requested', [$userID], 'Password reset requested for ' . $email);
	});

	EventQueue::get()->subscribe('user.forgotpassword.completed', function($userID) {
		$email = userName($userID);

		auditLog('user.forgotpassword.completed', [$userID], 'Password reset completed for ' . $email);
	});

	EventQueue::get()->subscribe('user.customdata.updated', function($userID, $key, $oldValue, $value) {
		$email = userName($userID);

		$oldStr = ($oldValue !== null) ? '"' . $oldValue . '"' : 'null';
		$extended = $key . ': ' . $oldStr . ' → "' . $value . '"';

		auditLog('user.customdata.updated', [$userID, $key, $oldValue, $value], 'Custom data "' . $key . '" for ' . $email . ' updated', $extended);
	});

	EventQueue::get()->subscribe('user.customdata.deleted', function($userID, $key) {
		$email = userName($userID);

		auditLog('user.customdata.deleted', [$userID, $key], 'Custom data "' . $key . '" for ' . $email . ' deleted');
	});

	// ---- API Key events ----

	EventQueue::get()->subscribe('apikey.created', function($userID, $maskedKey) {
		$email = userName($userID);

		auditLog('apikey.created', [$userID, $maskedKey], 'API key "' . $maskedKey . '" for ' . $email . ' created');
	});

	EventQueue::get()->subscribe('apikey.updated', function($userID, $maskedKey, $oldState, $newState) {
		$email = userName($userID);
		$changes = diffStates($oldState, $newState);

		auditLog('apikey.updated', [$userID, $maskedKey, $oldState, $newState], 'API key "' . $maskedKey . '" for ' . $email . ' updated', $changes);
	});

	EventQueue::get()->subscribe('apikey.deleted', function($userID, $maskedKey) {
		$email = userName($userID);

		auditLog('apikey.deleted', [$userID, $maskedKey], 'API key "' . $maskedKey . '" for ' . $email . ' deleted');
	});

	// ---- 2FA events ----

	EventQueue::get()->subscribe('2fa.created', function($userID, $keyID, $keyInfoJson) {
		$keyInfo = is_string($keyInfoJson) ? json_decode($keyInfoJson, true) : $keyInfoJson;
		$type = $keyInfo['type'] ?? 'unknown';
		$desc = $keyInfo['description'] ?? '';
		$email = userName($userID);

		$label = $type;
		if (!empty($desc)) $label .= ': "' . $desc . '"';

		auditLog('2fa.created', [$userID, $keyID, $keyInfoJson], '2FA key (' . $label . ') for ' . $email . ' created');
	});

	EventQueue::get()->subscribe('2fa.updated', function($userID, $keyID, $oldState, $newState) {
		$email = userName($userID);
		$key = TwoFactorKey::loadFromUserKey(DB::get(), $userID, $keyID);
		$changes = diffStates($oldState, $newState);

		$label = '#' . $keyID;
		if ($key !== FALSE) {
			$type = $key->getType();
			$desc = $key->getDescription();
			$label = $type;
			if (!empty($desc)) $label .= ': "' . $desc . '"';
		}

		auditLog('2fa.updated', [$userID, $keyID, $oldState, $newState], '2FA key (' . $label . ') for ' . $email . ' updated', $changes);
	});

	EventQueue::get()->subscribe('2fa.deleted', function($userID, $keyID, $keyInfoJson) {
		$keyInfo = is_string($keyInfoJson) ? json_decode($keyInfoJson, true) : $keyInfoJson;
		$type = $keyInfo['type'] ?? 'unknown';
		$desc = $keyInfo['description'] ?? '';
		$email = userName($userID);

		$label = $type;
		if (!empty($desc)) $label .= ': "' . $desc . '"';

		auditLog('2fa.deleted', [$userID, $keyID, $keyInfoJson], '2FA key (' . $label . ') for ' . $email . ' deleted');
	});

	EventQueue::get()->subscribe('2fa.verified', function($userID, $keyID) {
		$email = userName($userID);
		$key = TwoFactorKey::loadFromUserKey(DB::get(), $userID, $keyID);

		$label = '#' . $keyID;
		if ($key !== FALSE) {
			$type = $key->getType();
			$desc = $key->getDescription();
			$label = $type;
			if (!empty($desc)) $label .= ': "' . $desc . '"';
		}

		auditLog('2fa.verified', [$userID, $keyID], '2FA key (' . $label . ') for ' . $email . ' verified');
	});

	EventQueue::get()->subscribe('2fa.device.deleted', function($userID, $deviceID) {
		$email = userName($userID);

		auditLog('2fa.device.deleted', [$userID, $deviceID], '2FA remembered device #' . $deviceID . ' for ' . $email . ' deleted');
	});
