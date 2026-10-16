# Salt Media Stream Video

Cloudflare Stream HLS video for Elementor, without MP4s.

- **Stream video background**: an option on every Elementor container (Style tab). It plays a Stream video behind the container, reusing Elementor's background-video layer, so overlays, borders and stacking work as normal.
- **Stream Video widget**: an autoplaying, muted, looping video with no player controls, like a Stream embed with `autoplay&muted&loop&controls=false`.

Paste any Stream link (watch, iframe, manifest or download link), a bare video ID, or an `.m3u8` URL. Or use Elementor's dynamic tag icon to pull the link from an ACF field.

## Behaviour

- **Playback engine:** HLS through the bundled hls.js 1.7.3 (light build). Browsers with native HLS (iPhone, Safari) use it when hls.js can't run. If neither is possible, the poster stays.
- **Loading:** hls.js loads only when a video starts.
- **All videos:**
  - Autoplay muted with no player controls.
  - Download nothing until scrolled into view, then start; pause when out of view.
  - Show the poster only when the visitor has asked for reduced motion or turned on data saver.
  - Play on phones by default; turn "Play on mobile" off to show the poster only.
- **Max quality (per Elementor device):** caps the resolution, for example 360p for cards. "Auto" picks the best quality for the size shown.
- **Poster:** the Stream thumbnail (the time set in the Stream dashboard), a custom image, or none. Only the first video on a page has its poster in the HTML (it is usually the header). Every other poster loads when its video is about 600px from the screen, and Stream thumbnails are cropped to the size the video is shown at.
- **Looping:** short loops replay from memory, so Stream bills only the first play.
- **Debugging:** each player element carries `data-sm-state`: `loading`, `playing`, `paused`, `native`, `poster-only` or `error`.

## Settings

Go to **Settings → Stream Video** and enter the Stream customer code (the part after `customer-` in Stream URLs). It's only needed for bare video IDs; full links include it. Alternatively, define `SM_STREAM_CUSTOMER_CODE` in `wp-config.php`.

## Limitations

- **Editor canvas:** the Stream background shows on the live site and in Preview, but not inside the Elementor editor canvas (Elementor draws containers there with its own templates). The widget does show in the editor.
- **Public videos only:** signed URLs ("Require signed URLs" in Stream) aren't supported.
