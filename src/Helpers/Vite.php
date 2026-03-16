<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

use ZeroToProd\Thryds\Config;

readonly class Vite
{
    public const string app_entry = 'resources/js/app.js';
    public const string htmx_entry = 'resources/js/htmx.js';
    public const string app_css = 'resources/css/app.css';
    private const string DEV_SERVER_URL = 'http://localhost:5173';
    private const string MANIFEST_PATH = '/public/build/.vite/manifest.json';
    private const string css = 'css';
    private const string file = 'file';

    /** @param array<string, list<string>> $entry_css Maps entry points to their CSS source paths */
    public function __construct(
        private Config $Config,
        private string $baseDir,
        private array $entry_css = [],
    ) {}

    public function directivePhp(string $entry): string
    {
        return '<?php echo ' . var_export(value: $this->tags($entry), return: true) . '; ?>';
    }

    public function tags(string $entry): string
    {
        if (!$this->Config->isProduction()) {
            return $this->devTags($entry);
        }

        return $this->productionTags($entry);
    }

    private function devTags(string $entry): string
    {
        $url = self::DEV_SERVER_URL;
        $tags = "<script type=\"module\" src=\"{$url}/@vite/client\"></script>\n";

        foreach ($this->entry_css[$entry] ?? [] as $css_path) {
            $tags .= "    <link rel=\"stylesheet\" href=\"{$url}/{$css_path}\">\n";
        }

        $tags .= "    <script type=\"module\" src=\"{$url}/{$entry}\"></script>";

        return $tags;
    }

    private function productionTags(string $entry): string
    {
        $manifest_file = $this->baseDir . self::MANIFEST_PATH;
        $manifest = json_decode(file_get_contents(filename: $manifest_file), associative: true);
        $chunk = $manifest[$entry] ?? null;

        if ($chunk === null) {
            return '';
        }

        $tags = '';

        if (isset($chunk[self::css])) {
            foreach ($chunk[self::css] as $css) {
                $tags .= '<link rel="stylesheet" href="/build/' . $css . '">' . "\n";
            }
        }

        $tags .= '<script type="module" src="/build/' . $chunk[self::file] . '"></script>' . "\n";

        return $tags;
    }
}
