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
defined('MOODLE_INTERNAL') || die;

require_once('../../../config.php');
require('./xapihelper.php');
require('./vendor/autoload.php');
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use TinCan\Statement;
use TinCan\Agent;

class StatementController
{
    protected $container;
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $cfg = get_config('externalcontent');
        $this->enabled = $cfg->xapienable;
    }

    public function poststatement(Request $request, Response $response, array $args) {
        if (!$this->enabled) {
            return $response
                ->withStatus(401)
                ->withAddedHeader('Content-Type', 'text/plain')
                ->write('xAPI not enabled.');
        }

        $debug = $request->getQueryParam('debug');
        $contenttype = $request->getContentType();

        $isjson = ($contenttype === 'application/json');

        if ($isjson) {
            $body = $request->getBody();
        } else {
            // Get the json from the multipart/mixed
            $matches = array();
            preg_match('/boundary=([^"]+)/i', $contenttype, $matches);
            list(, $boundary) = $matches;
            $rawbody = $request->getBody()->getContents();
            $text = "";
            if (isset($boundary) && !empty($boundary)) {
                $boundary = '--' .trim($boundary);
                $requestsegments = explode($boundary, $rawbody);
                foreach ($requestsegments as $segment) {
                    if (!empty(trim($segment))) {
                        $text = trim(preg_replace('/(Content-(Type|Length):.*?(\r\n|\n))/i', "", $segment));
                        break;
                    }
                }
            }
            $body = trim($text);
        }

        $xapiversion = $request->getHeaderLine('X-Experience-API-Version');

        $statementids = array();
        $statements = array();
        $payloads = array();

        $receivedstatements = json_decode($body);

        if (is_array($receivedstatements)) {
            $statements = $receivedstatements;
        } else {
            array_push($statements, $receivedstatements);
        }

        foreach ($statements as $statement) {
            $payload = xapihelper::processstatement($xapiversion ? $xapiversion : '1.0.0', new Statement(json_decode(json_encode($statement), true)));
            if ($debug) {
                array_push($payloads, $payload);
            }
            array_push($statementids, $payload->statementId);
        }

        return $response->withJson($debug ? $payloads : $statementids);
    }

    public function notimplemented(Request $request, Response $response, array $args) {
        $message = 'Credentials invalid for this endpoint.';
        if (!$this->enabled) {
            $message = 'xAPI not enabled.';
        }

        return $response
            ->withStatus(401)
            ->withAddedHeader('Content-Type', 'text/plain')
            ->write($message);
    }
}