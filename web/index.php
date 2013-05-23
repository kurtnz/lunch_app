<?php

require_once dirname(__DIR__) . '/lib/silex.phar';
require(__DIR__.'/../vendor/swiftmailer/swiftmailer/lib/swift_required.php');

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app = new Silex\Application();
// Enable debugging.
$app['debug'] = true;
// Register Twig
$app['autoloader']->registerPrefixes(array(
    'Twig_Extensions_'  => array(__DIR__.'/../vendor/twig/extensions/lib')
));
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path'         => dirname(__DIR__) . '/lib/twig/templates',
    'twig.class_path'   => dirname(__DIR__) . '/lib/twig/lib',
    'twig.options'      => array('debug' => true),
));
$oldTwigConfiguration = isset($app['twig.configure']) ? $app['twig.configure']: function(){};
$app['twig.configure'] = $app->protect(function($twig) use ($oldTwigConfiguration) {
    $oldTwigConfiguration($twig);
    $twig->addExtension(new Twig_Extensions_Extension_Debug());
});

$app->register(new Silex\Provider\SwiftmailerServiceProvider());



// We use a database for keeping our data. Before every request we need a connection.
// The connection is kept in $conn.
$app['conn'] = NULL;
$app->before(function () use ($app) {
    $app['conn'] = new PDO('mysql:dbname=lunches_old;host=localhost', 'root', 'root');
});



// Routing and dispatching

/*
*
*  Get a list of users
*   /api/getUsers
*
*/
$app->get('/api/users', function() use($app) {
    $sql = "SELECT * FROM users";
    $rows = array();
    $return_arr = array();
    foreach ($app['conn']->query($sql) as $users) {
        $rows['id'] = $users['id'];
        $rows['name'] = $users['name'];
        array_push($return_arr, $rows);
    }
    print json_encode($return_arr);
});

/*
*
*  Get food type categories
*  /api/food-types
*
*/
$app->get('/api/food-types', function() use($app) {

    $sql = "SELECT * FROM food_type";

    $rows = array();
    $return_arr = array();

    foreach ($app['conn']->query($sql) as $foodType) {
        $rows['id'] = $foodType['id'];
        $rows['name'] = $foodType['name'];
        array_push($return_arr, $rows);
    }
    print json_encode($return_arr);

});

/*
*
*  Get food type categories
*  /api/food
*
*/
$app->get('/api/food', function() use($app) {

    $sql = "SELECT * FROM food";

    $rows = array();
    $return_arr = array();

    foreach ($app['conn']->query($sql) as $food) {
        $rows['id'] = $food['id'];
        $rows['name'] = $food['name'];
        $rows['food_type'] = $food['food_type'];
        array_push($return_arr, $rows);
    }
    print json_encode($return_arr);

});

/*
*
* Get all orders
*
*/
$app->get('/api/orders', function() use ($app) {

    $sql = "SELECT * FROM orders";

    $rows = array();
    $return_arr = array();

    foreach ($app['conn']->query($sql) as $order) {
        $rows['id'] = $order['id'];
        $rows['date'] = str_replace('/', '.', $order['date']);
        $rows['start'] = strtotime(str_replace('/', '.', $order['date']));
        $rows['title'] = $order['user'] . ' ' .$order['day'];
        $rows['day'] = $order['day'];
        $rows['week'] = $order['week'];
        $rows['user'] = $order['user'];
        $rows['ordertype'] = $order['ordertype'];
        $rows['orderdetails'] = $order['orderdetails'];
        $rows['comments'] = $order['comments'];
        $rows['sandwichorwrap'] = $order['sandwichorwrap'];
        $rows['breadtype'] = $order['breadtype'];
        array_push($return_arr, $rows);
    }
    print json_encode($return_arr);

});


// OLD -------------------------------------------

/*
*
*
*
*/
$app->get('/', function() use ($app){
    //$response = new Response($app['twig']->render('index.twig', array()));
    $response = new Response($app['twig']->render('review.twig', array()));
    return $response;
});

/*
*
*
*
*/
$app->get('/review', function() use ($app){
    $response = new Response($app['twig']->render('review.twig', array()));
    return $response;
});

/*
*
*
*
*/
$app->get('/users', function() use ($app) {
    $sql = "SELECT * FROM users";

    $rows = array();
    $return_arr = array();

    foreach ($app['conn']->query($sql) as $user) {
        $rows['name'] = $user['name'];
        array_push($return_arr, $rows);
    }
    print json_encode($return_arr);
});

/*
*
*
*
*/
$app->post('/save', function() use ($app) {

    $data = json_decode(file_get_contents('php://input'));

    $date           = $data->date;
    $day            = $data->day;
    $week           = $data->week;
    $user           = $data->user;
    $ordertype      = $data->orderType;
    $orderdetails   = $data->orderDetails;
    $comments       = addslashes($data->comments);
    $sandwichorwrap = $data->sandwichorwrap;
    $breadtype      = $data->breadtype;

    $sql = "DELETE FROM orders WHERE date = ' ".$date."' AND day = '".$day."' AND week = '".$week."' AND user = '".$user."'";

    $app['conn']->query($sql);

    $sql = "INSERT INTO orders (date, day, week, user, ordertype, orderdetails, comments, sandwichorwrap, breadtype) VALUES (' " . $date . "', '" . $day . "', '" . $week . "', '" . $user . "', '" . $ordertype . "', '" . $orderdetails ."', '". $comments ."', '". $sandwichorwrap ."', '". $breadtype ."')";

    $app['conn']->query($sql);
});

/*
*
*
*
*/
$app->delete('/orders/{id}', function($id) use ($app) {
    $sql = 'DELETE FROM orders WHERE id=' . $id;
    $app['conn']->query($sql);
});

/*
*
*
*
*/
$app->post('/mail', function(Request $request) use ($app) {

    $data = $request->getContent();

    $message = \Swift_Message::newInstance()
        ->setSubject('August Lunch Orders')
        ->setFrom(array('kurt.smith@august.com.au'))
        ->setTo(array('kurt.smith@august.com.au'))
        ->setBody( $app['twig']->render('mailtemplate.twig', array(
                        'data' =>  json_decode($data)
                    )), 'text/html');

    $app['mailer']->send($message);

    return new Response($data, 201);
});

// When we're done with the request: Throw away the connection.
$app->after(function() use ($app)
{
    $app['conn'] = NULL;
});

// Now do something already!
$app->run();
