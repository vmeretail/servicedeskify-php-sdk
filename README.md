### Service Deskify Helpdesk PHP

The ServiceDeskify helpdesk PHP library provides convienient access to VmeRetail's ServiceDeskify api from apps written in PHP. It includes a pre-defined set of classes for API resources.

### Composer

### Dependencies


### Getting Started

Create an authenticated client first through in order to get access to the API exposed by ServiceDeskify.

```
(new \VmeRetail\ServiceDeskify\Http\PublicClient())
->setBaseUrl(\VmeRetail\ServiceDeskify\ServiceDeskifyConstants::HELPDESK_API_URL_TEST)
->authenticate('example@vmeretail.com', 'password', '2', 'client_secret_SNo1LKmrXdUTdjTdb1bC4eZ6l');
```

After, you would be able to access an API resource like so:

```
$incidentsApi = new \VmeRetail\ServiceDeskify\Incidents();
```

And be able to make API calls:

```
$serviceDeskifyResponseObject = $incidentsApi->create([ //...properties ]);
```

### Reference API

Details about the properties that are to be passed in and responses can be found (https://test.helpdesk.vme.co/docs/)