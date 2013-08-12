<?php

namespace Themonkeys\Silverstripe;


interface ContentProcessor {
    /**
     * Called to render an <img> html tag. Implementations should return a string which is an HTML snippet that renders
     * the image.
     *
     * @param \Image $image the SilverStripe Image object instance to be rendered
     * @param string $alt the image's alternate text
     * @param array $otherAttributes map of other HTML attributes specified for the image in the CMS
     * @return string an HTML snippet that renders the image
     */
    public function renderImage(\Image $image, $alt, array $otherAttributes);

    /**
     * Called to perform any necessary final processing on the HTML content before it is sent to the browser. This is
     * the last method to be called on this interface prior to echoing the content to the page - so, for example, all
     * images would already have been passed through the renderImage() function before this method is called.
     * @param string $html the HTML content
     * @return string the modified HTML content, or null to preserve the HTML content as-is
     */
    public function processContent($html);
}