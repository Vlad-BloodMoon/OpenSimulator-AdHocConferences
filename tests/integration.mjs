// Integration test for the PHP registry on localhost with a disposable MySQL.
// Run by GitHub Actions; never sends real voice credentials or avatar UUIDs.
import assert from 'node:assert/strict';
import { createHmac } from 'node:crypto';
import { readFile } from 'node:fs/promises';

const secret = (await readFile(new URL('./test-voice-key.txt', import.meta.url), 'utf8')).trim();
const region = '11111111-1111-1111-1111-111111111111';
const owner = '22222222-2222-2222-2222-222222222222';
const guest = '33333333-3333-3333-3333-333333333333';
const later = '55555555-5555-5555-5555-555555555555';
const session = '44444444-4444-4444-4444-444444444444';

async function request(action, actor, fields = {}, { tamper = false } = {}) {
  const json = JSON.stringify({ action, region, session, ...fields });
  const time = String(Math.floor(Date.now() / 1000));
  const signedBody = tamper ? `${json} ` : json;
  const signature = createHmac('sha256', secret)
    .update(`${region}\n${actor}\n${time}\n${signedBody}`)
    .digest('hex');
  const response = await fetch('http://127.0.0.1:8787/wolf_api.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Wolf-Sim': region,
      'X-Wolf-Sim-Agent': actor,
      'X-Wolf-Sim-Time': time,
      'X-Wolf-Sim-Sig': signature,
    },
    body: json,
  });
  return { status: response.status, body: await response.json() };
}

let r = await request('start', owner, { name: 'Conference test', invitees: [guest] });
assert.equal(r.status, 200, JSON.stringify(r.body));
assert.equal(r.body.session.name, 'Conference test');
assert.deepEqual(r.body.added, [guest]);
assert.equal(r.body.members.find(m => m.agent === guest).state, 'invited');

r = await request('members', guest);
assert.equal(r.status, 200, JSON.stringify(r.body));

r = await request('invite', guest, { invitees: [later] });
assert.equal(r.status, 403, 'invited member cannot invite');

r = await request('accept', guest);
assert.equal(r.status, 200, JSON.stringify(r.body));
assert.equal(r.body.members.find(m => m.agent === guest).state, 'joined');

r = await request('invite', guest, { invitees: [later] });
assert.equal(r.status, 200, JSON.stringify(r.body));
assert.deepEqual(r.body.added, [later]);

r = await request('invite', guest, { invitees: [later] });
assert.deepEqual(r.body.added, [later], 'pending invitee can be invited again after losing their viewer session');

r = await request('accept', later);
assert.equal(r.status, 200, JSON.stringify(r.body));
r = await request('invite', guest, { invitees: [later] });
assert.deepEqual(r.body.added, [], 'already joined participants must not be re-invited');

// Crash/relogin: the viewer has lost its old conference window.
// The old membership must be removed so WebRTC cannot retry a ghost conference
// while teleporting; the avatar can still be freshly invited by another member.
r = await request('relogin', guest);
assert.equal(r.status, 200, JSON.stringify(r.body));
r = await request('members', owner);
assert.equal(r.body.members.some(m => m.agent === guest), false, 'login must remove the stale conference membership');
r = await request('invite', owner, { invitees: [guest] });
assert.deepEqual(r.body.added, [guest], 'reconnected avatar must receive a fresh invitation');
r = await request('accept', guest);
assert.equal(r.status, 200, JSON.stringify(r.body));
r = await request('invite', owner, { invitees: [guest] });
assert.deepEqual(r.body.added, [], 'do not re-invite a connected participant');

r = await request('voice', owner);
assert.equal(r.status, 200, JSON.stringify(r.body));
assert.equal(r.body.session.voice, 1);

r = await request('members', owner, {}, { tamper: true });
assert.equal(r.status, 403, 'tampered signed JSON must be rejected');

r = await request('leave', guest);
assert.equal(r.status, 200, JSON.stringify(r.body));
assert.equal(r.body.members.some(m => m.agent === guest), false);

r = await request('relogin', owner);
assert.equal(r.status, 200, JSON.stringify(r.body));

r = await request('members', owner);
assert.equal(r.status, 403, 'relogged owner is no longer a member of the old conference');

console.log('WolfConference PHP + MySQL integration: PASS');
