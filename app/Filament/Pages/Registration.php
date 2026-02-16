<?php

namespace App\Filament\Pages;

use App\Enums\Branch;
use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Database\Eloquent\Model;

class Registration extends BaseRegister
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                Select::make('branch')
                    ->options(collect(Branch::cases())->mapWithKeys(fn ($b) => [$b->value => $b->label()]))
                    ->required(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    protected function handleRegistration(array $data): Model
    {
        $data['role'] = UserRole::EMPLOYEE->value;
        return $this->getUserModel()::create($data);
    }
}
