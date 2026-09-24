<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Concerns\CanBeLengthConstrained;
use Filament\Forms\Components\Field;

/**
 * A searchable Font Awesome icon picker. State is a plain "fa-solid fa-{name}" class string —
 * the same format used everywhere the icon is rendered — so no schema change is needed anywhere
 * this field replaces a plain TextInput. The icon list is fetched client-side from
 * public/data/fontawesome-solid-icons.json (generated from the exact Font Awesome Free version
 * loaded on the public site) the first time the picker is opened.
 */
class IconPicker extends Field
{
    use CanBeLengthConstrained;

    protected string $view = 'filament.forms.components.icon-picker';

    protected string $style = 'solid';

    public function style(string $style): static
    {
        $this->style = $style;

        return $this;
    }

    public function getStyle(): string
    {
        return $this->style;
    }
}
