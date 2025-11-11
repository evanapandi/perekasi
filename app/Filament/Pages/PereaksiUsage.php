<?php

namespace App\Filament\Pages;

use App\Models\Pereaksi;
use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Grid;
use Filament\Notifications\Notification;
use App\Filament\Resources\UsageHistoryResource;
use App\Models\Summary; 
use App\Models\UsageHistory;
// use App\Events\StockUpdated; 
use Illuminate\Support\Facades\Log;

class PereaksiUsage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static string $view = 'filament.pages.pereaksi-usage';
    protected static ?string $navigationLabel = 'Reagent Usage';
    protected static ?string $title = 'Reagent Usage';
    protected static ?string $navigationGroup = 'Form';

    public $jenis_reagent = null;
    public $nama_reagent =null;
    public $kode_reagent = null;
    public $jumlah;
    public $nama;
    public $stock = null;
    public $status = null;
    public $satuan = null;

    public function mount()
    {
        $this->nama = Auth::user()->name;  // Ambil nama pengguna yang sedang login
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('nama')
                ->label('Nama Analis')
                ->disabled()
                ->default($this->nama),
            Grid::make(['default' => 2]) // Create a grid with 2 columns
                ->schema([
                    Select::make('nama_reagent')
                        ->label('Nama Reagent')
                        ->options(Pereaksi::all()->pluck('nama_reagent', 'nama_reagent')->toArray())
                        ->reactive()
                        ->searchable()
                        ->preload()
                        ->required()
                        ->afterStateUpdated(fn($state, callable $set) => $this->setJenisType($state, $set))
                        ->placeholder('Pilih nama reagent'),
                    TextInput::make('jenis_reagent')
                        ->label('Jenis Reagent')
                        ->disabled()
                        ->default($this->jenis_reagent),
                    TextInput::make('stock')
                        ->suffix(fn() => $this->satuan ?? 'satuan')
                        ->label('Jumlah Stock')
                        ->disabled()
                        ->default($this->stock),
                    TextInput::make('status')
                        ->label('Status')
                        ->disabled()
                        ->default($this->status)
                        ->extraAttributes(function () {
                            $color = match ($this->status) {
                                'In Stock' => 'text-green-600',
                                'Under Stock' => 'text-yellow-600',
                                'Out of Stock' => 'text-red-600',
                                default => 'text-gray-500',
                            };
                            Log::info('Status color applied:', ['status' => $this->status, 'color' => $color]); // Debugging
                            return ['class' => "font-bold $color"];
                        }),
                ]),
            TextInput::make('jumlah')
                ->label('Jumlah Penggunaan')
                ->suffix(fn() => $this->satuan ?? 'satuan')
                ->numeric()
                ->required()
                ->rule(['numeric', 'min:0.001'])
                ->minValue(0.001),
        ];
    }

    protected function setJenisType($state, callable $set)
    {
        $pereaksi = Pereaksi::where('nama_reagent', $state)->first();
        if ($pereaksi) {
            $this->jenis_reagent = $pereaksi->jenis_reagent;
            $this->nama_reagent = $pereaksi->nama_reagent;
            $this->kode_reagent = $pereaksi->kode_reagent;
            $this->stock = $pereaksi->Stock; // Set stock
            $this->status = $pereaksi->status; // Set status dari accessor
            $this->satuan = $pereaksi->satuan;
            $set('jenis_reagent', $pereaksi->jenis_reagent);
            $set('stock', $pereaksi->Stock); // Update field stock
            $set('status', $pereaksi->status); // Update field status
        }
    }

    public function submit()
    {
        
        $data = $this->form->getState();  // Mengambil data dari form

        // Logging untuk debugging
        Log::info('Form Data:', $data);

        // Cari pereaksi berdasarkan kode_reagent
        $pereaksi = Pereaksi::where('nama_reagent', $data['nama_reagent'])->first();

        // Logging untuk memeriksa hasil pencarian
        if ($pereaksi) {
            Log::info('Pereaksi ditemukan:', [
                'kode_reagent' => $pereaksi->kode_reagent,
                'nama_reagent' => $pereaksi->nama_reagent,
                'stock' => $pereaksi->Stock,
                'jumlah_diminta' => $data['jumlah'],
                'satuan' => $pereaksi->satuan,
            ]);
        } else {
            Log::warning('Pereaksi tidak ditemukan untuk kode_reagent: ' . $data['nama_reagent']);
        }
        
        if ($pereaksi && $pereaksi->Stock >= $data['jumlah']) {
            
            // Simpan ke UsageHistory
            UsageHistory::create([
                'nama_analis' => $this->nama,
                'kode_reagent' => $pereaksi->kode_reagent, // Gunakan kode_reagent dari pereaksi
                'nama_reagent' => $this->nama_reagent,
                'jenis_reagent' => $this->jenis_reagent,
                'jumlah_penggunaan' => $data['jumlah'],
                'satuan' => $pereaksi->satuan,
            ]);

            // Update Summary secara langsung
            Summary::updateSummary();
    
            Notification::make()
                ->title('Reagent successfully recorded')
                ->icon('heroicon-o-document-check')
                ->iconColor('success')
                ->send();  // Menampilkan pesan sukses
                return redirect(UsageHistoryResource::getUrl('index'));
        } else {
            Notification::make()
                ->title('Insufficient reagent stock')
                ->icon('heroicon-o-archive-box-x-mark')
                ->iconColor('error')
                ->send();  // Menampilkan pesan error jika stock tidak cukup
        }
    }

}
