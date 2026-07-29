<?php

declare(strict_types=1);

namespace VmeRetail\ServiceDeskify\Enums;

enum PostType: string
{
    case Public = 'public';
    case Private = 'private';
}
