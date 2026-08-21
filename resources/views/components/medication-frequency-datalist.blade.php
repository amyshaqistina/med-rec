<datalist id="medication-frequency-options">
    @foreach (\App\Models\Medication::FREQUENCY_OPTIONS as $option)
        <option value="{{ $option }}"></option>
    @endforeach
</datalist>
