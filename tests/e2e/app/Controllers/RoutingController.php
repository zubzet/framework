<?php

    /**
     * Test fixtures for tests/cypress/e2e/core/routing.cy.js.
     *
     * Holds the route action, route/group middleware, afterware variants
     * (with + without arguments) and the action_check that the /RouteAccept
     * and /RouteDeny groups dispatch to. Consolidating these here lets
     * CoreController stay focused on actual core actions.
     */
    class RoutingController extends z_controller {

        // Action that runs for /RouteAccept/check. /RouteDeny/check is
        // blocked by its middleware before reaching here, so the deny
        // assertion never observes this string.
        public function action_check(Request $req, Response $res) {
            print_r("Middleware Accept Executed\n");
        }

        // Default test route
        public function TestRoute(Request $req, Response $res) {
            print_r("TestRoute Executed");
            print_r($req->getRouteParameter());
        }

        // Middleware for Routes which let the request pass
        public function Route_Middleware_Accept(Request $req, Response $res) {
            print_r("Route Middleware Accept Executed");
            print_r($req->getRouteParameter());
            return true;
        }

        // Middleware for Groups which let the request pass
        public function Group_Middleware_Accept(Request $req, Response $res) {
            print_r("Group Middleware Accept Executed");
            print_r($req->getRouteParameter());
            return true;
        }

        // Middleware for Routes which block the request
        public function Route_Middleware_Block(Request $req, Response $res) {
            print_r("Route Middleware Blocked Executed");
            print_r($req->getRouteParameter());
        }

        // Middleware for Groups which block the request
        public function Group_Middleware_Block(Request $req, Response $res) {
            print_r("Group Middleware Blocked Executed");
            print_r($req->getRouteParameter());
        }

        // Afterware for Routes
        public function Route_Afterware(Request $req, Response $res) {
            print_r("Route Afterware Executed");
            print_r($req->getRouteParameter());
        }

        // Afterware for Groups
        public function Group_Afterware(Request $req, Response $res) {
            print_r("Group Afterware Executed");
            print_r($req->getRouteParameter());
        }

        // Action which prints arguments
        public function TestRoute_WithArguments(Request $req, Response $res, $arg1 = null, $arg2 = null) {
            print_r("TestRoute Executed");
            print_r($req->getRouteParameter());
            echo " Args: $arg1 $arg2";
        }

        // Middleware which prints and accepts
        public function Route_Middleware_Accept_WithArguments(Request $req, Response $res, $arg1 = null, $arg2 = null) {
            print_r("Route Middleware Accept Executed");
            print_r($req->getRouteParameter());
            echo " Args: $arg1 $arg2";
            return true;
        }

        // Afterware which prints arguments
        public function Route_Afterware_WithArguments(Request $req, Response $res, $arg1 = null, $arg2 = null) {
            print_r("Route Afterware Executed");
            print_r($req->getRouteParameter());
            echo " Args: $arg1 $arg2";
        }

    }

?>
