<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class PageController extends Controller {

    public function error_four(Request $request, Response $response, $args) {
        return $this->render($response, '404.php');
    }
    
    public function culture(Request $request, Response $response, $args) {
        return $this->render($response, 'culture.php');
    }

    public function solar_terms(Request $request, Response $response, $args) {
        return $this->render($response, '24_solar_terms.php');
    }
    
    public function experienceList(Request $request, Response $response, $args) {
        return $this->render($response, 'experienceList.php');
    }

    public function ESG_environmental(Request $request, Response $response, $args) {
        return $this->render($response, 'ESG_environmental.php');
    }

    public function ESG_food(Request $request, Response $response, $args) {
        return $this->render($response, 'ESG_food.php');
    }

    public function privacy(Request $request, Response $response, $args) {
        return $this->render($response, 'privacy.php');
    }

    public function terms(Request $request, Response $response, $args) {
        return $this->render($response, 'terms.php');
    }

    public function registration_license(Request $request, Response $response, $args) {
        return $this->render($response, 'registration-license.php');
    }

    public function products_liability(Request $request, Response $response, $args) {
        return $this->render($response, 'products-liability.php');
    }
    
}