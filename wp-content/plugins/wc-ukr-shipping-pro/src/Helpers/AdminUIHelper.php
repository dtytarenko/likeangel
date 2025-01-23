<?php

namespace kirillbdev\WCUkrShipping\Helpers;

if ( ! defined('ABSPATH')) {
  exit;
}

class AdminUIHelper
{
  public static function selectField($name, $options)
  {
    $id = str_replace([ '[', ']' ], [ '_', '' ], $name);

    $html = '<div class="wcus-form-group">';
    $html .= sprintf(
      '<label for="%s">%s</label>',
      $id,
      $options['label']
    );

    $html .= sprintf(
      '<select id="%s" name="%s" class="wcus-form-control">',
      $id,
      $name
    );

    foreach ($options['options'] as $value => $name) {
      $html .= sprintf(
        '<option value="%s"%s>%s</option>',
        esc_attr($value),
        isset($options['value']) && $value === $options['value'] ? ' selected' : '',
        esc_attr($name)
      );
    }

    $html .= '</select>';

    if (isset($options['tooltip'])) {
      $html .= '<div class="wcus-form-group__tooltip">' . esc_attr($options['tooltip']) . '</div>';
    }

    $html .= '</div>';

    return $html;
  }
}