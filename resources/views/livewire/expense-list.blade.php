<div>
    @if (session('saved'))
        <p>{{ session('saved') }}</p>
    @endif
    <h1>My expenses</h1>
    <table>
        @foreach ($mine as $expense)
            <tr>
                <td>{{ $expense->description }}</td>
                <td>{{ number_format($expense->amount_cents / 100, 2) }} €</td>
                <td>{{ $expense->status }}</td>
                <td>
                    @if ($expense->status === 'pending')
                        <a href="{{ route('expenses.edit', $expense) }}">Edit</a>
                    @endif
                </td>
            </tr>
        @endforeach
    </table>

    @if (auth()->user()->is_manager)
        <h2>To approve</h2>
        <table>
            @foreach ($toApprove as $expense)
                <tr wire:key="approve-{{ $expense->id }}">
                    <td>{{ $expense->user->name }}</td>
                    <td>{{ $expense->description }}</td>
                    <td>{{ number_format($expense->amount_cents / 100, 2) }} €</td>
                    <td>
                        <button wire:click="approve({{ $expense->id }})">Approve</button>
                        <button wire:click="reject({{ $expense->id }})">Reject</button>
                    </td>
                </tr>
            @endforeach
        </table>
    @endif
</div>
