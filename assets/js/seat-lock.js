// ============================================================
// BookMyTicket - Shared Seat Lock Helper
// Web + Android compatible show/seat key format
// ============================================================

import {
    db,
    doc,
    runTransaction,
    serverTimestamp
} from './firebase-config.js';

export function keyPart(value) {
    const cleaned = String(value ?? '')
        .toLowerCase()
        .replace(/\([^)]*\)/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
    return cleaned || 'x';
}

export function buildShowKey({ movieId, movieTitle, theatreId, theatreName, date, time }) {
    const movieKey = movieId ? keyPart(movieId) : keyPart(movieTitle);
    const theatreKey = theatreName ? keyPart(theatreName) : keyPart(theatreId);
    return `${movieKey}__${theatreKey}__${keyPart(date)}__${keyPart(time)}`;
}

export function buildSeatLockId(showKey, seatId) {
    return `${showKey}__${keyPart(seatId)}`;
}

// Create a temporary lock. Existing cancelled/expired locks are reusable.
export async function createTempLock(userId, showKey, seatId) {
    if (!userId) throw new Error('User is not logged in.');
    if (!showKey) throw new Error('Invalid show key.');
    if (!seatId) throw new Error('Invalid seat.');

    const lockId = buildSeatLockId(showKey, seatId);
    const lockRef = doc(db, 'seatLocks', lockId);

    await runTransaction(db, async (transaction) => {
        const existing = await transaction.get(lockRef);

        if (existing.exists()) {
            const data = existing.data();
            const status = String(data.status || '').toLowerCase();

            if (status === 'booked') {
                throw new Error(`Seat ${seatId} is already booked.`);
            }

            if (status === 'locked') {
                // A lock without expiresAt is treated as active for safety.
                const expiresAt = data.expiresAt;
                if (!expiresAt || expiresAt.toMillis() > Date.now()) {
                    if (data.userId === userId) return;
                    throw new Error(`Seat ${seatId} is already taken by another user.`);
                }
            }
        }

        transaction.set(lockRef, {
            showKey,
            seatId,
            userId,
            status: 'locked',
            lockedAt: serverTimestamp(),
            expiresAt: new Date(Date.now() + 5 * 60 * 1000)
        });
    });

    return true;
}

// Release only a temporary lock owned by the current user.
// A booked seat is never deleted; it must be cancelled from ticket history.
export async function releaseTempLock(userId, showKey, seatId) {
    if (!userId || !showKey || !seatId) return;

    const lockRef = doc(db, 'seatLocks', buildSeatLockId(showKey, seatId));

    await runTransaction(db, async (transaction) => {
        const snapshot = await transaction.get(lockRef);
        if (!snapshot.exists()) return;

        const data = snapshot.data();
        if (data.userId !== userId) return;
        if (String(data.status || '').toLowerCase() === 'booked') return;

        transaction.delete(lockRef);
    });
}
