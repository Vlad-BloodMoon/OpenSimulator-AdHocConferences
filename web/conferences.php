<?php
declare(strict_types=1);

// Experimental membership API. Never expose it publicly: IP admission alone
// cannot authenticate individual avatars. Restrict to owned simulator hosts.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function answer(int $status, array $data): never
{
    http_response_code($status);
    echo json_encode($data, JSON_THROW_ON_ERROR);
    exit;
}

function uuid(mixed $value): string
{
    if (!is_string($value) || !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD', $value)) {
        answer(400, ['success' => false, 'error' => 'invalid_uuid']);
    }
    return strtolower($value);
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    answer(405, ['success' => false, 'error' => 'post_required']);
}

$config = require __DIR__ . '/config.php';
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($ip, $config['allowed_ips'], true)) {
    answer(403, ['success' => false, 'error' => 'forbidden_host']);
}

try {
    $request = json_decode(file_get_contents('php://input', false, null, 0, 16384), true, 16, JSON_THROW_ON_ERROR);
    if (!is_array($request)) {
        answer(400, ['success' => false, 'error' => 'invalid_request']);
    }
    $action = $request['action'] ?? null;
    if (!in_array($action, ['start', 'invite', 'accept', 'leave', 'members'], true)) {
        answer(400, ['success' => false, 'error' => 'invalid_action']);
    }
    $session = uuid($request['session'] ?? null);
    $actor = uuid($request['actor'] ?? null);
    $invitees = [];
    foreach (($request['invitees'] ?? []) as $entry) {
        $candidate = uuid($entry);
        if ($candidate !== $actor) {
            $invitees[$candidate] = $candidate;
        }
    }
    if (count($invitees) + 1 > $config['max_members']) {
        answer(400, ['success' => false, 'error' => 'too_many_members']);
    }

    $db = new PDO($config['dsn'], $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $db->beginTransaction();
    $db->exec('DELETE FROM adhoc_sessions WHERE expires_at < UTC_TIMESTAMP()');

    if ($action === 'start') {
        if (!$invitees) {
            answer(400, ['success' => false, 'error' => 'empty_conference']);
        }
        $insert = $db->prepare('INSERT INTO adhoc_sessions (session_id, owner_id, expires_at) VALUES (?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL ? SECOND))');
        $insert->execute([$session, $actor, (int)$config['ttl_seconds']]);
        $membersToInsert = [$actor => 1];
        foreach ($invitees as $id) {
            $membersToInsert[$id] = 0;
        }
    } else {
        $lock = $db->prepare('SELECT session_id FROM adhoc_sessions WHERE session_id = ? AND expires_at > UTC_TIMESTAMP() FOR UPDATE');
        $lock->execute([$session]);
        if (!$lock->fetchColumn()) {
            $db->rollBack();
            answer(404, ['success' => false, 'error' => 'unknown_session']);
        }
        $check = $db->prepare('SELECT joined FROM adhoc_members WHERE session_id = ? AND agent_id = ?');
        $check->execute([$session, $actor]);
        $current = $check->fetchColumn();
        if ($current === false || (in_array($action, ['invite', 'members'], true) && (int)$current !== 1)) {
            $db->rollBack();
            answer(403, ['success' => false, 'error' => 'not_joined']);
        }
        $membersToInsert = [];
        if ($action === 'invite') {
            foreach ($invitees as $id) {
                $membersToInsert[$id] = 0;
            }
        } elseif ($action === 'accept') {
            $update = $db->prepare('UPDATE adhoc_members SET joined = 1, joined_at = UTC_TIMESTAMP() WHERE session_id = ? AND agent_id = ?');
            $update->execute([$session, $actor]);
        } elseif ($action === 'leave') {
            $delete = $db->prepare('DELETE FROM adhoc_members WHERE session_id = ? AND agent_id = ?');
            $delete->execute([$session, $actor]);
        }
    }

    $insertMember = $db->prepare('INSERT IGNORE INTO adhoc_members (session_id, agent_id, joined, joined_at) VALUES (?, ?, ?, IF(? = 1, UTC_TIMESTAMP(), NULL))');
    foreach ($membersToInsert as $id => $joined) {
        $insertMember->execute([$session, $id, $joined, $joined]);
    }
    $read = $db->prepare('SELECT agent_id, joined FROM adhoc_members WHERE session_id = ? ORDER BY agent_id');
    $read->execute([$session]);
    $members = [];
    while ($row = $read->fetch(PDO::FETCH_ASSOC)) {
        $members[] = ['id' => $row['agent_id'], 'joined' => (bool)$row['joined']];
    }
    if (!$members) {
        $deleteSession = $db->prepare('DELETE FROM adhoc_sessions WHERE session_id = ?');
        $deleteSession->execute([$session]);
    }
    $db->commit();
    answer(200, ['success' => true, 'members' => $members]);
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    answer(503, ['success' => false, 'error' => 'storage_error']);
} catch (JsonException $e) {
    answer(400, ['success' => false, 'error' => 'invalid_json']);
}
