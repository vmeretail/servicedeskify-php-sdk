<?php

namespace VmeRetail\ServiceDeskify;

use VmeRetail\ServiceDeskify\AbstractApiResource;

/**
 * Class IncidentPosts
 * @package VmeRetail\ServiceDeskify
 * https://test.helpdesk.vme.co/docs/#retrieve-an-incidents-posts
 */
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