<?php

use App\Enums\IncomeType;
use App\Models\Activity;
use App\Models\Income;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Livewire\Component;

new class extends Component {
    use WithPagination;

    public string $search = '';

    public string $type = '';

    public string $activityId = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $date = '';

    public string $incomeType = '';

    public string $description = '';

    public string $amount = '';

    public string $notes = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();

        $this->date = now()->format('Y-m-d');
        $this->incomeType = IncomeType::REGULAR_MEETING->value;

        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $income = Income::findOrFail($id);

        $this->editingId = $income->id;
        $this->date = $income->date->format('Y-m-d');
        $this->incomeType = $income->type->value;
        $this->description = $income->description;
        $this->amount = (string) $income->amount;
        $this->activityId = $income->activity_id ? (string) $income->activity_id : '';
        $this->notes = $income->notes ?? '';

        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'date' => ['required', 'date'],
            'incomeType' => ['required', 'in:' . collect(IncomeType::cases())->pluck('value')->implode(',')],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'activityId' => ['nullable', 'integer', 'exists:activities,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $data = [
            'date' => $validated['date'],
            'type' => $validated['incomeType'],
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'activity_id' => $validated['activityId'] ?: null,
            'notes' => $validated['notes'] ?: null,
        ];

        if ($this->editingId) {
            Income::findOrFail($this->editingId)->update($data);
        } else {
            $data['created_by'] = Auth::id();
            $data['transaction_number'] = $this->generateTransactionNumber();

            Income::create($data);
        }

        $this->closeForm();

        session()->flash('success', $this->editingId ? 'Pemasukan berhasil diperbarui.' : 'Pemasukan berhasil ditambahkan.');
    }

    public function delete(int $id): void
    {
        Income::findOrFail($id)->delete();

        session()->flash('success', 'Pemasukan berhasil dihapus.');
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->editingId = null;
        $this->date = '';
        $this->incomeType = '';
        $this->description = '';
        $this->amount = '';
        $this->activityId = '';
        $this->notes = '';

        $this->resetValidation();
    }

    protected function generateTransactionNumber(): string
    {
        $prefix = 'DK-IN-' . now()->format('Ym') . '-';

        $lastNumber = Income::query()
            ->where('transaction_number', 'like', $prefix . '%')
            ->orderByDesc('transaction_number')
            ->value('transaction_number');

        $sequence = $lastNumber ? ((int) substr($lastNumber, -4)) + 1 : 1;

        return $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public function with(): array
    {
        return [
            'incomes' => Income::query()
                ->with('activity')
                ->when($this->search, function ($query) {
                    $query->where(function ($query) {
                        $query->where('transaction_number', 'like', '%' . $this->search . '%')->orWhere('description', 'like', '%' . $this->search . '%');
                    });
                })
                ->when($this->type, function ($query) {
                    $query->where('type', $this->type);
                })
                ->latest('date')
                ->latest('id')
                ->paginate(10),

            'activities' => Activity::query()->orderBy('name')->get(),

            'incomeTypes' => IncomeType::cases(),
        ];
    }
};
?>

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Pemasukan</flux:heading>

            <flux:text class="mt-1">
                Kelola seluruh pemasukan dana kegiatan.
            </flux:text>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="create">
            Tambah Pemasukan
        </flux:button>
    </div>

    {{-- FLASH MESSAGE --}}
    @if (session('success'))
        <flux:callout variant="success" icon="check-circle">
            {{ session('success') }}
        </flux:callout>
    @endif

    {{-- FILTER --}}
    <flux:card class="space-y-4">
        <div class="grid gap-4 md:grid-cols-2">
            <flux:input wire:model.live.debounce.300ms="search" label="Pencarian"
                placeholder="Cari nomor transaksi atau keterangan..." icon="magnifying-glass" />

            <flux:select wire:model.live="type" label="Jenis Pemasukan">
                <option value="">Semua jenis</option>

                @foreach ($incomeTypes as $incomeTypeOption)
                    <option value="{{ $incomeTypeOption->value }}">
                        {{ $incomeTypeOption->label() }}
                    </option>
                @endforeach
            </flux:select>
        </div>
    </flux:card>

    {{-- TABLE --}}
    <flux:card class="overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="px-4 py-3 text-left font-medium">
                            Transaksi
                        </th>

                        <th class="px-4 py-3 text-left font-medium">
                            Tanggal
                        </th>

                        <th class="px-4 py-3 text-left font-medium">
                            Jenis
                        </th>

                        <th class="px-4 py-3 text-left font-medium">
                            Keterangan
                        </th>

                        <th class="px-4 py-3 text-left font-medium">
                            Kegiatan
                        </th>

                        <th class="px-4 py-3 text-right font-medium">
                            Jumlah
                        </th>

                        <th class="px-4 py-3 text-right font-medium">
                            Aksi
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($incomes as $income)
                        <tr wire:key="income-{{ $income->id }}">
                            <td class="px-4 py-4">
                                <div class="font-medium">
                                    {{ $income->transaction_number }}
                                </div>
                            </td>

                            <td class="px-4 py-4 whitespace-nowrap">
                                {{ $income->date->format('d/m/Y') }}
                            </td>

                            <td class="px-4 py-4">
                                <flux:badge>
                                    {{ $income->type->label() }}
                                </flux:badge>
                            </td>

                            <td class="px-4 py-4">
                                {{ $income->description }}
                            </td>

                            <td class="px-4 py-4">
                                {{ $income->activity?->name ?? '-' }}
                            </td>

                            <td class="px-4 py-4 text-right font-semibold whitespace-nowrap">
                                Rp {{ number_format($income->amount, 0, ',', '.') }}
                            </td>

                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <flux:button size="sm" variant="ghost" icon="pencil"
                                        wire:click="edit({{ $income->id }})" />

                                    <flux:button size="sm" variant="ghost" icon="trash"
                                        wire:click="delete({{ $income->id }})"
                                        wire:confirm="Yakin ingin menghapus pemasukan ini?" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <div class="space-y-2">
                                    <flux:icon name="banknotes" class="mx-auto size-10 text-zinc-400" />

                                    <flux:heading>
                                        Belum ada data pemasukan
                                    </flux:heading>

                                    <flux:text>
                                        Tambahkan pemasukan pertama untuk mulai mencatat keuangan.
                                    </flux:text>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($incomes->hasPages())
            <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">
                {{ $incomes->links() }}
            </div>
        @endif
    </flux:card>

    {{-- FORM MODAL --}}
    <flux:modal wire:model="showForm" class="md:w-150">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Pemasukan' : 'Tambah Pemasukan' }}
                </flux:heading>

                <flux:text class="mt-1">
                    {{ $editingId ? 'Perbarui informasi pemasukan.' : 'Masukkan informasi pemasukan baru.' }}
                </flux:text>
            </div>

            <form wire:submit="save" class="space-y-5">

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="date" label="Tanggal" type="date" required />

                    <flux:select wire:model="incomeType" label="Jenis Pemasukan" required>
                        @foreach ($incomeTypes as $incomeTypeOption)
                            <option value="{{ $incomeTypeOption->value }}">
                                {{ $incomeTypeOption->label() }}
                            </option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:input wire:model="description" label="Keterangan"
                    placeholder="Contoh: Iuran rapat rutin bulan Oktober" required />

                <flux:input wire:model="amount" label="Jumlah" type="number" min="0" step="0.01"
                    placeholder="0" required />

                <flux:select wire:model="activityId" label="Kegiatan">
                    <option value="">Tanpa kegiatan</option>

                    @foreach ($activities as $activity)
                        <option value="{{ $activity->id }}">
                            {{ $activity->name }}
                        </option>
                    @endforeach
                </flux:select>

                <flux:textarea wire:model="notes" label="Catatan" placeholder="Catatan tambahan (opsional)"
                    rows="3" />

                <div class="flex justify-end gap-3">
                    <flux:button type="button" variant="ghost" wire:click="closeForm">
                        Batal
                    </flux:button>

                    <flux:button type="submit" variant="primary">
                        {{ $editingId ? 'Simpan Perubahan' : 'Simpan Pemasukan' }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

</div>
