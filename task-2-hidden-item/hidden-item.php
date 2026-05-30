<?php


$grid = [
    '########',
    '#......#',
    '#.###..#',
    '#...#.##',
    '#X#....#',
    '########',
];

// ---------------------------------------------------------------------------
// Representasi arah gerak di grid 2D.
// North = naik (row berkurang), East = kanan (col bertambah), South = turun.
// ---------------------------------------------------------------------------
const DIRECTIONS = [
    'N' => [-1,  0],
    'E' => [ 0,  1],
    'S' => [ 1,  0],
];

/**
 * Cari posisi awal pemain (X) di dalam grid.
 *
 * @param  array<string> $grid
 * @return array{int, int} [$row, $col]
 *
 * @throws RuntimeException kalau X tidak ditemukan
 */
function findStartPosition(array $grid): array
{
    foreach ($grid as $rowIndex => $row) {
        $colIndex = strpos($row, 'X');

        if ($colIndex !== false) {
            return [$rowIndex, $colIndex];
        }
    }

    throw new RuntimeException('Posisi awal (X) tidak ditemukan di grid.');
}

/**
 * Cek apakah sebuah sel bisa diinjak atau tidak.
 * Sel dianggap valid kalau masih di dalam grid dan bukan tembok.
 *
 * @param array<string> $grid
 */
function canStepOn(array $grid, int $row, int $col): bool
{
    $isInsideGrid = isset($grid[$row][$col]);
    $isNotWall    = $isInsideGrid && $grid[$row][$col] !== '#';

    return $isInsideGrid && $isNotWall;
}

/**
 * Dari satu titik, jalan ke satu arah selangkah demi selangkah —
 * persis seperti manusia yang melangkah dan berhenti kalau udah mentok tembok.
 *
 * Kumpulkan semua titik yang berhasil diinjak sebelum mentok.
 *
 * @param  array<string>        $grid
 * @param  string               $direction  'N', 'E', atau 'S'
 * @return array<array{int,int}>            semua titik yang bisa dicapai
 */
function walkUntilBlocked(array $grid, int $row, int $col, string $direction): array
{
    [$rowDelta, $colDelta] = DIRECTIONS[$direction];

    $reachable = [];

    // Terus melangkah sampai ketemu tembok atau ujung grid
    while (true) {
        $row += $rowDelta;
        $col += $colDelta;

        // Kalau udah mentok, berhenti — ga perlu coba lebih jauh
        if (!canStepOn($grid, $row, $col)) {
            break;
        }

        $reachable[] = [$row, $col];
    }

    return $reachable;
}

/**
 * Cari semua kemungkinan lokasi item dengan cara menjelajahi grid
 * seperti manusia: dari X jalan ke North dulu, terus belok East,
 * terus turun ke South. Setiap titik akhir yang berupa jalan (.)
 * dicatat sebagai kandidat lokasi item.
 *
 * Pakai array berkey supaya koordinat yang sama ga dicatat dua kali.
 *
 * @param  array<string>        $grid
 * @return array<array{int,int}>
 */
function findPossibleLocations(array $grid): array
{
    [$startRow, $startCol] = findStartPosition($grid);

    $found = [];

    // Langkah 1: dari posisi X, lihat ke North — bisa injak titik mana aja?
    $northPoints = walkUntilBlocked($grid, $startRow, $startCol, 'N');

    foreach ($northPoints as [$northRow, $northCol]) {

        // Langkah 2: dari setiap titik North, belok ke East — bisa sampai mana?
        $eastPoints = walkUntilBlocked($grid, $northRow, $northCol, 'E');

        foreach ($eastPoints as [$eastRow, $eastCol]) {

            // Langkah 3: dari setiap titik East, turun ke South —
            // kalau titik akhirnya jalan (.), catat sebagai kandidat lokasi item
            $southPoints = walkUntilBlocked($grid, $eastRow, $eastCol, 'S');

            foreach ($southPoints as [$southRow, $southCol]) {
                if ($grid[$southRow][$southCol] === '.') {
                    $found["{$southRow},{$southCol}"] = [$southRow, $southCol];
                }
            }
        }
    }

    return array_values($found);
}

/**
 * Tampilkan grid dengan menandai kemungkinan lokasi item pakai simbol '$'.
 * Grid asli tidak diubah — kita kerja di salinannya.
 *
 * @param array<string>         $grid
 * @param array<array{int,int}> $locations
 */
function displayGridWithLocations(array $grid, array $locations): void
{
    foreach ($locations as [$row, $col]) {
        if ($grid[$row][$col] === '.') {
            $grid[$row][$col] = '$';
        }
    }

    foreach ($grid as $row) {
        echo $row . PHP_EOL;
    }
}

/**
 * Tampilkan daftar koordinat kandidat lokasi item dalam format yang mudah dibaca.
 *
 * @param array<array{int,int}> $locations
 */
function displayLocationList(array $locations): void
{
    if (empty($locations)) {
        echo 'Tidak ada lokasi yang bisa dicapai.' . PHP_EOL;
        return;
    }

    foreach ($locations as [$row, $col]) {
        echo "  Row {$row}, Column {$col}" . PHP_EOL;
    }
}

// ---------------------------------------------------------------------------
// Main
// ---------------------------------------------------------------------------

$locations = findPossibleLocations($grid);

echo 'Possible item locations:' . PHP_EOL;
displayLocationList($locations);

echo PHP_EOL . 'Grid with probable locations marked ($):' . PHP_EOL;
displayGridWithLocations($grid, $locations);