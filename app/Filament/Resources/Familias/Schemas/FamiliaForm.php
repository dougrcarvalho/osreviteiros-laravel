<?php

namespace App\Filament\Resources\Familias\Schemas;

use App\Filament\Support\SeletorCategoria;
use App\Enums\TipoItem;
use App\Models\Familia;
use App\Services\TaxonomiaService;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class FamiliaForm
{
    public const EXTENSOES = ['zip', 'rar', '7z', 'rfa', 'rvt', 'rte', 'rft'];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Grid::make(1)->columnSpan(2)->schema([
                    self::identificacao(),
                    self::categorias(),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    self::publicacao(),
                    self::imagem(),
                    self::arquivo(),
                ]),
            ]);
    }

    private static function identificacao(): Section
    {
        return Section::make('Identificação')->schema([
            ToggleButtons::make('tipo')
                ->label('Tipo')
                ->options(collect(TipoItem::cases())->mapWithKeys(fn (TipoItem $t) => [$t->value => $t->singular()])->all())
                ->icons(collect(TipoItem::cases())->mapWithKeys(fn (TipoItem $t) => [$t->value => $t->getIcon()])->all())
                ->default(TipoItem::Familia->value)
                ->inline()
                ->required()
                ->live()
                // Cada tipo tem suas taxonomias: a classificação anterior deixa de valer
                ->afterStateUpdated(fn (Set $set) => $set('termos', [])),
            TextInput::make('nome')
                ->label('Nome')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(function (Get $get, Set $set, ?string $state, string $operation) {
                    if ($operation === 'create' || blank($get('slug'))) {
                        $set('slug', Str::slug((string) $state));
                    }
                }),
            TextInput::make('slug')
                ->label('Endereço (slug)')
                ->prefix(fn (Get $get) => '/'.((($t = $get('tipo')) instanceof TipoItem ? $t : TipoItem::tryFrom((string) $t)) ?? TipoItem::Familia)->segmento().'/')
                ->required()
                ->maxLength(255)
                ->alphaDash()
                ->unique(ignoreRecord: true),
            Textarea::make('descricao')
                ->label('Descrição')
                ->rows(5)
                ->helperText('Também é usada na busca do site e como meta description.'),
        ]);
    }

    /**
     * Campos de classificação gerados das taxonomias do tipo escolhido
     * (cadastradas em Administração › Taxonomias): uma nova taxonomia
     * aparece aqui automaticamente.
     */
    private static function categorias(): Section
    {
        return Section::make('Classificação')
            ->description('Os filtros do site são acumulativos: marque tudo que se aplica. Use "+" para criar um termo novo.')
            ->columns(2)
            ->schema(fn (Get $get) => self::camposClassificacao($get('tipo')));
    }

    /** @return array<int, \Filament\Schemas\Components\Component> */
    public static function camposClassificacao(mixed $tipo): array
    {
        $tipo = $tipo instanceof TipoItem ? $tipo : TipoItem::tryFrom((string) $tipo);

        $campos = [
            Toggle::make('gratuita')
                ->label('Acessar grátis')
                ->helperText('Liberado para download no plano grátis.')
                ->columnSpanFull(),
        ];

        foreach (app(TaxonomiaService::class)->doTipo($tipo) as $taxonomia) {
            $campos[] = $taxonomia->usaFabricante()
                ? SeletorCategoria::fabricante($taxonomia)
                : SeletorCategoria::termos($taxonomia);
        }

        return $campos;
    }

    private static function publicacao(): Section
    {
        return Section::make('Publicação')->schema([
            Toggle::make('ativo')
                ->label('Publicado no site')
                ->default(true),
            Text::make(fn (?Familia $record) => $record
                ? "Cadastrada em {$record->created_at?->format('d/m/Y H:i')} · {$record->downloads_count} downloads · {$record->curtidas} curtidas"
                : 'Novo item')
                ->color('gray'),
        ]);
    }

    private static function imagem(): Section
    {
        return Section::make('Imagem de capa')->schema([
            Radio::make('imagem_origem')
                ->hiddenLabel()
                ->options(['upload' => 'Enviar imagem', 'url' => 'Link externo'])
                ->default('upload')
                ->inline()
                ->live(),
            FileUpload::make('imagem_upload')
                ->hiddenLabel()
                ->image()
                ->disk(config('reviteiros.imagens.disk'))
                ->directory('familias/imagens')
                ->visibility('public')
                ->maxSize(config('reviteiros.imagens.max_mb') * 1024)
                ->visible(fn (Get $get) => $get('imagem_origem') === 'upload'),
            TextInput::make('imagem_url')
                ->label('URL da imagem')
                ->url()
                ->maxLength(255)
                ->visible(fn (Get $get) => $get('imagem_origem') === 'url'),
        ]);
    }

    private static function arquivo(): Section
    {
        $maxMb = config('reviteiros.arquivos.max_mb');

        return Section::make('Arquivo para download')
            ->description('O link nunca é exibido no site: o botão confere o plano do usuário antes de liberar.')
            ->schema([
                Radio::make('arquivo_origem')
                    ->hiddenLabel()
                    ->options(['upload' => 'Enviar arquivo', 'url' => 'Link externo'])
                    ->default('upload')
                    ->inline()
                    ->live(),
                FileUpload::make('arquivo_upload')
                    ->hiddenLabel()
                    ->disk(config('reviteiros.arquivos.disk'))
                    ->directory('familias')
                    ->visibility('private')
                    ->storeFileNamesIn('arquivo_upload_nome')
                    // Nome aleatório (não adivinhável) mantendo a extensão original
                    ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file) => Str::random(40).'.'.strtolower($file->getClientOriginalExtension()))
                    ->rules(['extensions:'.implode(',', self::EXTENSOES)])
                    ->maxSize($maxMb * 1024)
                    ->helperText('Formatos: '.implode(', ', self::EXTENSOES).". Máx. {$maxMb} MB.")
                    ->visible(fn (Get $get) => $get('arquivo_origem') === 'upload'),
                TextInput::make('link_download')
                    ->label('URL do arquivo')
                    ->url()
                    ->maxLength(2048)
                    ->placeholder('https://drive.google.com/...')
                    ->visible(fn (Get $get) => $get('arquivo_origem') === 'url'),
                Text::make(fn (?Familia $record) => $record?->temArquivoLocal()
                    ? "Atual: {$record->arquivo_nome} (".Number::fileSize($record->arquivo_tamanho ?? 0).')'
                    : null)
                    ->color('gray')
                    ->visible(fn (?Familia $record, Get $get) => $record?->temArquivoLocal() && $get('arquivo_origem') === 'upload'),
            ]);
    }
}
