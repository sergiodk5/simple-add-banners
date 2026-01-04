/**
 * Simple Add Banners - Impression Tracking Script
 *
 * Tracks banner impressions using Intersection Observer API.
 * An impression is recorded when:
 * 1. Banner becomes at least 50% visible in the viewport
 * 2. Banner remains visible for at least 1 second
 * 3. Only one impression per banner per session is recorded
 *
 * @package
 */

/* global sabTracking */

(function () {
	'use strict';

	// Check for required globals.
	if (typeof sabTracking === 'undefined') {
		return;
	}

	// Check for Intersection Observer support.
	if (!('IntersectionObserver' in window)) {
		return;
	}

	const API_URL = sabTracking.restUrl + 'track/impression';
	const NONCE = sabTracking.nonce;
	const VISIBILITY_THRESHOLD = 0.5; // 50% visibility required.
	const VISIBILITY_TIME = 1000; // 1 second required.
	const STORAGE_KEY = 'sab_tracked';

	// Track which banners have been counted this session.
	const tracked = new Set(getTrackedFromStorage());

	// Timer map for pending impressions.
	const timers = new Map();

	/**
	 * Get tracked banners from sessionStorage.
	 *
	 * @return {Array} Array of tracked banner keys.
	 */
	function getTrackedFromStorage() {
		try {
			const stored = sessionStorage.getItem(STORAGE_KEY);
			return stored ? JSON.parse(stored) : [];
		} catch (e) {
			return [];
		}
	}

	/**
	 * Save tracked banners to sessionStorage.
	 */
	function saveTrackedToStorage() {
		try {
			sessionStorage.setItem(
				STORAGE_KEY,
				JSON.stringify(Array.from(tracked))
			);
		} catch (e) {
			// Storage might be full or disabled.
		}
	}

	/**
	 * Generate a unique key for a banner/placement combination.
	 *
	 * @param {Element} banner The banner element.
	 * @return {string} The unique key.
	 */
	function getKey(banner) {
		return banner.dataset.bannerId + ':' + banner.dataset.placementId;
	}

	/**
	 * Start the visibility timer for a banner.
	 *
	 * @param {Element} banner The banner element.
	 */
	function startTimer(banner) {
		const key = getKey(banner);

		// Skip if already tracked or timer already running.
		if (tracked.has(key) || timers.has(banner)) {
			return;
		}

		const timer = setTimeout(function () {
			trackImpression(banner);
		}, VISIBILITY_TIME);

		timers.set(banner, timer);
	}

	/**
	 * Clear the visibility timer for a banner.
	 *
	 * @param {Element} banner The banner element.
	 */
	function clearTimer(banner) {
		const timer = timers.get(banner);
		if (timer) {
			clearTimeout(timer);
			timers.delete(banner);
		}
	}

	/**
	 * Track an impression for a banner.
	 *
	 * @param {Element} banner The banner element.
	 */
	function trackImpression(banner) {
		const key = getKey(banner);

		// Skip if already tracked.
		if (tracked.has(key)) {
			return;
		}

		// Mark as tracked immediately to prevent duplicates.
		tracked.add(key);
		saveTrackedToStorage();

		// Remove from timers.
		timers.delete(banner);

		// Send tracking request.
		const data = {
			banner_id: parseInt(banner.dataset.bannerId, 10),
			placement_id: parseInt(banner.dataset.placementId, 10),
			token: banner.dataset.trackToken,
		};

		fetch(API_URL, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': NONCE,
			},
			body: JSON.stringify(data),
			credentials: 'same-origin',
		}).catch(function () {
			// Silently fail - we don't want to disrupt the user experience.
		});
	}

	/**
	 * Handle intersection changes.
	 *
	 * @param {IntersectionObserverEntry[]} entries The intersection entries.
	 */
	function handleIntersection(entries) {
		entries.forEach(function (entry) {
			if (
				entry.isIntersecting &&
				entry.intersectionRatio >= VISIBILITY_THRESHOLD
			) {
				startTimer(entry.target);
			} else {
				clearTimer(entry.target);
			}
		});
	}

	// Create the intersection observer.
	const observer = new IntersectionObserver(handleIntersection, {
		threshold: VISIBILITY_THRESHOLD,
	});

	// Find all banners with tracking tokens and observe them.
	const banners = document.querySelectorAll('.sab-banner[data-track-token]');
	banners.forEach(function (banner) {
		// Only observe banners that have all required data attributes.
		if (
			banner.dataset.bannerId &&
			banner.dataset.placementId &&
			banner.dataset.trackToken
		) {
			observer.observe(banner);
		}
	});
})();
