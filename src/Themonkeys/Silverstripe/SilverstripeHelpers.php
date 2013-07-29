<?php

use Illuminate\Support\Facades\View;

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

            // process the <img> tags through img.blade.php
            $value = preg_replace_callback('|<img[^>]+>|', function($matches) {
                $DOM = new DOMDocument;
                $DOM->loadHTML($matches[0]);

                $img = $DOM->getElementsByTagName('img')->item(0);
                $src = $img->getAttribute('src');
                $alt = $img->getAttribute('alt');

                if (preg_match('|assets/Uploads/(_resampled/ResizedImage([0-9]+)-)(.*)$|', $src, $matches)) {
                    // convert to the non-resampled src
                    $src = 'assets/Uploads/' . $matches[3];
                }

                // make a Silverstripe image out of this URL
                $image = Image::get()->filter('Filename', $src)->first();

                return View::make('partials/img', array(
                    'img' => $image,
                    'alt' => $alt,
                ))->render();

            }, $value);

            return $value;
        }
    }
}
