<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { inject, onMounted, ref } from "vue";
import LoadingIcon from "@/Components/Shared/LoadingIcon.vue";
import { ExclamationTriangleIcon } from "@heroicons/vue/20/solid";

import ApplicationMark from "@/Components/ApplicationMark.vue";

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
});

const axios = inject("axios"); // inject axios
const betAmount = ref(null);
const currentBalance = ref(null);
const noOfSpins = ref(null);
const batchId = ref(null);
const errors = ref(null);
const interval = ref(null);
const spinData = ref(null);
const loading = ref(false);
const percentage = ref(0);
const time = ref(5000);

onMounted(() => {
    // get batch id from local storage
    batchId.value = localStorage.getItem("batchId");
    if (batchId.value) {
        checkStatus();
    }
});

const checkStatus = () => {
    // loading.value = true;
    axios
        .get(`/api/slot/spin/${batchId.value}`)
        .then((response) => {
            console.log(response.data);
            if (response.data.percentage === 100) {
                clearInterval(interval.value);
                spinData.value = response.data.data.spin_data;
                console.log(spinData.value);
                localStorage.removeItem("batchId");
                loading.value = false;
            }
            if (
                response.data.percentage > 0 &&
                response.data.percentage < 100
            ) {
                loading.value = true;
                percentage.value = response.data.percentage;
            }

            if (response.data.percentage === 0) {
                loading.value = true;
            }
        })
        .catch((error) => {
            console.log(error);
            loading.value = false;
            localStorage.removeItem("batchId");
        });
};

const spin = () => {
    // console.log(betAmount.value);
    // console.log(currentBalance.value);
    // console.log(noOfSpins.value);
    // betAmount.value = 100;
    // currentBalance.value = 1000;
    // noOfSpins.value = 1000000;
    // noOfSpins.value = 10000;

    if (noOfSpins.value > 50000) {
        time.value = 6000;
    } else if (noOfSpins.value > 10000) {
        time.value = 4000;
    } else if (noOfSpins.value > 5000) {
        time.value = 3000;
    } else if (noOfSpins.value > 1000) {
        time.value = 2000;
    } else {
        time.value = 500;
    }

    // noOfSpins.value = 10;

    spinData.value = null;
    loading.value = true;
    axios
        .post("/api/slot/spin", {
            bet_amount: betAmount.value,
            current_balance: currentBalance.value,
            num_spins: noOfSpins.value,
        })
        .then((response) => {
            errors.value = null;
            console.log(response.data);
            betAmount.value = null;
            currentBalance.value = null;
            noOfSpins.value = null;
            batchId.value = response.data.batch_id;
            localStorage.setItem("batchId", batchId.value);
            setTimeout(() => {
                checkStatus();
            }, 1000);
            interval.value = setInterval(() => {
                checkStatus();
            }, time.value);
            // percentage.value = 1;
            // loading.value = false;
        })
        .catch((error) => {
            loading.value = false;

            // check if error is 422
            if (error.response.status === 422) {
                // show error message
                console.log(error.response.data.errors);
                errors.value = error.response.data.errors;
            } else {
                betAmount.value = null;
                currentBalance.value = null;
                noOfSpins.value = null;
            }
        });
};

const random = (min, max) => {
    return Math.floor(Math.random() * (max - min + 1)) + min;
};

const testData = () => {
    betAmount.value = random(10, 100);
    currentBalance.value = random(100, 1000);
    noOfSpins.value = random(100, 1000);
};
</script>

