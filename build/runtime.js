(function () {
	'use strict';
	var FREE_EFFECTS = [
		'fade-in',
		'fade-up',
		'fade-down',
		'fade-left',
		'fade-right',
		'fade-scale',
		'slide-up',
		'slide-down',
		'slide-left',
		'slide-right',
		'zoom-in',
		'zoom-out',
		'scale-up',
		'scale-down',
		'rotate-in-left',
		'rotate-in-right',
		'blur-in',
		'flip-x',
		'flip-y',
		'reveal-top'
	];
	var instances = new WeakMap(),
		roots = new Set(),
		pausedRoots = new WeakSet(),
		effectFactories = new Map(),
		mutationObserver = null,
		suspended = false;
	var settings = window.KinetivoSettings ||
		window.GutenbergMotionSettings || {
			mobileQuery: '(max-width: 767px)',
			tabletQuery: '(max-width: 1024px)',
			reducedPolicy: 'inherit',
			debug: false
		};
	var mobileMedia = window.matchMedia(settings.mobileQuery),
		tabletMedia = window.matchMedia(settings.tabletQuery || '(max-width: 1024px)'),
		reducedMedia = window.matchMedia('(prefers-reduced-motion: reduce)');
	var easing = {
		ease: 'ease',
		'ease-in': 'ease-in',
		'ease-out': 'ease-out',
		'ease-in-out': 'ease-in-out',
		linear: 'linear',
		gentle: 'cubic-bezier(.39,.575,.565,1)',
		standard: 'cubic-bezier(.16,1,.3,1)',
		emphasized: 'cubic-bezier(.19,1,.22,1)',
		expressive: 'cubic-bezier(.34,1.56,.64,1)'
	};

	var capabilities = {
		tier: 'free',
		engine: 'native',
		css: true,
		webAnimations: true,
		intersectionObserver: true,
		effects: FREE_EFFECTS.length
	};
	function warn(message, element) {
		if (settings.debug && window.console)
			console.warn('[Norin Motion - Block Animation] ' + message, element || '');
	}
	function clone(value) {
		return JSON.parse(JSON.stringify(value));
	}
	function merge(base, override) {
		var out = clone(base);
		if (!override || typeof override !== 'object') return out;
		Object.keys(override).forEach(function (key) {
			var value = override[key];
			if (
				value &&
				typeof value === 'object' &&
				!Array.isArray(value) &&
				out[key] &&
				typeof out[key] === 'object'
			)
				out[key] = merge(out[key], value);
			else out[key] = value;
		});
		return out;
	}

	function resolveConfig(root) {
		try {
			var config = JSON.parse(root.getAttribute('data-gmotion') || 'null');
			if (
				!config ||
				config.v !== 2 ||
				config.enabled !== true ||
				!config.effect ||
				!config.effect.preset
			)
				return null;
			if (mobileMedia.matches)
				config = merge(config, config.responsive && config.responsive.mobile);
			else if (tabletMedia.matches)
				config = merge(config, config.responsive && config.responsive.tablet);
			if (config.enabled === false) return null;
			return config;
		} catch (error) {
			warn('Invalid configuration ignored.', root);
			return null;
		}
	}

	function reducedPolicy(config) {
		if (!reducedMedia.matches) return 'allow';
		if (settings.reducedPolicy === 'disable' || settings.reducedPolicy === 'simplify')
			return settings.reducedPolicy;
		return config.a11y && config.a11y.reducedMotion === 'simplify' ? 'simplify' : 'disable';
	}

	function curatedState(config) {
		var preset = config.effect.preset,
			from = Object.assign(
				{ opacity: 0, x: 0, y: 0, scale: 1, rotation: 0, blur: 0 },
				config.effect.from || {}
			),
			to = Object.assign(
				{ opacity: 1, x: 0, y: 0, scale: 1, rotation: 0, blur: 0 },
				config.effect.to || {}
			),
			axis = /^(?:fade|slide)-(?:left|right)$/.test(preset) ? 'x' : 'y',
			savedDistance = config.effect.from && config.effect.from[axis],
			distance = typeof savedDistance === 'number' ? Math.abs(savedDistance) : 32;
		if (
			[
				'fade-in',
				'fade-scale',
				'zoom-in',
				'zoom-out',
				'scale-up',
				'scale-down',
				'rotate-in-left',
				'rotate-in-right',
				'blur-in',
				'flip-x',
				'flip-y',
				'reveal-top'
			].indexOf(preset) !== -1
		) {
			from.x = 0;
			from.y = 0;
		}
		if (preset === 'fade-up' || preset === 'slide-up') from.y = distance;
		if (preset === 'fade-down' || preset === 'slide-down') from.y = -distance;
		if (preset === 'fade-left' || preset === 'slide-left') {
			from.x = -distance;
			from.y = 0;
		}
		if (preset === 'fade-right' || preset === 'slide-right') {
			from.x = distance;
			from.y = 0;
		}
		if (preset.indexOf('slide-') === 0) from.opacity = 1;
		if (preset === 'fade-scale' || preset === 'zoom-in' || preset === 'scale-up')
			from.scale =
				config.effect.from && typeof config.effect.from.scale === 'number' ? from.scale : 0.82;
		if (preset === 'zoom-out' || preset === 'scale-down')
			from.scale =
				config.effect.from && typeof config.effect.from.scale === 'number' ? from.scale : 1.18;
		if (preset === 'rotate-in-left')
			from.rotation =
				config.effect.from && typeof config.effect.from.rotation === 'number' ? from.rotation : -12;
		if (preset === 'rotate-in-right')
			from.rotation =
				config.effect.from && typeof config.effect.from.rotation === 'number' ? from.rotation : 12;
		if (preset === 'blur-in')
			from.blur = Math.abs(
				config.effect.from && typeof config.effect.from.blur === 'number' ? from.blur : 12
			);
		return { from: from, to: to };
	}

	function frame(state, preset, initial) {
		var transform =
			'translate3d(' +
			state.x +
			'px,' +
			state.y +
			'px,0) scale(' +
			state.scale +
			') rotate(' +
			state.rotation +
			'deg)';
		var value = {
			opacity: state.opacity,
			transform: transform,
			filter: state.blur ? 'blur(' + state.blur + 'px)' : 'blur(0px)'
		};
		if (preset === 'flip-x')
			value.transform = initial
				? 'perspective(800px) rotateX(-90deg)'
				: 'perspective(800px) rotateX(0deg)';
		if (preset === 'flip-y')
			value.transform = initial
				? 'perspective(800px) rotateY(-90deg)'
				: 'perspective(800px) rotateY(0deg)';
		if (preset === 'reveal-top') {
			value.opacity = 1;
			value.clipPath = initial ? 'inset(0 0 100% 0)' : 'inset(0 0 0 0)';
		}
		return value;
	}

	function nativeFactory(root, config, simplified) {
		var state = curatedState(config),
			timing = config.timing || {},
			animation = null;
		var frames = simplified
			? [{ opacity: 0 }, { opacity: 1 }]
			: [
					frame(state.from, config.effect.preset, true),
					frame(state.to, config.effect.preset, false)
				];
		// Apply the full effect only during playback. No fill keeps content visible
		// before a delayed animation starts and after it finishes or is cancelled.
		function play() {
			if (typeof root.animate !== 'function') return;
			if (animation) animation.cancel();
			root.classList.add('is-kinetivo-animating');
			animation = root.animate(frames, {
				duration: simplified ? 200 : Math.max(100, (+timing.duration || 0.7) * 1000),
				delay: simplified ? 0 : Math.max(0, (+timing.delay || 0) * 1000),
				easing: simplified ? 'ease-out' : easing[timing.easing] || 'ease-out',
				iterations: simplified ? 1 : Math.max(1, +timing.iterations || 1),
				direction: simplified ? 'normal' : timing.direction || 'normal',
				fill: 'none'
			});
			var current = animation;
			animation.finished
				.catch(function () {})
				.then(function () {
					if (animation === current) root.classList.remove('is-kinetivo-animating');
				});
		}
		return {
			play: play,
			destroy: function () {
				if (animation) animation.cancel();
				root.classList.remove('is-kinetivo-animating');
			}
		};
	}

	FREE_EFFECTS.forEach(function (preset) {
		effectFactories.set(preset, nativeFactory);
	});

	function startMargin(config) {
		var match = /^(?:top|bottom) (\d{1,3})%$/.exec((config.trigger && config.trigger.start) || ''),
			percentage = match ? Math.max(1, Math.min(100, +match[1])) : 85;
		return (
			'0px 0px -' +
			((window.innerHeight || document.documentElement.clientHeight) * (100 - percentage)) / 100 +
			'px 0px'
		);
	}
	function inViewport(root) {
		if (!root.getBoundingClientRect) return false;
		var rect = root.getBoundingClientRect(),
			height = window.innerHeight || document.documentElement.clientHeight;
		return rect.bottom > 0 && rect.top < height;
	}
	function initialize(root) {
		if (suspended || !(root instanceof Element) || instances.has(root) || pausedRoots.has(root))
			return;
		var config = resolveConfig(root);
		if (!config) return;
		var policy = reducedPolicy(config),
			factory = effectFactories.get(config.effect.preset);
		if (policy === 'disable') {
			instances.set(root, {
				destroy: function () {
					instances.delete(root);
					roots.delete(root);
					root.removeAttribute('data-kinetivo-state');
				}
			});
			roots.add(root);
			root.setAttribute('data-kinetivo-state', 'reduced');
			return;
		}
		if (!factory) {
			root.setAttribute('data-kinetivo-state', 'inactive-capability');
			warn('Required effect provider is unavailable.', root);
			return;
		}
		var controller,
			observer = null,
			disposed = false,
			trigger = config.trigger || { type: 'load' };
		try {
			controller = factory(root, config, policy === 'simplify', {
				preserveVisibility: inViewport(root)
			});
		} catch (error) {
			warn('Effect initialization failed; content remains available.', root);
			return;
		}
		function destroyInstance() {
			if (disposed) return;
			disposed = true;
			if (observer) observer.disconnect();
			if (root.removeEventListener) root.removeEventListener('focusin', revealFocusedContent);
			try {
				controller.destroy();
			} catch (error) {
				warn('Effect cleanup failed.', root);
			} finally {
				instances.delete(root);
				roots.delete(root);
				root.removeAttribute('data-kinetivo-state');
			}
		}
		function play() {
			if (disposed) return;
			try {
				controller.play();
			} catch (error) {
				destroyInstance();
				warn('Effect playback failed; content restored.', root);
			}
		}
		function revealFocusedContent() {
			destroyInstance();
		}
		instances.set(root, { destroy: destroyInstance });
		roots.add(root);
		root.setAttribute('data-kinetivo-state', 'ready');
		// Focus must never land in an invisible entrance animation.
		if (
			root.addEventListener &&
			!controller.handlesFocus &&
			['hover', 'focus', 'click'].indexOf(trigger.type) === -1
		)
			root.addEventListener('focusin', revealFocusedContent);
		try {
			if (
				!controller.handlesTrigger &&
				trigger.type === 'viewport' &&
				'IntersectionObserver' in window
			) {
				observer = new IntersectionObserver(
					function (entries) {
						entries.forEach(function (entry) {
							if (entry.isIntersecting) {
								play();
								if (trigger.once) observer.disconnect();
							}
						});
					},
					{ rootMargin: startMargin(config), threshold: 0 }
				);
				observer.observe(root);
			} else if (!controller.handlesTrigger) play();
		} catch (error) {
			destroyInstance();
			warn('Effect trigger failed; content restored.', root);
		}
	}

	function refresh(root, automatic) {
		var scope = root && root.querySelectorAll ? root : document;
		function visit(element) {
			if (!automatic) pausedRoots.delete(element);
			initialize(element);
		}
		if (scope.matches && scope.matches('[data-gmotion]')) visit(scope);
		scope.querySelectorAll('[data-gmotion]').forEach(visit);
	}
	function destroy(root, automatic) {
		Array.from(roots).forEach(function (element) {
			if (!root || root === element || (root.contains && root.contains(element))) {
				if (!automatic) pausedRoots.add(element);
				var instance = instances.get(element);
				if (instance) instance.destroy();
			}
		});
	}
	function rebuild() {
		destroy();
		document.querySelectorAll('[data-gmotion]').forEach(function (element) {
			element.removeAttribute('data-kinetivo-state');
		});
		refresh();
	}
	function registerEffect(preset, factory) {
		if (typeof preset !== 'string' || typeof factory !== 'function') return function () {};
		effectFactories.set(preset, factory);
		return function () {
			if (effectFactories.get(preset) === factory) effectFactories.delete(preset);
			rebuild();
		};
	}
	function observe() {
		if (suspended || mutationObserver || !('MutationObserver' in window)) return;
		mutationObserver = new MutationObserver(function (records) {
			queueMicrotask(function () {
				if (suspended) return;
				records.forEach(function (record) {
					record.removedNodes.forEach(function (node) {
						if (node.nodeType === 1 && !node.isConnected) destroy(node, true);
					});
					record.addedNodes.forEach(function (node) {
						if (node.nodeType === 1 && node.isConnected) refresh(node, true);
					});
				});
			});
		});
		mutationObserver.observe(document.documentElement, { childList: true, subtree: true });
	}
	function suspend() {
		suspended = true;
		if (mutationObserver) {
			mutationObserver.disconnect();
			mutationObserver = null;
		}
		destroy();
	}
	function resume() {
		suspended = false;
		refresh();
		observe();
	}

	window.Kinetivo = Object.freeze({
		version: '1.1.0',
		schemaVersion: 2,
		get engine() {
			return capabilities.engine;
		},
		effects: Object.freeze(FREE_EFFECTS.slice()),
		getKeyframes: function (config) {
			var state = curatedState(config);
			return [
				frame(state.from, config.effect.preset, true),
				frame(state.to, config.effect.preset, false)
			];
		},
		resolveMotion: function (config) {
			var next = merge(
				config,
				mobileMedia.matches
					? config.responsive && config.responsive.mobile
					: tabletMedia.matches
						? config.responsive && config.responsive.tablet
						: null
			);
			return next.enabled === false || reducedPolicy(next) === 'disable'
				? null
				: { config: next, simplified: reducedPolicy(next) === 'simplify' };
		},
		registerEffect: registerEffect,
		refresh: refresh,
		destroy: destroy,
		setCapabilities: function (value) {
			capabilities = Object.assign({}, capabilities, value);
		},
		getCapabilities: function () {
			return Object.freeze(Object.assign({}, capabilities));
		},
		getDiagnostics: function () {
			return Object.freeze({
				initialized: roots.size,
				configured: document.querySelectorAll('[data-gmotion]').length,
				reducedMotion: reducedMedia.matches,
				mobile: mobileMedia.matches,
				tablet: !mobileMedia.matches && tabletMedia.matches
			});
		}
	});
	(window.KinetivoProviderQueue || []).splice(0).forEach(function (provider) {
		if (typeof provider === 'function') provider(window.Kinetivo);
	});
	[mobileMedia, tabletMedia, reducedMedia].forEach(function (media) {
		if (media.addEventListener) media.addEventListener('change', rebuild);
		else if (media.addListener) media.addListener(rebuild);
	});
	window.addEventListener('pagehide', suspend);
	window.addEventListener('pageshow', function (event) {
		if (event.persisted) resume();
	});
	window.addEventListener('beforeprint', suspend);
	window.addEventListener('afterprint', resume);
	if (document.readyState === 'loading')
		document.addEventListener(
			'DOMContentLoaded',
			function () {
				refresh();
				observe();
			},
			{ once: true }
		);
	else {
		refresh();
		observe();
	}
})();
