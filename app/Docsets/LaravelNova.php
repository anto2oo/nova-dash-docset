<?php

namespace App\Docsets;

use Godbout\DashDocsetBuilder\Docsets\BaseDocset;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Wa72\HtmlPageDom\HtmlPageCrawler;

class LaravelNova extends BaseDocset
{
    public const CODE = 'nova';
    public const NAME = 'Laravel Nova';
    public const URL = 'nova.laravel.com/docs/3.0/';
    public const INDEX = 'installation.html';
    public const PLAYGROUND = '';
    public const ICON_16 = '../../../../../icon.png';
    public const ICON_32 = '../../../../../icon.png';
    public const EXTERNAL_DOMAINS = ['nova.laravel.com'];


    public function entries(string $file): Collection
    {
        $crawler = HtmlPageCrawler::create(Storage::get($file));

        $entries = collect();

        $crawler->filter('h1')->each(function (HtmlPageCrawler $node) use ($entries, $file) {
            $entries->push($this->entry($file, $node, 'Guide'));
        });

        $crawler->filter('h2, h3')->each(function (HtmlPageCrawler $node) use ($entries, $file) {
            $entries->push($this->entry($file, $node, 'Section'));
        });

        return $entries;
    }

    protected function entry($file, $node, $type)
    {
        return [
            'name' => Str::after($node->text(), '# '),
            'type' => $type,
            'path' => $this->url() . Str::after($file, $this->url()) . '#' . Str::slug($node->text())
        ];
    }

    public function format(string $file): string
    {
        $crawler = HtmlPageCrawler::create(Storage::get($file));

        // Removing nav
        $crawler->filter('header.navbar')->remove();
        $crawler->filter('aside.sidebar')->remove();
        // Page navigation
        $crawler->filter('.page-nav')->remove();

        // Removing margins and paddings
        $crawler->filter('main.page')->setStyle('padding-left', '0');
        $crawler->filter('.theme-default-content')
            ->setStyle('max-width', '100%')
            ->setStyle('padding-top', '0');
        // and anchors
        $crawler->filter('a.header-anchor')->setInnerHtml('');

        // Adding ToC navigation
        $crawler->filter('h2, h3')->each(function (HtmlPageCrawler $node) {
            $node->prepend('<a id="' . Str::slug($node->text()) . '"></a>');
            $node->prepend(
                '<a name="//apple_ref/cpp/Section/' . rawurlencode(Str::after($node->text(), '# ')) . '" class="dashAnchor"></a>'
            );
        });

        return $crawler->saveHTML();
    }

    /**
     * Changes with the default grab method :
     * 1) --no-parent to avoid downloading all other versions of the doc
     * 2) --reject='*.js' : do not download useless javascript files
     * 3)
     * @return bool
     */
    public function grab(): bool
    {
        system(
            "echo; wget {$this->url()} \
                --mirror \
                --trust-server-names \
                --page-requisites \
                --adjust-extension \
                --convert-links \
                --span-hosts \
                --domains={$this->externalDomains()} \
                --directory-prefix=storage/{$this->downloadedDirectory()} \
                --reject='*.js' \
                -e robots=off \
                --no-parent \
                --quiet \
                --show-progress",
            $result
        );

        return $result === 0;
    }
}