<template>
    <Head title="Slot Casino" />

    <div class="h-screen bg-gray-100 flex flex-col items-center justify-center">
        <div class="flex justify-end mb-4 max-w-md mx-auto w-full">
            <Link :href="route('dashboard')"> Dashboard </Link>
        </div>
        <div class="max-w-md mx-auto w-full bg-white rounded-lg shadow p-8">
            <div class="rounded-md bg-yellow-50 p-4 mb-5 shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <ExclamationTriangleIcon
                            class="h-5 w-5 text-yellow-400"
                            aria-hidden="true"
                        />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">
                            Attention
                        </h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>
                                This is a simulation of a slot machine. The
                                results are not real. Each spin will delete the
                                previous spin data. Depending on the number of
                                spins, it may take a while to complete.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-7 w-full max-w-md mx-auto">
                <div class="relative">
                    <label
                        for="bet"
                        class="block text-sm font-medium leading-6 text-gray-900"
                        >Bet</label
                    >
                    <div class="mt-1">
                        <input
                            type="number"
                            name="bet"
                            id="bet"
                            v-model="betAmount"
                            :class="errors?.bet_amount ? '!ring-red-500' : ''"
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 sm:text-sm sm:leading-6"
                            placeholder="10"
                        />
                    </div>
                    <p class="-bottom-5 text-sm text-red-500 absolute">
                        {{ errors?.bet_amount }}
                    </p>
                </div>
                <div class="relative">
                    <label
                        for="currentBalance"
                        class="block text-sm font-medium leading-6 text-gray-900"
                        >Current Balance</label
                    >
                    <div class="mt-1">
                        <input
                            type="number"
                            name="currentBalance"
                            id="currentBalance"
                            v-model="currentBalance"
                            :class="
                                errors?.current_balance ? '!ring-red-500' : ''
                            "
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6"
                            placeholder="1000"
                        />
                    </div>
                    <p class="-bottom-5 text-sm text-red-500 absolute">
                        {{ errors?.current_balance }}
                    </p>
                </div>
                <div class="relative">
                    <label
                        for="noOfSpins"
                        class="block text-sm font-medium leading-6 text-gray-900"
                        >No. of spins</label
                    >
                    <div class="mt-1">
                        <input
                            type="number"
                            name="noOfSpins"
                            id="noOfSpins"
                            v-model="noOfSpins"
                            :class="errors?.num_spins ? '!ring-red-500' : ''"
                            class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6"
                            placeholder="10"
                        />
                    </div>
                    <p class="-bottom-5 text-sm text-red-500 absolute">
                        {{ errors?.num_spins }}
                    </p>
                </div>
            </div>

            <div class="mt-10 flex justify-between">
                <button
                    type="button"
                    @click="testData"
                    class="w-24 flex items-center justify-center animate rounded-md bg-white px-2.5 py-1.5 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                >
                    Test Data
                </button>

                <button
                    type="button"
                    @click="spin"
                    :disabled="loading"
                    class="flex w-24 justify-center animate items-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                >
                    <span v-if="loading" class="mr-2"><loading-icon /></span>
                    Spin
                </button>
            </div>

            <div
                v-if="loading && percentage"
                class="border bg-gray-100 rounded-lg flex flex-col items-center justify-center p-6 mt-5"
            >
                <span><loading-icon icon-color="text-gray-500" /></span>
                <span class="text-lg font-bold mt-2"> {{ percentage }}% </span>
            </div>

            <div
                v-if="!loading && spinData"
                class="border bg-gray-100 rounded-lg px-4 py-2 text-sm mt-5"
            >
                <ul class="flex flex-col space-y-1">
                    <li>
                        <strong>No. of spins:</strong>
                        {{ spinData?.total_spins }}
                    </li>
                    <li>
                        <strong>Total free spins:</strong>
                        {{ spinData?.total_free_spins }}
                    </li>
                    <li>
                        <strong>Bet amount:</strong>
                        {{ spinData?.bet_amount }}
                    </li>
                    <li>
                        <strong>Initial balance:</strong>
                        {{ spinData?.initial_balance }}
                    </li>
                    <li>
                        <strong>Final balance:</strong>
                        {{ spinData?.final_balance }}
                    </li>
                    <li>
                        <strong>Total payout:</strong>
                        {{ spinData?.total_payout }}
                    </li>
                    <li>
                        <strong>Adjusted total payout:</strong>
                        {{ spinData?.adjusted_total_payout }}
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>

<style></style>
