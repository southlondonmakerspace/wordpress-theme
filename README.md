South London Makerspace
=======================

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/southlondonmakerspace/wordpress-theme/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/southlondonmakerspace/wordpress-theme/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/southlondonmakerspace/wordpress-theme/badges/build.png?b=master)](https://scrutinizer-ci.com/g/southlondonmakerspace/wordpress-theme/build-status/master)

Development
---

```
npm install
gulp watch
```

### Deployment

Any changes committed to the `master` branch will be automatically uploaded to the development preview at [slms.wpengine.com](http://slms.wpengine.com)

### Front-page map

The map at the top of the front page uses [MapLibre GL](https://maplibre.org) with [Protomaps](https://protomaps.com) tiles. Everything it needs is in the theme, so it needs no API key and makes no requests to other servers.

| File | What it is |
|---|---|
| `static/map/slms.pmtiles` | Map tiles for south London (zoom 0–15), cut from the Protomaps planet build |
| `static/map/fonts/` | Label fonts (Noto Sans, Latin characters only), under the SIL Open Font Licence |
| `static/map/build-style.js` | The map's colours; generates `static/js/map-style.js` |
| `static/js/map-style.js` | Generated map layers. Don't edit by hand |
| `static/js/main.js` | Creates the map, the zoom buttons and the marker |
| `static/vendor/` | Copies of MapLibre GL 5.24.1 and PMTiles 4.5.0 |

The marker is MapLibre's standard pin in red, and clicking it shows the space's name. The pin, its position, the map centre and the zoom level are all set in `static/js/main.js`.

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
4. If you change `--bbox`, change `maxBounds` in `static/js/main.js` to match, so people can't pan past the edge of the tiles.

The map's credit (© OpenStreetMap contributors and Protomaps) is in the page footer, in `base.php`, rather than on the map, because on phones the intro covers the map's corner. The OpenStreetMap licence requires the credit, so keep it.

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
