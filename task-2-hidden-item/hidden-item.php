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
// Movement directions: North moves row up (-1), East moves column right (+1),
// South moves row down (+1). West is not used in this task.
// ---------------------------------------------------------------------------
const DIRECTIONS = [
    'N' => [-1,  0],
    'E' => [ 0,  1],
    'S' => [ 1,  0],
];

/**
 * Finds the player's starting position (X) in the grid.
 *
 * @param  array<string> $grid
 * @return array{int, int} [$row, $column]
 *
 * @throws RuntimeException if X is not found
 */
function findStartPosition(array $grid): array
{
    foreach ($grid as $rowIndex => $row) {
        $colIndex = strpos($row, 'X');

        if ($colIndex !== false) {
            return [$rowIndex, $colIndex];
        }
    }

    throw new RuntimeException('Starting position (X) not found in grid.');
}

/**
 * Moves a given number of steps in one direction from a starting cell.
 * Returns null if the path is blocked by an obstacle or goes out of bounds.
 *
 * @param  array<string> $grid
 * @param  string        $direction  One of 'N', 'E', 'S'
 * @return array{int, int}|null [$row, $column] or null if blocked
 */
function move(array $grid, int $row, int $col, string $direction, int $steps): ?array
{
    [$rowDelta, $colDelta] = DIRECTIONS[$direction];

    for ($i = 0; $i < $steps; $i++) {
        $row += $rowDelta;
        $col += $colDelta;

        $isOutOfBounds = !isset($grid[$row][$col]);
        $isObstacle    = isset($grid[$row][$col]) && $grid[$row][$col] === '#';

        if ($isOutOfBounds || $isObstacle) {
            return null;
        }
    }

    return [$row, $col];
}

/**
 * Finds all unique grid cells reachable by navigating North A steps,
 * then East B steps, then South C steps — for all valid combinations of A, B, C.
 *
 * Only cells containing '.' are considered valid item locations.
 *
 * @param  array<string> $grid
 * @return array<array{int, int}>
 */
function findPossibleLocations(array $grid): array
{
    [$startRow, $startCol] = findStartPosition($grid);

    $maxRows = count($grid);
    $maxCols = strlen($grid[0]);

    // Use a keyed array to avoid duplicate coordinates
    $found = [];

    for ($a = 1; $a <= $maxRows; $a++) {
        $afterNorth = move($grid, $startRow, $startCol, 'N', $a);
        if ($afterNorth === null) continue;

        for ($b = 1; $b <= $maxCols; $b++) {
            $afterEast = move($grid, $afterNorth[0], $afterNorth[1], 'E', $b);
            if ($afterEast === null) continue;

            for ($c = 1; $c <= $maxRows; $c++) {
                $afterSouth = move($grid, $afterEast[0], $afterEast[1], 'S', $c);
                if ($afterSouth === null) continue;

                [$row, $col] = $afterSouth;

                if ($grid[$row][$col] === '.') {
                    $found["{$row},{$col}"] = [$row, $col];
                }
            }
        }
    }

    return array_values($found);
}

/**
 * Prints the grid with probable item locations marked as '$'.
 *
 * @param array<string>       $grid
 * @param array<array{int,int}> $locations
 */
function displayGridWithLocations(array $grid, array $locations): void
{
    foreach ($locations as [$row, $col]) {
        // Only mark clear path cells, leave everything else as-is
        if ($grid[$row][$col] === '.') {
            $grid[$row][$col] = '$';
        }
    }

    foreach ($grid as $row) {
        echo $row . PHP_EOL;
    }
}

/**
 * Prints the list of probable coordinates in a readable format.
 *
 * @param array<array{int,int}> $locations
 */
function displayLocationList(array $locations): void
{
    if (empty($locations)) {
        echo 'No reachable locations found.' . PHP_EOL;
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