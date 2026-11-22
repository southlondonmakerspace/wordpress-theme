South London Makerspace
=======================

The WordPress theme for [South London Makerspace](https://southlondonmakerspace.org). Version 3.0.3, for WordPress 6.3 or later and PHP 7.4 or later.

Development
---

The styles are written in LESS, in `static/less/`, and compiled to `static/css/main.css` and `static/css/steps.css`. WordPress loads those compiled files directly, so compile the LESS and commit the CSS with your changes.

```
npm install
npm run watch
```

`npm run watch` recompiles whenever a `.less` file changes, and `npm run less` compiles once. The build uses gulp 3, which only runs on Node 10 or earlier. On a newer Node, compile with `lessc` instead:

```
npx lessc@2.7 static/less/main.less static/css/main.css
npx lessc@2.7 static/less/steps.less static/css/steps.css
```

### Deployment

There is no automatic deployment. To make a release, commit your changes on master, then run:

```
./release.sh 3.0.1
```

It sets the version in `style.css`, `package.json` and this README, commits that, tags it `v3.0.1` and pushes. A GitHub Action (`.github/workflows/release.yml`) then makes the release on GitHub, with the theme attached as `slms-theme-v3.0.1.zip`, without the files only used for development (listed in `.gitattributes`). Pushing any `v` tag by hand does the same.

Upload that zip in WordPress under Appearance → Themes → Add New Theme → Upload Theme, or unzip it into `wp-content/themes/` on the server.

### Featured images

A post or page's featured image fills the band at the top of the page. Upload a landscape photo of **2560 × 1100 pixels** (about 2.3:1), as a JPEG at about 80% quality and under about 500 KB. Smaller images of the same shape work too, such as 2000 × 900, but are a little softer on high-density screens. WordPress scales anything wider than 2560 down.

The theme crops a copy from the middle of each upload to that shape, in four sizes, 2560, 1920, 1280 and 768 pixels wide, and the browser picks the one that suits the screen. The band shows more or less of it depending on the screen:

| Area | What's there | Roughly |
|---|---|---|
| Top | The menu and its darkening fade, on wide screens | top 25–30% |
| Top left | The logo | a 90px square |
| Bottom | The page's card, overlapping the band | bottom 15% |
| Bottom right | The caption's button, if the image has a caption | a small corner |
| Sides, on phones | Cropped off: phones show only a nearly square middle | the outer quarter each side |

As the page scrolls, the photo moves at half speed (the parallax), so its lower part slides out of sight first.

So put the subject in the middle half across, and between about a quarter and two-thirds of the way down, with calm areas such as sky or wall around it.

Images uploaded before the theme added these sizes show uncropped until their sizes are made. To make them, run `wp media regenerate --only-missing` with WP-CLI, or use a plugin such as Regenerate Thumbnails.

### Front-page map

The map at the top of the front page uses [MapLibre GL](https://maplibre.org) with [Protomaps](https://protomaps.com) tiles. Everything it needs is in the theme, so it needs no API key and makes no requests to other servers.

| File | What it is |
|---|---|
| `static/map/slms.pmtiles` | Map tiles for south London (zoom 0–15), cut from the Protomaps planet build |
| `static/map/fonts/` | Label fonts (Noto Sans, Latin characters only), under the SIL Open Font Licence |
| `static/map/build-style.js` | The map's colours; generates `static/js/map-style.js` |
| `static/js/map-style.js` | Generated map layers. Don't edit by hand |
| `static/js/main.js` | Creates the map, the zoom buttons and the marker |
| `static/vendor/` | Copies of MapLibre GL 5.24.1 and PMTiles 4.5.0, and Font Awesome Free 7.3.1 for the Facility widget's icons |

The marker is MapLibre's standard pin in red. It links to the Find us page (a published page with the slug `find-us`), or, without that page, clicking it shows the space's name. The pin, its position, the map centre and the zoom level are all set in `static/js/main.js`.

#### Changing the colours

1. Edit the colours in `static/map/build-style.js`. The named colours at the top (`paper`, `graphite`, `pencil` and so on) cover most of the map.
2. Run `npm install` once, then `npm run map-style`.
3. Reload the front page.

#### Updating the tiles or the area they cover

The tiles are a snapshot and don't update by themselves, so re-cut them now and then to pick up changes to OpenStreetMap.

1. Download the `pmtiles` command-line tool for your system from [go-pmtiles releases](https://github.com/protomaps/go-pmtiles/releases).
2. Find the latest daily build at [maps.protomaps.com/builds](https://maps.protomaps.com/builds), for example `20261118.pmtiles`.
3. Cut out south London. This only downloads the part you ask for, not the whole planet:

   ```
   pmtiles extract https://build.protomaps.com/20261118.pmtiles static/map/slms.pmtiles --bbox=-0.165,51.425,-0.04,51.478 --maxzoom=15
   ```

   `--bbox` is west,south,east,north. A bigger area or a higher `--maxzoom` makes the file larger (this one is about 8 MB).
4. If you change `--bbox`, change `maxBounds` in `static/js/main.js` to stay inside it, so people can't pan past the edge of the tiles. It's set a little inside the tiles so the pin is in the middle.

The map's credit (© OpenStreetMap contributors and Protomaps) is a caption at the foot of the map, in `base.php`. It starts open, then closes to its © button after five seconds. The OpenStreetMap licence requires the credit, so keep it.

### Events widget

The Events widget lists upcoming events from the forum's Public Events calendar, read from its iCal feed: `https://discourse.southlondonmakerspace.org/c/public-events/l/calendar.ics`.

- The feed address can be changed in the widget's settings (Appearance → Widgets). Any iCal (`.ics`) feed works, for example `/c/events/l/calendar.ics` for the Events category.
- The feed is cached for 15 minutes. If the forum can't be reached, the widget shows no events and tries again after 5 minutes.
- Each event links to its forum topic. The description is the start of the topic's first post.

### Discourse Comments

As discourse is the primary forum for discussions we use their [wp-discourse](https://github.com/discourse/wp-discourse) plugin to sync comments through from the Blog category. These are settings we've got within WordPress.

```html
<li class="comment even thread-even depth-1">
  <article class="comment-body">
    <footer class="comment-meta">
      <div class="comment-author vcard">
        <img alt="" src="{avatar_url}" class="avatar avatar-64 photo avatar-default" height="45" width="45">
        <b class="fn"><a href="{topic_url}" rel="external" class="url">{username}</a></b>
      <div class="comment-metadata">
        <time pubdate="" datetime="{comment_created_at}">{comment_created_at}</time>
      </div><!-- .comment-metadata -->

      </div><!-- .comment-author -->
    </footer><!-- .comment-meta -->
    <div class="comment-content">{comment_body}</div><!-- .comment-content -->
  </article><!-- .comment-body -->
</li>
```
