#!/usr/bin/env php
<?php
	// This takes events from the event queue, and creates audit log entries.

	use shanemcc\phpdb\DB;

	require_once(dirname(__FILE__) . '/../functions.php');

	echo showTime(), ' ', 'Audit Logger started.', "\n";

	function actor() {
		$actor = EventQueue::get()->getActor();
		if (empty($actor)) return '';

		if (($actor['type'] ?? '') === 'domainkey') {
			$desc = 'domainkey';
			if (!empty($actor['key'])) $desc .= ' "' . $actor['key'] . '"';
			if (!empty($actor['domain'])) $desc .= ' for ' . $actor['domain'];
		} else {
			$desc = $actor['email'] ?? 'unknown';
			if (($actor['type'] ?? '') === 'apikey' && !empty($actor['key'])) {
				$desc .= ' via apikey "' . $actor['key'] . '"';
			}
		}
		if (!empty($actor['impersonator'])) {
			$desc .= ' impersonated by ' . $actor['impersonator'];
		}
		return $desc;
	}

	function auditLog($type, $args, $summary, $extendedSummary = '') {
		$entry = new AuditEntry(DB::get());
		$entry->setTime(time());
		$entry->setActor(actor());
		$entry->setType($type);
		$entry->setArgs($args);
		$entry->setSummary($summary);
		$entry->setExtendedSummary($extendedSummary);
		$entry->save();

		$line = $summary;
		if (!empty($extendedSummary)) $line .= ' | ' . $extendedSummary;
		echo showTime(), ' ', 'Audit: [', $type, '] ', $line, ' (', actor(), ')', "\n";
	}

	function domainName($domainID) {
		$domain = Domain::load(DB::get(), $domainID);
		return ($domain !== FALSE) ? $domain->getDomainRaw() : 'unknown domain #' . $domainID;
	}

	function userName($userID) {
		$user = User::load(DB::get(), $userID);
		return ($user !== FALSE) ? $user->getEmail() : 'unknown user #' . $userID;
	}

	function diffStates($oldJson, $newJson, $prefix = '') {
		$old = is_string($oldJson) ? json_decode($oldJson, true) : $oldJson;
		$new = is_string($newJson) ? json_decode($newJson, true) : $newJson;

		if (!is_array($old) || !is_array($new)) return '';

		$changes = [];
		$allKeys = array_unique(array_merge(array_keys($old), array_keys($new)));
		sort($allKeys);

		foreach ($allKeys as $key) {
			$oldVal = $old[$key] ?? null;
			$newVal = $new[$key] ?? null;
			$fullKey = $prefix !== '' ? $prefix . '.' . $key : $key;

			if ($oldVal !== $newVal) {
				if (is_array($oldVal) && is_array($newVal)) {
					$sub = diffStates($oldVal, $newVal, $fullKey);
					if (!empty($sub)) $changes[] = $sub;
				} else {
					$oldStr = is_string($oldVal) ? '"' . $oldVal . '"' : json_encode($oldVal);
					$newStr = is_string($newVal) ? '"' . $newVal . '"' : json_encode($newVal);
					$changes[] = $fullKey . ': ' . $oldStr . ' → ' . $newStr;
				}
			}
		}

		return implode(', ', $changes);
	}

	foreach (recursiveFindFiles(__DIR__ . '/handlers') as $file) {
		echo showTime(), ' ', 'Loading from: ', $file, "\n";
		include_once($file);
	}

	EventQueue::get()->consumeEvents(function ($event) {
		if (is_array($event) && isset($event['event'])) {
			// TODO: We probably want to check this less-often than every event.
			checkDBAlive();

			EventQueue::get()->handleSubscribers($event);
		}
	});

	RabbitMQ::get()->consume();
