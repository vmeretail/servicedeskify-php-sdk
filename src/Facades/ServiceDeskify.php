<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Facades;

use Illuminate\Support\Facades\Facade;
use VmeRetail\ServiceDeskify\Resources\IncidentResource;
use VmeRetail\ServiceDeskify\Resources\LocationResource;
use VmeRetail\ServiceDeskify\Resources\ReleaseResource;
use VmeRetail\ServiceDeskify\Resources\SoftwareAssetResource;
use VmeRetail\ServiceDeskify\ServiceDeskifyManager;

/**
 * @method static IncidentResource incidents()
 * @method static LocationResource locations()
 * @method static SoftwareAssetResource softwareAssets()
 * @method static ReleaseResource releases()
 *
 * @see ServiceDeskifyManager
 */
final class ServiceDeskify extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ServiceDeskifyManager::class;
    }
}
