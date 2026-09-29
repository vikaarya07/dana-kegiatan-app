<?php

use App\Models\Activity;
use App\Models\Expense;
use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Livewire\Component;

new class extends Component {
    use WithPagination;

    public string $search = '';

    public string $activityId = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $date = '';

    public string $description = '';

    public string $amount = '';

    public string $notes = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();

        $this->date = now()->format('Y-m-d');

        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $expense = Expense::findOrFail($id);

        $this->editingId = $expense->id;
        $this->date = $expense->date->format('Y-m-d');
        $this->description = $expense->description;
        $this->amount = (string) $expense->amount;
        $this->activityId = $expense->activity_id ? (string) $expense->activity_id : '';
        $this->notes = $expense->notes ?? '';

        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'activityId' => ['nullable', 'integer', 'exists:activities,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $data = [
            'date' => $validated['date'],
            'description' => $validated['description'],
            'amount' => $validated['amount'],
            'activity_id' => $validated['activityId'] ?: null,
            'notes' => $validated['notes'] ?: null,
        ];

        $wasEditing = $this->editingId !== null;

        if ($wasEditing) {
            Expense::findOrFail($this->editingId)->update($data);
        } else {
            $data['created_by'] = Auth::id();
            $data['transaction_number'] = $this->generateTransactionNumber();

            Expense::create($data);
        }

        $this->closeForm();

        session()->flash('success', $wasEditing ? 'Pengeluaran berhasil diperbarui.' : 'Pengeluaran berhasil ditambahkan.');
    }

    public function delete(int $id): void
    {
        Expense::findOrFail($id)->delete();

        session()->flash('success', 'Pengeluaran berhasil dihapus.');
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
        $this->description = '';
        $this->amount = '';
        $this->activityId = '';
        $this->notes = '';

        $this->resetValidation();
    }

    protected function generateTransactionNumber(): string
    {
        $prefix = 'DK-EX-' . now()->format('Ym') . '-';

        $lastNumber = Expense::query()
            ->where('transaction_number', 'like', $prefix . '%')
            ->orderByDesc('transaction_number')
            ->value('transaction_number');

        $sequence = $lastNumber ? ((int) substr($lastNumber, -4)) + 1 : 1;

        return $prefix . str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    public function with(): array
    {
        return [
            'expenses' => Expense::query()
                ->with('activity')
                ->when($this->search, function ($query) {
                    $query->where(function ($query) {
                        $query->where('transaction_number', 'like', '%' . $this->search . '%')->orWhere('description', 'like', '%' . $this->search . '%');
                    });
                })
                ->latest('date')
                ->latest('id')
                ->paginate(10),

            'activities' => Activity::query()->orderBy('name')->get(),
        ];
    }
};
?>

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">
                Pengeluaran
            </flux:heading>

            <flux:text class="mt-1">
                Kelola seluruh pengeluaran dana kegiatan.
            </flux:text>
        </div>

        <flux:button variant="primary" icon="plus" wire:click="create">
            Tambah Pengeluaran
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
        <flux:input wire:model.live.debounce.300ms="search" label="Pencarian"
            placeholder="Cari nomor transaksi atau keterangan..." icon="magnifying-glass" />
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
                    @forelse ($expenses as $expense)
                        <tr wire:key="expense-{{ $expense->id }}">
                            <td class="px-4 py-4">
                                <div class="font-medium">
                                    {{ $expense->transaction_number }}
                                </div>
                            </td>

                            <td class="px-4 py-4 whitespace-nowrap">
                                {{ $expense->date->format('d/m/Y') }}
                            </td>

                            <td class="px-4 py-4">
                                {{ $expense->description }}
                            </td>

                            <td class="px-4 py-4">
                                {{ $expense->activity?->name ?? '-' }}
                            </td>

                            <td class="px-4 py-4 text-right font-semibold whitespace-nowrap">
                                Rp {{ number_format($expense->amount, 0, ',', '.') }}
                            </td>

                            <td class="px-4 py-4">
                                <div class="flex justify-end gap-2">
                                    <flux:button size="sm" variant="ghost" icon="pencil"
                                        wire:click="edit({{ $expense->id }})" />

                                    <flux:button size="sm" variant="ghost" icon="trash"
                                        wire:click="delete({{ $expense->id }})"
                                        wire:confirm="Yakin ingin menghapus pengeluaran ini?" />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <div class="space-y-2">
                                    <flux:icon name="receipt-percent" class="mx-auto size-10 text-zinc-400" />

                                    <flux:heading>
                                        Belum ada data pengeluaran
                                    </flux:heading>

                                    <flux:text>
                                        Tambahkan pengeluaran pertama untuk mulai mencatat penggunaan dana.
                                    </flux:text>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($expenses->hasPages())
            <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">
                {{ $expenses->links() }}
            </div>
        @endif
    </flux:card>

    {{-- FORM MODAL --}}
    <flux:modal wire:model="showForm" class="md:w-150">
        <div class="space-y-6">

            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Pengeluaran' : 'Tambah Pengeluaran' }}
                </flux:heading>

                <flux:text class="mt-1">
                    {{ $editingId ? 'Perbarui informasi pengeluaran.' : 'Masukkan informasi pengeluaran baru.' }}
                </flux:text>
            </div>

            <form wire:submit="save" class="space-y-5">

                <flux:input wire:model="date" label="Tanggal" type="date" required />

                <flux:input wire:model="description" label="Keterangan" placeholder="Contoh: Konsumsi rapat rutin"
                    required />

                <flux:input wire:model="amount" label="Jumlah" type="number" min="0" step="0.01"
                    placeholder="0" required />

                <flux:select wire:model="activityId" label="Kegiatan">
                    <option value="">
                        Tanpa kegiatan
                    </option>

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
                        {{ $editingId ? 'Simpan Perubahan' : 'Simpan Pengeluaran' }}
                    </flux:button>
                </div>

            </form>
        </div>
    </flux:modal>

</div>
