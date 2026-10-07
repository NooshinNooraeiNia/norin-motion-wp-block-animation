(function (wp) {
	'use strict';
	var el = wp.element.createElement,
		Fragment = wp.element.Fragment,
		__ = wp.i18n.__,
		sprintf = wp.i18n.sprintf;
	var supported = [
		'core/heading',
		'core/paragraph',
		'core/image',
		'core/group',
		'core/columns',
		'core/gallery',
		'core/list',
		'core/cover',
		'core/buttons',
		'core/button',
		'core/quote',
		'core/media-text'
	];
	var effects = [
		['fade-in', __('Fade In', 'norinmotion')],
		['fade-up', __('Fade Up', 'norinmotion')],
		['fade-down', __('Fade Down', 'norinmotion')],
		['fade-left', __('Fade Left', 'norinmotion')],
		['fade-right', __('Fade Right', 'norinmotion')],
		['fade-scale', __('Fade + Scale', 'norinmotion')],
		['slide-up', __('Slide Up', 'norinmotion')],
		['slide-down', __('Slide Down', 'norinmotion')],
		['slide-left', __('Slide Left', 'norinmotion')],
		['slide-right', __('Slide Right', 'norinmotion')],
		['zoom-in', __('Zoom In', 'norinmotion')],
		['zoom-out', __('Zoom Out', 'norinmotion')],
		['scale-up', __('Scale Up', 'norinmotion')],
		['scale-down', __('Scale Down', 'norinmotion')],
		['rotate-in-left', __('Rotate In Left', 'norinmotion')],
		['rotate-in-right', __('Rotate In Right', 'norinmotion')],
		['blur-in', __('Blur In', 'norinmotion')],
		['flip-x', __('Flip X', 'norinmotion')],
		['flip-y', __('Flip Y', 'norinmotion')],
		['reveal-top', __('Reveal From Top', 'norinmotion')]
	];
	var effectSlugs = effects.map(function (item) {
			return item[0];
		}),
		previews = new WeakMap();
	function defaults() {
		return {
			v: 2,
			enabled: true,
			effect: {
				preset: 'fade-up',
				from: { opacity: 0, x: 0, y: 32, scale: 1, rotation: 0, blur: 0 },
				to: { opacity: 1, x: 0, y: 0, scale: 1, rotation: 0, blur: 0 }
			},
			timing: {
				duration: 0.7,
				delay: 0,
				easing: 'ease-out',
				iterations: 1,
				direction: 'normal'
			},
			trigger: {
				type: 'viewport',
				once: true,
				start: 'top 85%'
			},
			responsive: { tablet: {}, mobile: {} },
			a11y: { reducedMotion: 'disable' }
		};
	}
	function clone(value) {
		return JSON.parse(JSON.stringify(value));
	}
	function withDefaults(base, value) {
		var out = clone(base);
		if (!value || typeof value !== 'object' || Array.isArray(value)) return out;
		Object.keys(value).forEach(function (key) {
			if (!Object.prototype.hasOwnProperty.call(base, key)) return;
			if (base[key] && typeof base[key] === 'object' && !Array.isArray(base[key]))
				out[key] = withDefaults(base[key], value[key]);
			else if (value[key] !== null && (base[key] == null || typeof value[key] === typeof base[key]))
				out[key] = clone(value[key]);
		});
		return out;
	}
	function responsiveOverride(value) {
		var result = {};
		if (!value || typeof value !== 'object' || Array.isArray(value)) return result;
		if (Object.prototype.hasOwnProperty.call(value, 'enabled')) result.enabled = value.enabled !== false;
		var from = value.effect && value.effect.from, state = {};
		Object.keys(defaults().effect.from).forEach(function (key) {
			if (from && typeof from[key] === 'number' && Number.isFinite(from[key])) state[key] = from[key];
		});
		if (Object.keys(state).length) result.effect = { from: state };
		var timing = {};
		['duration', 'delay'].forEach(function (key) {
			if (value.timing && typeof value.timing[key] === 'number' && Number.isFinite(value.timing[key])) timing[key] = value.timing[key];
		});
		if (value.timing && ['linear', 'ease', 'ease-in', 'ease-out', 'ease-in-out', 'gentle', 'standard', 'emphasized', 'expressive'].indexOf(value.timing.easing) !== -1) timing.easing = value.timing.easing;
		if (Object.keys(timing).length) result.timing = timing;
		return result;
	}

	function normalize(raw) {
		if (!raw || typeof raw !== 'object') return null;
		if (raw.v === 2) {
			var normalized = withDefaults(applyEffect(null, (raw.effect && raw.effect.preset) || 'fade-up'), raw);
			if (effectSlugs.indexOf(normalized.effect.preset) === -1) normalized.effect.preset = 'fade-up';
			['tablet', 'mobile'].forEach(function (device) {
				normalized.responsive[device] = responsiveOverride(raw.responsive && raw.responsive[device]);
			});
			normalized.enabled = raw.enabled === true;
			if (['viewport', 'load'].indexOf(normalized.trigger.type) === -1) normalized.trigger.type = 'viewport';
			normalized.timing.iterations = Math.max(1, Math.min(21, Math.round(normalized.timing.iterations) || 1));
			if (normalized.timing.direction !== 'alternate') normalized.timing.direction = 'normal';
			return normalized;
		}
		return null;
	}
	function update(config, path, value) {
		var next = clone(config),
			cursor = next;
		path.slice(0, -1).forEach(function (key) {
			if (!cursor[key] || typeof cursor[key] !== 'object') cursor[key] = {};
			cursor = cursor[key];
		});
		cursor[path[path.length - 1]] = value;
		return next;
	}
	function applyEffect(config, preset) {
		var next = defaults();
		preset = effectSlugs.indexOf(preset) === -1 ? 'fade-up' : preset;
		next.effect.preset = preset;
		next.effect.from = defaults().effect.from;
		next.effect.from.y = 0;
		next.effect.to = defaults().effect.to;
		if (/^(?:fade|slide)-up$/.test(preset)) next.effect.from.y = 32;
		if (/^(?:fade|slide)-down$/.test(preset)) next.effect.from.y = -32;
		if (/^(?:fade|slide)-left$/.test(preset)) next.effect.from.x = -32;
		if (/^(?:fade|slide)-right$/.test(preset)) next.effect.from.x = 32;
		if (preset.indexOf('slide-') === 0) next.effect.from.opacity = 1;
		if (/^(?:fade-scale|zoom-in|scale-up)$/.test(preset)) next.effect.from.scale = 0.82;
		if (/^(?:zoom-out|scale-down)$/.test(preset)) next.effect.from.scale = 1.18;
		if (preset === 'rotate-in-left') next.effect.from.rotation = -12;
		if (preset === 'rotate-in-right') next.effect.from.rotation = 12;
		if (preset === 'blur-in') next.effect.from.blur = 12;
		return next;
	}
	function findBlock(id) {
		var selector = '[data-block="' + String(id).replace(/"/g, '') + '"]',
			direct = document.querySelector(selector);
		if (direct) return direct;
		var frames = document.querySelectorAll(
			'iframe[name="editor-canvas"], iframe.editor-canvas__iframe'
		);
		for (var i = 0; i < frames.length; i++) {
			try {
				var found = frames[i].contentDocument && frames[i].contentDocument.querySelector(selector);
				if (found) return found;
			} catch (error) {}
		}
		return null;
	}
	function keyframes(config) {
		var state = clone(config.effect.from),
			preset = config.effect.preset;
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
			state.x = 0;
			state.y = 0;
		}
		if (preset.indexOf('slide-') === 0) state.opacity = 1;
		var from = {
				opacity: state.opacity,
				transform:
					'translate3d(' +
					state.x +
					'px,' +
					state.y +
					'px,0) scale(' +
					state.scale +
					') rotate(' +
					state.rotation +
					'deg)',
				filter: 'blur(' + state.blur + 'px)'
			},
			to = { opacity: 1, transform: 'none', filter: 'blur(0px)' };
		if (preset === 'flip-x') from.transform = 'perspective(800px) rotateX(-90deg)';
		if (preset === 'flip-y') from.transform = 'perspective(800px) rotateY(-90deg)';
		if (preset === 'reveal-top') {
			from.opacity = 1;
			from.clipPath = 'inset(0 0 100% 0)';
			to.clipPath = 'inset(0 0 0 0)';
		}
		return [from, to];
	}
	function preview(id, config) {
		var root = findBlock(id);
		if (!root || !root.animate) return;
		var reduced = root.ownerDocument.defaultView.matchMedia(
			'(prefers-reduced-motion: reduce)'
		).matches;
		if (reduced && config.a11y.reducedMotion !== 'simplify') return;
		var previous = previews.get(root);
		if (previous) previous.cancel();
		var animation = root.animate(reduced ? [{ opacity: 0 }, { opacity: 1 }] : keyframes(config), {
			duration: reduced ? 200 : config.timing.duration * 1000,
			delay: reduced ? 0 : config.timing.delay * 1000,
			easing:
				{
					ease: 'ease',
					'ease-in': 'ease-in',
					'ease-out': 'ease-out',
					'ease-in-out': 'ease-in-out',
					linear: 'linear',
					gentle: 'cubic-bezier(.39,.575,.565,1)',
					standard: 'cubic-bezier(.16,1,.3,1)',
					emphasized: 'cubic-bezier(.19,1,.22,1)',
					expressive: 'cubic-bezier(.34,1.56,.64,1)'
				}[config.timing.easing] || 'ease-out',
			iterations: reduced ? 1 : config.timing.iterations || 1,
			direction: reduced ? 'normal' : config.timing.direction || 'normal',
			fill: 'none'
		});
		previews.set(root, animation);
		animation.finished
			.catch(function () {})
			.then(function () {
				if (previews.get(root) === animation) previews.delete(root);
			});
	}
	function effectOptions() {
		return effects.map(function (item) {
			return { value: item[0], label: item[1] };
		});
	}
	function deviceControls(device, label, config, set) {
		var override = config.responsive[device] || {},
			from = (override.effect && override.effect.from) || {},
			timing = override.timing || {},
			movement = /(?:up|down|left|right)/.test(config.effect.preset),
			axis = /left|right/.test(config.effect.preset) ? 'x' : 'y',
			sign = /down|left/.test(config.effect.preset) ? -1 : 1,
			baseDistance = Math.abs(config.effect.from[axis] || 0);
		var controls = [
			el(wp.components.ToggleControl, {
				key: device + '-enabled',
				/* translators: %s: device name, e.g. Tablet. */
				label: sprintf(__('Enable on %s', 'norinmotion'), label),
				checked: override.enabled !== false,
				onChange: function (value) {
					set(['responsive', device, 'enabled'], value);
				}
			})
		];
		if (movement)
			controls.push(
				el(wp.components.RangeControl, {
					key: device + '-distance',
					/* translators: %s: device name, e.g. Tablet. */
					label: sprintf(__('%s distance (px)', 'norinmotion'), label),
					value: typeof from[axis] === 'number' ? Math.abs(from[axis]) : baseDistance,
					min: 0,
					max: 300,
					onChange: function (value) {
						set(['responsive', device, 'effect', 'from', axis], value * sign);
					}
				})
			);
		controls.push(
			el(wp.components.RangeControl, {
				key: device + '-duration',
				/* translators: %s: device name, e.g. Tablet. */
				label: sprintf(__('%s duration (seconds)', 'norinmotion'), label),
				value: typeof timing.duration === 'number' ? timing.duration : config.timing.duration,
				min: 0.1,
				max: 10,
				step: 0.1,
				onChange: function (value) {
					set(['responsive', device, 'timing', 'duration'], value);
				}
			})
		);
		return controls;
	}

	function easingOptions() {
		return [
			{ label: __('Linear', 'norinmotion'), value: 'linear' },
			{ label: __('Ease', 'norinmotion'), value: 'ease' },
			{ label: __('Ease in', 'norinmotion'), value: 'ease-in' },
			{ label: __('Ease out', 'norinmotion'), value: 'ease-out' },
			{ label: __('Ease in-out', 'norinmotion'), value: 'ease-in-out' },
			{ label: __('Gentle', 'norinmotion'), value: 'gentle' },
			{ label: __('Standard', 'norinmotion'), value: 'standard' },
			{ label: __('Emphasized', 'norinmotion'), value: 'emphasized' },
			{ label: __('Expressive (overshoot)', 'norinmotion'), value: 'expressive' }
		];
	}
	var copiedSettings = null;
	function copySettings(config) {
		copiedSettings = clone(config);
		return navigator.clipboard && navigator.clipboard.writeText
			? navigator.clipboard.writeText(JSON.stringify(config)).then(
					function () {
						return __('Settings copied. Select another block and use Paste settings.', 'norinmotion');
					},
					function () {
						return __('Settings copied within this editor. Select another block and use Paste settings.', 'norinmotion');
					}
				)
			: Promise.resolve(
					__('Settings copied within this editor. Select another block and use Paste settings.', 'norinmotion')
				);
	}
	function pasteSettings() {
		return (
			copiedSettings
				? Promise.resolve(JSON.stringify(copiedSettings))
				: navigator.clipboard && navigator.clipboard.readText
					? navigator.clipboard.readText()
					: Promise.reject(new Error(__('Copy motion settings from a block first.', 'norinmotion')))
		).then(function (text) {
			if (text.length > 24576) throw new Error(__('These motion settings are too large.', 'norinmotion'));
			var value = JSON.parse(text, function (key, item) {
				if (['__proto__', 'prototype', 'constructor'].indexOf(key) !== -1)
					throw new Error(__('Invalid motion settings.', 'norinmotion'));
				return item;
			});
			if (
				!value ||
				value.v !== 2 ||
				typeof value.enabled !== 'boolean' ||
				!value.effect ||
				typeof value.effect.preset !== 'string'
			)
				throw new Error(__('The clipboard does not contain valid motion settings.', 'norinmotion'));
			return normalize(value);
		});
	}
	window.NorinMotionEditor = {
		defaults: function (preset) {
			return applyEffect(null, preset || 'fade-up');
		},
		normalize: normalize,
		options: effectOptions,
		copy: copySettings,
		paste: pasteSettings
	};

	wp.hooks.addFilter('blocks.registerBlockType', 'norinmotion/attribute', function (settings, name) {
		if (supported.indexOf(name) === -1) return settings;
		return Object.assign({}, settings, {
			attributes: Object.assign({}, settings.attributes, { norinmotion: { type: 'object' } })
		});
	});
	var withMotion = wp.compose.createHigherOrderComponent(function (BlockEdit) {
		return function (props) {
			var feedback = wp.element.useState(''),
				message = feedback[0],
				setMessage = feedback[1];
			if (!props.isSelected || supported.indexOf(props.name) === -1)
				return el(BlockEdit, props);
			var raw = props.attributes.norinmotion,
				config = normalize(raw),
				enabled = !!(config && config.enabled);
			function commit(next) {
				props.setAttributes({ norinmotion: next });
			}
			function set(path, value) {
				commit(update(config, path, value));
			}
			var summary = [
				el(wp.components.ToggleControl, {
					key: 'enable',
					label: __('Enable motion', 'norinmotion'),
					checked: enabled,
					onChange: function (value) {
						commit(update(config || defaults(), ['enabled'], value));
					}
				})
			];
			var actions = [];
			if (enabled)
				actions.push(
					el(
						wp.components.Button,
						{
							key: 'preview',
							variant: 'primary',
							onClick: function () {
								preview(props.clientId, config);
							}
						},
						__('Preview', 'norinmotion')
					),
					el(
						wp.components.Button,
						{
							key: 'copy',
							variant: 'secondary',
							onClick: function () {
								copySettings(config).then(setMessage);
							}
						},
						__('Copy settings', 'norinmotion')
					)
				);
			actions.push(
				el(
					wp.components.Button,
					{
						key: 'paste',
						variant: 'secondary',
						onClick: function () {
							pasteSettings()
								.then(function (next) {
									commit(next);
									setMessage(__('Motion settings pasted.', 'norinmotion'));
								})
								.catch(function (error) {
									setMessage(error.message);
								});
						}
					},
					__('Paste settings', 'norinmotion')
				)
			);
			if (enabled)
				actions.push(
					el(
						wp.components.Button,
						{
							key: 'reset',
							isDestructive: true,
							variant: 'secondary',
							onClick: function () {
								commit(undefined);
							}
						},
						__('Reset', 'norinmotion')
					)
				);
			summary.push(
				el(
					'div',
					{
						key: 'actions',
						className: 'norinmotion-actions' + (enabled ? '' : ' norinmotion-actions--single')
					},
					actions
				)
			);
			if (message)
				summary.push(
					el(
						'p',
						{ key: 'feedback', className: 'norinmotion-actions-feedback', role: 'status' },
						message
					)
				);
			var panels = [
				el(
					wp.components.PanelBody,
					{
						key: 'summary',
						title: enabled
							? __('Norin Motion - Block Animation · Active', 'norinmotion')
							: __('Norin Motion - Block Animation', 'norinmotion'),
						initialOpen: true
					},
					summary
				)
			];
			if (enabled) {
				var movement = /(?:up|down|left|right)/.test(config.effect.preset),
					scaling = /(?:scale|zoom)/.test(config.effect.preset),
					rotating = /rotate/.test(config.effect.preset),
					blurring = config.effect.preset === 'blur-in';
				var animationControls = [
					el(wp.components.SelectControl, {
						key: 'effect',
						label: __('Effect', 'norinmotion'),
						value: config.effect.preset,
						options: effectOptions(),
						onChange: function (value) {
							commit(applyEffect(config, value));
						}
					})
				];
				if (movement)
					animationControls.push(
						el(wp.components.RangeControl, {
							key: 'distance',
							label: __('Distance (px)', 'norinmotion'),
							value: Math.abs(config.effect.from.x || config.effect.from.y),
							min: 0,
							max: 300,
							onChange: function (value) {
								var axis = /left|right/.test(config.effect.preset) ? 'x' : 'y',
									sign = /down|left/.test(config.effect.preset) ? -1 : 1;
								set(['effect', 'from', axis], value * sign);
							}
						})
					);
				if (scaling)
					animationControls.push(
						el(wp.components.RangeControl, {
							key: 'scale',
							label: __('Starting scale', 'norinmotion'),
							value: config.effect.from.scale,
							min: 0.1,
							max: 2,
							step: 0.01,
							onChange: function (value) {
								set(['effect', 'from', 'scale'], value);
							}
						})
					);
				if (rotating)
					animationControls.push(
						el(wp.components.RangeControl, {
							key: 'rotation',
							label: __('Rotation (degrees)', 'norinmotion'),
							value: config.effect.from.rotation,
							min: -180,
							max: 180,
							onChange: function (value) {
								set(['effect', 'from', 'rotation'], value);
							}
						})
					);
				if (blurring)
					animationControls.push(
						el(wp.components.RangeControl, {
							key: 'blur',
							label: __('Blur (px)', 'norinmotion'),
							value: config.effect.from.blur,
							min: 0,
							max: 50,
							onChange: function (value) {
								set(['effect', 'from', 'blur'], value);
							}
						})
					);
				panels.push(
					el(
						wp.components.PanelBody,
						{ key: 'animation', title: __('Animation', 'norinmotion'), initialOpen: true },
						animationControls
					)
				);
				panels.push(
					el(
						wp.components.PanelBody,
						{ key: 'trigger', title: __('Trigger', 'norinmotion'), initialOpen: false },
						el(wp.components.SelectControl, {
							label: __('Trigger', 'norinmotion'),
							value: config.trigger.type,
							options: [
								{ label: __('Viewport enter', 'norinmotion'), value: 'viewport' },
								{ label: __('Page load', 'norinmotion'), value: 'load' }
							],
							onChange: function (value) {
								set(['trigger', 'type'], value);
							}
						}),
						config.trigger.type === 'viewport' &&
							el(wp.components.SelectControl, {
								label: __('Viewport start', 'norinmotion'),
								value: config.trigger.start,
								options: ['top 95%', 'top 85%', 'top 75%', 'top 50%', 'top 25%'].map(
									function (value) {
										return { label: value, value: value };
									}
								),
								onChange: function (value) {
									set(['trigger', 'start'], value);
								}
							}),
						config.trigger.type === 'viewport' &&
							el(wp.components.ToggleControl, {
								label: __('Play only once', 'norinmotion'),
								checked: config.trigger.once !== false,
								onChange: function (value) {
									set(['trigger', 'once'], value);
								}
							})
					)
				);
				panels.push(
					el(
						wp.components.PanelBody,
						{ key: 'timing', title: __('Timing', 'norinmotion'), initialOpen: false },
						el(wp.components.RangeControl, {
							label: __('Duration (seconds)', 'norinmotion'),
							value: config.timing.duration,
							min: 0.1,
							max: 10,
							step: 0.1,
							onChange: function (value) {
								set(['timing', 'duration'], value);
							}
						}),
						el(wp.components.RangeControl, {
							label: __('Delay (seconds)', 'norinmotion'),
							value: config.timing.delay,
							min: 0,
							max: 10,
							step: 0.1,
							onChange: function (value) {
								set(['timing', 'delay'], value);
							}
						}),
						el(wp.components.SelectControl, {
							label: __('Easing', 'norinmotion'),
							value: config.timing.easing,
							options: easingOptions(),
							onChange: function (value) {
								set(['timing', 'easing'], value);
							}
						}),
						el(wp.components.RangeControl, {
							label: __('Repeat count', 'norinmotion'),
							help: __('Number of extra plays after the first one.', 'norinmotion'),
							value: Math.max(0, (config.timing.iterations || 1) - 1),
							min: 0,
							max: 20,
							onChange: function (value) {
								set(['timing', 'iterations'], 1 + Math.max(0, Math.min(20, value || 0)));
							}
						}),
						config.timing.iterations > 1 &&
							el(wp.components.ToggleControl, {
								label: __('Alternate direction on each repeat', 'norinmotion'),
								checked: config.timing.direction === 'alternate',
								onChange: function (value) {
									set(['timing', 'direction'], value ? 'alternate' : 'normal');
								}
							})
					)
				);
				panels.push(
					el(
						wp.components.PanelBody,
						{ key: 'responsive', title: __('Responsive', 'norinmotion'), initialOpen: false },
						deviceControls('tablet', __('Tablet', 'norinmotion'), config, set),
						deviceControls('mobile', __('Mobile', 'norinmotion'), config, set)
					)
				);
				panels.push(
					el(
						wp.components.PanelBody,
						{ key: 'a11y', title: __('Accessibility', 'norinmotion'), initialOpen: false },
						el(wp.components.SelectControl, {
							label: __('Reduced motion', 'norinmotion'),
							value: config.a11y.reducedMotion,
							options: [
								{ label: __('Disable animation', 'norinmotion'), value: 'disable' },
								{ label: __('Simplify to a short fade', 'norinmotion'), value: 'simplify' }
							],
							onChange: function (value) {
								set(['a11y', 'reducedMotion'], value);
							}
						})
					)
				);
			}
			return el(
				Fragment,
				null,
				el(BlockEdit, props),
				el(wp.blockEditor.InspectorControls, { group: 'settings' }, panels)
			);
		};
	}, 'withNorinMotion');
	wp.hooks.addFilter('editor.BlockEdit', 'norinmotion/inspector', withMotion);
})(window.wp);
