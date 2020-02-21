<?php

namespace VmeRetail\ServiceDeskify;

/**
 * Class IncidentClose
 * @package VmeRetail\ServiceDeskify
 */
class IncidentClose extends AbstractApiResource
{
    public $incidentId;

    public function __construct($incidentId)
    {
        $this->incidentId = $incidentId;
        parent::__construct();
    }


    public function resource()
    {
        return 'incidents.close';
    }

    public function incidents()
    {
        return $this->incidentId;
    }
}