<?php

namespace App\Jobs;

use App\Models\Spin;
use App\Models\SpinGroup;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunSpin implements ShouldQueue
{
    use Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels,
        Batchable;

    private int $columns;
    private int $rows;
    /**
     * @var array|string[]
     */
    private array $lowestElements;
    /**
     * @var array|string[]
     */
    private array $highestElements;
    private float $rtpFreeSpins;
    private mixed $numSpins;
    private mixed $currentBalance;
    private mixed $betAmount;
    private float $rtpGame;
    private string $additionalElement;
    private mixed $firstSpin;
    private mixed $lastSpin;
    private mixed $groupId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        $betAmount,
        $currentBalance,
        $numSpins,
        $firstSpin,
        $lastSpin,
        $groupId
    ) {
        $this->columns = 5;
        $this->rows = 3;
        $this->lowestElements = ["A", "B", "C", "D"];
        $this->highestElements = ["Cat", "Dog", "Pig", "Horse"];
        $this->additionalElement = "Elephant";
        $this->rtpGame = 0.97;
        $this->rtpFreeSpins = 0.95;
        $this->betAmount = $betAmount;
        $this->currentBalance = $currentBalance;
        $this->numSpins = $numSpins;
        $this->firstSpin = $firstSpin;
        $this->lastSpin = $lastSpin;
        $this->groupId = $groupId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->spin();
    }

    public function spin()
    {
        if ($this->batch()->cancelled()) {
            // dd("Batch cancelled");
            // Determine if the batch has been cancelled...
            return;
        }

        $totalWins = 0;
        $totalFreeSpins = 0;
        $totalPayout = 0;
        $initialBalance = $this->currentBalance;

        for ($spin = $this->firstSpin; $spin <= $this->lastSpin; $spin++) {
            if ($this->currentBalance < $this->betAmount) {
                // cancel batch
                $this->batch()->cancel();

                // dump(
                //     "Balance is not enough for the bet",
                //     $this->currentBalance,
                //     $this->betAmount,
                //     $spin,
                //     $this->numSpins
                // );

                // $this->numSpins = $spin;
                break; // Stop if balance is not enough for the bet
            }
            $this->numSpins = $spin;
            $this->currentBalance -= $this->betAmount;
            $board = $this->generateRandomBoard();

            // $board[2] = ["Cat", "Cat", "Elephant", "A", "Cat"];
            // $board[1] = ["Cat", "B", "Cat", "A", "A"];
            // $board[0] = ["Horse", "Cat", "D", "D", "D"];
            // $board[0] = ["Elephant", "Cat", "A", "Elephant", "Elephant"];
            // $board[1] = ["Elephant", "Cat", "A", "Elephant", "Elephant"];

            // Check for wins
            $wins = $this->checkForWins($board);
            $winAmount = 0;

            foreach ($wins as $win) {
                if ($win["element"] == $this->additionalElement) {
                    // If the win contains the additional element, add free spins
                    continue;
                }
                $winMultiplier = $this->getWinMultiplier($win["element"]);
                // dump($win["element"], $board, $winMultiplier);
                $winAmount += $winMultiplier * $this->betAmount;
            }

            // Check for free spins
            $freeSpins = $this->checkForFreeSpins($board);
            $numFreeSpins = count($freeSpins);

            $totalWins += $winAmount;
            $totalFreeSpins += $numFreeSpins;
            $totalPayout += $winAmount;

            $this->currentBalance += $winAmount;

            $data = [
                "bet_amount" => $this->betAmount,
                "board" => $board,
                "win" => $winAmount / $this->betAmount,
                "total_win" => $winAmount,
                "total_free_spins" => $numFreeSpins,
                "total_payout" => $winAmount,
                "adjusted_payout" => round($winAmount * $this->rtpGame, 2),
                "current_balance" => $this->currentBalance,
            ];

            // find the group id
            Spin::create([
                "group_id" => $this->groupId,
                "spin_data" => $data,
            ]);
        }

        // Apply RTP
        $adjustedTotalPayout = $totalPayout * $this->rtpGame;

        $data = [
            "initial_balance" => $initialBalance,
            "bet_amount" => $this->betAmount,
            "total_spins" => $this->numSpins,
            "total_wins" => $totalWins,
            "total_free_spins" => $totalFreeSpins,
            "total_payout" => $totalPayout,
            "adjusted_total_payout" => round($adjustedTotalPayout, 2),
            "final_balance" => $this->currentBalance,
        ];

        SpinGroup::create([
            "group_id" => $this->groupId,
            "spin_data" => $data,
        ]);
    }

    private function generateRandomBoard(): array
    {
        $board = [];
        for ($row = 0; $row < $this->rows; $row++) {
            $board[$row] = [];

            for ($col = 0; $col < $this->columns; $col++) {
                $board[$row][$col] = $this->getRandomElement();
            }
        }

        return $board;
    }

    private function getRandomElement()
    {
        $elements = array_merge($this->lowestElements, $this->highestElements, [
            $this->additionalElement,
        ]);

        // reshuffle elements
        shuffle($elements);

        return $elements[array_rand($elements)];
    }

    private function checkForWins($board): array
    {
        // Check for 3+ identical elements in different columns
        $wins = [];

        foreach ($board as $row) {
            $counts = array_count_values($row);
            foreach ($counts as $element => $count) {
                if ($count >= 3) {
                    $wins[] = ["element" => $element, "count" => $count];
                }
            }
        }
        return $wins;
    }

    private function getWinMultiplier($element): float|int
    {
        if (in_array($element, $this->lowestElements)) {
            return 1.3; // Same multiplier for all lowest elements
        } elseif (in_array($element, $this->highestElements)) {
            // Assign different multipliers to the highest elements if needed
            // For simplicity, let's assume all highest elements have the same multiplier
            return 5; // Example multiplier
        }

        return 0; // No multiplier for additional element
    }

    private function checkForFreeSpins($board): array
    {
        // Check for 3+ Elephants
        $freeSpins = [];

        foreach ($board as $row) {
            $counts = array_count_values($row);
            if (
                isset($counts[$this->additionalElement]) &&
                $counts[$this->additionalElement] >= 3
            ) {
                $freeSpins[] = true;
            }
        }
        return $freeSpins;
    }
}
