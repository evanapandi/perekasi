<?php

namespace App\Filament\Pages;

use App\Models\Pereaksi;
use App\Models\Summary; 
use Filament\Pages\Page;
use App\Models\RestockHistory;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
// use App\Events\StockUpdated; 
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use App\Filament\Resources\RestockHistoryResource;

class PereaksiRestock extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static string $view = 'filament.pages.pereaksi-usage';
    protected static ?string $navigationLabel = 'Reagent Restock';
    protected static ?string $title = 'Reagent Restock';
    protected static ?string $navigationGroup = 'Form';

    public $jenis_reagent = null;
    public $nama_reagent =null;
    public $kode_reagent = null;
    public $jumlah_restock;
    public $nama;
    public $lot_numbers;
    public $receipt_date;
    public $location;
    public $expired_date;

    // public $certificate_of_analysis =null;
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
                    TextInput::make('jumlah_restock')
                        ->label('Jumlah Restock')
                        ->suffix(fn() => $this->satuan ?? 'satuan')
                        ->numeric()
                        ->required()
                        ->minValue(1),
                    TextInput::make('lot_numbers')
                        ->label('Lot Numbers')
                        ->required(),
                    DatePicker::make('receipt_date')
                        ->label('Receipt Date')
                        ->required(),

                    DatePicker::make('expired_date')
                        ->label('Expired Date')
                        ->required(),
                    // TextInput::make('certificate_of_analysis')
                    //     ->label('Certificate of Analysis')
                    //     ->required(),
                    TextInput::make('location')
                        ->label('Location')
                        ->required(),
                ]),

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
                'jumlah_diminta' => $data['jumlah_restock'],
                'satuan' => $pereaksi->satuan,
            ]);
        } else {
            Log::warning('Pereaksi tidak ditemukan untuk kode_reagent: ' . $data['nama_reagent']);
        }
        
        if ($pereaksi) {
            
            RestockHistory::create([
                'kode_reagent' => $pereaksi->kode_reagent, 
                'nama_reagent' => $pereaksi->nama_reagent,
                'jenis_reagent' => $pereaksi->jenis_reagent,
                'jumlah_restock' => $data['jumlah_restock'],
                'satuan' => $pereaksi->satuan,
                'lot_numbers' => $data['lot_numbers'],
                'receipt_date' => $data['receipt_date'],
                'expired_date' => $data['expired_date'],
                // 'certificate_of_analysis' => $data['certificate_of_analysis'],
                'location' => $data['location'],
                'nama_analys'=>$this->nama,
            ]);

            Notification::make()
                ->title('Saved successfully')
                ->icon('heroicon-o-check-circle')
                ->iconColor('success')
                ->send();  // Menampilkan pesan sukses

            // Redirect ke halaman Restock History
            return redirect(RestockHistoryResource::getUrl('index'));
        } else {
            Notification::make()
                ->title('Reagent not found')
                ->icon('heroicon-o-x-circle')
                ->iconColor('error')
                ->send();  // Menampilkan pesan error
            
        }
    }

}
