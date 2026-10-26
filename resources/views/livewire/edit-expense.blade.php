<div>
    <h1>Edit expense</h1>
    <form wire:submit="save">
        <label>Description <input type="text" wire:model="description"></label>
        @error('description') <span>{{ $message }}</span> @enderror
        <label>Amount (EUR) <input type="text" wire:model="amount"></label>
        @error('amount') <span>{{ $message }}</span> @enderror
        <label>Receipt <input type="file" wire:model="receipt"></label>
        @error('receipt') <span>{{ $message }}</span> @enderror
        <button type="submit">Save</button>
    </form>
</div>
