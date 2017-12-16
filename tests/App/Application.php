<?php
namespace CakeDC\Favorites\Test\App;

use Cake\Http\BaseApplication;
use Cake\Error\Middleware\ErrorHandlerMiddleware;

class Application extends BaseApplication
{
    /**
     * {@inheritDoc}
     */
    public function bootstrap()
    {
    }
    
    public function middleware($middlewareQueue)
    {
        $middlewareQueue->add(new ErrorHandlerMiddleware());

        return $middlewareQueue;
    }
}