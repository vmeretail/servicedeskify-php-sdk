<?php

namespace VmeRetail\ServiceDeskify;

use VmeRetail\ServiceDeskify\HelpdeskClient\AbstractApiResource;

class IncidentPosts extends AbstractApiResource
{

    public function resource()
    {
        return 'incidents.posts';
    }

    // TODO This will get the id of the resource it is nested into
    public function incidents()
    {
        return 1;
    }
}