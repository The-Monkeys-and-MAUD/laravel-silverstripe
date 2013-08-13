<?php

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\App;

if ( ! function_exists('ss'))
{
    /**
     * Process Silverstripe HTML content
     *
     * @param  string  $value
     * @return string
     */
    function ss($value)
    {
        if ($value) {
            // replace silverstripe links with ordinary links
            $value = \DBField::create_field('HTMLText', $value);

            if (App::offsetExists('\Themonkeys\Silverstripe\ContentProcessor')) {
                $contentProcessor = App::make('\Themonkeys\Silverstripe\ContentProcessor');
                // process the <img> tags
                $value = preg_replace_callback('|<img[^>]+>|', function($matches) use ($contentProcessor) {
                    $DOM = new DOMDocument;
                    $DOM->loadHTML($matches[0]);
    
                    $img = $DOM->getElementsByTagName('img')->item(0);
                    $src = $img->getAttribute('src');
                    $alt = $img->getAttribute('alt');
                    $attributes = array();
                    foreach ($img->attributes as $name => $value) {
                        if ($name != 'src' && $name != 'alt') {
                            $attributes[$name] = $value->textContent;
                        }
                    }
    
                    if (preg_match('|assets/Uploads/(_resampled/ResizedImage([0-9]+)-)(.*)$|', $src, $matches)) {
                        // convert to the non-resampled src
                        $src = 'assets/Uploads/' . $matches[3];
                    }
    
                    // make a Silverstripe image out of this URL
                    $image = Image::get()->filter('Filename', $src)->first();
    
                    // allow the ContentProcessor implementation to process the image
                    return $contentProcessor->renderImage($image, $alt, $attributes);

                }, $value);

                $processed = $contentProcessor->processContent($value);
                if ($processed) {
                    $value = $processed;
                }
            }

            return $value;
        }
    }
}
