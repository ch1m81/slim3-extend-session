<?php

namespace Slim\Middleware;

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

class SessionExtend
{
    /**
     * @var array
     */
    protected $settings;

    /**
     * Constructor
     *
     * @param array $settings
     */
    public function __construct($settings = []) {
        				
			$defaults = [
				'lifetime'    => '30 minutes',
				'path'        => '/',
				'domain'      => null,
				'secure'      => false,
				'httponly'    => false,
				'name'        => 'xxx',
				'autorefresh' => true,
			];
        
			$settings = array_merge($defaults, $settings);

			if (is_string($lifetime = $settings['lifetime'])) {
				$settings['lifetime'] = strtotime($lifetime) - time();
			}
        
			$this->settings = $settings;

			ini_set('session.gc_probability', 0);
			ini_set('session.gc_divisor', 1);
			ini_set('session.gc_maxlifetime', 30 * 24 * 60 * 60);
    }

    /**
     * Called when middleware needs to be executed.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(Request $request, Response $response, callable $next) {
				
			//$token = ($request->hasHeader('token')) ? $request->getHeaderLine('token') : $request->getCookieParams()["supportfaq"]? : false;
			$token = ($request->hasHeader('token')) ? $request->getHeaderLine('token') :  false;
		
			$isValid = $this->isValidSessionID($token);
			
			if ($isValid) {				
				
				// return user id (for adding or updating the item in DB, will be used as doneBY)
				$request = $request->withAttribute('userID', $_SESSION['user']['userID']); 
				
				$response = $next($request, $response);				
				
				$this->extendSession();				

			} else {				
				return $response->withStatus(403)->withHeader('kicked', 'oyea')->write('You must be logged in to be able to use this function!!!');
			}
			
			return $response;
    }

    /**
     * Check if the user provided token matching session ID
     */
    protected function isValidSessionID($token) {        
					 
			if (!$token && !isset($_SESSION['token'])) 	return false;
			
			if (empty(session_id())) {
				$name = $this->settings['name'];
				session_name($name);       
				session_start();
			}			
		
		
			if (isset($_SESSION['token']) && $_SESSION['token'] && $_SESSION['token'] === $token) {					
				return true;
			}						
				return false;
    }
		
		
		/**
     * Extend session
     */
    protected function extendSession()
    {
								
			$settings = $this->settings;
			$name = $settings['name'];

		 session_set_cookie_params(
				$settings['lifetime'],
				$settings['path'],
				$settings['domain'],
				$settings['secure'],
				$settings['httponly']
			);

			if (session_id()) {            
				if ($settings['autorefresh'] && isset($_COOKIE[$name])) {
                								
					setcookie(
						$name,
						$_COOKIE[$name],
						time() + $settings['lifetime'],
						$settings['path'],
						$settings['domain'],
						$settings['secure'],
						$settings['httponly']
					);
				}		
			}
    }
}
