<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Basic LRS functionality to allow
 *
 * @package     mod_externalcontent
 * @copyright   2019-2020 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('NO_MOODLE_COOKIES', true);
require_once(__DIR__.'/../../../config.php');

require($CFG->dirroot.'/mod/externalcontent/lrs/vendor/autoload.php');
require($CFG->dirroot.'/mod/externalcontent/lrs/statementcontroller.php');
require($CFG->dirroot.'/mod/externalcontent/lrs/aboutcontroller.php');
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$configuration = [
    'settings' => [
        'displayErrorDetails' => true,
    ],
];
$c = new \Slim\Container($configuration);
$app = new \Slim\App($c);

$cfg = get_config('externalcontent');

$app->add(new \Tuupola\Middleware\HttpBasicAuthentication([
    "users" => [
        $cfg->xapiusername => $cfg->xapipassword,
    ]
]));

$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
        ->withHeader('X-Experience-API-Version', '1.0.0')
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers',
                     'X-Requested-With, Content-Type, Accept, Origin, Authorization, X-Experience-API-Version')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

$app->post('/statements', '\StatementController:poststatement');
$app->put('/statements', '\StatementController:poststatement');
$app->get('/statements', '\StatementController:fakegetstatement');

$app->get('/about', '\AboutController:about');

$app->run();
