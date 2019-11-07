<?php

namespace VmeRetail\ServiceDeskify;

/**
 * Class Incidents
 * @package VmeRetail\ServiceDeskify
 * https://test.helpdesk.vme.co/docs/#incidents
 */
class Incidents extends AbstractApiResource
{
    public function  resource()
    {
        return 'incidents';
    }
}