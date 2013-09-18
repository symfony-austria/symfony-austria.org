<?php

use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Parser;
use Suin\RSSWriter\Channel;
use Suin\RSSWriter\Feed;
use Suin\RSSWriter\Item;
use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;

$yaml = new Parser();
$database = $yaml->parse(file_get_contents(__DIR__."/../data/database.yml"));
$linksDatabase = $yaml->parse(file_get_contents(__DIR__."/../data/links.yml"));
$newsDatabase = $yaml->parse(file_get_contents(__DIR__."/../data/news.yml"));
$selectorsDatabase = $yaml->parse(file_get_contents(__DIR__."/../data/selectors.yml"));
$resoucresDatabase = $yaml->parse(file_get_contents(__DIR__."/../data/resources.yml"));
$jobDatabase = $yaml->parse(file_get_contents(__DIR__."/../data/jobs.yml"));

$db = array
(
    'news_actual' => $selectorsDatabase['news_actual'],
    'events_actual' => $selectorsDatabase['events_actual'],
    'unscheduled_talks' => $selectorsDatabase['unscheduled_talks'],

    'news' => $newsDatabase['news'],
    'our_links' => $linksDatabase['our_links'],
    'links' => $linksDatabase['links'],
    'supporters' => $linksDatabase['supporters'],
    
    'events' => $database['events'],
    'events_reverse' => array_reverse($database['events']),
    'locations' => $database['locations'],
    'talks' => $database['talks'],
    'persons' => $database['persons'],
    'companies' => $database['companies'],
    'resources' => $resoucresDatabase['resoucres'],
    'jobs' => $jobDatabase['jobs'],
);
$app['controllers']->value('db', $db);

$app->get('/', function ($db) use ($app) 
{
    $pageData = array_merge(array('page' => 'home'),$db);
    return $app['twig']->render('index.html.twig', $pageData);
})->bind('home');

$app->get('/resources', function ($db) use ($app) 
{
    $pageData = array_merge(array('page' => 'resources'),$db);
    return $app['twig']->render('resources.html.twig', $pageData);
})->bind('resources');

$app->get('/sitemap', function ($db) use ($app) 
{
    $pageData = array_merge(array('page' => 'sitemap'),$db);
    return $app['twig']->render('sitemap.html.twig', $pageData);
})->bind('sitemap');

$app->get('/news', function ($db) use ($app) 
{
    $pageData = array_merge(array('page' => 'news'),$db);
    return $app['twig']->render('news.html.twig', $pageData);
})->bind('news');

$app->get('/jobs', function ($db) use ($app) 
{
    $pageData = array_merge(array('page' => 'jobs'),$db);
    return $app['twig']->render('jobs.html.twig', $pageData);
})->bind('jobs');

$app->get('/supporters', function ($db) use ($app) 
{
    $pageData = array_merge(array('page' => 'supporters'),$db);
    return $app['twig']->render('supporters.html.twig', $pageData);
})->bind('supporters');
$app->get('/links', function ($db) use ($app) 
{
    $pageData = array_merge(array('page' => 'links'),$db);
    return $app['twig']->render('links.html.twig', $pageData);
})->bind('links');

$app->get('/about', function ($db) use ($app) 
{
    $pageData = array_merge(array('page' => 'about'),$db);
    return $app['twig']->render('aboutus.html.twig', $pageData);
})->bind('about');

$app->get('/events', function ($db) use ($app) 
{
    $pageData = array_merge(array('page' => 'events'),$db);
    return $app['twig']->render('events.html.twig', $pageData);
})->bind('events');

//$app->get('/speaker', function ($db) use ($app) 
//{
//    $pageData = array_merge(array('page' => 'speaker'),$db);
//    return $app['twig']->render('speaker.html.twig', $pageData);
//})->bind('speaker');

$app->get('/contact', function ($db) use ($app) 
{
    $pageData = array_merge(array('page' => 'contact'),$db);
    return $app['twig']->render('contact.html.twig', $pageData);
})->bind('contact');

$app->get('/events/symfony-austria.ics', function ($db) use ($app) 
{
    $pageData = array_merge(array('page' => 'contact'),$db);
    
    $vCalendar = new Calendar('symfony-austria.org');
    
    foreach ($db['events'] as $id => $event)
    {
        $starttime = new \DateTime($event['date'].' '.$event['time']);
        $endtime = clone $starttime;
        $endtime->modify("+3 hours");
        $location = $app['twig']->render('ical.location.html.twig', array_merge(
                array(
                    'location' => $db['locations'][$event['location']], 
                    ),$db));
        $summary = $event['title'];

        $description = $app['twig']->render('ical.description.html.twig', array_merge(
                array(
                    'event' => $event, 
                    ),$db));
        $url = 'http://symfony-austria.org/';
        
        $vEvent = new Event();
            $vEvent->setLocation($location);
            $vEvent->setUrl($url);
            $vEvent->setDtStart($starttime);
            $vEvent->setDtEnd($endtime);
            $vEvent->setNoTime(false);
            $vEvent->setDescription($description);
            $vEvent->setSummary($summary);
        $vCalendar->addEvent($vEvent);
    }
    return new Response($vCalendar->render(), 200, array('Content-Type' => 'text/calendar; charset=utf-8', 'Content-Disposition' => 'attachment; filename="symfony-austria.ics"'));
})->bind('ical');

$app->get('/rss/events', function ($db) use ($app) 
{
    $pageData = array_merge(array('page' => 'contact'),$db);
    
    $feed = new Feed();
    $channel = new Channel();
    $channel
        ->title("Symfony Austria Events")
        ->description("Usergroup Symfony Austria - Events")
        ->url('http://symfony-austria.org')
        //->copyright($copyright)
        //->lastBuildDate($lastBuildDate)
        //->pubDate(new \DateTime())
        //->ttl($ttl)
        ->appendTo($feed);
    
    foreach ($db['events'] as $id => $event)
    {
        if (in_array($id, $db['events_actual']))
        {
            $item = new Item();
            $item
                ->title($event['title'])
                ->description($app['twig']->render('rss.event.html.twig', array_merge(array('event' => $event),$db)))
                ->url('http://symfony-austria.at/events#'.$event['date'])
                ->appendTo($channel);
            
        }
    }

    return $feed;
    //return new Response($vCalendar->render(), 200, array('Content-Type' => 'application/rss+xml; charset=utf-8'));
})->bind('rss_events');
