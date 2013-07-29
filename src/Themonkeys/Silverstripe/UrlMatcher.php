<?php

namespace Themonkeys\Silverstripe;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Matcher\UrlMatcher as L4_UrlMatcher;


class UrlMatcher extends L4_UrlMatcher {
    /**
     * {@inheritdoc}
     */
    public function match($pathinfo)
    {
        $this->allow = array();

        if ($ret = $this->matchCollection(rawurldecode($pathinfo), $this->routes)) {
            return $ret;
        }

        if (0 < count($this->allow)) {
            throw new MethodNotAllowedException(array_unique(array_map('strtoupper', $this->allow)));
        }
        return $ret;
    }

}