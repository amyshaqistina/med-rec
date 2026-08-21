<datalist id="medication-name-options">
    @foreach (\App\Models\Medication::query()->orderBy('name')->pluck('name') as $name)
        <option value="{{ $name }}"></option>
    @endforeach
</datalist>
