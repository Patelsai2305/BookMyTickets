// Firebase Configuration for Book My Ticket
const firebaseConfig = {
    apiKey: "AIzaSyA6rBujIxYxb_1W2UacEVTa18EQ1sX9x3c",
    authDomain: "book-my-ticket-35ee6.firebaseapp.com",
    projectId: "book-my-ticket-35ee6",
    storageBucket: "book-my-ticket-35ee6.firebasestorage.app",
    messagingSenderId: "328788774788",
    appId: "1:328788774788:android:b00bfa0267c58463d397d1"
};

// Initialize Firebase (Standard v9 Modular Import used in modern JS)
// Note: In standard PHP projects, you might use the compat/CDN version
// for easier integration without build tools like Webpack.
import { initializeApp } from "https://www.gstatic.com/firebasejs/9.6.10/firebase-app.js";
import { getFirestore, collection, addDoc, onSnapshot, query, where, doc, setDoc } from "https://www.gstatic.com/firebasejs/9.6.10/firebase-firestore.js";
import { getAuth } from "https://www.gstatic.com/firebasejs/9.6.10/firebase-auth.js";

const app = initializeApp(firebaseConfig);
const db = getFirestore(app);
const auth = getAuth(app);

export { db, auth, collection, addDoc, onSnapshot, query, where, doc, setDoc };
