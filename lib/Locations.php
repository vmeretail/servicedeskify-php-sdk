<?php

namespace VmeRetail\ServiceDeskify;


use VmeRetail\ServiceDeskify\HelpdeskClient\AbstractApiResource;

class Locations extends AbstractApiResource
{
    public function resource()
    {
        return 'locations';
    }
}