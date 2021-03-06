<?php
namespace Ens\JobeetBundle\Tests\Controller;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
 
class CategoryControllerTest extends WebTestCase
{
  public function testShow()
  {
    $client = static::createClient();
 
    $crawler = $client->request('GET', '/category/programming');
    $this->assertEquals('Ens\JobeetBundle\Controller\CategoryController::showAction', $client->getRequest()->attributes->get('_controller'));
    $this->assertTrue(200 === $client->getResponse()->getStatusCode());
    
     // get the custom parameters from app config.yml
    $kernel = static::createKernel();
    $kernel->boot();
    $max_jobs_on_homepage = $kernel->getContainer()->getParameter('max_jobs_on_homepage');
    $max_jobs_on_category = $kernel->getContainer()->getParameter('max_jobs_on_category');
 
    $client = static::createClient();
 
    // categories on homepage are clickable
    $crawler = $client->request('GET', '/');
    $link = $crawler->selectLink('Programming')->link();
    $crawler = $client->click($link);
    $this->assertEquals('Ens\JobeetBundle\Controller\CategoryController::showAction', $client->getRequest()->attributes->get('_controller'));
    $this->assertEquals('programming', $client->getRequest()->attributes->get('slug'));
 
    // categories with more than $max_jobs_on_category jobs also have a "more" link
    $crawler = $client->request('GET', '/');
    $link = $crawler->selectLink('Programming')->link();
    $crawler = $client->click($link);
    $link = $crawler->selectLink('2')->link();
    $crawler = $client->click($link);
    $this->assertEquals('Ens\JobeetBundle\Controller\CategoryController::showAction', $client->getRequest()->attributes->get('_controller'));
    $this->assertEquals('programming', $client->getRequest()->attributes->get('slug'));
 
    // only $max_jobs_on_category jobs are listed
    $crawler = $client->request('GET', '/');
    $link = $crawler->selectLink('Programming')->link();
    $crawler = $client->click($link);
    $this->assertTrue($crawler->filter('.jobs tr')->count() == $max_jobs_on_category);
    $this->assertRegExp('/31 jobs/', $crawler->filter('.pagination_desc')->text());
    $this->assertRegExp('/page 1\/2/', $crawler->filter('.pagination_desc')->text());
 
    $link = $crawler->selectLink('2')->link();
    $crawler = $client->click($link);
    $this->assertEquals(2, $client->getRequest()->attributes->get('page'));
    $this->assertRegExp('/page 2\/2/', $crawler->filter('.pagination_desc')->text());
   
   
  }
}