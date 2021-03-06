<?php

namespace VmeRetail\ServiceDeskify;

/**
 * Class IncidentPosts
 * @package VmeRetail\ServiceDeskify
 * https://test.helpdesk.vme.co/docs/#retrieve-an-incidents-posts
 */
class IncidentPosts extends AbstractApiResource
{
    public $incidentId;

    public function __construct($incidentId)
    {
        $this->incidentId = $incidentId;
        parent::__construct();
    }


    public function resource()
    {
        return 'incidents.posts';
    }

    public function incidents()
    {
        return $this->incidentId;
    }
}