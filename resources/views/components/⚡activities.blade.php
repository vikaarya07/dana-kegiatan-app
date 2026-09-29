<?php

use App\Enums\ActivityStatus;
use App\Models\Activity;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';

    public string $status = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $description = '';

    public ?string $date = null;

    public string $location = '';

    public string $budget = '0';

    public string $activityStatus = 'planned';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetForm();

        $this->showForm = true;
    }

    public function edit(Activity $activity): void
    {
        $this->editingId = $activity->id;

        $this->name = $activity->name;
        $this->description = $activity->description ?? '';
        $this->date = $activity->date?->format('Y-m-d');
        $this->location = $activity->location ?? '';
        $this->budget = (string) $activity->budget;
        $this->activityStatus = $activity->status->value;

        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'date' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'budget' => ['required', 'numeric', 'min:0'],
            'activityStatus' => ['required', 'in:' . implode(',', array_column(ActivityStatus::cases(), 'value'))],
        ]);

        $data = [
            'name' => $validated['name'],
            'description' => $validated['description'],
            'date' => $validated['date'],
            'location' => $validated['location'],
            'budget' => $validated['budget'],
            'status' => $validated['activityStatus'],
        ];

        if ($this->editingId) {
            Activity::findOrFail($this->editingId)->update($data);

            session()->flash('success', 'Kegiatan berhasil diperbarui.');
        } else {
            Activity::create([...$data, 'created_by' => Auth::id()]);

            session()->flash('success', 'Kegiatan berhasil ditambahkan.');
        }

        $this->closeForm();
    }

    public function delete(int $id): void
    {
        Activity::findOrFail($id)->delete();

        session()->flash('success', 'Kegiatan berhasil dihapus.');
    }

    public function closeForm(): void
    {
        $this->showForm = false;

        $this->resetForm();

        $this->resetValidation();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'description', 'date', 'location', 'budget']);

        $this->activityStatus = 'planned';
        $this->budget = '0';
    }

    public function with(): array
    {
        return [
            'activities' => Activity::query()->when($this->search, fn($query) => $query->where(fn($query) => $query->where('name', 'like', "%{$this->search}%")->orWhere('location', 'like', "%{$this->search}%")))->when($this->status, fn($query) => $query->where('status', $this->status))->latest('date')->paginate(10),

            'statuses' => ActivityStatus::cases(),
        ];
    }
};
?>

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">
                Kegiatan
            </flux:heading>

            <flux:text class="mt-1">
                Kelola kegiatan Dana Kegiatan.
            </flux:text>
        </div>

        <flux:button wire:click="create" variant="primary" icon="plus">
            Tambah Kegiatan
        </flux:button>
    </div>

    {{-- FLASH MESSAGE --}}
    @if (session('success'))
        <flux:callout icon="check-circle" variant="success">
            {{ session('success') }}
        </flux:callout>
    @endif

    {{-- FILTER --}}
    <flux:card>
        <div class="grid gap-4 md:grid-cols-[1fr_220px]">

            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass"
                placeholder="Cari kegiatan..." />

            <flux:select wire:model.live="status">
                <option value="">
                    Semua Status
                </option>

                @foreach ($statuses as $activityStatusOption)
                    <option value="{{ $activityStatusOption->value }}">
                        {{ $activityStatusOption->label() }}
                    </option>
                @endforeach
            </flux:select>

        </div>
    </flux:card>

    {{-- TABLE --}}
    <flux:card class="overflow-hidden p-0">

        <div class="overflow-x-auto">

            <table class="w-full text-left text-sm">

                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/50">

                    <tr>
                        <th class="px-6 py-3 font-medium">
                            Kegiatan
                        </th>

                        <th class="px-6 py-3 font-medium">
                            Tanggal
                        </th>

                        <th class="px-6 py-3 font-medium">
                            Lokasi
                        </th>

                        <th class="px-6 py-3 font-medium">
                            Anggaran
                        </th>

                        <th class="px-6 py-3 font-medium">
                            Status
                        </th>

                        <th class="px-6 py-3">
                        </th>
                    </tr>

                </thead>

                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">

                    @forelse ($activities as $activity)
                        <tr wire:key="activity-{{ $activity->id }}">

                            <td class="px-6 py-4">
                                <flux:text class="font-medium">
                                    {{ $activity->name }}
                                </flux:text>

                                @if ($activity->description)
                                    <flux:text size="sm" class="mt-0.5 max-w-sm truncate text-zinc-500">
                                        {{ $activity->description }}
                                    </flux:text>
                                @endif
                            </td>

                            <td class="whitespace-nowrap px-6 py-4">
                                {{ $activity->date?->translatedFormat('d M Y') ?? '-' }}
                            </td>

                            <td class="px-6 py-4">
                                {{ $activity->location ?: '-' }}
                            </td>

                            <td class="whitespace-nowrap px-6 py-4">
                                Rp {{ number_format($activity->budget, 0, ',', '.') }}
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    @if ($activity->status === \App\Enums\ActivityStatus::ONGOING)
                                        <flux:badge :color="$activity->status->color()">
                                            <span class="relative flex h-3 w-3 me-1.5">
                                                <span
                                                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                                                <span
                                                    class="relative inline-flex h-3 w-3 rounded-full bg-red-500"></span>
                                            </span>
                                            {{ $activity->status->label() }}
                                        </flux:badge>
                                    @else
                                        <flux:badge :color="$activity->status->color()">
                                            {{ $activity->status->label() }}
                                        </flux:badge>
                                    @endif
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-1">
                                    <flux:button wire:click="edit({{ $activity->id }})" variant="ghost" size="sm"
                                        icon="pencil" />

                                    <flux:button wire:click="delete({{ $activity->id }})"
                                        wire:confirm="Yakin ingin menghapus kegiatan ini?" variant="ghost"
                                        size="sm" icon="trash" />
                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <flux:text>
                                    Belum ada kegiatan.
                                </flux:text>
                            </td>
                        </tr>
                    @endforelse

                </tbody>

            </table>

        </div>

        @if ($activities->hasPages())
            <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">
                {{ $activities->links() }}
            </div>
        @endif

    </flux:card>

    {{-- FORM MODAL --}}
    <flux:modal wire:model="showForm" class="md:w-150">

        <form wire:submit="save" class="space-y-6">

            <div>
                <flux:heading size="lg">
                    {{ $editingId ? 'Edit Kegiatan' : 'Tambah Kegiatan' }}
                </flux:heading>

                <flux:text class="mt-1">
                    {{ $editingId ? 'Perbarui informasi kegiatan.' : 'Tambahkan kegiatan baru.' }}
                </flux:text>
            </div>

            <flux:input wire:model="name" label="Nama Kegiatan" placeholder="Contoh: Rapat Koordinasi" required />

            <flux:textarea wire:model="description" label="Deskripsi" placeholder="Deskripsi kegiatan..."
                rows="3" />

            <div class="grid gap-4 sm:grid-cols-2">

                <flux:input wire:model="date" type="date" label="Tanggal" />

                <flux:input wire:model="location" label="Lokasi" placeholder="Contoh: Sekretariat" />

            </div>

            <div class="grid gap-4 sm:grid-cols-2">

                <flux:input wire:model="budget" type="number" min="0" step="1000" label="Anggaran" />

                <flux:select wire:model="activityStatus" label="Status">
                    @foreach ($statuses as $activityStatusOption)
                        <option value="{{ $activityStatusOption->value }}">
                            {{ $activityStatusOption->label() }}
                        </option>
                    @endforeach
                </flux:select>

            </div>

            <div class="flex justify-end gap-3">

                <flux:button type="button" wire:click="closeForm" variant="ghost">
                    Batal
                </flux:button>

                <flux:button type="submit" variant="primary">
                    {{ $editingId ? 'Simpan Perubahan' : 'Simpan' }}
                </flux:button>

            </div>

        </form>

    </flux:modal>

</div>
