# Salt Media Stream Video

Cloudflare Stream HLS video for Elementor, without MP4s.

- **Stream video background**: an option on every Elementor container (Style tab). It plays a Stream video behind the container, reusing Elementor's background-video layer, so overlays, borders and stacking work as normal.
- **Stream Video widget**: a video with optional controls, sound, autoplay and loop.

Paste any Stream link (watch, iframe, manifest or download link), a bare video ID, or an `.m3u8` URL. Or use Elementor's dynamic tag icon to pull the link from an ACF field.

## Behaviour

- **Playback engine:** HLS through the bundled hls.js 1.7.3 (light build). Browsers with native HLS (iPhone, Safari) use it when hls.js can't run. If neither is possible, the poster stays.
- **Loading:** hls.js loads only when a video starts.
- **Background and autoplay videos:**
  - Start when scrolled into view, and pause when out of view.
  - Are always muted.
  - Show the poster only when the visitor has asked for reduced motion or turned on data saver.
  - Can show the poster only on phones, using "Play on mobile".
- **Click-to-play videos:** download no video until the visitor presses play.
- **Max quality (per Elementor device):** caps the resolution, for example 360p for cards. "Auto" picks the best quality for the size shown.
- **Poster:** the Stream thumbnail (the time set in the Stream dashboard), a custom image, or none.
- **Looping:** short loops replay from memory, so Stream bills only the first play.
- **Debugging:** each player element carries `data-sm-state`: `loading`, `ready`, `playing`, `native`, `poster-only` or `error`.

## Settings

Go to **Settings → Stream Video** and enter the Stream customer code (the part after `customer-` in Stream URLs). It's only needed for bare video IDs; full links include it. Alternatively, define `SM_STREAM_CUSTOMER_CODE` in `wp-config.php`.

## Limitations

- **Editor canvas:** the Stream background shows on the live site and in Preview, but not inside the Elementor editor canvas (Elementor draws containers there with its own templates). The widget does show in the editor.
- **Public videos only:** signed URLs ("Require signed URLs" in Stream) aren't supported.
