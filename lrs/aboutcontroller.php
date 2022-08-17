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
 * @copyright   2019-2022 LushOnline
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require($CFG->dirroot.'/mod/externalcontent/lrs/vendor/autoload.php');
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Container\ContainerInterface;

/**
 * Class containing controllers for about
 *
 * @package   mod_externalcontent
 * @copyright 2019-2022 LushOnline
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class AboutController {
    /**
     * container
     *
     * @var ContainerInterface ContainerInterface instance passed by Slim
     */
    protected $container;

    /**
     * __construct
     *
     * @param ContainerInterface $container ContainerInterface instance passed by Slim
     * @return self
     */
    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $cfg = get_config('externalcontent');
        $this->enabled = $cfg->xapienable;
    }

    /**
     * Process the about request
     *
     * @param Request $request object that represents the current HTTP request.
     * @param Response $response object that represents the current HTTP response.
     * @param mixed[] $args associative array that contains values for the current route’s named placeholders.
     * @return object the response object
     */
    public function about(Request $request, Response $response, array $args) {
        if (!$this->enabled) {
            return $response
                ->withStatus(401)
                ->withAddedHeader('Content-Type', 'text/plain')
                ->write('xAPI not enabled.');
        }

        $versions = array();
        array_push($versions, '1.0.0');

        return $response->withJson($versions);
    }
}
