<?php

class CyprusMailBridge extends BridgeAbstract
{
    const NAME = 'Cyprus Mail Bridge';
    const URI = 'https://www.cyprus-mail.com';
    const DESCRIPTION = 'Fetch news from Cyprus Mail.';
    const MAINTAINER = 'No maintainer';
    const PARAMETERS = [
        [
            'url' => [
                'name'         => 'URL',
                'type'         => 'text',
                'required'     => true,
                'title'        => 'Enter the URL of the Cyprus Mail page to fetch news from.',
                'exampleValue' => 'https://www.cyprus-mail.com/news',
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
        foreach ($html->find('div.articleInfos') as $element) {
            $i++;
            if ($i > $this->getInput('limit')) {
                break;
            }

            $item = [];

            $item['uri'] = "https://www.cyprus-mail.com" . $element->find('a', 0)->href;

            # Get the content
            $article = getSimpleHTMLDOM($item['uri']);

            $item['title'] = $article->find('h1', 0)->plaintext;

            $time = $article->find('time', 0)->plaintext;

            $item['content'] = $article->find('div.articleBody', 0)->innertext;
            $item['timestamp'] = $this->parseTimestamp($time);
            $item['author'] = str_replace('By ', '', trim($article->find('div._authors_1tedo_125', 0)->plaintext));

            $this->items[] = $item;
        }
    }

    /**
     * Parse Cyprus Mail timestamp format to Unix timestamp
     *
     * @param string $timeString Format example: 'Saturday 15 March | 14:29'
     * @return int|string Unix timestamp if parsing succeeds, original string otherwise
     */
    private function parseTimestamp($timeString)
    {
        if (!$timeString) {
            return '';
        }

        $timeString = trim($timeString);
        // Extract the date parts
        $parts = explode('|', $timeString);
        if (count($parts) == 2) {
            $datePart = trim($parts[0]); // 'Saturday 15 March'
            $timePart = trim($parts[1]); // '14:29'

            // Remove day name if present (e.g., 'Saturday')
            $datePart = preg_replace('/^[a-zA-Z]+ /', '', $datePart);

            // Create a properly formatted date string
            $formattedDate = $datePart . ' ' . date('Y') . ' ' . $timePart;
            $timestamp = strtotime($formattedDate);

            if ($timestamp) {
                return $timestamp;
            }
        }

        return $timeString; // Return original string if parsing fails
    }
}
