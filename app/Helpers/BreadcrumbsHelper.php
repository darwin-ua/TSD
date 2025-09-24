<?php

if (!function_exists('breadcrumbs')) {
    function breadcrumbs()
    {
        $segments = request()->segments();
        $url = url('/');
        $breadcrumbs = [];

        foreach ($segments as $segment) {
            $url .= '/' . $segment;
            $breadcrumbs[] = [
                'label' => ucfirst($segment),
                'url' => $url,
            ];
        }

        return $breadcrumbs;
    }
}

