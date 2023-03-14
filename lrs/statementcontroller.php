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
 * @copyright   2019-2023 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require($CFG->dirroot.'/mod/externalcontent/lrs/xapihelper.php');
require($CFG->dirroot.'/mod/externalcontent/lrs/vendor/autoload.php');
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;
use TinCan\Statement;
use TinCan\Agent;

/**
 * Class containing controllers for statements
 *
 * @package   mod_externalcontent
 * @copyright 2019-2023 LushOnline
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class StatementController {
    /**
     * container
     *
     * @var ContainerInterface instance passed by Slim
     */
    protected $container;

    /**
     * __construct
     *
     * @param ContainerInterface $container
     * @return self
     */
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $cfg = get_config('externalcontent');
        $this->enabled = $cfg->xapienable;
    }


    /**
     *
     * Parse a header value to get the options
     *
     * @param string $content The header content
     *
     * @return array() the options for the header
     */
    private static function parseheadercontent($content) {
        $parts = explode(';', $content);
        $options = array();
        // Parse options.
        foreach ($parts as $part) {
            if (false === empty($part)) {
                $partsplit = explode('=', $part, 2);
                if (2 === count($partsplit)) {
                    list ($key, $value) = $partsplit;
                    if ('*' === substr($key, -1)) {
                        // RFC 5987.
                        $key = substr($key, 0, -1);
                        if (preg_match(
                            "/(?P<charset>[\w!#$%&+^_\u0060{}~-]+)'(?P<language>[\w-]*)'(?P<value>.*)$/",
                            $value,
                            $matches
                        )) {
                            $value = mb_convert_encoding(
                                rawurldecode($matches['value']),
                                'utf-8',
                                $matches['charset']
                            );
                        }
                    }
                    $options[trim($key)] = trim($value, ' "');
                } else {
                    // Bogus option.
                    $options[$partsplit[0]] = '';
                }
            }
        }

        return $options;
    }

    /**
     * Gets the multipart boundary
     *
     * @param string $contenttype The content-type header
     *
     * @return string
     */
    private function getboundary($contenttype) {
        // Find Content-Type multipart boundary.
        $options = self::parseheadercontent($contenttype);
        return $options['boundary'];
    }

    /**
     * Gets the JSON from multipart/mixed body
     *
     * @param string $boundary The multipart boundary
     * @param string $rawbody The rawbody of the request
     *
     * @return string
     */
    private function getjsonbodyfrommixed($boundary, $rawbody) {
        $text = "";
        if (isset($boundary) && !empty($boundary)) {
            $boundary = '--' .trim($boundary);
            $requestsegments = explode($boundary, $rawbody);
            foreach ($requestsegments as $segment) {
                if (!empty(trim($segment))) {
                    $endheaders = strpos($segment, "\r\n\r\n", 4);
                    $startcontent = $endheaders + 4;
                    $text = substr($segment, $startcontent);
                    break;
                }
            }
        }
        return trim($text);
    }

    /**
     * Process inbound xAPI statements
     *
     * @param Request $request object that represents the current HTTP request.
     * @param Response $response object that represents the current HTTP response.
     * @param mixed[] $args associative array that contains values for the current route’s named placeholders.
     * @return object the response object
     */
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
            // Get the json from the multipart/mixed.
            $boundary = self::getboundary($contenttype);
            $rawbody = $request->getBody()->getContents();
            $body = self::getjsonbodyfrommixed($boundary, $rawbody);
        }

        $xapiversion = $request->getHeaderLine('X-Experience-API-Version');

        $statementids = array();
        $statements = array();
        $payloads = array();

        $receivedstatements = json_decode($body);

        is_array($receivedstatements) ? $statements = $receivedstatements : array_push($statements, $receivedstatements);

        foreach ($statements as $statement) {
            $payload = xapihelper::processstatement($xapiversion ? $xapiversion : '1.0.0',
                                                    new Statement(json_decode(json_encode($statement), true)));
            array_push($payloads, $payload);
            array_push($statementids, $payload->statementId);
        }

        return $response->withJson($debug ? $payloads : $statementids);
    }

    /**
     * Process requests for xAPI statements with "null" response
     *
     * @param Request $request object that represents the current HTTP request.
     * @param Response $response object that represents the current HTTP response.
     * @param mixed[] $args associative array that contains values for the current route’s named placeholders.
     * @return object the response object
     */
    public function fakegetstatement(Request $request, Response $response, array $args) {
        if (!$this->enabled) {
            return $response
                ->withStatus(401)
                ->withAddedHeader('Content-Type', 'text/plain')
                ->write('xAPI not enabled.');
        }

        $payload = new \stdClass;
        $payload->statements = array();
        $payload->more = '';

        return $response->withJson($payload);
    }


    /**
     * Send a 401 unauthorised response for anyresource paths we havent implemented
     *
     * @param Request $request object that represents the current HTTP request.
     * @param Response $response object that represents the current HTTP response.
     * @param mixed[] $args associative array that contains values for the current route’s named placeholders.
     * @return object the response object
     */
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
