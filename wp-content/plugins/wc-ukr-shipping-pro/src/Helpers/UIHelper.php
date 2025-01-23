<?php

declare(strict_types=1);

namespace kirillbdev\WCUkrShipping\Helpers;

class UIHelper
{
    public function textField(string $name, string $label, ?string $value, ?string $tooltip = null): string
    {
        $id = $this->getIdFromName($name);
        $html = '<div class="wcus-form-group">';
        $html .= sprintf('<label for="%s">%s</label>', $id, $label);
        $html .= sprintf(
            '<input type="text" id="%s" name="%s" class="wcus-form-control" value="%s">',
            $id,
            $name,
            $value
        );

        if ($tooltip !== null) {
            $html .= sprintf('<div class="wcus-form-group__tooltip">%s</div>', $tooltip);
        }

        $html .= '</div>';

        return $html;
    }

    public function switcherField(string $name, string $label, bool $checked): string
    {
        $html = '<div class="wcus-form-group wcus-form-group--horizontal">';
        $html .= '<label class="wcus-switcher">';
        $html .= sprintf('<input type="hidden" name="%s" value="0">', $name);
        $html .= sprintf(
            '<input type="checkbox" name="%s" value="1" %s>',
            $name,
            $checked ? 'checked' : ''
        );
        $html .= '<span class="wcus-switcher__control"></span>';
        $html .= '</label>';
        $html .= sprintf('<div class="wcus-control-label">%s</div>', $label);
        $html .= '</div>';

        return $html;
    }

    private function getIdFromName(string $name): string
    {
        return trim(str_replace(['[', ']'], '_', $name), '_');
    }
}
