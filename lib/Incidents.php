<?php

namespace VmeRetail\ServiceDeskify;

use VmeRetail\ServiceDeskify\HelpdeskClient\AbstractApiResource;

class Incidents extends AbstractApiResource
{
    public function  resource()
    {
        return 'incidents';
    }
}