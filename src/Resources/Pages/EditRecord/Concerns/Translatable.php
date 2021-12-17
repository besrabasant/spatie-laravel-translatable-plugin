<?php

namespace Filament\Resources\Pages\EditRecord\Concerns;

use Filament\Resources\Pages\Concerns\HasActiveFormLocaleSelect;
use Illuminate\Database\Eloquent\Model;

trait Translatable
{
    use HasActiveFormLocaleSelect;

    public $activeFormLocale = null;

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');

        if ($this->activeFormLocale === null) {
            $this->setActiveFormLocale();
        }

        $data = $this->record->toArray();

        foreach (static::getResource()::getTranslatableAttributes() as $attribute) {
            $data[$attribute] = $this->record->getTranslation($attribute, $this->activeFormLocale, false);
        }

        $this->form->fill($data);

        $this->callHook('afterFill');
    }

    protected function setActiveFormLocale(): void
    {
        $resource = static::getResource();

        $availableLocales = array_keys($this->record->getTranslations($resource::getTranslatableAttributes()[0]));
        $resourceLocales = $resource::getTranslatableLocales();
        $defaultLocale = $resource::getDefaultTranslatableLocale();

        if(in_array($defaultLocale, $availableLocales)) {
            $this->activeFormLocale = $defaultLocale;
        } elseif (in_array('0', $intersectedResourceLocales = array_intersect($availableLocales, $resourceLocales))) {
            $this->activeFormLocale = $intersectedResourceLocales[0];
        } else {
            $this->activeFormLocale = app()->getLocale();
        }

        $this->record->setLocale($this->activeFormLocale);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->setLocale($this->activeFormLocale)->fill($data)->save();

        return $record;
    }

    public function updatedActiveFormLocale(): void
    {
        $this->fillForm();
    }

    public function updatingActiveFormLocale(): void
    {
        $this->save(shouldRedirect: false);
    }

    protected function getActions(): array
    {
        return array_merge(
            [$this->getActiveFormLocaleSelectAction()],
            parent::getActions(),
        );
    }
}
