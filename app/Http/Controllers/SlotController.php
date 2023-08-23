<?php

namespace App\Http\Controllers;

use App\Jobs\RunSpin;
use App\Models\Spin;
use App\Models\SpinGroup;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Bus\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Support\Str;
use Throwable;

class SlotController extends Controller
{
    public function spin()
    {
        // Artisan::call("migrate:refresh");
        Spin::truncate();
        SpinGroup::truncate();

        $validator = ValidatorFacade::make(
            request()->all(),
            [
                "bet_amount" =>
                    "required|numeric|min:1|max:" . request("current_balance"),
                "current_balance" => "required|numeric|min:1",
                "num_spins" => "required|numeric|min:1|max:1000000",
            ],
            [
                "bet_amount.max" =>
                    "Bet amount must be less than or equal to current balance.",
                "num_spins.min" => "Number of spins must be greater than 0.",
                "num_spins.max" => "Number of spins must be less than 1000000.",
                "num_spins.required" => "Number of spins is required.",
            ]
        );

        if ($validator->fails()) {
            $errors = [];
            foreach ($validator->errors()->toArray() as $key => $value) {
                $errors[$key] = $value[0];
            }

            throw new HttpResponseException(
                response()->json(
                    [
                        "message" => "The given data was invalid.",
                        "errors" => $errors,
                    ],
                    422
                )
            );
        }

        $betAmount = request("bet_amount");
        $currentBalance = request("current_balance");
        $numSpins = request("num_spins");

        // chunk the spins by 100 and run them in batches
        $spins = collect(range(1, $numSpins))->chunk(10000);
        $jobs = [];
        // get session id
        $groupId = Str::random(5);
        // append timestamp to group id
        $groupId .= "_" . time();

        foreach ($spins as $spin) {
            $firstSpin = $spin->first();
            $lastSpin = $spin->last();
            $job = new RunSpin(
                $betAmount,
                $currentBalance,
                $numSpins,
                $firstSpin,
                $lastSpin,
                $groupId
            );
            $jobs[] = $job;
        }
        $batch = Bus::batch($jobs)
            ->then(function (Batch $batch) {
                // All jobs completed successfully...
            })
            ->catch(function (Batch $batch, Throwable $e) {
                // First batch job failure detected...
            })
            ->finally(function (Batch $batch) {
                // The batch has finished executing...
            })
            ->name("Spin Group #" . $groupId)
            ->dispatch();

        return response()->json([
            "status" => "success",
            "batch_id" => $batch->id,
        ]);
    }

    public function progress($batchId)
    {
        $batch = Bus::findBatch($batchId);
        $percentage = $batch->progress();
        $spin = null;
        if ($batch->finished()) {
            $batchName = $batch->name;
            $group = explode("#", $batchName)[1];

            // get last spin
            $spin = SpinGroup::where("group_id", $group)
                ->orderBy("id", "desc")
                ->first();
            // dd($spin);
        }

        if ($batch->cancelled()) {
            $percentage = 100;
        }

        return response()->json([
            "status" => "success",
            "percentage" => $percentage,
            "data" => $spin,
        ]);
    }
}
