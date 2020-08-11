<?php

namespace VmeRetail\ServiceDeskify;

/**
 * Class IncidentClosureDetails
 * @package VmeRetail\ServiceDeskify
 */
class IncidentClosureDetails extends AbstractApiResource
{
    public $incidentId;

    public function __construct($incidentId)
    {
        $this->incidentId = $incidentId;
        parent::__construct();
    }


    public function resource()
    {
        return 'incidents.closure-details';
    }

    public function incidents()
    {
        return $this->incidentId;
    }
}