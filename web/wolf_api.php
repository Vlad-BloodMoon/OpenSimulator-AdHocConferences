<?php
declare(strict_types=1);

// WolfConference registry prototype for a private, trusted BloodMoon test network.
// HMAC uses the EXISTING voice credential key, not a newly distributed secret.
// Do not expose to arbitrary simulator operators: a trusted sim can act for its users.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function reply(int $status, bool $ok, string $error = '', array $extra = []): never
{
    http_response_code($status);
    echo json_encode(array_merge(['success' => $ok], $ok ? [] : ['error' => $error], $extra), JSON_THROW_ON_ERROR);
    exit;
}

function validUuid(mixed $value): string
{
    if (!is_string($value) || !preg_match('/\A[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\z/i', $value)) {
        reply(400, false, 'invalid uuid');
    }
    return strtolower($value);
}

function readConference(PDO $db, string $session, array $added = []): array
{
    $q = $db->prepare('SELECT name, voice FROM wolf_conf_sessions WHERE id = ?');
    $q->execute([$session]);
    $s = $q->fetch(PDO::FETCH_ASSOC);
    if (!$s) {
        return ['session' => ['name' => '', 'voice' => 0], 'members' => [], 'added' => $added];
    }
    $q = $db->prepare('SELECT agent_id AS agent, display_name AS name, state FROM wolf_conf_members WHERE session_id = ? ORDER BY agent_id');
    $q->execute([$session]);
    return [
        'session' => ['name' => $s['name'], 'voice' => (int)$s['voice']],
        'members' => $q->fetchAll(PDO::FETCH_ASSOC),
        'added' => $added,
    ];
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    reply(405, false, 'POST required');
}

