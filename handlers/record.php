<?php

	EventQueue::get()->subscribe('record.add', function($domainID, $recordID, $recordJson) {
		$record = is_string($recordJson) ? json_decode($recordJson, true) : $recordJson;
		$name = $record['name'] ?? 'unknown';
		$type = $record['type'] ?? 'unknown';
		$domain = domainName($domainID);

		$details = [];
		if (isset($record['content'])) $details[] = 'content: "' . $record['content'] . '"';
		if (isset($record['ttl'])) $details[] = 'ttl: ' . $record['ttl'];
		if (isset($record['priority'])) $details[] = 'priority: ' . $record['priority'];

		auditLog('record.add', [$domainID, $recordID, $recordJson], '"' . $name . '" ' . $type . ' record on "' . $domain . '" added', implode(', ', $details));
	});

	EventQueue::get()->subscribe('record.update', function($domainID, $recordID, $oldRecordJson, $newRecordJson) {
		$oldRecord = is_string($oldRecordJson) ? json_decode($oldRecordJson, true) : $oldRecordJson;
		$newRecord = is_string($newRecordJson) ? json_decode($newRecordJson, true) : $newRecordJson;
		$name = $newRecord['name'] ?? $oldRecord['name'] ?? 'unknown';
		$type = $newRecord['type'] ?? $oldRecord['type'] ?? 'unknown';
		$domain = domainName($domainID);

		$changes = diffStates($oldRecord, $newRecord);

		auditLog('record.update', [$domainID, $recordID, $oldRecordJson, $newRecordJson], '"' . $name . '" ' . $type . ' record on "' . $domain . '" updated', $changes);
	});

	EventQueue::get()->subscribe('record.delete', function($domainID, $recordID, $recordJson) {
		$record = is_string($recordJson) ? json_decode($recordJson, true) : $recordJson;
		$name = $record['name'] ?? 'unknown';
		$type = $record['type'] ?? 'unknown';
		$domain = domainName($domainID);

		auditLog('record.delete', [$domainID, $recordID, $recordJson], '"' . $name . '" ' . $type . ' record on "' . $domain . '" deleted');
	});
