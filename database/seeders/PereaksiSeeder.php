<?php
namespace Database\Seeders;
use App\Models\Pereaksi;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PereaksiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('data/pereaksi.json');
        
        if (!file_exists($jsonPath)) {
            $this->command->error('File pereaksi.json tidak ditemukan di folder database/data');
            return;
        }

        $json = file_get_contents($jsonPath);
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error('Error parsing JSON: ' . json_last_error_msg());
            return;
        }

        $this->command->info('Memulai import data pereaksi...');
        $bar = $this->command->getOutput()->createProgressBar(count($data));
        $bar->start();

        $successCount = 0;
        $updateCount = 0;

        foreach ($data as $item) {
            try {
                // Parse created_at
                $createdAt = null;
                if (!empty($item['created_at'])) {
                    try {
                        $createdAt = Carbon::parse($item['created_at']);
                    } catch (\Exception $e) {
                        $createdAt = null;
                    }
                }

                // Parse updated_at
                $updatedAt = null;
                if (!empty($item['updated_at'])) {
                    try {
                        $updatedAt = Carbon::parse($item['updated_at']);
                    } catch (\Exception $e) {
                        $updatedAt = null;
                    }
                }

                // Parse deleted_at
                $deletedAt = null;
                if (!empty($item['deleted_at'])) {
                    try {
                        $deletedAt = Carbon::parse($item['deleted_at']);
                    } catch (\Exception $e) {
                        $deletedAt = null;
                    }
                }

                // Gunakan updateOrCreate untuk menghindari duplikasi
                $pereaksi = Pereaksi::updateOrCreate(
                    ['kode_reagent' => $item['kode_reagent']],
                    [
                        'nama_reagent' => $item['nama_reagent'],
                        'jenis_reagent' => $item['jenis_reagent'],
                        'Stock' => $item['Stock'] ?? 0,
                        'satuan' => $item['satuan'] ?? null,
                        'min_stock' => $item['min_stock'] ?? null,
                        'created_at' => $createdAt,
                        'updated_at' => $updatedAt ?? now(),
                        'deleted_at' => $deletedAt,
                    ]
                );

                if ($pereaksi->wasRecentlyCreated) {
                    $successCount++;
                } else {
                    $updateCount++;
                }

            } catch (\Exception $e) {
                $this->command->warn("\nError importing kode_reagent {$item['kode_reagent']}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->command->newLine();
        $this->command->info("Import selesai! {$successCount} data baru ditambahkan, {$updateCount} data diperbarui.");
    }
}

