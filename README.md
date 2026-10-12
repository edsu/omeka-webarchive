# Web Archive for Omeka Classic

An Omeka Classic plugin that plays WACZ web archive files attached to items, using the
[ReplayWeb.page](https://replayweb.page/) player. For Omeka S, see the
[Web Archive](https://github.com/omeka-s-modules/WebArchive) module.

- `WebArchive/`: the plugin.
- `docker/` and `sample/`: a local Omeka setup for trying the plugin.

## Install

1. Copy `WebArchive/` into your Omeka `plugins/` directory.
2. In the admin, open **Plugins** and install **Web Archive**.

Installing adds `wacz` to **Settings → Security → Allowed File Extensions**. Uninstalling leaves
it there. Omeka detects WACZ files as `application/zip`, which must stay in **Allowed File Types**
(it is there by default).

## Use

Upload a `.wacz` file to an item. The player, with a download link below it, appears on:

- public item pages, in themes that list an item's files (including the default theme)
- public file pages, and the admin file page and file edit form
- Exhibit Builder "File" and "File with Text" blocks, and the large file in a "Gallery" block

Pages that show thumbnails display Omeka's file icon: the admin item page, gallery thumbnails,
carousels, and themes that show an item's files as an image gallery.

The player opens on the archive's page list. As visitors browse the archive, the page's URL
hash records the archived page, and archived pages opened in a new tab open on the Omeka page.

### Theme options

Themes can pass these options to `files_for_item()` or `file_markup()`:

```php
echo files_for_item([
    'replay_embed'  => 'replayonly',                    // default | full | replayonly | replay-with-info
    'replay_url'    => 'https://example.com/some/page', // page to open first
    'replay_height' => '80vh',                          // CSS height, or a number of pixels (default: 600px)
]);
```

`default` and `full` open on the page list when `replay_url` is unset. `replayonly` and
`replay-with-info` have no page list and need a `replay_url`. See the
[ReplayWeb.page embedding docs](https://replayweb.page/docs/embedding/) for each mode.

The plugin prints the `ui.js` script tag with the first player on a page. If a theme discards
that first file markup, later players on the page don't load.

## How it works

`WebArchive/WebArchivePlugin.php` registers a display callback for the `.wacz` extension. The
callback prints:

```html
<script defer src="/plugins/WebArchive/views/shared/javascripts/replaywebpage/ui.js?v=<mtime>"></script>
<replay-web-page source="/files/original/<file>.wacz"
                 replaybase="/plugins/WebArchive/views/shared/javascripts/replaywebpage/"
                 embed="default" title="crawl.wacz" deeplink></replay-web-page>
<p class="web-archive-download"><a href="https://example.org/files/original/<file>.wacz">Download crawl.wacz</a></p>
```

ReplayWeb.page's service worker (`sw.js`) must come from the same origin as the page. The plugin
bundles `sw.js` next to `ui.js` and sets `replaybase` to that directory. Omeka's standard
`.htaccess` serves it without extra web server configuration.

The service worker reads the WACZ with HTTP Range requests and fetches only the parts it needs.

## Requirements and limitations

- Omeka Classic 3.0 or later.
- HTTPS, or `localhost`. Browsers allow service workers only in secure contexts.
- HTTP Range support for `files/original/`. Apache and nginx provide it for static files by
  default.
- No compression of `.wacz` responses: a `Content-Encoding` header breaks Range requests. Apache
  sends `.wacz` without a Content-Type and nginx sends `application/octet-stream`, so exclude
  WACZ by extension. In Apache:

  ```apache
  SetEnvIfNoCase Request_URI \.wacz$ no-gzip
  ```

- PHP `upload_max_filesize` and `post_max_size` larger than your largest archive.
- Local file storage. With the S3 storage adapter, Omeka issues signed URLs that expire during
  playback.
- WACZ only. Convert WARC files with [py-wacz](https://github.com/webrecorder/py-wacz).
- The extension match is case-sensitive: `.wacz` and `.WACZ` get the player, `.Wacz` does not.
- The player's "collection name" shows Omeka's hashed filename.

## Updating the player

`ui.js` and `sw.js` come from the [`replaywebpage`](https://www.npmjs.com/package/replaywebpage)
npm package, version **2.5.3**. Both files must be the same version:

```sh
VERSION=2.5.3
cd WebArchive/views/shared/javascripts/replaywebpage
curl -fLO https://cdn.jsdelivr.net/npm/replaywebpage@$VERSION/ui.js
curl -fLO https://cdn.jsdelivr.net/npm/replaywebpage@$VERSION/sw.js
```

Then update the version in `WebArchive/plugin.ini` and in this README. The `ui.js` URL includes
the file's modification time, so browsers fetch the new copy. Inside the player, the service
worker loads `ui.js` without that parameter, so a browser can use its cached copy there until
the cache expires.

## Try it

Requires Docker. Runs Omeka Classic 3.2.2 at http://localhost:8081 (bound to `127.0.0.1`), with
`WebArchive/` mounted as the plugin.

1. Start Omeka:

   ```sh
   docker compose -f docker/docker-compose.yml up --build -d
   ```

2. Open http://localhost:8081/install/ and complete the installer form. The database is
   preconfigured.
3. Log in at http://localhost:8081/admin/, open **Plugins**, and install **Web Archive**.
4. Download a 25 MB sample WACZ from Webrecorder's
   [example-webarchive](https://github.com/webrecorder/example-webarchive):

   ```sh
   sh sample/fetch-sample.sh
   ```

5. Open **Items → Add an Item**, enter a title, and check **Public**. On the **Files** tab,
   upload `sample/archive.wacz`, then save.
6. Click **View Public Page**, then **The birth of the Web | CERN** in the player's page list.

To remove the containers and their data:

```sh
docker compose -f docker/docker-compose.yml down -v
```

## License

The plugin is GPLv3 (`WebArchive/LICENSE`). The bundled `ui.js` and `sw.js` are part of
ReplayWeb.page, copyright Webrecorder Software, and AGPLv3
(`WebArchive/views/shared/javascripts/replaywebpage/LICENSE`).
