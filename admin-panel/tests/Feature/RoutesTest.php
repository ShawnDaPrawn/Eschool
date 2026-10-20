<?php

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

class RoutesTest extends TestCase
{
    public function test_every_route_points_to_an_existing_controller_method(): void
    {
        $missing = collect(RouteFacade::getRoutes()->getRoutes())
            ->filter(fn (Route $route) => is_string($route->getAction('uses')) && str_contains($route->getAction('uses'), '@'))
            ->reject(fn (Route $route) => method_exists(...explode('@', $route->getAction('uses'))))
            ->map(fn (Route $route) => implode('|', $route->methods()).' '.$route->uri().' -> '.$route->getAction('uses'))
            ->values()
            ->all();

        // Such routes return 500. For resource routes, leave the action out with ->except().
        $this->assertSame([], $missing, 'Routes point to controller methods that do not exist');
    }
}
