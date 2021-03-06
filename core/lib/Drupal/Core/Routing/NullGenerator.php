<?php

/**
 * @file
 * Contains Drupal\Core\Routing\NullGenerator.
 */

namespace Drupal\Core\Routing;

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * No-op implementation of a Url Generator, needed for backward compatibility.
 */
class NullGenerator extends UrlGenerator {

  /**
   * Override the parent constructor.
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   *
   * generate(), generateFromRoute(), and getPathFromRoute() all call this
   * protected method.
   */
  protected function getRoute($name) {
    throw new RouteNotFoundException();
  }

  /**
   * Overrides Drupal\Core\Routing\UrlGenerator::setContext();
   */
  public function setContext(RequestContext $context) {
  }

  /**
   * Implements Symfony\Component\Routing\RequestContextAwareInterface::getContext();
   */
  public function getContext() {
  }

  /**
   * Overrides Drupal\Core\Routing\UrlGenerator::processPath().
   */
  protected function processPath($path, &$options = array()) {
    return $path;
  }
}
