<?php

namespace VmeRetail\ServiceDeskify;


use VmeRetail\ServiceDeskify\HelpdeskClient\AbstractApiResource;

// TODO There is no access to this resource in the Service Deskify API
class UserLocations extends AbstractApiResource
{
    public function resource()
    {
        return 'users.locations';
    }
}