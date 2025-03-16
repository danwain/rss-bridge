<?php

class BuySellCyprusBridge extends BridgeAbstract
{
    const NAME = 'BuySellCyprus Bridge';
    const URI = 'https://www.buysellcyprus.com';
    const DESCRIPTION = 'Fetch news from BuySellCyprus.';
    const MAINTAINER = 'No maintainer';
    const PARAMETERS = [
        [
            'url' => [
                'name'         => 'URL',
                'type'         => 'text',
                'required'     => true,
                'title'        => 'Enter the URL of the BuySellCyprus page to fetch properties from.',
                'exampleValue' => 'https://www.buysellcyprus.com/properties-for-sale/type-land/cur-eur/sort-ru/location-all-paphos/page-1',
            ],
            'limit' => [
                'name'         => 'Limit',
                'type'         => 'number',
                'required'     => false,
                'title'        => 'Enter the number of news to fetch.',
                'exampleValue' => '10',
                'defaultValue' => 10,
            ]
        ]
    ];

    public function collectData()
    {
        $html = getSimpleHTMLDOM($this->getInput('url'));

        $i = 0;
        foreach ($html->find('div.bs-card-title') as $element) {
            $i++;
            if ($i > $this->getInput('limit')) {
                break;
            }

            $item = [];

            $item['uri'] = $element->find('a', 0)->href;

            # Get the content
            $listing = getSimpleHTMLDOM($item['uri']);

            $item['title'] = $listing->find('h1', 0)->plaintext;

            $time = $listing->find('time', 0)->plaintext;

            $item['content'] = $this->formatContent($listing);
            $item['author'] = $listing->find('h3', 0)->plaintext;

            $this->items[] = $item;
        }
    }

    /**
     * Format content in the specified order:
     * 1. First image
     * 2. div.bs-listing-info-features-main
     * 3. div.bs-listing-info-description-main
     * 4. Remaining images
     */
    private function formatContent($listing)
    {
        $content = '';

        // Get all images
        $images = $listing->find('img.js-lazy-image');

        // Add first image at the top
        if (count($images) > 0) {
            $firstImg = $images[0];
            $imgSrc = $firstImg->getAttribute('data-src') ?: $firstImg->src;
            $content .= '<div class="property-main-image"><img src="' . $imgSrc . '" /></div>';
        }

        // Add features
        $features = $listing->find('div.bs-listing-info-features-main', 0);
        if ($features) {
            $content .= $features->outertext;
        }

        // Add description
        $description = $listing->find('div.bs-listing-info-description-main', 0);
        if ($description) {
            // Remove the "Read More" and "Show Less" labels
            $readMoreLabels = $description->find('label.load-more-listing');
            foreach ($readMoreLabels as $label) {
                $label->outertext = '';
            }

            $showLessLabels = $description->find('label.show-less-listing');
            foreach ($showLessLabels as $label) {
                $label->outertext = '';
            }

            // Get the cleaned description HTML
            $descriptionText = $description->outertext;

            // Trim whitespace
            $descriptionText = trim($descriptionText);

            $content .= $descriptionText;
        }

        // Add remaining images at the end
        if (count($images) > 1) {
            $content .= '<div class="property-additional-images">';
            for ($i = 1; $i < count($images); $i++) {
                $img = $images[$i];
                $imgSrc = $img->getAttribute('data-src') ?: $img->src;
                $content .= '<img src="' . $imgSrc . '" />';
            }
            $content .= '</div>';
        }

        return $content;
    }
}
