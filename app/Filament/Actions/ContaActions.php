<?php

namespace App\Filament\Actions;

use App\Enums\OrigemPlano;
use App\Models\Plano;
use App\Models\User;
use App\Services\AssinaturaService;
use App\Services\ContaService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;

/**
 * Ações de atendimento sobre contas, usadas na tabela e na página do
 * usuário. Toda alteração passa pelos Services (histórico + auditoria).
 */
class ContaActions
{
    public static function alterarPlano(): Action
    {
        return Action::make('alterarPlano')
            ->label('Alterar plano')
            ->icon('heroicon-m-arrows-right-left')
            ->color('warning')
            ->modalHeading(fn (User $record) => "Alterar plano de {$record->name}")
            ->fillForm(fn (User $record) => ['plano_id' => $record->plano_id])
            ->schema(self::camposPlano())
            ->action(function (User $record, array $data) {
                $historico = app(AssinaturaService::class)->alterarPlano(
                    $record,
                    Plano::find($data['plano_id']),
                    OrigemPlano::Manual,
                    $data['observacao'] ?? null,
                );

                Notification::make()
                    ->title($historico ? 'Plano alterado' : 'O usuário já está neste plano')
                    ->status($historico ? 'success' : 'info')
                    ->send();
            });
    }

    public static function alterarPlanoEmMassa(): BulkAction
    {
        return BulkAction::make('alterarPlanoEmMassa')
            ->label('Alterar plano')
            ->icon('heroicon-m-arrows-right-left')
            ->color('warning')
            ->schema(self::camposPlano())
            ->requiresConfirmation()
            ->action(function (Collection $records, array $data) {
                $plano = Plano::find($data['plano_id']);
                $total = $records->filter(fn (User $u) => app(AssinaturaService::class)
                    ->alterarPlano($u, $plano, OrigemPlano::Manual, $data['observacao'] ?? null))->count();

                Notification::make()->title("Plano alterado em {$total} conta(s)")->success()->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    public static function bloquear(): Action
    {
        return Action::make('bloquear')
            ->label('Bloquear conta')
            ->icon('heroicon-m-no-symbol')
            ->color('danger')
            ->visible(fn (User $record) => ! $record->isBloqueado() && $record->isNot(auth()->user()))
            ->modalDescription('O usuário é desconectado e não consegue mais entrar até a conta ser reativada.')
            ->schema([
                Textarea::make('motivo')->label('Motivo')->required()->maxLength(255),
            ])
            ->action(function (User $record, array $data) {
                app(ContaService::class)->bloquear($record, $data['motivo']);
                Notification::make()->title('Conta bloqueada')->success()->send();
            });
    }

    public static function desbloquear(): Action
    {
        return Action::make('desbloquear')
            ->label('Reativar conta')
            ->icon('heroicon-m-check-circle')
            ->color('success')
            ->visible(fn (User $record) => $record->isBloqueado())
            ->requiresConfirmation()
            ->action(function (User $record) {
                app(ContaService::class)->desbloquear($record);
                Notification::make()->title('Conta reativada')->success()->send();
            });
    }

    public static function definirSenha(): Action
    {
        return Action::make('definirSenha')
            ->label('Definir nova senha')
            ->icon('heroicon-m-key')
            ->color('gray')
            ->modalDescription('O usuário é desconectado de todos os dispositivos. Prefira enviar o link de redefinição quando possível.')
            ->schema([
                TextInput::make('senha')
                    ->label('Nova senha')
                    ->password()
                    ->revealable()
                    ->required()
                    ->rule(PasswordRule::defaults())
                    ->confirmed(),
                TextInput::make('senha_confirmation')->label('Confirmar senha')->password()->revealable()->required(),
            ])
            ->action(function (User $record, array $data) {
                app(ContaService::class)->definirSenha($record, $data['senha']);
                Notification::make()->title('Senha alterada')->success()->send();
            });
    }

    public static function enviarLinkSenha(): Action
    {
        return Action::make('enviarLinkSenha')
            ->label('Enviar link de senha')
            ->icon('heroicon-m-envelope')
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription(fn (User $record) => "Um e-mail com link para criar nova senha será enviado para {$record->email}.")
            ->action(function (User $record) {
                $status = app(ContaService::class)->enviarLinkSenha($record);

                $status === Password::RESET_LINK_SENT
                    ? Notification::make()->title('Link enviado')->success()->send()
                    : Notification::make()->title('Não foi possível enviar')->body(__($status))->danger()->send();
            });
    }

    /** @return array<int, \Filament\Forms\Components\Field> */
    private static function camposPlano(): array
    {
        return [
            Select::make('plano_id')
                ->label('Novo plano')
                ->options(fn () => Plano::ordenados()->pluck('nome', 'id')->all())
                ->required(),
            Textarea::make('observacao')
                ->label('Observação (fica no histórico)')
                ->placeholder('Ex.: cortesia, pagamento confirmado manualmente, pedido de cancelamento…')
                ->maxLength(1000),
        ];
    }
}