try {
    $cfg = require __DIR__ . '/wolf_config.php';
    $raw = file_get_contents('php://input', false, null, 0, 16384);
    if (!is_string($raw) || strlen($raw) === 0 || strlen($raw) >= 16384) {
        reply(400, false, 'invalid body');
    }
    $body = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
    if (!is_array($body)) {
        reply(400, false, 'invalid JSON object');
    }
    $region = validUuid($_SERVER['HTTP_X_WOLF_SIM'] ?? null);
    $actor = validUuid($_SERVER['HTTP_X_WOLF_SIM_AGENT'] ?? null);
    $time = $_SERVER['HTTP_X_WOLF_SIM_TIME'] ?? '';
    $signature = $_SERVER['HTTP_X_WOLF_SIM_SIG'] ?? '';
    if (!is_string($time) || !preg_match('/\A[0-9]{10,11}\z/', $time)
        || abs(time() - (int)$time) > 300 || !is_string($signature)
        || !preg_match('/\A[a-f0-9]{64}\z/i', $signature)) {
        reply(403, false, 'invalid signature metadata');
    }
    if (($body['region'] ?? '') !== $region) {
        reply(403, false, 'region mismatch');
    }
    $secret = trim((string)file_get_contents($cfg['existing_cred_key_file']));
    if (strlen($secret) < 32) {
        reply(503, false, 'credential key unavailable');
    }
    $expected = hash_hmac('sha256', $region . "\n" . $actor . "\n" . $time . "\n" . $raw, $secret);
    if (!hash_equals($expected, strtolower($signature))) {
        reply(403, false, 'bad signature');
    }

    $action = $body['action'] ?? null;
    if (!in_array($action, ['start', 'invite', 'accept', 'leave', 'members', 'relogin', 'voice'], true)) {
        reply(400, false, 'unsupported action');
    }
    $session = validUuid($body['session'] ?? null);
    $invitees = [];
    foreach ($body['invitees'] ?? [] as $value) {
        $id = validUuid($value);
        if ($id !== $actor) {
            $invitees[$id] = $id;
        }
    }
    if (count($invitees) >= (int)$cfg['max_members']) {
        reply(400, false, 'too many invitees');
    }
    $db = new PDO($cfg['dsn'], $cfg['db_user'], $cfg['db_password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    // The same authorization source used by WolfVoice's administration:
    // automatically and manually registered hosts must both be status=allowed.
    // The local list is only for on-server diagnostics; it is not a second
    // remote-server allowlist. Never trust X-Forwarded-For here.
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!filter_var($clientIp, FILTER_VALIDATE_IP)) {
        reply(403, false, 'host not allowed');
    }
    if (!in_array($clientIp, $cfg['allowed_ips'] ?? [], true)) {
        $host = $db->prepare(
            "SELECT 1 FROM wolfvoice_admin.servers
             WHERE status = 'allowed'
               AND (resolved_ip = ? OR server_address = ?)
             LIMIT 1"
        );
        $host->execute([$clientIp, $clientIp]);
        if (!$host->fetchColumn()) {
            reply(403, false, 'host not allowed');
        }
    }
    $db->beginTransaction();
    // Bounded cleanup: expired sessions cascade-delete their memberships.
    $db->exec('DELETE FROM wolf_conf_sessions WHERE expires_at <= UTC_TIMESTAMP() LIMIT 100');

    if ($action === 'relogin') {
        $q = $db->prepare("UPDATE wolf_conf_members SET state = 'invited' WHERE agent_id = ?");
        $q->execute([$actor]);
        $db->commit();
        reply(200, true, '', ['members' => []]);
    }

    if ($action === 'start') {
        if (!$invitees) {
            $db->rollBack();
            reply(400, false, 'empty conference');
        }
        $name = $body['name'] ?? '';
        if (!is_string($name) || $name === '' || mb_strlen($name) > 128) {
            $db->rollBack();
            reply(400, false, 'invalid name');
        }
        $q = $db->prepare('INSERT INTO wolf_conf_sessions (id, name, expires_at) VALUES (?, ?, DATE_ADD(UTC_TIMESTAMP(), INTERVAL ? SECOND))');
        $q->execute([$session, $name, (int)$cfg['ttl_seconds']]);
        $q = $db->prepare("INSERT INTO wolf_conf_members (session_id, agent_id, state) VALUES (?, ?, ?)");
        $q->execute([$session, $actor, 'joined']);
        foreach ($invitees as $id) {
            $q->execute([$session, $id, 'invited']);
        }
        $added = array_values($invitees);
    } else {
        $q = $db->prepare('SELECT id FROM wolf_conf_sessions WHERE id = ? AND expires_at > UTC_TIMESTAMP() FOR UPDATE');
        $q->execute([$session]);
        if (!$q->fetchColumn()) {
            $db->rollBack();
            reply(404, false, 'conference does not exist');
        }
        $q = $db->prepare('SELECT state FROM wolf_conf_members WHERE session_id = ? AND agent_id = ?');
        $q->execute([$session, $actor]);
        $state = $q->fetchColumn();
        if ($state === false || (in_array($action, ['invite', 'voice'], true) && $state !== 'joined')) {
            $db->rollBack();
            reply(403, false, 'not a member');
        }
        $added = [];
        if ($action === 'invite') {
            $count = $db->prepare('SELECT COUNT(*) FROM wolf_conf_members WHERE session_id = ?');
            $count->execute([$session]);
            $existing = (int)$count->fetchColumn();
            $existingIds = $db->prepare('SELECT agent_id FROM wolf_conf_members WHERE session_id = ?');
            $existingIds->execute([$session]);
            $known = array_fill_keys($existingIds->fetchAll(PDO::FETCH_COLUMN), true);
            $newInvitees = array_diff_key($invitees, $known);
            if ($existing + count($newInvitees) > (int)$cfg['max_members']) {
                $db->rollBack();
                reply(403, false, 'conference full');
            }
            $q = $db->prepare("INSERT IGNORE INTO wolf_conf_members (session_id, agent_id, state) VALUES (?, ?, 'invited')");
            foreach ($newInvitees as $id) {
                $q->execute([$session, $id]);
                if ($q->rowCount() === 1) {
                    $added[] = $id;
                }
            }
        } elseif ($action === 'accept') {
            $q = $db->prepare("UPDATE wolf_conf_members SET state = 'joined' WHERE session_id = ? AND agent_id = ?");
            $q->execute([$session, $actor]);
        } elseif ($action === 'leave') {
            $q = $db->prepare('DELETE FROM wolf_conf_members WHERE session_id = ? AND agent_id = ?');
            $q->execute([$session, $actor]);
        } elseif ($action === 'voice') {
            $q = $db->prepare('UPDATE wolf_conf_sessions SET voice = 1 WHERE id = ?');
            $q->execute([$session]);
        }
    }
    $result = readConference($db, $session, $added);
    $db->commit();
    reply(200, true, '', $result);
} catch (JsonException $e) {
    reply(400, false, 'invalid JSON');
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    reply(503, false, 'storage unavailable');
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    reply(500, false, 'internal error');
}
