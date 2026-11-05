// No production secret. Protocol regression vectors for WolfConference/brain.
// Run: node tests/hmac-vectors.mjs
import assert from 'node:assert/strict';
import { createHmac } from 'node:crypto';

const secret = 'TEST-ONLY-KEY-DO-NOT-USE-ON-A-GRID-0000000000000';
const region = '11111111-1111-1111-1111-111111111111';
const actor = '22222222-2222-2222-2222-222222222222';
const recipient = '33333333-3333-3333-3333-333333333333';
const timestamp = '1700000000';
const body = JSON.stringify({ action: 'members', region, session: '44444444-4444-4444-4444-444444444444' });
const envelope = JSON.stringify({ k: 'text', session: '44444444-4444-4444-4444-444444444444', ts: timestamp });
const hmac = value => createHmac('sha256', secret).update(value, 'utf8').digest('hex');

const serviceSignature = hmac(`${region}\n${actor}\n${timestamp}\n${body}`);
const transportSignature = hmac(`wolfconf1:${recipient}:${envelope}`);

assert.equal(serviceSignature.length, 64);
assert.equal(transportSignature.length, 64);
assert.notEqual(serviceSignature, transportSignature, 'HTTP and grid-envelope domains must differ');
assert.notEqual(hmac(`wolfconf1:${actor}:${envelope}`), transportSignature, 'recipient binding');
assert.notEqual(hmac(`${region}\n${recipient}\n${timestamp}\n${body}`), serviceSignature, 'actor binding');
assert.notEqual(hmac(`${region}\n${actor}\n${timestamp}\n${body} `), serviceSignature, 'exact JSON bytes');
console.log('WolfConference HMAC format vectors: PASS');
console.log('HTTP:', serviceSignature);
console.log('IM:', transportSignature);
