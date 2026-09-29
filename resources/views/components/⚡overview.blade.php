<?php

use App\Enums\IncomeType;
use App\Models\Activity;
use App\Models\Expense;
use App\Models\Income;
use Livewire\Component;

new class extends Component {
    public function with(): array
    {
        $totalIncome = Income::sum('amount');
        $totalExpense = Expense::sum('amount');

        return [
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'balance' => $totalIncome - $totalExpense,

            'regularMeetingIncome' => Income::query()->where('type', IncomeType::REGULAR_MEETING)->sum('amount'),

            'additionalFundIncome' => Income::query()->where('type', IncomeType::ADDITIONAL_FUND)->sum('amount'),

            'activities' => Activity::query()->latest('date')->limit(5)->get(),

            'recentIncomes' => Income::query()->with('activity')->latest('date')->limit(5)->get(),

            'recentExpenses' => Expense::query()->with('activity')->latest('date')->limit(5)->get(),
        ];
    }

    public function formatCurrency(float|int|string $amount): string
    {
        return 'Rp ' . number_format((float) $amount, 0, ',', '.');
    }
};
?>

<div class="space-y-6">
    {{-- HEADER --}}
    <div>
        <flux:heading size="xl">
            Ringkasan
        </flux:heading>

        <flux:text class="mt-1">
            Ringkasan keuangan Dana Kegiatan.
        </flux:text>
    </div>

    {{-- FINANCIAL SUMMARY --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        {{-- INCOME --}}
        <flux:card class="relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <flux:text class="text-zinc-500">
                        Total Pemasukan
                    </flux:text>

                    <flux:heading size="lg" class="mt-2">
                        {{ $this->formatCurrency($totalIncome) }}
                    </flux:heading>
                </div>

                <div
                    class="flex size-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                    <flux:icon name="arrow-down-left" class="size-5" />
                </div>
            </div>
        </flux:card>

        {{-- EXPENSE --}}
        <flux:card class="relative overflow-hidden">
            <div class="flex items-start justify-between">
                <div>
                    <flux:text class="text-zinc-500">
                        Total Pengeluaran
                    </flux:text>

                    <flux:heading size="lg" class="mt-2">
                        {{ $this->formatCurrency($totalExpense) }}
                    </flux:heading>
                </div>

                <div
                    class="flex size-10 items-center justify-center rounded-xl bg-rose-100 text-rose-600 dark:bg-rose-950 dark:text-rose-400">
                    <flux:icon name="arrow-up-right" class="size-5" />
                </div>
            </div>
        </flux:card>

        {{-- BALANCE --}}
        <flux:card class="relative overflow-hidden sm:col-span-2 xl:col-span-1">
            <div class="flex items-start justify-between">
                <div>
                    <flux:text class="text-zinc-500">
                        Saldo
                    </flux:text>

                    <flux:heading size="lg" class="mt-2">
                        {{ $this->formatCurrency($balance) }}
                    </flux:heading>
                </div>

                <div
                    class="flex size-10 items-center justify-center rounded-xl bg-sky-100 text-sky-600 dark:bg-sky-950 dark:text-sky-400">
                    <flux:icon name="wallet" class="size-5" />
                </div>
            </div>
        </flux:card>
    </div>

    {{-- INCOME BREAKDOWN --}}
    <div class="grid gap-4 lg:grid-cols-2">
        <flux:card>
            <div class="mb-5">
                <flux:heading size="lg">
                    Pemasukan
                </flux:heading>

                <flux:text class="mt-1">
                    Berdasarkan jenis pemasukan.
                </flux:text>
            </div>

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex size-9 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400">
                            <flux:icon name="calendar-days" class="size-4" />
                        </div>

                        <div>
                            <flux:text class="font-medium">
                                Rapat Rutin
                            </flux:text>

                            <flux:text size="sm" class="text-zinc-500">
                                Pemasukan rutin
                            </flux:text>
                        </div>
                    </div>

                    <flux:text class="font-semibold">
                        {{ $this->formatCurrency($regularMeetingIncome) }}
                    </flux:text>
                </div>

                <flux:separator />

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex size-9 items-center justify-center rounded-lg bg-indigo-100 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400">
                            <flux:icon name="currency-dollar" class="size-4" />
                        </div>

                        <div>
                            <flux:text class="font-medium">
                                Tambahan Dana / Bantuan
                            </flux:text>

                            <flux:text size="sm" class="text-zinc-500">
                                Dana tambahan
                            </flux:text>
                        </div>
                    </div>

                    <flux:text class="font-semibold">
                        {{ $this->formatCurrency($additionalFundIncome) }}
                    </flux:text>
                </div>
            </div>
        </flux:card>

        {{-- ACTIVITY SUMMARY --}}
        <flux:card>
            <div class="mb-5">
                <flux:heading size="lg">
                    Kegiatan
                </flux:heading>

                <flux:text class="mt-1">
                    Kegiatan yang tercatat.
                </flux:text>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div class="rounded-xl bg-zinc-50 p-4 dark:bg-zinc-800/50">
                    <flux:text size="sm" class="text-zinc-500">
                        Total
                    </flux:text>

                    <flux:heading size="lg" class="mt-1">
                        {{ $activities->count() }}
                    </flux:heading>
                </div>

                <div class="rounded-xl bg-amber-50 p-4 dark:bg-amber-950/30">
                    <flux:text size="sm" class="text-amber-700 dark:text-amber-400">
                        Mendatang
                    </flux:text>

                    <flux:heading size="lg" class="mt-1">
                        {{ $activities->where('status.value', 'planned')->count() }}
                    </flux:heading>
                </div>

                <div class="rounded-xl bg-emerald-50 p-4 dark:bg-emerald-950/30">
                    <flux:text size="sm" class="text-emerald-700 dark:text-emerald-400">
                        Berlangsung
                    </flux:text>

                    <flux:heading size="lg" class="mt-1">
                        {{ $activities->where('status.value', 'ongoing')->count() }}
                    </flux:heading>
                </div>
            </div>
        </flux:card>
    </div>

    {{-- RECENT TRANSACTIONS --}}
    <div class="grid gap-4 lg:grid-cols-2">
        {{-- INCOME --}}
        <flux:card>
            <div class="mb-5 flex items-center justify-between gap-4">
                <div>
                    <flux:heading size="lg">
                        Pemasukan Terbaru
                    </flux:heading>

                    <flux:text class="mt-1">
                        Lima pemasukan terakhir.
                    </flux:text>
                </div>

                <flux:button href="{{ route('incomes') }}" variant="ghost" size="sm">
                    Lihat semua
                </flux:button>
            </div>

            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($recentIncomes as $income)
                    <div class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0">
                        <div class="min-w-0">
                            <flux:text class="truncate font-medium">
                                {{ $income->description }}
                            </flux:text>

                            <flux:text size="sm" class="mt-0.5 text-zinc-500">
                                {{ $income->date->translatedFormat('d M Y') }}
                            </flux:text>
                        </div>

                        <flux:text class="shrink-0 font-semibold text-emerald-600 dark:text-emerald-400">
                            +{{ $this->formatCurrency($income->amount) }}
                        </flux:text>
                    </div>
                @empty
                    <div class="py-8 text-center">
                        <flux:text>
                            Belum ada pemasukan.
                        </flux:text>
                    </div>
                @endforelse
            </div>
        </flux:card>

        {{-- EXPENSE --}}
        <flux:card>
            <div class="mb-5 flex items-center justify-between gap-4">
                <div>
                    <flux:heading size="lg">
                        Pengeluaran Terbaru
                    </flux:heading>

                    <flux:text class="mt-1">
                        Lima pengeluaran terakhir.
                    </flux:text>
                </div>

                <flux:button href="{{ route('expenses') }}" variant="ghost" size="sm">
                    Lihat semua
                </flux:button>
            </div>

            <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($recentExpenses as $expense)
                    <div class="flex items-center justify-between gap-4 py-3 first:pt-0 last:pb-0">
                        <div class="min-w-0">
                            <flux:text class="truncate font-medium">
                                {{ $expense->description }}
                            </flux:text>

                            <flux:text size="sm" class="mt-0.5 text-zinc-500">
                                {{ $expense->date->translatedFormat('d M Y') }}
                            </flux:text>
                        </div>

                        <flux:text class="shrink-0 font-semibold text-rose-600 dark:text-rose-400">
                            -{{ $this->formatCurrency($expense->amount) }}
                        </flux:text>
                    </div>
                @empty
                    <div class="py-8 text-center">
                        <flux:text>
                            Belum ada pengeluaran.
                        </flux:text>
                    </div>
                @endforelse
            </div>
        </flux:card>
    </div>
</div>
