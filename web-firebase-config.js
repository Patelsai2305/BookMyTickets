// Firebase Configuration (v10 JS Modular SDK)

import { initializeApp } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js";

import {
    getAuth,
    onAuthStateChanged,
    signInWithEmailAndPassword,
    createUserWithEmailAndPassword,
    signOut,
    sendPasswordResetEmail,
    sendEmailVerification
} from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";

import {
    getFirestore,
    doc,
    getDoc,
    setDoc,
    updateDoc,
    deleteDoc,
    collection,
    addDoc,
    query,
    where,
    getDocs,
    onSnapshot,
    runTransaction,
    writeBatch,
    arrayUnion,
    serverTimestamp,
    orderBy,
    limit
} from "https://www.gstatic.com/firebasejs/10.7.1/firebase-firestore.js";


const firebaseConfig = {
    apiKey: "AIzaSyCtl2DZRo8144tsUfkHPo3ixSN3LMKP8_I",
    authDomain: "book-my-ticket-35ee6.firebaseapp.com",
    projectId: "book-my-ticket-35ee6",
    storageBucket: "book-my-ticket-35ee6.firebasestorage.app",
    messagingSenderId: "328788774788",
    appId: "1:328788774788:web:2ec5aacaad34a473d397d1",
    measurementId: "G-LTR3600WS2"
};


const app = initializeApp(firebaseConfig);

const auth = getAuth(app);

const db = getFirestore(app);


export {
    auth,
    db,

    onAuthStateChanged,

    signInWithEmailAndPassword,
    createUserWithEmailAndPassword,
    signOut,

    sendPasswordResetEmail,
    sendEmailVerification,

    doc,
    getDoc,
    setDoc,
    updateDoc,
    deleteDoc,

    collection,
    addDoc,

    query,
    where,
    getDocs,
    onSnapshot,

    runTransaction,
    writeBatch,

    arrayUnion,
    serverTimestamp,

    orderBy,
    limit
};