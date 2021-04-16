<?php


namespace App\Twig;


use Psr\Cache\CacheItemPoolInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ViteAssetExtension extends AbstractExtension
{
    private ?array $manifestData = null;
    const CACHE_KEY = 'vite_manifest';

    public function __construct(
        private bool $isDev,
        private string $manifest,
        private CacheItemPoolInterface $cache,
    )
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('vite_entry_link_tags', [$this, 'links'], ['is_safe' => ['html']]),
            new TwigFunction('vite_entry_script_tags', [$this, 'scripts'], ['is_safe' => ['html']])
        ];
    }


    public function links(string $entry)
    {
        if ($this->isDev) {
            return;
        }
        $this->loadManifest();
        $css = $this->manifestData[$entry]['css'] ?? [];
        $html = "";

        foreach ($css as $cssFile) {
            $html .= <<<HTML
<link rel="stylesheet" media="screen" href="/assets/{$cssFile}"/>
HTML;
        }
        $imports = $this->manifestData[$entry]['imports'] ?? [];
        foreach ($imports as $import) {
            $html .= <<<HTML
<link rel="modulepreload" href="/assets/{$import}"/>
HTML;
        }
        return $html;

    }

    public function scripts(string $entry)
    {
        if ($this->isDev) {
            return $this->scriptsDev($entry);
        }
        return $this->scriptsProd($entry);
    }

    public function scriptsDev(string $entry): string
    {
        $html = <<<HTML
<script type="module" src="http://localhost:3000/assets/@vite/client"></script>
<script type="module" src="http://localhost:3000/assets/{$entry}" defer></script>
HTML;
        return $html;
    }


    private function loadManifest()
    {
        if ($this->manifestData === null) {
            $item = $this->cache->getItem(self::CACHE_KEY);
            if ($item->isHit()) {
                $this->manifestData = $item->get();
            } else {
                $this->manifestData = json_decode(file_get_contents($this->manifest), true);
                $item->set($this->manifestData);
                $this->cache->save($item);
            }
        }
    }

    public function scriptsProd(string $entry): string
    {
        $this->loadManifest();

        $file = $this->manifestData[$entry]['file'];
        $html = <<<HTML
<script type="module" src="/assets/{$file}" defer></script>
HTML;


        return $html;
    }
}