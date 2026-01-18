/**
 * Ava CMS Admin - Minimal Service Worker
 * 
 * This service worker enables PWA installation without aggressive caching.
 * It simply passes through all requests to the network.
 */

const CACHE_VERSION = 'ava-admin-v1';

// Install event - skip waiting to activate immediately
self.addEventListener('install', (event) => {
    self.skipWaiting();
});

// Activate event - claim all clients immediately
self.addEventListener('activate', (event) => {
    event.waitUntil(
        Promise.all([
            // Take control of all pages immediately
            self.clients.claim(),
            // Clean up any old caches
            caches.keys().then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((name) => name.startsWith('ava-admin-') && name !== CACHE_VERSION)
                        .map((name) => caches.delete(name))
                );
            })
        ])
    );
});

// Fetch event - network-first, no caching
self.addEventListener('fetch', (event) => {
    // Just pass through to network - no caching
    event.respondWith(fetch(event.request));
});
