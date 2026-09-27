=== Norin Motion - Block Animation ===
Contributors: norania
Tags: animation, gutenberg, blocks, scroll animation, accessibility
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Bring pages to life with 20 animation effects for 12 Gutenberg block types. Preview, customize and reuse your motion settings. No coding required.

== Description ==

Make your next page feel as good as it looks. Norin Motion - Block Animation brings 20 animation effects directly into the WordPress block editor, so you can introduce a headline, reveal an image or give a whole section a memorable entrance.

Choose a block, pick an effect and preview the result. Fine-tune the movement and timing, adapt it for desktop, tablet and mobile, then reuse your favorite settings across the page. Everything happens in the editor you already know—no coding required.

= 20 effects to make your content stand out =

* 6 fade effects: Fade In, Fade Up, Fade Down, Fade Left, Fade Right and Fade + Scale.
* 4 directional slides: introduce content from above, below, left or right.
* 4 zoom and scale effects: add emphasis with a closer, wider, larger or smaller starting view.
* 2 rotation effects: bring elements into place with a subtle turn.
* 2 flip effects: rotate content around the horizontal or vertical axis.
* Blur In: bring your content smoothly into focus.
* Reveal From Top: uncover content with a clean directional reveal.

= Built for 12 Gutenberg block types =

Animate headings, paragraphs, images, groups, columns, galleries, lists, covers, buttons, individual buttons, quotes and media-text blocks. Add motion to individual elements or give an entire section a coordinated entrance.

= Motion that fits your design =

* Preview before publishing: see the selected effect inside the block editor.
* Choose when it starts: play on page load or when a block enters the viewport.
* Set the pace: adjust duration, delay and familiar CSS easing options, including ease-in, ease-out and ease-in-out.
* Refine each effect: adjust distance, scale, rotation or blur where supported.
* Adapt for smaller screens: control animation visibility, distance and duration for tablet and mobile.
* Reuse your work: Copy settings and Paste settings transfer motion between blocks.
* Start fresh with confidence: choosing another effect restores that animation's default settings.

= Considerate by design =

Norin Motion - Block Animation respects reduced-motion preferences, with options to disable animation or simplify it to a short fade. It keeps your existing block structure and readable page content, and loads its frontend animation assets only where motion is configured. No external animation service or CDN is required.

= Take motion further with WP Motion Block Pro =

The optional Pro add-on expands the collection to 56 effects in total and supports up to 5 animations per block. Explore animated text, media effects, staggered groups, hover interactions and scroll-driven motion. Your existing animation settings carry over when you upgrade.

== Installation ==

1. Upload and activate Norin Motion - Block Animation.
2. Select a supported block and open Norin Motion - Block Animation in the block settings sidebar.
3. Enable motion, choose an effect, preview and save.

== Frequently Asked Questions ==

= Is Norin Motion - Block Animation free to use? =

Yes. All 20 included effects and the plugin's preview, timing, responsive and copy/paste controls work without a paid license or an account. Advanced features are available separately in WP Motion Block Pro.

= Which blocks can I animate? =

The plugin supports 12 core block types: Heading, Paragraph, Image, Group, Columns, Gallery, List, Cover, Buttons, Button, Quote and Media & Text.

= Do I need to write code? =

No. Select a supported block, enable motion and choose an effect in the block settings sidebar.

= Can I adjust animations for mobile? =

Yes. Tablet and mobile overrides let you enable or disable motion and adjust duration and movement distance where supported. Desktop uses the main animation settings.

= Does it respect reduced-motion preferences? =

Yes. Animations can be disabled or simplified to a short fade when a visitor prefers reduced motion. Administrators can also apply a site-wide policy.

= Does the plugin change my content or SEO metadata? =

Animation settings are stored with your blocks. Frontend rendering adds motion attributes while preserving the content's semantic markup, links and image attributes. The plugin does not generate or replace titles, meta descriptions, canonical URLs or structured data.

= What happens if I deactivate the plugin? =

Your block content remains available without animations. Saved block animation settings stay in the content so you can use them again after reactivation. Uninstalling removes the plugin's site-wide settings.

= Does it use external services or collect visitor data? =

No. This plugin includes no analytics, telemetry, license checks or external service requests. Its animation scripts are bundled locally. Copy and Paste settings access the clipboard only when you use those buttons.

= Can I add several animations to one block? =

This version provides one animation per block. The optional WP Motion Block Pro add-on supports up to five, with separate triggers and ordering controls.

== Source Code and Build Instructions ==

The complete, unminified JavaScript and CSS source code is included in this plugin:

* build/editor.js
* build/runtime.js
* build/editor.css
* build/style.css

Despite the directory name, these files are the original source files maintained directly by the developer. They are not generated bundles.

No compilation, transpilation, bundling, or minification step is required. To modify the plugin's JavaScript or CSS, edit these files directly. WordPress loads them as shipped. A release ZIP packages the files without transforming their contents.

The free plugin does not bundle third-party JavaScript libraries. Editor dependencies are provided by WordPress through registered script dependencies.

Project repository: https://github.com/NooshinNooraeiNia/norin-motion-wp-block-animation

== Changelog ==

= 1.1.0 =
* Introduced Norin Motion - Block Animation branding and the directory-ready package.
* Added 20 animation effects for 12 core Gutenberg block types.
* Added editor previews, CSS easing, responsive controls and motion settings copy/paste.
* Improved fade, blur, flip and reveal playback, and reset defaults when changing effects.
* Included reduced-motion preferences and site-wide accessibility controls.
