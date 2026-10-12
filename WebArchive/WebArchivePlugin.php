<?php
/**
 * Web Archive: replay WACZ files with ReplayWeb.page.
 *
 * @copyright Copyright 2026
 * @license https://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */
class WebArchivePlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_hooks = ['initialize', 'install'];

    /**
     * Set once the callback prints ui.js, so a page with several archives loads it once.
     */
    protected static $scriptPrinted = false;

    /**
     * Add wacz to the allowed upload extensions. Omeka's default MIME
     * allow-list includes application/zip, the type finfo reports for a
     * WACZ, so the extension list is the only one to change.
     */
    public function hookInstall()
    {
        $option = Omeka_Validate_File_Extension::WHITELIST_OPTION;
        $extensions = array_filter(array_map('trim', explode(',', (string) get_option($option))));
        if (!in_array('wacz', array_map('strtolower', $extensions))) {
            $extensions[] = 'wacz';
            set_option($option, implode(',', $extensions));
        }
    }

    /**
     * Display .wacz files with the player. Themes can override the defaults
     * below through the options they pass to files_for_item() or file_markup().
     * The replay_ prefix keeps them apart from Omeka's own options, such as
     * the pixel height it uses for video.
     */
    public function hookInitialize()
    {
        add_file_display_callback(
            ['fileExtensions' => ['wacz', 'WACZ']],
            [__CLASS__, 'display'],
            ['replay_embed' => 'default', 'replay_url' => null, 'replay_height' => '600px']
        );
    }

    public static function display($file, array $options)
    {
        // Root-relative paths keep the service worker and the archive on the
        // page's own origin, even when Omeka works out a different scheme or host.
        $replayBase = WEB_RELATIVE_PLUGIN . '/WebArchive/views/shared/javascripts/replaywebpage/';
        $source = parse_url($file->getWebPath('original'), PHP_URL_PATH);
        $height = $options['replay_height'];
        if (is_numeric($height)) {
            $height .= 'px';
        }

        $html = '';
        // Themes print their header before they display files, so
        // queue_js_file() would run too late.
        if (!self::$scriptPrinted) {
            // The file's modification time makes browsers fetch ui.js again after an update.
            $version = filemtime(__DIR__ . '/views/shared/javascripts/replaywebpage/ui.js');
            $html .= '<script defer src="' . html_escape($replayBase . 'ui.js?v=' . $version) . '"></script>';
            self::$scriptPrinted = true;
        }

        // deeplink sends archived pages opened in a new tab back to this page.
        // Without it, the player sends them to replayweb.page.
        $html .= '<replay-web-page ' . tag_attributes([
            'source' => $source,
            'replaybase' => $replayBase,
            'embed' => $options['replay_embed'],
            'url' => $options['replay_url'],
            'title' => $file->getProperty('display_title'),
            'deeplink' => true,
            'style' => 'display:block;width:100%;height:' . $height,
        ]) . '></replay-web-page>';

        // Keep Omeka's download link, for visitors whose browser can't run
        // the player and for feeds, which drop the script and the element.
        // Feeds need the absolute URL.
        $html .= '<p class="web-archive-download"><a ' . tag_attributes(['href' => $file->getWebPath('original')]) . '>'
            . html_escape(__('Download %s', $file->original_filename)) . '</a></p>';

        return $html;
    }
}
