{{-- <!DOCTYPE html>
<html>
<head>
    <title>Booking Page</title>
</head>
<body>

<h1>Book a Slot</h1>

@if(session('success'))
    <p style="color:green">{{ session('success') }}</p>
@endif

@if(session('error'))
    <p style="color:red">{{ session('error') }}</p>
@endif

<form method="POST" action="{{ route('book.slot') }}">
    @csrf

    <select name="slot_id">
        @foreach(\App\Models\Slot::all() as $slot)
            <option value="{{ $slot->id }}">
                {{ $slot->date }} ({{ $slot->start_time }} - {{ $slot->end_time }})
            </option>
        @endforeach
    </select>

    <button type="submit">Book Now</button>
</form>

</body>
</html> --}}
