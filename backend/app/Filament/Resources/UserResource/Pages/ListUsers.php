<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('generateScanners')
                ->label('Generate scanner accounts')
                ->icon('heroicon-o-qr-code')
                ->form([
                    Forms\Components\TextInput::make('count')
                        ->label('How many accounts')
                        ->numeric()
                        ->required()
                        ->minValue(1)
                        ->maxValue(100)
                        ->default(10),
                ])
                ->action(function (array $data): void {
                    $numbers = $this->nextScannerNumbers((int) $data['count']);
                    $lines = [];

                    foreach ($numbers as $n) {
                        $email = sprintf('scanner%02d@tbilisistyle.ge', $n);
                        $password = Str::random(8);

                        $user = new User([
                            'name' => "Scanner {$n}",
                            'email' => $email,
                            'password' => Hash::make($password),
                        ]);
                        $user->role = 'scanner';
                        $user->save();

                        $lines[] = "{$email} / {$password}";
                    }

                    Notification::make()
                        ->title('Scanner accounts created — copy these now, passwords are not shown again')
                        ->body(implode("\n", $lines))
                        ->success()
                        ->persistent()
                        ->send();
                }),
            Actions\CreateAction::make(),
        ];
    }

    private function nextScannerNumbers(int $count): array
    {
        $existing = User::where('email', 'like', 'scanner%@tbilisistyle.ge')
            ->pluck('email')
            ->map(fn (string $email): int => (int) preg_replace('/\D/', '', explode('@', $email)[0]))
            ->filter()
            ->values();

        $start = $existing->isEmpty() ? 1 : $existing->max() + 1;

        return range($start, $start + $count - 1);
    }
}
